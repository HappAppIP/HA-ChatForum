<?php

namespace Lib;

use Model\UserModel;

class Dispatcher
{
    /**
     * @var Dispatcher
     */
    protected static $_instance;

    /**
     * @var string
     */
    protected $_requestUrl;
    /**
     * @var string
     */
    protected $_requestMethod;
    /**
     * @var string
     */
    protected $_controllerName;
    /**
     * @var object
     */
    protected $_controller;
    /**
     * @var string
     */
    protected $_actionName;

    /**
     * @var bool
     */
    protected $_debug = false;

    /**
     * @var array
     */
    protected $_responseHeaders = [];

    /**
     * @var array (JSON)
     */
    protected $_responseBody;

    /**
     * @var array (JSON)
     */
    protected $_debugData = [];


    /**
     * @return Dispatcher
     */
    public static function getInstance(){
        if(self::$_instance === null){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @param null $key
     * @return array|mixed
     */
    public static function getUserCredentials($key=null){
        return self::getInstance()->getController()->getUserCredentials($key);
    }

    /**
     * @param $label
     * @param array $data
     */
    public static function setDebugData($label, array $data){
        if(DEBUG === true) {
            self::getInstance()->_debugData[$label] = $data;
        }
    }

    /**
     * Dispatcher constructor.
     * Just make it un-usable for client code.
     */
    protected function __construct(){}

    /**
     * @param string $url
     * @param string $requestMethod
     * @param bool $debug
     * @return $this
     */
    public function setRequest($url, $requestMethod, $debug=false)
    {
        $this->_requestUrl = str_replace(['.', '&', '\\'], '', $url);
        $this->_requestMethod = $requestMethod;
        $this->addHeader('Content-Type: application/json');
        $this->_debug = $debug;
        return $this;
    }

    /**
     * @param $url
     * @param $requestMethod
     */
    protected function _parseUrl($url, $requestMethod){
        $url = str_replace(['../', '/./', '//'], '/', explode('#', explode('?', $url)[0])[0]);
        if($url == '/' || empty($url)){
            $controller = 'index';
            $this->_actionName = 'index';
        }else{
            $full = array_values(array_filter(explode('/', $url), 'strlen'));
            if(count($full)== 1){
                $controller = $full[0];
                $this->_actionName = 'index';
            }else{
                $this->_actionName = array_pop($full);
                $full = array_map('ucfirst', $full);
                $controller =  implode('', $full);
            }
        }
        $this->_controllerName = sprintf('\Controller\%sController', ucfirst($controller));
        $this->_actionName = strtolower($requestMethod) . ucfirst($this->_actionName) . 'Action';
    }

    /**
     * @throws \Exception
     * @return $this
     */
    public function loadController(){
        $this->_parseUrl($this->_requestUrl, $this->_requestMethod);
        if(class_exists($this->_controllerName) === false){
            throw new \Exception(CONTROLLER_NOT_FOUND, 404);
        }
        $this->_controller = new $this->_controllerName($_REQUEST);
        return $this;
    }

    /**
     * @return BaseController
     */
    public function getController(){
        return $this->_controller;
    }

    /**
     * @throws \Exception
     * @return $this
     */
    public function checkAuth(){
        if($this->_controller->tokenValidationRequiredFor($this->_actionName)){
            $headers = self::getAllHeaders();
            if(isset($headers[TOKEN_HEADER_FIELD])){
                $this->_controller->setUserCredentials(UserModel::authenticateToken($headers[TOKEN_HEADER_FIELD]));
                return $this;
            }
            throw new \Exception(AUTH_PERMISSION_DENIED, 403);
        }

        return $this;
    }
    /**
     * @throws \Exception
     * @return $this
     */
    public function execute(){
        if(method_exists($this->_controller, $this->_actionName) === false) {

            throw new \Exception(ACTION_NOT_FOUND . '(' . $this->_actionName . ')', 404);
        }
        $response = $this->_controller->{$this->_actionName}();
        if(is_array($response)) {
            $this->_responseBody = $response;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function sendHeaders(){
        foreach($this->_responseHeaders as $value){
            header($value['label'], $value['replace'] ?? true, $value['code']);
        }
        return $this;
    }

    /**
     *  @return $this
     */
    public function sendBody(){
        if(is_array($this->_debugData) && count($this->_debugData) > 0){
            $this->_responseBody['__DEBUG_DATA__'] = $this->_debugData;
        }
        if(is_null($this->_responseBody)){
            return $this;
        }
        echo json_encode($this->_responseBody);
        return $this;
    }

    /**
     * @param string $label
     * @param int $code
     * @param bool $replace
     * @return $this
     */
    public function addHeader($label, $code=null, $replace=true){
        $this->_responseHeaders[] = ['code' => $code, 'label' => $label, 'replace' => $replace];
        return $this;
    }

    /**
     * @return array|false
     */
    public static function getAllHeaders(){
        if (!function_exists('getallheaders')) {
            if(defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING === true){
                // fake for cli mode.
                return  [
                    'REQUEST_METHOD' => 'POST',
                    'Content-Type' => 'application/json'
                ];
            }
            // we are on nginx and/or FPM or CGI
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        }
        return \getallheaders();
    }
}