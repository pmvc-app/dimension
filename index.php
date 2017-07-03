<?php
namespace PMVC\App\dimension;

use PMVC;
use PMVC\Action;
use PMVC\PlugIn\dotenv;
use PMVC\MappingBuilder;

$b = new MappingBuilder();
$b->addAction('index');
$b->addForward('dump',[_TYPE=>'view']);

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\dimension';
${_INIT_CONFIG}[_INIT_BUILDER] = $b;

\PMVC\unplug('view_config_helper');

const DEBUG_KEY = 'dimension';

class dimension extends Action
{
    private $_dot;
    private $_inputs = [];

    function index($m, $f)
    {
        $this->_dot = \PMVC\plug('dotenv');
        $options = $this->_dot->getUnderscoreToArray(
            \PMVC\get(
                $this,
                'options',
                '.env.dimension'
            )
        );
        $this->_dot[dotenv\ESCAPE] = \PMVC\get($options, dotenv\ESCAPE);
        $allConfigs = $this->store()->getConfigs('.dimension.base');

        // <!-- Reset Buckets
        // Put after $allConfigs
        $resetBuckets = \PMVC\value(
            $options,
            explode('_', $f['UTM'])
        );
        if (!empty($resetBuckets)) { 
            $f['BUCKETS'] = explode(',', $resetBuckets);
            $allConfigs['resetBuckets'] = $resetBuckets;
        }
        // Reset Buckets -->

        foreach($options['DIMENSIONS'] as $dimension)
        {
            $dimensionConfigs = $this->processInputForOneDimension(
                $this->getFlattenInput($f, $dimension),
                $dimension
            );
            \PMVC\dev(function() use ($allConfigs, $dimensionConfigs, $dimension) {
                return [
                    'before'=> $allConfigs,
                    'merge' => $dimensionConfigs,
                    'with'  => $dimension
                ];
            }, DEBUG_KEY.'-level');
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
        $callback = \PMVC\getOption('dimensionCallback');
        if (is_callable($callback)) {
            call_user_func_array($callback, [&$allConfigs]);
        }
        $go = $m['dump'];
        $go->set($allConfigs);
        return $go;
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
        $store = $this->store();
        if (count($flattenInputs)>1) {
            return $store->getMultiInputConfigs($flattenInputs, $dimension);
        } else {
            $flattenInputs = reset($flattenInputs);
            return $store->getOneInputConfigs($flattenInputs, $dimension);
        }
    }

}
