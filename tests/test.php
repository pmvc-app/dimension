<?php

namespace PMVC\App\dimension;

use PHPUnit_Framework_TestCase;

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
        \PMVC\option('set', 'dimensionFolder', __DIR__.'/resources');
    }

    function testProcessAction()
    {
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
        $actual = $run->flatten()->flattenArray($arr);
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
        $actual = $run->
            flatten()->
            flattenInput($f,$dim);
        $expected = [
            'foo_a_bar',
            'foo_b_bar'
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException DomainException
     */
    function testDimensionFolderNotFound()
    {
        \PMVC\option('set', 'dimensionFolder', 'xxx');
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->plugApp(['../']);
        $result = $c->process();
    }

    function testUTMResetBucket()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->plugApp(['../']);
        $r = $c->getRequest();
        $r['UTM'] = 'foo_bar';
        $result = $c->process();
        $actual = \PMVC\value($result,[0,'v','resetBuckets']);
        $this->assertEquals('a1', $actual);
    }

}


