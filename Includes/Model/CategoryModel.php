<?php
namespace Model;

use Lib\BaseModel;
use Lib\Dispatcher;

/**
 * Class CategoryModel
 * @package Model
 */
class CategoryModel extends BaseModel{

    /**
     * @var array
     */
    private static $_categoryLookup = [];

    /**
     * @param $category_id
     * @return bool
     * @throws \Exception
     */
    public function isAllowed($category_id){
        if(!isset(self::$_categoryLookup[$category_id])) {
            $Q = <<<EOS
SELECT u.local_branch_id, u.local_company_id, u.local_office_id 
    FROM categories AS c
      JOIN userTokens AS u USING(token_id)
    WHERE category_id = ?
EOS;
            $row = self::fetchRow($Q, [$category_id]);
            self::$_categoryLookup[$category_id] = $row;
        }else {
            $row = self::$_categoryLookup[$category_id];
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
        if(isset($data['parent_id'])) {
            // parent_id will not be set when we are creating a new forum or chat (like the first ever time it is viewed)
            // @todo We need to check permissions for this within the controller.
            self::isAllowed($data['parent_id']);
        }
        unset($data['forum_type']); // in case it is set.
        return self::_insert($data, 'categories');
    }

    /**
     * @param $category_id
     * @param $data
     * @return bool
     * @throws \ErrorException
     *
     */
    public static function update($category_id, $data){
        self::isAllowed($category_id);
        return self::_update('categories', 'category_id', $category_id, $data);
    }

    /**
     * @param $category_id
     * @return int
     *
     * @throws \Exception
     */
    public static function delete($category_id){
        self::isAllowed($category_id);
        $data = self::_query('SELECT category_id FROM categories WHERE parent_id=?', [$category_id]);
        while($row=$data->fetch()){
            self::delete($row['category_id']);
        }
        TopicModel::deleteByCategory($category_id);
        return self::_delete('categories', 'category_id', $category_id);
    }

    /**
     * @param $category_id
     * @return array
     * @throws \Exception
     *
     */
    public static function get($category_id, $limitStart=0, $limitSize=20, $ordeDirection='DESC'){
        self::isAllowed($category_id);
        $ordeDirection = (strtoupper($ordeDirection)=='ASC'?'ASC':'DESC');
        $limit = [(int) $limitStart, (int) $limitSize];

        $aclWhere = BaseModel::getACLWhere();
        $Qcount = <<<EOS
SELECT SUM(c) AS total_records 
  FROM (
    SELECT
       COUNT(DISTINCT(c.category_id)) AS c, 1 AS uniqueUnion
      FROM categories AS c
        JOIN userTokens AS u USING(token_id)
        JOIN companies AS co USING(local_company_id)
      WHERE c.parent_id=? AND $aclWhere
    UNION SELECT
        COUNT(DISTINCT(t.topic_id)) c, 2 AS uniqueUnion
      FROM topics AS t
        JOIN userTokens AS u USING(token_id)
        JOIN companies AS co USING(local_company_id)
      WHERE t.category_id =? AND $aclWhere
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
    LEFT JOIN topics AS t ON t.category_id=c.category_id
    LEFT JOIN categories AS c2 ON c.category_id=c2.parent_id
    JOIN companies AS co USING(local_company_id)
  WHERE c.parent_id=? AND $aclWhere
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
  WHERE t.category_id =? AND  $aclWhere
  GROUP BY t.topic_id
ORDER BY type ASC, created_at $ordeDirection
LIMIT $limit[0], $limit[1];
EOS;
        Dispatcher::setDebugData('CategoryModel->get()', [
            'category_id' => $category_id,
            'query' => $Q,
            'queryCount' => $Qcount,
            'params' => [$category_id, $category_id]
        ]);
        $count = self::_query($Qcount, [$category_id, $category_id])->fetch();
        $result = self::_query($Q, [$category_id, $category_id])->fetchall();

        if($result === null){
            return [];
        }
        return ['data' => $result, 'total_records' => $count['total_records'], 'limit_start' => $limit[0], 'limit_size' => $limit[1]];
    }

    /**
     * @param $data
     * @return array
     * @throws \ErrorException
     *
     */
    public static function getOrCreate($data){
        $aclWhere = BaseModel::getACLWhere();
        $q = 'SELECT category_id FROM categories AS c JOIN userTokens AS u USING(token_id) WHERE c.title=? AND ' . $aclWhere;
        $row = self::fetchRow($q, [
            $data['title']
        ]);
        Dispatcher::setDebugData('CategoryModel->getOrCreate()', ['query' => $q, 'result' => $row]);
        if($row===false){
            $row['category_id'] = self::create($data);
            Dispatcher::setDebugData('CategoryModel->getOrCreate()', ['creating new record' => true]);
        }
        Dispatcher::setDebugData('CategoryModel->getOrCreate()', ['creating new record' => false]);
        $data = self::get($row['category_id']);
        $return['category_id'] = $row['category_id'];
        $return['data'] = $data;
        return $return;
    }
}