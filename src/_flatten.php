<?php

namespace PMVC\App\dimension;

use DomainException; 

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\Flatten';

class Flatten
{
    public function __invoke()
    {
        return $this;
    }

    public function flattenInput($form, array $keys)
    {
        $inputs = [];
        foreach ($keys as $key) {
            $val = \PMVC\value($form, [$key], '');
            if (is_array($val)) {
                $inputs[] = array_map('strtolower', $val);
            } else {
                $inputs[] = strtolower($val);
            }
        }
        $all_input = $this->flattenArray($inputs);
        return $all_input;
    }

    public function flattenArray(array $array)
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
}
