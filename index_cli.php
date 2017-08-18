<?php

namespace PMVC\App\dimension;

use PMVC\Action;
use PMVC\MappingBuilder;
use DomainException; 

$b = new MappingBuilder();
$b->addAction('decode');
$b->addAction('encode');
$b->addForward('dump',[_TYPE=>'view']);

${_INIT_CONFIG}[_CLASS] = 
    __NAMESPACE__.
    '\DimensionCliAction';
${_INIT_CONFIG}[_INIT_BUILDER] = $b;

class DimensionCliAction extends Action
{
    const PLAIN_TEXT_EXT='.plaintext';
    static public function encode($m, $f)
    {
        $encryptor = \PMVC\plug('simple_encryptor', ['key'=>$f['key']]);
        $folderConf = \PMVC\getOption('dimensionFolder');
        $folder = \PMVC\realPath($folderConf);
        if (empty($folder)) {
            throw new DomainException('Dimensions settings folder not exists. ['.$folderConf.']');
        }
        $plainTextFiles = glob(
            $folder.
            '/.*.pw'.self::PLAIN_TEXT_EXT
        );
        if (empty($plainTextFiles)) {
            throw new DomainException('Not found plain text files. ['.$folderConf.']');
        }
        foreach ($plainTextFiles as $f) {
            $text = file_get_contents($f);
            $newName = substr($f, 0, strrpos($f, self::PLAIN_TEXT_EXT));
            $encodeText = $encryptor->encode($text);
            file_put_contents($newName, $encodeText);
        }
        $go = $m['dump'];
        $go->set('O.K.');
        return $go;
    }

    static public function decode($m, $f)
    {
        $encryptor = \PMVC\plug('simple_encryptor', ['key'=>$f['key']]);
        $folderConf = \PMVC\getOption('dimensionFolder');
        $folder = \PMVC\realPath($folderConf);
        if (empty($folder)) {
            throw new DomainException('Dimensions settings folder not exists. ['.$folderConf.']');
        }
        $secretFiles = glob(
            $folder.
            '/.*.pw'
        );
        if (empty($secretFiles)) {
            throw new DomainException('Not found secret files. ['.$folderConf.']');
        }
        foreach ($secretFiles as $f) {
            $text = file_get_contents($f);
            $newName = $f.self::PLAIN_TEXT_EXT;
            $decodeText = $encryptor->decode($text);
            file_put_contents($newName, $decodeText);
        }
        $go = $m['dump'];
        $go->set('O.K.');
        return $go;
    }
}
