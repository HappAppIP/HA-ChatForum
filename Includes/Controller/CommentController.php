<?php
namespace Controller;

use Lib\BaseController;
use Model\CommentModel;

class CommentController extends BaseController{

    /**
     * @var array
     */
    public $postValues = [
        'topic_id' => [
            'type' => 'int',
            'required' => true
        ],
        'description' => [
            'type' => 'text',
            'required' => true
        ]
    ];

    /**
     * @var array
     */
    public $putValues = [
        'comment_id' => [
            'type' => 'int',
            'required' => true
        ],
        'topic_id' => [
            'type' => 'int',
            'required' => true
        ],
        'description' => [
            'type' => 'text',
            'required' => true
        ]
    ];

    /**
     * @var array
     */
    public $getValues = [
        'comment_id' => [
            'type' => 'int',
            'required' => false
        ],
        'topic_id' => [
            'type' => 'int',
            'required' => false
        ],
        'last_timestamp' => [
            'type' => 'int',
            'required' => false
        ],
        'first_timestamp' => [
            'type' => 'int',
            'required' => false
        ],
        'limit_start' => [
            'type' => 'int',
            'required' => false,
            'default' => 0
        ],
        'limit_size' => [
            'type' => 'int',
            'required' => false,
            'max' => 200,
            'default' => 20
        ],
        'order_by' => [
            'type' => 'enum',
            'enum' => ['asc', 'desc', 'ASC', 'DESC'],
            'required' => false,
            'default' => 'desc'
        ]

    ];

    /**
     * @var array
     */
    public $deleteValues = [
        'comment_id' => ['type' => 'int', 'required' => true]
    ];


    /**
     * @return array
     * @throws \ErrorException
     * @throws \Exception
     */
    public function postIndexAction(){
        $parameters = $this->validate($this->postValues, $this->postData);
        $parameters['token_id'] = $this->getUserCredentials('token_id');
        $id = CommentModel::create($parameters);
        return ['status' => true, 'comment_id' => $id];
    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     */
    public function putIndexAction(){
        $parameters = $this->validate($this->putValues, $this->putData);
        $parameters['token_id'] = $this->getUserCredentials('token_id');
        $comment_id = $parameters['comment_id'];
        unset($parameters['comment_id']);
        CommentModel::update($comment_id, $parameters);
        $this->addHeader(402, 'HTTP/1.0 No content');
    }

    /**
     * @throws \Exception
     */
    public function getIndexAction(){
        $parameters = $this->validate($this->getValues, $this->getData);
        $result = CommentModel::get($parameters, $this->getUserCredentials('ext_user_id'));
        $result['status'] = true;
        return $result;
    }

    /**
     * @throws \Exception
     */
    public function deleteIndexAction(){
        $parameters = $this->validate($this->deleteValues, $this->deleteData);
        CommentModel::delete($parameters['comment_id']);
        $this->addHeader(402, 'HTTP/1.0 No content');
    }
}