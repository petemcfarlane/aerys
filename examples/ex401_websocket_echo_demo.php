<?php

/**
 * IMPORTANT: Websocket endpoints are NOT normal HTTP resources.
 *
 * If you actually want to see something happen with a websocket endpoint you need to simultaneously
 * serve an HTML resource with javascript that connects to that endpoint to send data back and
 * forth over the websocket connections. If you serve websocket endpoint by itself and try to open
 * that address in a browser you'll receive a *426 Upgrade Required* response. Websockets don't work
 * that way. They aren't pages you can simply open in a browser.
 *
 * Though you can add any number of websocket endpoints for a given Aerys app, this example only
 * specifies the one endpoint at ws://127.0.0.1/echo
 *
 * To run:
 * $ bin/aerys -c examples/ex401_websocket_echo_demo.php
 *
 * Once started, load http://127.0.0.1/ in your browser.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// This file contains our websocket handler class: Ex401_WebsocketEchoEndpoint
require_once __DIR__ . '/support/Ex401_WebsocketEchoEndpoint.php';

$myApp = (new Aerys\Framework\App)
    ->setDocumentRoot(__DIR__ . '/support/docroot/websockets')
    ->addWebsocket('/echo', 'Ex401_WebsocketEchoEndpoint');
