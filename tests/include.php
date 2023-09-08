<?php

const vendorDir = __DIR__.'/../vendor/';

$path = vendorDir.'autoload.php';
include $path;

\PMVC\Load::plug(
    [
        'unit' => null,
        'controller'=>null,
        'dimension'=>false,
    ],
    [__DIR__ . '/../../']
);

\PMVC\l(vendorDir.'pmvc-plugin/controller/tests/resources/FakeView.php');

$pDot = \PMVC\plug('dotenv');
$pDot[\PMVC\PlugIn\dotenv\ENV_FOLDER] = __DIR__.'/resources';
