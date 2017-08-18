<?php

namespace PMVC\App\dimension;

use PHPUnit_Framework_TestCase;

class DimensionCliActionText
    extends PHPUnit_Framework_TestCase
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

    public function testEncode()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->setAppAction('encode');
        $c->plugApp(['../'], [], 'index_cli');
        $r = $c->getRequest();
        $r['key'] = 'fakeKey';
        $result = $c->process();
        $file=$c['dimensionFolder'].'/.dimension.encode.pw';
        $actual = file_get_contents($file);
        $expected = '4CYRwF3LTFSNhKs9lhwaUQ==';
        $this->assertEquals($expected, $actual);
    }

    public function testDecode()
    {
        $c = \PMVC\plug('controller');
        $c->setApp('dimension');
        $c->setAppAction('decode');
        $c->plugApp(['../'], [], 'index_cli');
        $r = $c->getRequest();
        $r['key'] = 'fakeKey';
        $result = $c->process();
        $file=$c['dimensionFolder'].'/.dimension.decode.pw.plaintext';
        $actual = file_get_contents($file);
        $expected = 'PW_123=456'.PHP_EOL.PHP_EOL;
        $this->assertEquals($expected, $actual);
    }
}
