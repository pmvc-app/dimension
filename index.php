<?php
namespace PMVC\App\dimension;

use PMVC;
use PMVC\Action;
use PMVC\PlugIn\dotenv;

$b = new \PMVC\MappingBuilder();
$b->addAction('index');
$b->addForward('dump',[_TYPE=>'view']);

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\dimension';
${_INIT_CONFIG}[_INIT_BUILDER] = $b;

\PMVC\unplug('view_config_helper');

const DEBUG_KEY = 'dimension';

class dimension extends Action
{
    private $_dot;
    private $_folder;
    private $_underscore;
    private $_inputs = [];

    function index($m, $f)
    {
        $this->init();
        $configs = $this->_dot->getUnderscoreToArray('.env.dimension');
        $this->_folder = \PMVC\lastSlash(\PMVC\value($configs, ['FOLDER']));
        if (!\PMVC\realpath($this->_folder)) {
            return !trigger_error('Dimensions settings folder not exists. ['.$this->_folder.']');
        }
        $this->_dot[dotenv\ESCAPE] = \PMVC\value($configs,[dotenv\ESCAPE]);
        $allConfigs = $this->getConfigs('.dimension.base');

        foreach($configs['DIMENSIONS'] as $dimension)
        {
            $dimensionConfigs = $this->processInputForOneDimension(
                $this->getFlattenInput($f, $dimension),
                $dimension
            );
            $allConfigs = array_replace_recursive(
                $allConfigs, 
                $dimensionConfigs
            );
        }
        \PMVC\dev(function(){return $this->_inputs;}, DEBUG_KEY);
        if (isset($allConfigs['_'])) {
            $allConfigs = $this->_dot
                 ->processConstantArray($allConfigs);
        }
        $go = $m['dump'];
        $go->set($allConfigs);
        return $go;
    }

    function init()
    {
        $this->_dot = \PMVC\plug('dotenv');
        $this->_underscore = \PMVC\plug('underscore');
    }

    function processInputForOneDimension(array $flattenInputs, $dimension)
    {
        if (empty($flattenInputs)) {
            return [];
        }
        if (\PMVC\isdev(DEBUG_KEY)) {
            foreach ($flattenInputs as $i) {
                $this->_inputs[$i] = $dimension;
            }
        }
        if (count($flattenInputs)>1) {
            return $this->getMultiInputConfigs($flattenInputs, $dimension);
        } else {
            $flattenInputs = reset($flattenInputs);
            return $this->getOneInputConfigs($flattenInputs, $dimension);
        }
    }

    function getFlattenInput($f, $dimension)
    {
        $keys = explode('_',$dimension);
        $inputs = [];
        foreach ($keys as $key) {
            $val = \PMVC\value($f, [$key]);
            if (is_array($val)) {
                $inputs[] = array_map('strtolower', $val);
            } else {
                $inputs[] = strtolower($val);
            }
        }
        $all_input = $this->flatten($inputs);
        return $all_input;
    }

    function flatten(array $array)
    {
        $lines = [];
        foreach($array as $v) {
            if (empty($v)) {
                continue;
            }
            $new = [];
            if (is_array($v)) {
                if (empty($lines)) {
                    foreach ($v as $v1) {
                        $new[$v1] = null;
                    }
                } else {
                    foreach ($lines as $lk=>$lv) {
                        foreach ($v as $v1) {
                            $new[$lk.'_'.$v1] = null;
                        }
                    }
                }
            } else {
                if (empty($lines)) {
                    $new[$v] = null;
                } else {
                    foreach ($lines as $lk=>$lv) {
                        $new[$lk.'_'.$v] = null;
                    }
                }
            }
            $lines = $new;
        }
        return array_keys($lines);
    }

    function getMultiInputConfigs($inputs, $dimension)
    {
        $allKeys = [];
        $allKeyMap = [];
        $allConfigs = [];
        foreach($inputs as $input)
        {
            $arr = $this->getOneInputConfigs($input, $dimension);

            /*<!-- Verify Conflict*/
            $keys = $this->_underscore
                ->array()
                ->toUnderscore($arr);
            $keys = array_keys($keys);
            $found = false;
            foreach($keys as $key)
            {
                if (isset($allKeyMap[$key])) {
                    $found = $key;
                } else {
                    foreach ($allKeys as $aV) {
                       if (0===strpos($aV,$key) || 0===strpos($key,$aV)) {
                            $found=$aV;
                            break;
                       }
                    }
                    if (!$found) {
                        $allKeys[] = $key;
                        $allKeyMap[$key] = $input;
                        continue;
                    }
                }
                trigger_error('Conflict for '.$dimension.' key: ['.$found.'].'.
                    ' Between ['.$allKeyMap[$found].'] and ['.$input.']'
                );
            }
            /*-->*/

            $allConfigs = array_replace_recursive(   
                $allConfigs,   
                $arr
            );
        }
        return $allConfigs;
    }

    function getOneInputConfigs($input, $dimension)
    {
        $file = $this->getOneInputFile($input, $dimension);
        $configs =  $this->getConfigs($file);
        if (!empty($configs['base'])) {
            $baseFile = $this->getOneInputFile($configs['base'], $dimension);
            $baseConfigs =  $this->getConfigs($baseFile);
            $configs = array_replace_recursive(   
                $baseConfigs,   
                $configs
            );
        }
        return $configs;
    }

    function getOneInputFile($input, $dimension)
    {
        return '.dimension.'.$dimension.'.'.$input;
    }

    function getConfigs($file)
    {
        $path = $this->_folder.$file;
        $allFile = glob($path.'.*');
        if (\PMVC\realPath($path)) {
            $allFile[]=$path;
        }
        $allKeys = [];
        $allConfigs = [];
        foreach($allFile as $file)
        {
            $arr = $this->_dot->getUnderscoreToArray($file);
            if (!is_array($arr)) {
                trigger_error(
                    '[\PMVC\App\dimension\getConfigs] '.
                    'Parse dimension setting fail. ['.$file.']'
                );
                return [];
            }

            // <!-- check if key conflict
            $keys = $this->_underscore
                ->array()
                ->toUnderscore($arr);
            $keys = array_keys($keys);
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
            // -->

            $allConfigs = array_replace(   
                $allConfigs,   
                $arr
            );
        }
        return $allConfigs;
    }
}
