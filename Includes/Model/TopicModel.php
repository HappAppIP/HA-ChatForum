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
     * @return array
     */
    public static function get($topic_id){
        $Q=<<<EOS
SELECT 
    t.title,
    t.description,
    t.created_at,
    COUNT(comment_id) AS total_comments,
    user_name,
    company_name
  FROM topics AS t
    JOIN userTokens AS u USING(token_id)
    JOIN companies AS co USING(local_company_id)
    LEFT JOIN comments AS c USING(topic_id)
  WHERE topic_id=?
  GROUP BY topic_id
  ORDER BY t.created_at DESC
EOS;
        $result = self::_query($Q, [$topic_id])->fetchall();
        return $result;
    }

    public static function deleteByCategory($category_id)
    {
        return self::_delete('topics', 'category_id', $category_id);
    }
}