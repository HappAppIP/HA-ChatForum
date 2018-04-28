<?php
namespace Controller;

use Lib\BaseController;
use Model\UserModel;

class UserController extends BaseController{

    /**
     * @var array
     */
    public $paramsAuthenticate = [
        'user_name' => [
            'required' => true,
            'type' => 'varchar',
            'max_length' => 255,
            'min_length' => 2,
        ],
        'ext_user_id' => [
            'required' => true,
            'type' => 'int',
        ],
        'company_name' => [
            'required' => true,
            'type' => 'varchar',
            'max_length' => 255,
            'min_length' => 2,
        ],
        'ext_company_id' => [
            'required' => true,
            'type' => 'int',
        ],
        'branch_name' => [
            'required' => true,
            'type' => 'varchar',
            'max_length' => 255,
            'min_length' => 2,
        ],
        'ext_branch_id' => [
            'required' => true,
            'type' => 'int',
        ],
        'forum_type' => [
            'required' => true,
            'type' => 'enum',
            'enum' => ['chat', 'forum']
        ],
        'avatar_url' => [
            'required' => true,
            'type' => 'varchar',
            'allow_empty' => true // not implemented in dashboard.
        ]
    ];

    /**
     * @var array
     */
    public $paramsGet=[
      'ext_user_id' => [
          'type' => 'int',
          'required' => true
      ]
    ];

    /**
     * @var array
     */
    protected $_skipTokenValidationFor = ['postAuthenticateAction'];

    /**
     * @return mixed
     * @throws \Exception
     */
    public function postAuthenticateAction(){
        $parameters = $this->validate($this->paramsAuthenticate, $this->postData);
        $response['token'] = UserModel::getUserToken($parameters);
        $response['status'] = true;
        return $response;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAction(){
        $parameters = $this->validate($this->paramsGet, $this->getData);
        $response = UserModel::get($parameters['ext_user_id']);
        return ['status' => true, 'data' => $response];
    }
}