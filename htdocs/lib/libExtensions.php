<?php

#
# Copyright (C) 2017 Nethesis S.r.l.
# http://www.nethesis.it - nethserver@nethesis.it
#
# This script is part of NethServer.
#
# NethServer is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License,
# or any later version.
#
# NethServer is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with NethServer.  If not, see COPYING.
#

function generateRandomPassword($length = 15) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function checkTableExists($table) {
    try {
        $dbh = \FreePBX::Database();
        $sql = 'SHOW TABLES LIKE ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($table));
        if($sth->fetch(\PDO::FETCH_ASSOC)) {
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function createExtension($extension,$secret,$context = 'webcall'){
    try {
        global $astman;
        $errors = array(); $warnings = array(); $infos = array();

        if (!ctype_digit($extension) || !is_string($context) || !is_string($secret)) {
            throw new InvalidArgumentException('Expected a numeric extension, a context and a secret');
        }
        $res = checkFreeExtension($extension);
        $infos = array_merge($infos,$res['infos']);
        $warnings = array_merge($warnings,$res['warnings']);
        $errors = array_merge($errors,$res['errors']);
        if ($res['status'] === false) {
            return array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings);
        }

        $fpbx = FreePBX::create();
        $dbh = FreePBX::Database();

        // FreePBX 17 accepts these PJSIP settings during quick create.
        $data = [
            'name' => 'extension_'.$extension,
            'outboundcid' => '',
            'vm' => 'no',
            'secret' => $secret,
            'accountcode' => $extension,
            'avpf' => 'yes',
            'icesupport' => 'yes',
            'rtcp_mux' => 'yes',
            'media_use_received_transport' => 'yes',
            'media_encryption' => 'dtls',
        ];
        // A concurrent reload must see the device only after its context and DTLS are ready.
        $dbh->beginTransaction();
        $res = $fpbx->Core->processQuickCreate('pjsip', $extension, $data);
        if (!$res['status']) {
            throw new RuntimeException($res['message'] ?? 'Error creating extension');
        }
        $created = true;
        //Set cid_masquerade (CID Num Alias)
        $astman->database_put("AMPUSER",$extension."/cidnum",$extension);

        //Add device
        $astman->database_put("AMPUSER", $extension."/device", $extension);

        $astman->database_del("CW", $extension);

        // Quick create does not accept a custom context.
        $sth = $dbh->prepare('REPLACE INTO sip (id, keyword, data) VALUES (?, "context", ?)');
        $sth->execute([$extension, $context]);
        $fpbx->Certman->addDTLSOptions($extension, [
            'auto_generate_cert' => 1, 'verify' => 'fingerprint', 'setup' => 'actpass', 'rekey' => 0,
        ]);
        $dbh->commit();

        return array('status' => true, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos, 'extension' => $extension, 'secret' => $secret, 'port' => '443', 'path' => '/ws');
    } catch (Throwable $e) {
       try {
           if (!empty($created)) {
               // FreePBX also writes to AstDB, outside the SQL transaction.
               $fpbx->Core->delUser($extension);
               $fpbx->Core->delDevice($extension);
           }
       } finally {
           if (isset($dbh) && $dbh->inTransaction()) {
               $dbh->rollBack();
           }
       }
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
    }
}

function deleteExtension($extension) {
    try {
        $errors = array(); $warnings = array(); $infos = array();
        $res = checkFreeExtension($extension);
        if ($res['status'] === true) {
            $warnings[] = 'Extension doesn\'t exists';
        }

        // clean extension
        $fpbx = FreePBX::create();
        $fpbx->Core->delUser($extension);
        $fpbx->Core->delDevice($extension);

        $fpbx->Certman->removeDTLSOptions($extension);

        return array('status' => true, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
    } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
    }
}

function checkFreeExtension($extension){
    try {
        $errors = array(); $warnings = array(); $infos = array();
        $dbh = \FreePBX::Database();
        //Check extensions
        $sql = 'SELECT 1 FROM sip WHERE id = ?
                UNION ALL SELECT 1 FROM devices WHERE id = ?
                UNION ALL SELECT 1 FROM users WHERE extension = ? LIMIT 1';
        $sth = $dbh->prepare($sql);
        $sth->execute([$extension, $extension, $extension]);
        if($sth->fetch(\PDO::FETCH_ASSOC)) {
            $errors[] = "Extension $extension already in use";
            return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
        }

        //Check ringgroups
        $sql = 'SELECT * FROM `ringgroups` WHERE `grpnum`= ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($extension));
        if($sth->fetch(\PDO::FETCH_ASSOC)) {
            $errors[] = "Extension $extension already in use in groups";
            return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
        }

        //check custom featurecodes
        $sql = 'SELECT * FROM `featurecodes` WHERE `customcode` = ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($extension));
        if($sth->fetch(\PDO::FETCH_ASSOC)) {
            $errors[] = "Extension $extension already in use as custom code";
            return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
        }

        //check default feature codes
        if (checkTableExists("featurecodes")){
            $sql = 'SELECT * FROM `featurecodes` WHERE `defaultcode` = ? AND `customcode` IS NULL';
            $sth = $dbh->prepare($sql);
            $sth->execute(array($extension));
            if($sth->fetch(\PDO::FETCH_ASSOC)) {
                $errors[] = "Extension $extension already in use as default code";
                return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
            }
        }

        //check queues
        $sql = 'SELECT * FROM `queues_details` WHERE `id` = ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($extension));
        if($sth->fetch(\PDO::FETCH_ASSOC)) {
            $errors[] = "Extension $extension already in use as queue";
            return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
        }

        //check trunks
        $sql = 'SELECT * FROM `trunks` WHERE `channelid` = ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($extension));
        if($sth->fetch(\PDO::FETCH_ASSOC)) {
            $errors[] = "Extension $extension already in use as trunk";
            return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
        }

        //check parkings
        if (checkTableExists("parkplus")){
            $sql = 'SELECT * FROM `parkplus` WHERE `parkext` = ?';
            $sth = $dbh->prepare($sql);
            $sth->execute(array($extension));
            if($sth->fetch(\PDO::FETCH_ASSOC)) {
                $errors[] = "Extension $extension already in use as parking";
                return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
            }
        }
        return array('status' => true, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $errors[] = $e->getMessage();
        return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
    }
}
