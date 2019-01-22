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

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

include_once('/var/www/html/freepbx/contatta/lib/libExtensions.php');

$app->post('/extension/{extension}', function (Request $request, Response $response, $args) {
    try {
        global $astman;
        $errors = array(); $warnings = array(); $infos = array();

        $route = $request->getAttribute('route');
        $extension = $route->getArgument('extension');
        $body = $request->getParsedBody();
        $context = isset($body['context']) && !empty($body['context']) ? $body['context'] : 'webcall' ;
        $secret = isset($body['secret']) && !empty($body['secret']) ? $body['secret'] : generateRandomPassword();

        $res = createExtension($extension,$secret,$context);
        if ($res['status'] === false) {
            return $response->withJson($res, 500);
        }

        system('/var/www/html/freepbx/contatta/lib/retrieveHelper.sh > /dev/null &');
        return $response->withJson($res, 200);
   } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
   }
});

$app->delete('/extension/{extension}', function (Request $request, Response $response, $args) {
    try {
        global $astman;
        $route = $request->getAttribute('route');
        $extension = $route->getArgument('extension');

        $res = deleteExtension($extension);
        if ($res['status'] === false) {
            return $response->withJson($res, 500);
        }
        system('/var/www/html/freepbx/contatta/lib/retrieveHelper.sh > /dev/null &');
        return $response->withJson($res, 200);
    } catch (Exception $e) {
       error_log($e->getMessage());
       $errors[] = $e->getMessage();
       return $response->withJson(array('status' => false, 'errors' => $errors, 'infos' => $infos, 'warnings' => $warnings), 500);
    }
});

