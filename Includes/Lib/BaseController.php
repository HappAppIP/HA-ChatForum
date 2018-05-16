<?php

namespace Lib;
use Model\UserModel;

/**
 * Class BaseController
 * @package Lib
 */
class BaseController
{

    /**
     * @var array
     */
    protected $_skipTokenValidationFor = [];

    /**
     * @var array
     */
    private $_userCredentials = [];


    protected $postData = [];
    protected $putData = [];
    protected $deleteData = [];
    protected $getData = [];

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        $headers = $this->_getAllHeaders();

        // json data... get raw post from input.. store it to $_POST, $_PUT or $_DELETE;
        $entityBody = file_get_contents('php://input');
        if (isset($headers['Content-Type']) && strstr($headers['Content-Type'], 'json')) {
            $entityBody = json_decode($entityBody, true);
        }
        $this->getData = $_GET;
        if (!empty($entityBody)) {
            switch (strtoupper($_SERVER['REQUEST_METHOD'])){
                case 'POST':
                    $this->postData = $entityBody;
                    break;
                case 'PUT':
                    $this->putData = $entityBody;
                    break;
                case 'DELETE':
                    $this->deleteData = $_GET;
                break;
            }
        }
    }

    /**
     * Validate your input data using the given ruleset.
     *
     * @param array $ruleset
     * @param array $parameters ($_POST / $_GET)
     * @return array Validated parameters.
     * @throws \Exception
     */
    public function validate(array $ruleset, array $parameters)
    {
        $validate = new Validate();
        if ($validate->validate($ruleset, $parameters) === true) {
            return $parameters;
        }
        throw new \Exception(json_encode($validate->getErrors()), 422);

    }

    /**
     * Use this for adding headers too the stack
     *
     * @param $code 404
     * @param $label 'HTTP/1.0 404 Not Found'
     * @param bool $replace true
     */
    public function addHeader($code, $label, $replace = true)
    {
        Dispatcher::getInstance()->addHeader($label, $code, $replace);
    }

    /**
     * @param null $key
     * @return array|mixed
     *
     */
    public function getUserCredentials($key = null)
    {
        return UserModel::getCurrentUserData($key);
    }

    /**
     * @param $actionName
     * @return bool
     */
    public function tokenValidationRequiredFor($actionName)
    {
        return !in_array($actionName, $this->_skipTokenValidationFor);
    }

    /**
     * @return array|false
     *
     * @codeCoverageIgnore
     */
    protected function _getAllHeaders(){
        return Dispatcher::getAllHeaders();
    }

}