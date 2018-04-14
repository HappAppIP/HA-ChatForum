<?php
namespace Model;

use Lib\BaseModel;
use Lib\Dispatcher;

class CategoryModel extends BaseModel{

    /**
     * @param $data
     * @return int
     * @throws \ErrorException
     */
    public static function create($data){
        unset($data['forum_type']); // in case it is set.
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
     * @param $forumType
     * @return array
     */
    public static function get($category_id, $localBranch_id, $forumType, $limitStart=0, $limitSize=20, $ordeDirection='DESC'){


        $ordeDirection = (strtoupper($ordeDirection)=='ASC'?'ASC':'DESC');
        $limit = [(int) $limitStart, (int) $limitSize];


        $Qcount = <<<EOS
SELECT SUM(c) AS total_records 
  FROM (
    SELECT
       COUNT(DISTINCT(c.category_id)) AS c, 1 AS uniqueUnion
      FROM categories AS c
        JOIN userTokens AS u USING(token_id)
        JOIN companies AS co USING(local_company_id)
      WHERE c.parent_id=? AND c.local_branch_id=? AND u.forum_type=?
    UNION SELECT
        COUNT(DISTINCT(t.topic_id)) c, 2 AS uniqueUnion
      FROM topics AS t
        JOIN userTokens AS u USING(token_id)
        JOIN companies AS co USING(local_company_id)
      WHERE t.category_id =? AND t.local_branch_id=? AND u.forum_type=?
  ) 
AS sub
EOS;

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
  WHERE c.parent_id=? AND c.local_branch_id=? AND u.forum_type=?
  GROUP BY category_id
UNION SELECT
    'topic' AS type,
    t.category_id,
    t.topic_id, 
    t.title,
    CONCAT(SUBSTRING(t.description, 1, 255), '...') AS description,
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
  WHERE t.category_id =? AND t.local_branch_id=? AND u.forum_type=?
  GROUP BY t.topic_id
ORDER BY type ASC, created_at $ordeDirection
LIMIT $limitStart, $limitSize;
EOS;
        Dispatcher::setDebugData('CategoryModel->get()', [
            'category_id' => $category_id,
            'local_branch_id' => $localBranch_id,
            'forum_type' => $forumType,
            'query' => $Q,
            'queryCount' => $Qcount,
            'params' => [$category_id, $localBranch_id, $forumType, $category_id, $localBranch_id, $forumType]
        ]);
        $count = self::_query($Qcount, [$category_id, $localBranch_id, $forumType, $category_id, $localBranch_id, $forumType])->fetch();
        $result = self::_query($Q, [$category_id, $localBranch_id, $forumType, $category_id, $localBranch_id, $forumType])->fetchall();

        if($result === null){
            return [];
        }
        return ['data' => $result, 'total_records' => $count['total_records'], 'limit_start' => $limitStart, 'limit_size' => $limitSize];
    }

    /**
     * @param $data
     * @return array
     * @throws \ErrorException
     */
    public static function getOrCreate($data){
        $q = 'SELECT category_id FROM categories AS c JOIN userTokens AS u USING(token_id) WHERE c.title=? AND c.local_branch_id=? AND u.forum_type=?';
        $row = self::fetchRow($q, [
            $data['title'], $data['local_branch_id'], $data['forum_type']
        ]);
        Dispatcher::setDebugData('CategoryModel->getOrCreate()', ['query' => $q, 'result' => $row]);
        if($row===false){
            $row['category_id'] = self::create($data);
            Dispatcher::setDebugData('CategoryModel->getOrCreate()', ['creating new record' => true]);
        }
        Dispatcher::setDebugData('CategoryModel->getOrCreate()', ['creating new record' => false]);
        $data = self::get($row['category_id'], $data['local_branch_id'], $data['forum_type']);
        $return['category_id'] = $row['category_id'];
        $return['data'] = $data;
        return $return;
    }
}