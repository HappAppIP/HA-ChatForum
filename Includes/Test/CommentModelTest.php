<?php
namespace Test;

use Lib\BaseModel;
use Model\CategoryModel;
use Model\CommentModel;
use Model\TopicModel;
use Model\UserModel;
use PHPUnit\Framework\TestCase;


class CommentModelTest extends TestCase{

    public function setUp(){
        BaseModel::_truncateAll();
        $credentials = [
            "user_name" => "g.e broken",
            "ext_user_id" => '66',
            "company_name" => "stuk bv",
            "ext_company_id" => '66',
            "forum_type" => "forum",
            "branch_name" => "Fysio-therapie",
            "ext_branch_id" => '66'
        ];
        $category = [
            'title' => 'Toplevel category',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $subCategory = [
            'title' => 'Sublevel category',
            'description' => 'Long story short .',
            'parent_id' => 2,
            'token_id' => 2,
            'local_branch_id' => 2
        ];

        UserModel::getUserToken($credentials);
        CategoryModel::create($category);
        CategoryModel::create($subCategory);

        $fakeBranch = [
           'ext_branch_id' => 123,
           'branch_name' => 'fake branch',
        ];
        BaseModel::_insert($fakeBranch, 'branches');

        $topic = [
            'category_id' => 1,
            'title' => 'My first topic',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $subtopic = [
            'category_id' => 2,
            'title' => 'My first sub topic',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        TopicModel::create($topic);
        TopicModel::create($subtopic);


    }

    public function tearDown(){
        BaseModel::_truncateAll();
    }

    public function testCud(){
        $comment = [
            'topic_id' => 2,
            'token_id' => 2,
            'description' => 'my very generous comment'
        ];

        $id = CommentModel::create($comment);
        $this->assertEquals(1,$id);
        $row = $this->getComment($id);
        $this->assertEquals($comment['topic_id'], $row['topic_id']);
        $this->assertEquals($comment['token_id'], $row['token_id']);
        $this->assertEquals($comment['description'], $row['description']);
        $this->assertNotNull($row['created_at']);
        $this->assertNull($row['updated_at']);

        $comment = [
            'topic_id' => 2,
            'token_id' => 2,
            'description' => 'my very generous comment (edited)'
        ];
        CommentModel::update($id, $comment);
        $row = $this->getComment($id);
        $this->assertEquals($comment['topic_id'], $row['topic_id']);
        $this->assertEquals($comment['token_id'], $row['token_id']);
        $this->assertEquals($comment['description'], $row['description']);
        $this->assertNotNull($row['created_at']);
        $this->assertNotNull($row['updated_at']);

        CommentModel::delete($id);
        $this->assertFalse($this->getComment($id));
    }

    public function testR(){
        $not_category = [
            'parent_id' => 1,
            'title' => 'This should not show',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 3
        ];

        $category_1 = [
            'parent_id' => 1,
            'title' => 'Toplevel category 1',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $category_2 = [
            'parent_id' => 1,
            'title' => 'Toplevel category 2',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $subcategory_1 = [
            'parent_id' => 3,
            'title' => 'Sublevel category 1',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];

        CategoryModel::create($not_category);
        CategoryModel::create($category_1);
        CategoryModel::create($category_2);
        CategoryModel::create($subcategory_1);

        $not_topic = [
            'category_id' => 1,
            'title' => 'This should not show',
            'description' => 'Long story short edited',
            'token_id' => 2,
            'local_branch_id' => 3
        ];
        $not_subtopic = [
            'category_id' => 2,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $topic_1 = [
            'category_id' => 1,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $topic_2 = [
            'category_id' => 1,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $subtopic = [
            'category_id' => 3,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
            'local_branch_id' => 2
        ];

        TopicModel::create($not_topic);
        TopicModel::create($not_subtopic);
        TopicModel::create($topic_1);
        TopicModel::create($topic_2);
        TopicModel::create($subtopic);

        $not_comment = [
            'topic_id' => 1,
            'token_id' => 2,
            'description' => 'this should not show'
        ];
        $not_subcomment = [
            'topic_id' => 2,
            'token_id' => 2,
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
        CommentModel::create($subcomment);

        $result = CommentModel::get(['topic_id' => 3], 2);

        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['total_records']);
        $expected_values = [
            ['comment_id' => 3, 'topic_id' => 3, 'description' => 'comment 1'],
            ['comment_id' => 4, 'topic_id' => 3, 'description' => 'comment 1'],
        ];
        foreach($expected_values as $k => $v){
            foreach($v as $key => $value){
                $this->assertArrayHasKey($key, $result['data'][$k], $key . ' is not set');
                $this->assertEquals($value, $result['data'][$k][$key], $key . ' contains wrong value');
            }
        }

        $result = CommentModel::get(['comment_id' => 5], 11);
        $this->assertCount(10, $result);
        $this->assertEquals($result['comment_id'], 5);
        $this->assertEquals($result['topic_id'], 4);
        $this->assertEquals($result['description'], 'comment 2');
    }


    public function getComment($commentId){
        return BaseModel::_query('SELECT * FROM comments WHERE comment_id=?', [$commentId])->fetch();

    }
}