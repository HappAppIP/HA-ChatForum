<?php
namespace Model;

use Lib\BaseModel;


/**
 * Class TopicModel
 * @package Model
 */
class TopicModel extends BaseModel{

    /**
     * @var array
     */
    private static $_topicLookup = [];

    /**
     * @param $topicId
     * @return bool
     * @throws \Exception
     */
    public static function isAllowed($topicId){
        if(!isset(self::$_topicLookup[$topicId])) {
            $Q = <<<EOS
SELECT u.local_branch_id, u.local_company_id, u.local_office_id 
    FROM topics AS c
      JOIN userTokens AS u USING(token_id)
    WHERE category_id = ?
EOS;
            $row = self::fetchRow($Q, [$topicId]);
            self::$_topicLookup[$topicId] = $row;
        }else {
            $row = self::$_topicLookup[$topicId];
        }
        UserModel::isAllowed($row['local_branch_id'], $row['local_company_id'], $row['local_office_id']);
        return true;
    }

    /**
     * @param $data
     * @return int
     * @throws \ErrorException
     */
    public static function create($data){
        CategoryModel::isAllowed($data['category_id']);
        return self::_insert($data, 'topics');
    }

    /**
     * @param $topic_id
     * @param $data
     * @return bool
     * @throws \ErrorException
     */
    public static function update($topic_id, $data){
        self::isAllowed($topic_id);
        return self::_update('topics', 'topic_id', $topic_id, $data);
    }

    /**
     * @param $topic_id
     * @return int
     * @throws \Exception
     */
    public static function delete($topic_id){
        self::isAllowed($topic_id);
        return self::_delete('topics', 'topic_id', $topic_id);
    }

    /**
     * @param $topic_id
     * @param $forumType
     * @return array
     * @throws \Exception
     */
    public static function get($topic_id){
        self::isAllowed($topic_id);
        $aclWhere = BaseModel::getACLWhere();
        $Q=<<<EOS
SELECT 
    t.topic_id,
    t.title,
    t.description,
    t.created_at,
    COUNT(comment_id) AS total_comments,
    MAX(c.created_at) AS last_comment,
    user_name,
    avatar_url,
    company_name,
    sub.c AS user_topics_created
    
  FROM topics AS t
    JOIN userTokens AS u USING(token_id)
    JOIN companies AS co USING(local_company_id)
    JOIN (SELECT COUNT(topic_id) AS c, token_id FROM topics GROUP BY token_id) AS sub ON sub.token_id = u.token_id
    LEFT JOIN comments AS c USING(topic_id)
    
  WHERE topic_id=? AND $aclWhere
  GROUP BY topic_id
  ORDER BY t.created_at DESC
EOS;
        $result = self::fetchRow($Q, [$topic_id]);
        return $result;
    }

    /**
     * @param $category_id
     * @return int
     * @throws \Exception
     */
    public static function deleteByCategory($category_id)
    {
        CategoryModel::isAllowed($category_id);
        return self::_delete('topics', 'category_id', $category_id);
    }

    /**
     * @param $data
     * @return array
     * @throws \ErrorException
     */
    public static function getOrCreate($data){
        $aclWhere = BaseModel::getACLWhere();
        $row = self::fetchRow('SELECT topic_id FROM topics JOIN userTokens AS u USING(token_id) WHERE title=? AND category_id=? AND ' . $aclWhere, [
            $data['title'], $data['category_id']
        ]);
        if($row===false){
            $insertData = $data;
            unset($insertData['forum_type']);
            $row['topic_id'] = self::create($insertData);
        }
        return self::get($row['topic_id']);
    }
}