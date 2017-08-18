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
        $allConfigs = $this->store()->getOneInputConfigs('base');

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
                $dimension,
                $this->flatten()->FlattenInput(
                    $f,
                    $dimension
                )
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

    function processInputForOneDimension($dimension, array $flattenInputs)
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
            return $store->getMultiInputConfigs($dimension, $flattenInputs);
        } else {
            $flattenInput = reset($flattenInputs);
            return $store->getOneInputConfigs($dimension, $flattenInput);
        }
    }

}
