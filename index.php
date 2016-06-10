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
    private $_escape;

    function index($m, $f)
    {
        $this->_dot = \PMVC\plug('dotenv');
        $this->_underscore = \PMVC\plug('underscore');
        $configs = $this->_dot->getUnderscoreToArray('.env.dimension');
        $this->_folder = \PMVC\lastSlash(\PMVC\value($configs,['FOLDER']));
        if (!\PMVC\realpath($this->_folder)) {
            return !trigger_error('Dimensions settings folder not exists. ['.$this->_folder.']');
        }
        $this->_escape = \PMVC\value($configs,['ESCAPE']);
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
        $keys = explode('_',$dimension);
        $inputs = [];
        foreach ($keys as $key) {
            $inputs[]=\PMVC\value($f, [$key]); 
        }
        $all_input = $this->flatten($inputs);
        if (empty($all_input)) {
            return [];
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
        $allConfigs = [];
        foreach($inputs as $input)
        {
            $file = $this->getOneInputFile($input, $dimension);
            $arr  = $this->getConfigs($file);

            /*<!-- Verify Conflict*/
            $keys = $this->_underscore
                ->array()
                ->toUnderscore($arr);
            $keys = array_keys($keys);
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
            /*-->*/

            $allConfigs = array_replace_recursive(   
                $allConfigs,   
                $arr
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
        return $this->getConfigs($file);
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
