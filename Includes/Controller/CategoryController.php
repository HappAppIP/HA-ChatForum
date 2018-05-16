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
            'default' => 2
        ],
        'order_by' => [
            'type' => 'enum',
            'enum' => ['asc', 'desc', 'ASC', 'DESC'],
            'required' => false,
            'default' => 'desc'
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
        $result = CategoryModel::get($data['category_id'], $data['limit_start'], $data['limit_size'], $data['order_by']);
        $result['status'] = true;
        return $result;
    }

    /**
     * get category by name
     * This will create the category if it does not exists!!
     */
    public function postGetOrCreateAction(){
        $data = $this->validate($this->getOrCreateValues, $this->postData);
        $data['token_id'] = $this->getUserCredentials('token_id');
        $result = CategoryModel::getOrCreate($data);
        return ['status' => true, 'data' => $result];

    }

}