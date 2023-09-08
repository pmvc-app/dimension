<?php

namespace PMVC\App\dimension;

use PMVC\TestCase;

class FlattenTest extends TestCase
{
    function pmvc_setup()
    {
        foreach (['controller', 'view', _RUN_APP] as $p) {
          \PMVC\unplug($p);
        } 
        \PMVC\plug('view', [
            _CLASS => '\PMVC\FakeView',
        ]);
        \PMVC\option('set', 'dimensionFolder', __DIR__ . '/resources');
    }

    function testFlatten()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->plugApp(['../']);
        $arr = ['foo', ['a', 'b'], 'bar'];
        $run = \PMVC\plug(_RUN_APP);
        $expected = ['foo_a_bar', 'foo_b_bar'];
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
            'foo' => 'fooV',
            'xxx' => ['A', 'B'],
            'bar' => 'barV',
        ];
        $actual = $run->flatten()->flattenInput($f, ['foo', 'xxx', 'bar']);
        $expected = ['foov_a_barv', 'foov_b_barv'];
        $this->assertEquals($expected, $actual);
    }
}
