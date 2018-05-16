<?php
namespace Controller;

use Lib\BaseController;
use Model\TopicModel;

/**
 * Class TopicController
 * @package Controller
 */
class TopicController extends BaseController{

    /**
     * @var array
     */
    public $postValues = [
        'category_id' => [
            'type' => 'int',
            'required' => false,
            'default' => 1
        ],
        'title' => [
            'type' => 'varchar',
            'required' => true,
            'min_length' => 4
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
        'topic_id' => [
            'type' => 'int',
            'required' => true,
        ],
        'title' => [
            'type' => 'varchar',
            'required' => true,
            'min_length' => 4
        ],
        'description' => [
            'type' => 'text',
            'required' => true
        ]
    ];

    /**
     * @var array
     */
    public $deleteValues = [
        'topic_id' =>  [
            'type' => 'int',
            'required' => true
        ]
    ];

    /**
     * @var array
     */
    public $getValues = [
        'topic_id' => [
            'type' => 'int',
            'required' => true
        ]
    ];

    /**
     * @var array
     */
    public $getOrCreateValues = [
        'category_id' => [
            'type' => 'int',
            'required' => true,
            'default' => 0
        ],
        'title' => [
            'type' => 'varchar',
            'required' => true,
            'min_length' => 4
        ],
        'description' => [
            'type' => 'text',
            'required' => true
        ]
    ];

    /**
     * @return array
     * @throws \Exception
     */
    public function postIndexAction(){
        $data = $this->validate($this->postValues, $this->postData);
        $data['token_id'] = $this->getUserCredentials('token_id');

        $id = TopicModel::create($data);
        return ['status' => true, 'topic_id' => $id];

    }

    /**
     * @throws \Exception
     */
    public function putIndexAction(){
        $data = $this->validate($this->putValues, $this->putData);
        $topic_id = $data['topic_id'];
        unset($data['topic_id']);
        TopicModel::update($topic_id, $data);
        $this->addHeader(402, 'HTTP/1.0 No content');
    }

    /**
     * @throws \Exception
     */
    public function deleteIndexAction(){
        $data = $this->validate($this->deleteValues, $this->deleteData);
        TopicModel::delete($data);
        $this->addHeader(402, 'HTTP/1.0 No content');
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getIndexAction(){
        $data = $this->validate($this->getValues, $this->getData);
        return ['status' => true, 'data' => TopicModel::get($data['topic_id'])];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function postGetOrCreateAction()
    {
        $data = $this->validate($this->getOrCreateValues, $this->postData);
        $data['token_id'] = $this->getUserCredentials('token_id');
        $result = TopicModel::getOrCreate($data);
        return ['status' => true, 'data' => $result];
    }

}