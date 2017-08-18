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
        $folder = \PMVC\getOption('dimensionFolder');
        $this->_folder = \PMVC\realPath($folder);
        if (!$this->_folder) {
            throw new DomainException('Dimensions settings folder not exists. ['.$folder.']');
        }
    }

    public function __invoke()
    {
        return $this;
    }

    public function getMultiInputConfigs($dimension, array $inputs)
    {
        $allKeys = [];
        $allKeyMap = [];
        $allConfigs = [];
        foreach($inputs as $input)
        {
            $arr = $this->getOneInputConfigs($dimension, $input);

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

    public function getOneInputConfigs( $dimension, $input=null )
    {
        $file = $this->_getOneInputFile($dimension, $input);
        $configs =  $this->_getConfigs($file);
        if (!empty($configs['base'])) {
            $baseFile = $this->_getOneInputFile($dimension, $configs['base']);
            $baseConfigs =  $this->_getConfigs($baseFile);
            $configs = array_replace_recursive(   
                $baseConfigs,   
                $configs
            );
        }
        return $configs;
    }

    private function _getOneInputFile($dimension, $input=null)
    {
        $file = '.dimension.'.$dimension;
        if (!is_null($input)) {
            $file .='.'.$input;
        }
        return $file;
    }

    private function _getConfigs($file)
    {
        $path = $this->_folder.'/'.$file;
        $allFile = glob($path.'.*');
        if (\PMVC\realPath($path)) {
            $allFile[]=$path;
        }
        $allKeys = [];
        $allConfigs = [];
        $encryptor = \PMVC\plug('simple_encryptor');
        foreach($allFile as $file)
        {
            if (fnmatch('*.pw.plaintext', $file)) {
                continue;
            } elseif (fnmatch('*.pw', $file)) {
                $file = (object)[
                    'path'=>$file
                ];
                $file->raw = $encryptor->decode(file_get_contents($file->path));
            }
            $arr = $this->_dot->getUnderscoreToArray($file);
            if (!is_array($arr)) {
                trigger_error(
                    '[\PMVC\App\dimension\getConfigs] '.
                    'Parse dimension setting fail. ['.print_r($file,true).']'
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
