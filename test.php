<?php
namespace PMVC\App\dimension;

use PMVC;
use PHPUnit_Framework_TestCase;

PMVC\Load::plug();
PMVC\addPlugInFolders(['../']);
PMVC\l(__DIR__.'/vendor/pmvc-plugin/controller/tests/resources/FakeView.php');

class DimensionActionTest extends PHPUnit_Framework_TestCase
{
    function testProcessAction()
    {
        $view = \PMVC\plug(
            'view',
            [
                _CLASS => '\PMVC\FakeView',
            ]
        );
        $pDot = \PMVC\plug('dotenv');
        $pDot[\PMVC\PlugIn\dotenv\ENV_FOLDER] = __DIR__.'/tests/resources';
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->plugApp(['./']);
        $r = $c->getRequest();
        $result = $c->process();
        $actual = \PMVC\value($result,[0,'v']);
        $expected = [
            'test'=>1234
        ];
        $this->assertEquals($expected, $actual);
    }
}


