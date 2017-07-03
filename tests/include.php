<?php

namespace PMVC\PlugIn\url;

const vendorDir = __DIR__.'/../vendor/';

$path = vendorDir.'autoload.php';
include $path;

\PMVC\Load::plug(['controller'=>null]);
\PMVC\l(vendorDir.'pmvc-plugin/controller/tests/resources/FakeView.php');

$pDot = \PMVC\plug('dotenv');
$pDot[\PMVC\PlugIn\dotenv\ENV_FOLDER] = __DIR__.'/resources';
