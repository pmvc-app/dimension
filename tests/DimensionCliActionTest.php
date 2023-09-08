<?php

namespace PMVC\App\dimension;

use PMVC\TestCase;

class DimensionCliActionTest
    extends TestCase
{
    function pmvc_setup()
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
        \PMVC\plug('dev')->debug_with_cli('debug');
    }

    public function testEncode()
    {
        \PMVC\option('set', 'dimensionFolder', __DIR__.'/resources/encode');
        $key = 'fakeKey';
        $c = \PMVC\plug('controller');
        $appName = basename(dirname(__DIR__));
        $c->setApp($appName);
        $c->setAppAction('encode');
        $c->plugApp(['../'], [], 'index_cli');
        $r = $c->getRequest();
        $r['key'] = $key;
        $result = $c->process();
        $file=$c['dimensionFolder'].'/.dimension.encode.pw';
        $encode = file_get_contents($file);
        $encryptor = \PMVC\plug('simple_encryptor', ['key'=>$key]); 
        $decode = $encryptor->decode($encode);
        $expected = 'PW_123=456'.PHP_EOL.PHP_EOL;
        $this->assertEquals($expected, $decode);
    }

    public function testDecode()
    {
        \PMVC\option('set', 'dimensionFolder', __DIR__.'/resources/decode');
        $c = \PMVC\plug('controller');
        $appName = basename(dirname(__DIR__));
        $c->setApp($appName);
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
