<?php
namespace Model;

use Lib\BaseModel;

class TopicModel extends BaseModel{

    /**
     * @param $data
     * @return int
     * @throws \ErrorException
     */
    public static function create($data){
        return self::_insert($data, 'topics');
    }

    /**
     * @param $topic_id
     * @param $data
     * @return bool
     * @throws \ErrorException
     */
    public static function update($topic_id, $data){
        return self::_update('topics', 'topic_id', $topic_id, $data);
    }

    /**
     * @param $topic_id
     * @return int
     */
    public static function delete($topic_id){
        return self::_delete('topics', 'topic_id', $topic_id);
    }

    /**
     * @param $topic_id
     * @param $localBranchId
     * @param $forumType
     * @return array
     */
    public static function get($topic_id, $localBranchId, $forumType){
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
    
  WHERE topic_id=? AND u.local_branch_id=? AND u.forum_type=?
  GROUP BY topic_id
  ORDER BY t.created_at DESC
EOS;
        $result = self::fetchRow($Q, [$topic_id, $localBranchId, $forumType]);
        return $result;
    }

    public static function deleteByCategory($category_id)
    {
        return self::_delete('topics', 'category_id', $category_id);
    }

    /**
     * @param $data
     * @return array
     * @throws \ErrorException
     */
    public static function getOrCreate($data){
        $row = self::fetchRow('SELECT topic_id FROM topics JOIN userTokens AS u USING(token_id) WHERE title=? AND category_id=? AND u.local_branch_id=? AND forum_type=?', [
            $data['title'], $data['category_id'], $data['local_branch_id'], $data['forum_type']
        ]);
        if($row===false){
            $insertData = $data;
            unset($insertData['forum_type']);
            $row['topic_id'] = self::create($insertData);
        }
        return self::get($row['topic_id'], $data['local_branch_id'], $data['forum_type']);
    }
}