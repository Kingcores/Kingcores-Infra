<?php

define('ENV', 'dev'); // dev, prod, test, unit

require_once '../lib/Bluefin/bluefin.php';

Bluefin\App::getInstance()->bootstrap()->startGateway();