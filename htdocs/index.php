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

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;

set_exception_handler(function (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal server error']);
});

$restRoot = '/var/www/html/freepbx/rest';
$restLoader = require $restRoot . '/vendor/autoload.php';

// Resolve the REST stack's PSR contracts before FreePBX registers its own versions.
foreach (['Message', 'Request', 'ServerRequest', 'Response', 'Stream', 'Uri', 'UploadedFile'] as $contract) {
    interface_exists('Psr\\Http\\Message\\' . $contract . 'Interface');
}
foreach (['Request', 'Response', 'ServerRequest', 'Stream', 'Uri', 'UploadedFile'] as $contract) {
    interface_exists('Psr\\Http\\Message\\' . $contract . 'FactoryInterface');
}
interface_exists('Psr\\Http\\Server\\MiddlewareInterface');
interface_exists('Psr\\Http\\Server\\RequestHandlerInterface');

$bootstrap_settings['freepbx_error_handler'] = false;
define('FREEPBX_IS_AUTH', 1);
require_once '/etc/freepbx.conf';

$restLoader->unregister();
$restLoader->register(true);
require_once $restRoot . '/lib/JsonResponse.php';
require_once __DIR__ . '/lib/AuthMiddleware.php';

$app = AppFactory::create();
$app->setBasePath('/freepbx/contatta');
$app->addRoutingMiddleware();
$app->add(new MethodOverrideMiddleware());
$app->addBodyParsingMiddleware();
$app->add(new AuthMiddleware($app->getResponseFactory()));
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->getDefaultErrorHandler()->forceContentType('application/json');

foreach (glob(__DIR__ . '/modules/*.php') as $filename) {
    require $filename;
}

$app->run();
