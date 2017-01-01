<?php
namespace PMVC\App\dimension;

use PMVC;
use PHPUnit_Framework_TestCase;

PMVC\Load::plug();
PMVC\addPlugInFolders(['../']);

/*Fake View*/
PMVC\initPlugIn(['controller'=>null]);
PMVC\l(__DIR__.'/vendor/pmvc-plugin/controller/tests/resources/FakeView.php');
PMVC\option('set', 'DIMENSION_FOLDER', './tests/resources');

class DimensionActionTest extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        \PMVC\unplug('controller');
        \PMVC\unplug('view');
        \PMVC\unplug(_RUN_APP);
        \PMVC\plug(
            'view',
            [
                _CLASS => '\PMVC\FakeView',
            ]
        );
    }

    function testProcessAction()
    {
        $pDot = \PMVC\plug('dotenv');
        $pDot[\PMVC\PlugIn\dotenv\ENV_FOLDER] = __DIR__.'/tests/resources';
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->plugApp(['../']);
        $result = $c->process();
        $actual = \PMVC\value($result,[0,'v']);
        $expected = [
            'testKey'=>1234
        ];
        $this->assertEquals($expected, $actual);
    }

    function testDebug()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->plugApp(['../']);
        \PMVC\plug('dev');
        \PMVC\plug('debug',[
            'level'=>'dimension',
            'output'=>'debug_store'
        ]);
        $r = $c->getRequest();
        $r['test'] = 'fakeDimension';
        $result = $c->process();
        $actual = \PMVC\value($result,[0,'v','debugs','0']);
        $expected = [
            'dimension',
            ['fakedimension' => 'test']
        ];
        $this->assertEquals($expected, $actual);
    }

    function testFlatten()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->plugApp(['../']);
        $arr = [
            'foo',
            [
                'a',
                'b'
            ],
            'bar'
        ];
        $run = \PMVC\plug(_RUN_APP);
        $expected = [
            'foo_a_bar',
            'foo_b_bar'
        ];
        $actual = $run->flatten($arr);
        $this->assertEquals($expected, $actual);
    }

    function testValueToLower()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->plugApp(['../']);
        $run = \PMVC\plug(_RUN_APP);
        $run->init();
        $f = [
            'foo'=>'foo',
            'xxx'=>['A','B'],
            'bar'=>'bar'
        ];
        $dim = 'foo_xxx_bar';
        $actual = $run->getFlattenInput($f,$dim);
        $expected = [
            'foo_a_bar',
            'foo_b_bar'
        ];
        $this->assertEquals($expected, $actual);
    }
}


