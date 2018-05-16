<?php
namespace Test;

use Lib\BaseModel;
use Model\CategoryModel;
use Model\CommentModel;
use Model\TopicModel;
use Model\UserModel;
use PHPUnit\Framework\TestCase;

class CategoryModelTest extends TestCase{

    /**
     * @throws \Exception
     */
    public function setUp(){
        BaseModel::_truncateAll();
        $credentials = [
            "user_name" => "g.e broken",
            "ext_user_id" => '66',

            "forum_type" => "forum",
            "branch_name" => "Fysio-therapie",
            "ext_branch_id" => '66',
            "company_name" => "stuk bv",
            "ext_company_id" => '66',
            "office_name" => "Office name",
            "ext_office_id" => "80",
            "branch_restricted" => 1,
            "company_restricted" => 0,
            "office_restricted" => 0,
        ];

        $token = UserModel::getUserToken($credentials);
        UserModel::authenticateToken($token);
    }

    /**
     *
     */
    public function tearDown(){
        BaseModel::_truncateAll();
    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     */
    public function testCud(){
        $data_1 = [
            'parent_id' => 1,
            'title' => 'Toplevel category',
            'description' => 'Long story short .',
            'token_id' => 2,
        ];

        $category_id = CategoryModel::create($data_1);
        $this->assertEquals(2, $category_id, 'Category_id is not 2');

        $data = [
            'title' => 'Toplevel category without parent_id',
            'description' => 'Long story short .',
            'token_id' => 2,
        ];

        $category_id = CategoryModel::create($data);
        $this->assertEquals(3, $category_id, 'Category_id is not 3');
        $row = $this->getCategory(3);
        $this->assertNotNull($row['parent_id'], 'Parent_id can never be null');

        $data = [
            'parent_id' => 3,
            'title' => 'sub category',
            'description' => 'Long story short .',
            'token_id' => 2,
        ];

        $category_id = CategoryModel::create($data);
        $this->assertEquals(4, $category_id, 'Category_id is not 4');

        $data = [
            'parent_id' => 4,
            'title' => 'sub  sub category',
            'description' => 'Long story short .',
            'token_id' => 2,
        ];

        $category_id = CategoryModel::create($data);
        $this->assertEquals(5, $category_id, 'Category_id is not 5');



        $data = [
            'parent_id' => 0,
            'title' => 'Toplevel category (edited)',
            'description' => 'Long story short (edited)',
            'token_id' => 2,
        ];
        $catData = $this->getCategory(2);
        self::assertEquals($data_1['title'], $catData['title']);
        self::assertEquals($data_1['description'], $catData['description']);
        self::assertNull($catData['updated_at']);

        CategoryModel::update(2, $data);
        $catData = $this->getCategory(2);
        self::assertEquals($data['title'], $catData['title']);
        self::assertEquals($data['description'], $catData['description']);
        self::assertNotNull($catData['updated_at']);


        CategoryModel::delete(2);
        CategoryModel::delete(3);
        $stmt = BaseModel::_query('SELECT COUNT(category_id) AS c FROM categories', []);
        $count = $stmt->fetch()['c'];
        $stmt->closeCursor();
        $this->assertEquals(1, $count, 'Delete does not remove recursively.');
    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     */
    public function testR(){
        $not_category = [
            'parent_id' => 1,
            'title' => 'This should not show',
            'description' => 'Long story short .',
            'token_id' => 1,
        ];

        $category_1 = [
            'parent_id' => 1,
            'title' => 'Toplevel category 1',
            'description' => 'Long story short .',
            'token_id' => 2,
        ];
        $category_2 = [
            'parent_id' => 1,
            'title' => 'Toplevel category 2',
            'description' => 'Long story short .',
            'token_id' => 2,
        ];
        $subcategory_1 = [
            'parent_id' => 3,
            'title' => 'Sublevel category 1',
            'description' => 'Long story short .',
            'token_id' => 2,
        ];

        CategoryModel::create($not_category);
        CategoryModel::create($category_1);
        CategoryModel::create($category_2);
        CategoryModel::create($subcategory_1);

        $not_topic = [
            'category_id' => 1,
            'title' => 'This should not show',
            'description' => 'Long story short edited',
            'token_id' => 1,
        ];
        $not_subtopic = [
            'category_id' => 2,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
        ];
        $topic_1 = [
            'category_id' => 1,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
        ];
        $topic_2 = [
            'category_id' => 1,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
        ];
        $subtopic = [
            'category_id' => 3,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
        ];

        TopicModel::create($not_topic);
        TopicModel::create($not_subtopic);
        TopicModel::create($topic_1);
        TopicModel::create($topic_2);
        TopicModel::create($subtopic);

        $not_comment = [
            'topic_id' => 1,
            'token_id' => 1,
            'description' => 'this should not show'
        ];
        $not_subcomment = [
            'topic_id' => 2,
            'token_id' => 1,
            'description' => 'this should not show'
        ];
        $comment_1 = [
            'topic_id' => 3,
            'token_id' => 2,
            'description' => 'comment 1'
        ];
        $comment_2 = [
            'topic_id' => 4,
            'token_id' => 2,
            'description' => 'comment 2'
        ];
        $subcomment = [
            'topic_id' => 5,
            'token_id' => 2,
            'description' => 'subcomment'
        ];

        CommentModel::create($not_comment);
        CommentModel::create($not_subcomment);
        CommentModel::create($comment_1);
        CommentModel::create($comment_1);
        CommentModel::create($comment_2);
        CommentModel::create($subcomment);
        $result = CategoryModel::get(1);
        $expected_values = [
            ['type' => 'category', 'category_id' => 3, 'topic_id' => null, 'total_categories' => 1, 'total_topics' => 1, 'total_comments' => null],
            ['type' => 'category', 'category_id' => 4, 'topic_id' => null, 'total_categories' => 0, 'total_topics' => 0, 'total_comments' => null],
            ['type' => 'topic', 'category_id' => 1, 'topic_id' => 3, 'total_categories' => 0, 'total_topics' => 0, 'total_comments' => 2],
            ['type' => 'topic', 'category_id' => 1, 'topic_id' => 4, 'total_categories' => 0, 'total_topics' => 0, 'total_comments' => 1]
        ];

        $this->assertCount(4, $result['data'], 'Count did not give expected result');
        $this->assertEquals(4, $result['total_records'], 'Count on "total_records" did not give expected result');
        foreach($expected_values as $k => $v){
            foreach($v as $key => $value){
                $this->assertArrayHasKey($key, $result['data'][$k]);
                $this->assertEquals($value, $result['data'][$k][$key]);
            }
        }

    }

    public function testGetOrCreate(){

        $data = [
            'parent_id' => 1,
            'title' => 'Toplevel category 1',
            'description' => 'Long story short .',
            'token_id' => 2,
        ];

        $data_1 = CategoryModel::getOrCreate($data);
        $data_2 = CategoryModel::getOrCreate($data);

        $this->assertEquals($data_1, $data_2, 'Categories are different');

    }

    /**
     * @param $id
     * @return mixed
     */
    public function getCategory($id){
        $stmt = BaseModel::_query('SELECT * FROM categories WHERE category_id=?', [$id]);
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result;
    }

}