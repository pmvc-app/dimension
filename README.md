[![Latest Stable Version](https://poser.pugx.org/pmvc-app/dimension/v/stable)](https://packagist.org/packages/pmvc-app/dimension) 
[![Latest Unstable Version](https://poser.pugx.org/pmvc-app/dimension/v/unstable)](https://packagist.org/packages/pmvc-app/dimension) 
[![Build Status](https://travis-ci.org/pmvc-app/dimension.svg?branch=master)](https://travis-ci.org/pmvc-app/dimension)
[![License](https://poser.pugx.org/pmvc-app/dimension/license)](https://packagist.org/packages/pmvc-app/dimension)
[![Total Downloads](https://poser.pugx.org/pmvc-app/dimension/downloads)](https://packagist.org/packages/pmvc-app/dimension) 

# PMVC multi-dimensional configuration library 
===

## Config format use .env
   * https://github.com/pmvc-plugin/dotenv
   * How to defined array? Ans: use "underscore" plugin
      * https://github.com/pmvc-plugin/underscore

## How to translate Constant
   * Prefix with '_', and the key will call constant($k)
   * https://github.com/pmvc-app/dimension/blob/master/index.php#L55-L68

## How to escape
   * Prefix with escap character such as '\'.
   * If detected key start with escap character will bypass underscore process

## How to extend another config file
   * add a config base=xxx at start of file
```
base=xxx
```
   * Important: only could extend same level config

## Last cook callback
```
\PMVC\option('set', 'dimensionCallback', function(){

});
```


## Install with Composer
### 1. Download composer
   * mkdir test_folder
   * curl -sS https://getcomposer.org/installer | php

### 2. Install Use composer.json or use command-line directly
#### 2.1 Install Use composer.json
   * vim composer.json
```
{
    "require": {
        "pmvc-app/dimension": "dev-master"
    }
}
```
   * php composer.phar install

#### 2.2 Or use composer command-line
   * php composer.phar require pmvc-app/dimension

## Other Resource
   * Deployment environment
      * https://en.wikipedia.org/wiki/Deployment_environment
