<?php
namespace Controller;

use Lib\BaseController;
use Model\TopicModel;

class TopicController extends BaseController{

    public $postValues = [
        'category_id' => [
            'type' => 'int',
            'required' => false,
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

    public $deleteValues = [
        'topic_id' =>  [
            'type' => 'int',
            'required' => true
        ]
    ];

    public $getValues = [
        'topic_id' => [
            'type' => 'int',
            'required' => true
        ]
    ];

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
        $data['local_branch_id'] = $this->getUserCredentials('local_branch_id');
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
        return TopicModel::get($data);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function postGetOrCreateAction()
    {
        $data = $this->validate($this->getOrCreateValues, $this->postData);
        $data['local_branch_id'] = $this->getUserCredentials('local_branch_id');
        $data['token_id'] = $this->getUserCredentials('token_id');
        $result = TopicModel::getOrCreate($data);
        return $result;
    }

}