<?php
namespace PMVC\App\dimension;

use PMVC;
use PMVC\Action;

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
    private $_escape;
    private $_inputs = [];

    function index($m, $f)
    {
        $this->_dot = \PMVC\plug('dotenv');
        $this->_underscore = \PMVC\plug('underscore');
        $configs = $this->_dot->getUnderscoreToArray('.env.dimension');
        $this->_folder = \PMVC\lastSlash(\PMVC\getOption('DIMENSION_FOLDER'));
        if (!\PMVC\realpath($this->_folder)) {
            return !trigger_error('Dimensions settings folder not exists. ['.$this->_folder.']');
        }
        $this->_escape = \PMVC\value($configs,['ESCAPE']);
        $allConfigs = $this->getConfigs('.dimension.base');

        foreach($configs['DIMENSIONS'] as $dimension)
        {
            $dimensionConfigs = $this->processInputForOneDimension($f, $dimension);
            $allConfigs = array_replace_recursive(
                $allConfigs, 
                $dimensionConfigs
            );
        }
        \PMVC\dev($this->_inputs, DEBUG_KEY);
        if (isset($allConfigs['_'])) {
            $this->processConstantArray($allConfigs);
        }
        $go = $m['dump'];
        $go->set($allConfigs);
        return $go;
    }

    function processConstantArray(&$arr)
    {
        $_ = \PMVC\plug('underscore')
            ->array()
            ->toUnderscore($arr['_']);
        unset($arr['_']);
        foreach ($_ as $k=>$v) {
            $k = substr($k,1);
            if (defined($k)) {
                $k = constant($k);
            }
            $arr[$k] = $v;
        }
    }

    function processInputForOneDimension($f, $dimension)
    {
        $keys = explode('_',$dimension);
        $inputs = [];
        foreach ($keys as $key) {
            $inputs[]=\PMVC\value($f, [$key]); 
        }
        $all_input = $this->flatten($inputs);
        if (empty($all_input)) {
            return [];
        }
        if (\PMVC\isdev(DEBUG_KEY)) {
            foreach($all_input as $i) {
                $this->_inputs[$i] = $dimension;
            }
        }
        if (count($all_input)>1) {
            return $this->getMultiInputConfigs($all_input, $dimension);
        } else {
            $all_input = reset($all_input);
            return $this->getOneInputConfigs($all_input, $dimension);
        }
    }

    function flatten($array, $prefix='')
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
            $arr = $this->_dot->getArray($file);
            if (!is_array($arr)) {
                trigger_error(
                    '[\PMVC\App\dimension\getConfigs] '.
                    'Parse dimension setting fail. ['.$file.']'
                );
                return [];
            }
            $arr = $this->_underscore
                ->underscore()
                ->toArray($arr, $this->_escape);

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
            $allConfigs = array_replace(   
                $allConfigs,   
                $arr
            );
        }
        return $allConfigs;
    }
}
