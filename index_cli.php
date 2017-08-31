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
        $folderConf = \PMVC\getOption('dimensionFolder');
        $encryption = \PMVC\plug('dimension')->
            encryption($folderConf, $f['key'])->
            encode();
        $go = $m['dump'];
        $go->set('O.K.');
        return $go;
    }

    static public function decode($m, $f)
    {
        $folderConf = \PMVC\getOption('dimensionFolder');
        $encryption = \PMVC\plug('dimension')->
            encryption($folderConf, $f['key'])->
            decode();
        $go = $m['dump'];
        $go->set('O.K.');
        return $go;
    }
}
