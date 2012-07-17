<?php

//define('ENV', 'prod'); // dev, prod, test, unit

require_once '../lib/Bluefin/bluefin.php';

$gateway = new Bluefin\Gateway();
$gateway->service();
