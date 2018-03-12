<?php
namespace Model;

use Lib\BaseModel;

class CategoryModel extends BaseModel{

    /**
     * @param $data
     * @return int
     * @throws \ErrorException
     */
    public static function create($data){
        return self::_insert($data, 'categories');
    }

    /**
     * @param $category_id
     * @param $data
     * @return bool
     * @throws \ErrorException
     */
    public static function update($category_id, $data){
        return self::_update('categories', 'category_id', $category_id, $data);
    }

    /**
     * @param $category_id
     * @return int
     */
    public static function delete($category_id){
        $data = self::_query('SELECT category_id FROM categories WHERE parent_id=?', [$category_id]);
        while($row=$data->fetch()){
            self::delete($row['category_id']);
        }
        TopicModel::deleteByCategory($category_id);
        return self::_delete('categories', 'category_id', $category_id);
    }

    /**
     * @param $category_id
     * @param $localBranch_id
     * @return array
     */
    public static function get($category_id, $localBranch_id){
        $Q=<<<EOS
SELECT
    'category' AS type,
    c.category_id,
    NULL AS topic_id, 
    c.title,
    COALESCE(c.description, '') AS description,
    c.created_at,
    COUNT(c2.category_id) AS total_categories,
    COUNT(topic_id) AS total_topics,
    NULL AS total_comments,
    MAX(u.user_name) AS user_name,
    MAX(u.avatar_url) AS avatar_url,
    MAX(co.company_name) AS company_name,
    MAX(t.created_at) AS last_topic,
    NULL as last_comment
  FROM categories AS c
    JOIN userTokens AS u USING(token_id)
    LEFT JOIN topics AS t ON t.category_id=c.category_id AND t.local_branch_id=c.local_branch_id
    LEFT JOIN categories AS c2 ON c.category_id=c2.parent_id
    JOIN companies AS co USING(local_company_id)
  WHERE c.parent_id=? AND c.local_branch_id=?
  GROUP BY category_id
UNION SELECT
    'topic' AS type,
    t.category_id,
    t.topic_id, 
    t.title,
    t.description,
    t.created_at,
    NULL AS total_categories,
    NULL AS total_topics,
    COUNT(c.comment_id) AS total_comments,
    MAX(u.user_name) AS user_name,
    MAX(u.avatar_url) AS avatar_url,
    MAX(co.company_name) AS company_name,
    NULL AS last_topic,
    MAX(c.created_at) AS last_comment
  FROM topics AS t
    JOIN userTokens AS u USING(token_id)
    LEFT JOIN comments as c USING(topic_id)
    JOIN companies AS co USING(local_company_id)
  WHERE t.category_id =? AND t.local_branch_id=?
  GROUP BY t.topic_id
ORDER BY type ASC, created_at DESC;
EOS;
        $result = self::_query($Q, [$category_id, $localBranch_id,$category_id, $localBranch_id])->fetchall();
        return $result;
    }

    /**
     * @param $data
     * @return array
     * @throws \ErrorException
     */
    public static function getOrCreate($data){
        $row = self::fetchRow('SELECT category_id FROM categories WHERE title=? AND local_branch_id=?', [
            $data['title'], $data['local_branch_id']
        ]);
        if($row===false){
            $row['category_id'] = self::create($data);
        }
        $data = self::get($row['category_id'], $data['local_branch_id']);
        $return['category_id'] = $row['category_id'];
        $return['data'] = $data;
        return $return;
    }
}