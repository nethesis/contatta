<?php
#
# Copyright (C) 2022 Nethesis S.r.l.
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

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/trunk', function (Request $request, Response $response, $args) {
    try {
        $result = array();
        $trunks = FreePBX::Core()->listTrunks();
        foreach($trunks as $trunk) {
            // Get trunk username
            $details = FreePBX::Core()->getTrunkDetails($trunk['trunkid']);
            $trunk['username'] = $details['username'];
            array_push($result, $trunk);
        }
        return $response->withJson($result,200);
    }
    catch (Exception $e) {
      error_log($e->getMessage());
      return $response->withJson('An error occurred', 500);
    }
});

$app->post('/trunk[/{trunkid}]', function (Request $request, Response $response, $args) {
    try {
        $route = $request->getAttribute('route');
        $name = $route->getArgument('name');
        $params = $request->getParsedBody();

        $dbh = FreePBX::Database();

        $body_parameters = ['name','outcid','sipserver','sipserverport','context','authentication','registration','username','secret','contactuser','fromdomain','fromuser','codecs'];
        foreach ($body_parameters as $p) {
            if (!isset($params[$p])) {
                throw new Exception("Missing $p parameter");
            }
        }
        if (empty($trunkid)) {
            // Get first available trunk id
            $sql = 'SELECT trunkid FROM trunks';
            $sth = $dbh->prepare($sql);
            $sth->execute();
            $trunkid = 1;
            while ($res = $sth->fetchColumn()) {
                if ($res > $trunkid) {
                    break;
                }
                $trunkid++;
            }
            if ($res == $trunkid) {
                $trunkid++;
            }
        } else {
            // Insert data into trunks table
            $sql = "DELETE IGNORE FROM `trunks` WHERE `trunkid` = ?";
            $sth = $dbh->prepare($sql);
            $sth->execute([$trunkid]);

            $sql = "DELETE IGNORE FROM `pjsip` WHERE `id` = ?";
            $sth = $dbh->prepare($sql);
            $sth->execute([$trunkid]);
        }
        $sql = "INSERT INTO `trunks` (`trunkid`,`tech`,`channelid`,`name`,`outcid`,`keepcid`,`maxchans`,`failscript`,`dialoutprefix`,`usercontext`,`provider`,`disabled`,`continue`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $sth = $dbh->prepare($sql);
        $sth->execute(array(
            $trunkid,
            'pjsip',
            $params['name'],
            $params['name'],
            $params['outcid'],
            'off',
            '',
            '',
            '',
            '',
            '',
            'off',
            'off'
        ));

        // Insert data into pjsip table
        // Set pjsip data
        usort($params['codecs'], function($a, $b) {
            return $a['position'] - $b['position'];
        });
        $codecs = array();
        foreach ($params['codecs'] as $c) {
            if ($c['enabled']) $codecs[] = $c['nome'];
        }
        $pjsip_data = array(
            "aor_contact" => "",
            "aors" => "",
            "auth_rejection_permanent" => "off",
            "authentication" => $params['authentication'],
            "client_uri" => "",
            "codecs" => implode(',',$codecs),
            "contact_user" => $params['contactuser'],
            "context" => $params['context'],
            "dialopts" => "",
            "dialoutopts_cb" => "sys",
            "direct_media" => "no",
            "disabletrunk" => "off",
            "dtmfmode" => "auto",
            "expiration" => "300",
            "extdisplay" => "OUT_$trunkid",
            "failtrunk_enable" => "0",
            "fatal_retry_interval" => "0",
            "fax_detect" => "no",
            "forbidden_retry_interval" => "10",
            "force_rport" => "yes",
            "from_domain" => $params['fromdomain'],
            "from_user" => $params['fromuser'],
            "hcid" => "on",
            "identify_by" => "default",
            "inband_progress" => "no",
            "language" => "",
            "match" => "",
            "max_retries" => "10000",
            "maxchans" => "",
            "media_address" => "",
            "media_encryption" => "no",
            "message_context" => "",
            "npanxx" => "",
            "outbound_proxy" => "",
            "peerdetails" => "",
            "qualify_frequency" => "60",
            "register" => "",
            "registration" => $params['registration'],
            "retry_interval" => "60",
            "rewrite_contact" => "yes",
            "rtp_symmetric" => "yes",
            "secret" => "secret",
            "sendrpid" => "no",
            "server_uri" => "",
            "sip_server" =>  $params['sipserver'],
            "sip_server_port" => $params['sipserverport'],
            "support_path" => "no",
            "sv_channelid" => $params['name'],
            "sv_trunk_name" => $params['name'],
            "sv_usercontext" => "",
            "t38_udptl" => "no",
            "t38_udptl_ec" => "none",
            "t38_udptl_maxdatagram" => "",
            "t38_udptl_nat" => "no",
            "transport" => "0.0.0.0-udp",
            "trust_rpid" => "no",
            "trunk_name" => $params['name'],
            "userconfig" => "",
            "username" => "username",
        );

        $insert_data = array();
        $insert_qm = array();
        foreach ($pjsip_data as $keyword => $data) {
            $insert_data = array_merge($insert_data,[$trunkid,$keyword,$data,0]);
            $insert_qm[] = '(?,?,?,?)';
        }
        $sql = 'INSERT INTO `pjsip` (`id`,`keyword`,`data`,`flags`) VALUES '.implode(',',$insert_qm);
        $sth = $dbh->prepare($sql);
        $res = $sth->execute($insert_data);
        if (!$res) {
            return $response->withStatus(500);
        }

        system('/var/www/html/freepbx/contatta/lib/retrieveHelper.sh > /dev/null &');
        return $response->withJson(["trunkid" => $trunkid], 200);
   } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
   }
});

$app->delete('/trunk/{trunkid}', function (Request $request, Response $response, $args) {
    try {
        $route = $request->getAttribute('route');
        $trunkid = $route->getArgument('trunkid');
        $dbh = FreePBX::Database();

        $sql = "DELETE IGNORE FROM `trunks` WHERE `trunkid` = ?";
        $sth = $dbh->prepare($sql);
        $sth->execute([$trunkid]);

        $sql = "DELETE IGNORE FROM `pjsip` WHERE `id` = ?";
        $sth = $dbh->prepare($sql);
        $sth->execute([$trunkid]);

        system('/var/www/html/freepbx/contatta/lib/retrieveHelper.sh > /dev/null &');
        return $response->withStatus(204);
    } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
    }
});

$app->get('/inboundroute', function (Request $request, Response $response, $args) {
    try {
      $routes = FreePBX::Core()->getAllDIDs('extension');
      return $response->withJson($routes, 200);
    } catch (Exception $e) {
      error_log($e->getMessage());
      return $response->withJson('An error occurred', 500);
    }
});

$app->post('/inboundroute', function (Request $request, Response $response, $args) {
    try {
        $route = $request->getAttribute('route');
        $settings = $request->getParsedBody();
        $dbh = FreePBX::Database();

        $body_parameters = ['cidnum','description','extension','destination'];
        foreach ($body_parameters as $p) {
            if (!isset($settings[$p])) {
                throw new Exception("Missing $p parameter");
            }
        }

        if (FreePBX::Core()->addDID($settings)) {
            $res = FreePBX::Core()->getDID($settings['extension'],$settings['cidnum']);
        } else {
            throw new Exception("Error creating DID");
        }

        system('/var/www/html/freepbx/contatta/lib/retrieveHelper.sh > /dev/null &');
        return $response->withJson($res, 200);
   } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
   }
});

$app->delete('/inboundroute', function (Request $request, Response $response, $args) {
    try {
        $params = $request->getParsedBody();
        $extension = $params['extension'];
        $cidnum = $params['cid'];
        FreePBX::Core()->delDID($extension, $cidnum ? $cidnum : '');
        system('/var/www/html/freepbx/contatta/lib/retrieveHelper.sh > /dev/null &');
        return $response->withStatus(204);
    } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
    }
});

$app->get('/outboundroute', function (Request $request, Response $response, $args) {
    try {
        include_once '/var/www/html/freepbx/admin/modules/core/functions.inc.php';
        $routes = [];
        $allRoutes = FreePBX::Core()->getAllRoutes();
        foreach($allRoutes as $route) {
            $route_trunks = core_routing_getroutetrunksbyid($route['route_id']);
            $route['trunks'] = [];
            foreach($route_trunks as $trunkID) {
                $trunk = core_trunks_getDetails($trunkID);
                $route['trunks'][] = array("trunkid" => $trunkID, "name" => $trunk['name']);
            }
            $route['trunks'] = core_routing_getroutetrunksbyid($route['route_id']);
            $route['patterns'] = FreePBX::Core()->getRoutePatternsByID($route['route_id']);
            $routes[] = $route;
        }
        return $response->withJson($routes, 200);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $errors[] = $e->getMessage();
        return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
    }
});

$app->post('/outboundroute[/{route_id}]', function (Request $request, Response $response, $args) {
    try {
        include_once '/var/www/html/freepbx/admin/modules/core/functions.inc.php';
        $route = $request->getAttribute('route');
        $route_id = $route->getArgument('route_id');
        $params = $request->getParsedBody();
        $dbh = FreePBX::Database();

        if (isset($route_id)) {
            // Route exists. Get its parameter as defaults and delete it
            $default_params = FreePBX::Core()->getRouteByID($route_id);
            $default_params['trunks'] = core_routing_getroutetrunksbyid($route_id);
            $default_params['patterns'] = FreePBX::Core()->getRoutePatternsByID($route_id);
            core_routing_delbyid($route_id);
        } else {
            $default_params = array(
                'outcid' => '',
                'outcid_mode' => '',
                'password' => '',
                'emergency_route' => '',
                'intracompany_route' => '',
                'mohclass' => 'default',
                'time_group_id' => NULL,
                'seq' => NULL,
                'dest' => '',
                'time_mode' => '',
                'timezone' => '',
                'calendar_id' => '',
                'calendar_group_id' => '',
            );
            $default_params['trunks'] = [];
            $default_params['patterns'] = [];
        }

        $parameters_list = ['name','outcid','outcid_mode','password','emergency_route','intracompany_route','mohclass','time_group_id','patterns','trunks','seq','dest','time_mode','timezone','calendar_id','calendar_group_id'];
        $parameters = [];
        foreach ($parameters_list as $p) {
            if (array_key_exists($p,$params)) {
                $parameters[$p] = $params[$p];
            } elseif (array_key_exists($p,$default_params)) {
                $parameters[$p] = $default_params[$p];
            } else {
                throw new Exception("Missing $p parameter");
            }
        }
        call_user_func_array('core_routing_addbyid',$parameters);
        system('/var/www/html/freepbx/contatta/lib/retrieveHelper.sh > /dev/null &');
        return $response->withStatus(201);
   } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
   }
});

$app->delete('/outboundroute/{route_id}', function (Request $request, Response $response, $args) {
    try {
        include_once '/var/www/html/freepbx/admin/modules/core/functions.inc.php';
        $route = $request->getAttribute('route');
        $route_id = $route->getArgument('route_id');
        core_routing_delbyid($route_id);
        system('/var/www/html/freepbx/contatta/lib/retrieveHelper.sh > /dev/null &');
        return $response->withStatus(204);
    } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
    }
});
