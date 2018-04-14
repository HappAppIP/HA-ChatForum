<?php
namespace Test;

use Lib\BaseModel;
use Model\CategoryModel;
use Model\CommentModel;
use Model\TopicModel;
use Model\UserModel;
use PHPUnit\Framework\TestCase;


class TopicModelTest extends TestCase{

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
            'token_id' => 1,
            'local_branch_id' => 1
        ];
        $subCategory = [
            'title' => 'Sublevel category',
            'description' => 'Long story short .',
            'parent_id' => 1,
            'token_id' => 1,
            'local_branch_id' => 1
        ];

        UserModel::getUserToken($credentials);
        CategoryModel::create($category);
        CategoryModel::create($subCategory);
    }

    public function tearDown(){
        BaseModel::_truncateAll();
    }

    public function testCud(){
        $topic = [
            'category_id' => 1,
            'title' => 'My first topic',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $id = TopicModel::create($topic);
        $this->assertEquals(1,$id);
        $row = $this->getTopic(1);
        $this->assertEquals($topic['category_id'], $row['category_id']);
        $this->assertEquals($topic['token_id'], $row['token_id']);
        $this->assertEquals($topic['local_branch_id'], $row['local_branch_id']);
        $this->assertEquals($topic['title'], $row['title']);
        $this->assertEquals($topic['description'], $row['description']);
        $this->assertNotNull($row['created_at']);
        $this->assertNull($row['updated_at']);

        $topic = [
            'category_id' => 2,
            'title' => 'My first sub topic',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $id = TopicModel::create($topic);
        $this->assertEquals(2,$id);
        $row = $this->getTopic(2);
        $this->assertEquals($topic['category_id'], $row['category_id']);
        $this->assertEquals($topic['token_id'], $row['token_id']);
        $this->assertEquals($topic['local_branch_id'], $row['local_branch_id']);
        $this->assertEquals($topic['title'], $row['title']);
        $this->assertEquals($topic['description'], $row['description']);
        $this->assertNotNull($row['created_at']);
        $this->assertNull($row['updated_at']);

        $topic = [
            'category_id' => 2,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        TopicModel::update(2, $topic);
        $row = $this->getTopic(2);
        $this->assertEquals($topic['category_id'], $row['category_id']);
        $this->assertEquals($topic['token_id'], $row['token_id']);
        $this->assertEquals($topic['local_branch_id'], $row['local_branch_id']);
        $this->assertEquals($topic['title'], $row['title']);
        $this->assertEquals($topic['description'], $row['description']);
        $this->assertNotNull($row['created_at']);
        $this->assertNotNull($row['updated_at']);

        $row = $this->getTopic(1);
        $this->assertNotEquals($topic['title'], $row['title']);
        $this->assertNotEquals($topic['description'], $row['description']);
        $this->assertNotNull($row['created_at']);
        $this->assertNull($row['updated_at']);

        TopicModel::delete(2);
        $this->assertNotFalse($this->getTopic(1));
        $this->assertFalse($this->getTopic(2));

        $result = BaseModel::_query('SELECT COUNT(*) AS c FROM topics')->fetch();
        $this->assertEquals(1, $result['c']);


        $topic = [
            'category_id' => 2,
            'title' => 'My first sub topic',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $id = TopicModel::create($topic);

        TopicModel::deleteByCategory(2);
        $result = BaseModel::_query('SELECT COUNT(*) AS c FROM topics')->fetch();
        $this->assertEquals(1, $result['c']);

    }

    public function testR(){
        $not_category = [
            'parent_id' => 0,
            'title' => 'This should not show',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];

        $category_1 = [
            'parent_id' => 0,
            'title' => 'Toplevel category 1',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $category_2 = [
            'parent_id' => 0,
            'title' => 'Toplevel category 2',
            'description' => 'Long story short .',
            'token_id' => 2,
            'local_branch_id' => 2
        ];
        $subcategory_1 = [
            'parent_id' => 2,
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
            'local_branch_id' => 2
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

        $result = TopicModel::get(3, 2, 'FORUM');
        $this->assertCount(10, $result);
        $expected_values = ['total_comments' => 2];

        foreach($expected_values as $k => $v){
                $this->assertArrayHasKey($k, $result);
                $this->assertEquals($v, $result[$k]);
        }
    }

    public function testGetOrCreate(){

        $topic = [
            'category_id' => 2,
            'title' => 'My first sub topic edited',
            'description' => 'Long story short edited',
            'token_id' => 2,
            'local_branch_id' => 2,
            'forum_type' => 'FORUM'
        ];

        $topic_1 = TopicModel::getOrCreate($topic);
        $topic_2 = TopicModel::getOrCreate($topic);

        $this->assertEquals($topic_1, $topic_2, 'Topics are different');

    }


    public function getTopic($topicId){
        return BaseModel::_query('SELECT * FROM topics WHERE topic_id=?', [$topicId])->fetch();

    }
}