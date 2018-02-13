<?php
namespace Lib;

/**
 * Class Bootstrap
 * @package Lib
 *
 * @codeCoverageIgnore
 */
class Bootstrap{

    public function __construct(){
        // php 4.0 would accept static methods with the same name as its class as beeing a contructor.
        // silly php... bypassing this depreciated "feature"
    }

    public static function bootstrap(){
        $obj = new self();
        ini_set('display_errors', DEBUG);
        $obj->setincludePaths();
    }

    public function setIncludePaths(){
        $includePath = realpath(dirname(__FILE__) . '/../');
        $openBaseDir = dirname($includePath);
        ini_set('include_path', $includePath);
        ini_set('open_basedir', $openBaseDir . ':' . ini_get('open_basedir'));
        return $this;
    }

}