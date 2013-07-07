<?php

include __DIR__ . '/vendor/autoload.php';

use Warlock\WarlockKernel;

WarlockKernel::getInstance()->init(array(
    'debug'    => true,
    'appDir'   => __DIR__ ,
    'cacheDir' => __DIR__ . '/cache/',
));

$demo = new \Demo\Example\General('test');
$demo->publicHello();