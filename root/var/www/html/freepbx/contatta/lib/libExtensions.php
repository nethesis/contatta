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
        $randomString .= $characters[rand(0, $charactersLength - 1)];
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

function createExtension($extension,$secret){
    try {
        global $astman;
        $errors = array(); $warnings = array(); $infos = array();

        $res = checkFreeExtension($extension);
        $infos = array_merge($infos,$res['infos']);
        $warnings = array_merge($warnings,$res['warnings']);
        $errors = array_merge($errors,$res['errors']);
        if ($res['status'] === false) {
            return array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings);
        }

        $fpbx = FreePBX::create();
        $dbh = FreePBX::Database();

        //delete extension
        $fpbx->core->delDevice($extension, true);
        $fpbx->core->delUser($extension, true);
 
        //create physical extension
        $data['name'] = 'extension_'.$extension;
        $res = $fpbx->Core->processQuickCreate('pjsip', $extension, $data);
        if (!$res['status']) {
            throw ("Error creating extension");
        }
        //Set cid_masquerade (CID Num Alias)
        $astman->database_put("AMPUSER",$extension."/cidnum",$extension);

        //Add device
        $astman->database_put("AMPUSER", $extension."/device", $extension);

        //set accountcode = mainextension
        $sql = 'UPDATE IGNORE `sip` SET `data` = ? WHERE `id` = ? AND `keyword` = "accountcode"';
        $stmt = $dbh->prepare($sql);
        $stmt->execute(array($extension,$extension));

        //disable call waiting
        $astman->database_del("CW",$extension);

        // Overwrite sip password with the one provided
        $sql = 'UPDATE `sip` SET `data` = ? WHERE `id` = ? AND `keyword` = "secret"';
        $stmt = $dbh->prepare($sql);
        $stmt->execute(array($secret,$extension));

        // Add extension to webcall context
        $sql = 'UPDATE `sip` SET `data` = "webcall" WHERE `id` = ? AND `keyword` = "context"';
        $stmt = $dbh->prepare($sql);
        $stmt->execute(array($extension));

        return array('status' => true, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos, 'extension' => $extension, 'secret' => $secret);
    } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return array('status' => false, 'errors'=> $errors, 'warnings' => $warnings, 'infos' => $infos);
    }
}

function deleteExtension($extension) {
    try {
        global $astman;
        $errors = array(); $warnings = array(); $infos = array();
        $dbh = FreePBX::Database();

        $res = checkFreeExtension($extension);
        if ($res['status'] === true) {
            $warnings[] = 'Extension doesn\'t exists';
        }

        // clean extension
        $fpbx = FreePBX::create();
        $fpbx->Core->delUser($extension);
        $fpbx->Core->delDevice($extension);
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
        $sql = 'SELECT * FROM `sip` WHERE `id`= ?';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($extension));
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

        //check defaul feturecodes
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

