<?php

namespace PMVC\App\dimension;

use DomainException; 

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\Store';

class Store
{
    private $_folder;
    private $_dot;
    private $_underscore;

    public function __construct()
    {
        $this->_dot = \PMVC\plug('dotenv');
        $this->_underscore = \PMVC\plug('underscore');
        $this->_folder = \PMVC\lastSlash(\PMVC\getOption('dimensionFolder'));
        if (!\PMVC\realpath($this->_folder)) {
            throw new DomainException('Dimensions settings folder not exists. ['.$this->_folder.']');
        }
    }

    public function __invoke()
    {
        return $this;
    }

    function getMultiInputConfigs($inputs, $dimension)
    {
        $allKeys = [];
        $allKeyMap = [];
        $allConfigs = [];
        foreach($inputs as $input)
        {
            $arr = $this->getOneInputConfigs($input, $dimension);

            // <!-- Verify Conflict
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
            // -->

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

            \PMVC\dev(function() use ($allConfigs, $arr, $file) {
                return [
                    'before'=> $allConfigs,
                    'merge' => $arr,
                    'with'  => $file
                ];
            }, DEBUG_KEY.'-file');

            $allConfigs = array_replace_recursive(
                $allConfigs,   
                $arr
            );
        }
        return $allConfigs;
    }
}
