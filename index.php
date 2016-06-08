<?php
namespace PMVC\App\dimension;

use PMVC;

$b = new \PMVC\MappingBuilder();
${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\dimension';
${_INIT_CONFIG}[_INIT_BUILDER] = $b;

$b->addAction('index');
\PMVC\unplug('view_config_helper');
$b->addForward('dump',[_TYPE=>'view']);

class dimension extends \PMVC\Action
{
    private $_dot;
    private $_folder;
    private $_underscore;

    function index($m, $f)
    {
        $this->_dot = \PMVC\plug('dotenv');
        $this->_underscore = \PMVC\plug('underscore')->underscore();
        $configs = $this->_dot->getUnderscoreToArray('.env.dimension');
        $this->_folder = \PMVC\lastSlash($configs['FOLDER']);
        $allConfigs = $this->getConfigs('.dimension.base');

        foreach($configs['DIMENSIONS'] as $dimension)
        {
            $dimensionConfigs = $this->processInput($f, $dimension);
            $allConfigs = array_replace_recursive(
                $allConfigs, 
                $dimensionConfigs
            );
        }
        $go = $m['dump'];
        $go->set($allConfigs);
        return $go;
    }

    function processInput($f, $dimension)
    {
        $input = \PMVC\value($f, [$dimension]);
        if (!$input) {
            return [];
        }
        if (is_array($input) && count($input)>1) {
            return $this->getMultiInputConfigs($input, $dimension);
        } else {
            if (is_array($input)) {
                $input = reset($input);
            }
            return $this->getOneInputConfigs($input, $dimension);
        }
    }

    function getMultiInputConfigs($inputs, $dimension)
    {
        $allKeys = [];
        $allConfigs = [];
        foreach($inputs as $input)
        {
            $file = $this->getOneInputFile($input, $dimension);
            $arr  = $this->getConfigs($file);

            $keys = array_keys($arr);
            foreach($keys as $key)
            {
                if (!isset($allKeys[$key])) {
                    $allKeys[$key] = $input;
                } else {
                    trigger_error('Conflict for '.$dimension.' key: ['.$key.'].'.
                        ' Between ['.$allKeys[$key].'] and ['.$input.']'
                    );
                }
            }
            $allConfigs = array_replace_recursive(   
                $allConfigs,   
                $this->_underscore->toArray($arr)
            );
        }
        return $allConfigs;
    }

    function getOneInputFile($input, $dimension)
    {
        return '.dimension.'.$dimension.'.'.$input;
    }

    function getOneInputConfigs($input, $dimension)
    {
        $file = $this->getOneInputFile($input, $dimension);
        $configs = $this->getConfigs($file);
        return $this->_underscore->toArray($configs); 
    }

    function getConfigs($file)
    {
        $path = $this->_folder.$file;
        $allFile = glob($path.'*');
        $allKeys = [];
        $allConfigs = [];
        foreach($allFile as $file)
        {
            $arr = $this->_dot->getArray($file);
            $keys = array_keys($arr);
            foreach($keys as $key)
            {
                if (!isset($allKeys[$key])) {
                    $allKeys[$key] = $file;
                } else {
                    trigger_error('Conflict for key: ['.$key.'].'.
                        ' Between ['.$allKeys[$key].'] and ['.$file.']'
                    );
                }
            }
            $allConfigs = array_replace(   
                $allConfigs,   
                $arr
            );
        }
        return $allConfigs;
    }

}
