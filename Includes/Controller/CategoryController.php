<?php
namespace Controller;

use Lib\BaseController;
use Model\CategoryModel;

class CategoryController extends BaseController{

    public $postValues = [
      'parent_id' => [
          'type' => 'int',
          'required' => false,
          'default' => 0
      ],
      'title' => [
          'type' => 'varchar',
          'required' => true,
          'max_length' => 255,
          'min_length' => 4
      ],
      'description' => [
          'type' => 'text',
          'required' => false,
          'allow_empty' => true
      ]
    ];

    public $putValues = [
        'category_id' => [
            'type' => 'int',
            'required' => true
        ],
        'parent_id' => [
            'type' => 'int',
            'required' => false,
            'default' => 0
        ],
        'title' => [
            'type' => 'varchar',
            'required' => false,
            'max_length' => 255,
            'min_length' => 4
        ],
        'description' => [
            'type' => 'text',
            'required' => false,
            'allow_empty' => true
        ]
    ];

    public $deleteValues = [
        'category_id' =>  [
            'type' => 'int',
            'required' => true
        ]
     ];

    public $getValues = [
        'category_id' =>  [
            'type' => 'int',
            'required' => true
        ]
    ];

    public $getOrCreateValues = [
        'parent_id' =>  [
            'type' => 'int',
            'required' => false,
            'default' => 0
        ],
        'title' => [
          'type' => 'varchar',
            'max_length' => 255,
            'min_length' => 2,
            'required' => false

        ],
        'description' => [
          'type' => 'varchar',
            'max_length' => 255,
            'min_length' => 2,
            'required' => true,
            'allow_empty' => true

        ]
    ];

    /**
     * @return array
     * @throws \ErrorException
     * @throws \Exception
     */
    public function postIndexAction(){
        $data = $this->validate($this->postValues, $this->postData);
        $data['local_branch_id'] = $this->getUserCredentials('local_branch_id');
        $data['token_id'] = $this->getUserCredentials('token_id');

        $id = CategoryModel::create($data);
        return ['status' => true, 'category_id' => $id];

    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     */
    public function putIndexAction(){
        $data = $this->validate($this->putValues, $this->putData);
        $data['local_branch_id'] = $this->getUserCredentials('local_branch_id');
        $category_id = $data['category_id'];
        unset($data['category_id']);
        CategoryModel::update($category_id, $data);
        $this->addHeader(402, 'HTTP/1.0 No content');
    }

    /**
     * @throws \Exception
     */
    public function deleteIndexAction(){
        $data = $this->validate($this->deleteValues, $this->deleteData);
        CategoryModel::delete($data);
        $this->addHeader(402, 'HTTP/1.0 No content');
    }

    /**
     * Get category(s)
     */
    public function getIndexAction()
    {
        $data = $this->validate($this->getValues, $this->getData);
        $result = CategoryModel::get($data['category_id'], $this->getUserCredentials('local_branch_id'));
        return $result;
    }

    /**
     * get category by name
     * This will create the category if it does not exists!!
     */
    public function postGetOrCreateAction(){
        $data = $this->validate($this->getOrCreateValues, $this->postData);
        $data['local_branch_id'] = $this->getUserCredentials('local_branch_id');
        $data['token_id'] = $this->getUserCredentials('token_id');
        $result = CategoryModel::getOrCreate($data);
        return $result;

    }

}