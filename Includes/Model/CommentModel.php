<?php
namespace Model;

use Lib\BaseModel;
use Lib\Dispatcher;

class CommentModel extends BaseModel{

    /**
     * @param array $data
     * @return int
     * @throws \ErrorException
     */
    public static function create(array $data){
        return self::_insert($data, 'comments');
    }

    /**
     * @param $comment_id
     * @param array $data
     * @return int
     * @throws \ErrorException
     */
    public static function update($comment_id, array $data){
        return self::_update('comments', 'comment_id', $comment_id, $data);
    }

    /**
     * @param $comment_id
     * @return int
     */
    public static function delete($comment_id){
        return self::_delete('comments', 'comment_id', $comment_id);

    }

    public static function get(array $data, $userId){
        $order_by = 'DESC';
        if(isset($data['comment_id'])){
            return self::getCommentById($data['comment_id'], $userId);
        }else{
            $filters = [];
            if(isset($data['order_by'])){
                $order_by = (strtoupper($data['order_by'])=='ASC'?'ASC':'DESC');
                unset($data['order_by']);
            }
            $limit = [$data['limit_start'] ?? 0, $data['limit_size'] ?? 100];
            unset($data['limit_start']);
            unset($data['limit_size']);

            if(count($data) > 1){
                $filters = $data;
                unset($filters['topic_id']);
            }
            return self::getCommentsByTopicId($data['topic_id'], $filters, $order_by, $limit, $userId);
        }
    }

    /**
     * @param $commentId
     * @return mixed
     */
    public function getCommentById($commentId, $userId){
        $query=<<<EOS
SELECT 
    c.comment_id, 
    c.topic_id, 
    u.user_name, 
    u.avatar_url,
    co.company_name, 
    c.description, 
    c.created_at,
    UNIX_TIMESTAMP(c.created_at) created_timestamp,
    COALESCE((SELECT COUNT(ext_user_id) FROM topics WHERE ext_user_id=?)) AS user_topics_created,
    u.ext_user_id=? AS isPostOwner
FROM comments AS c
  JOIN userTokens AS u USING(token_id)
  JOIN companies AS co USING(local_company_id)
WHERE c.comment_id = ?
  ORDER BY c.created_at DESC
EOS;

        $result = self::_query($query, [$userId, $userId, $commentId]);
        $data = $result->fetch();
        $result->closeCursor();
        return $data;
    }

    /**
     * @param $topicId int
     * @param $filters array
     * @return mixed
     */
    public function getCommentsByTopicId($topicId, $filters=[], $order_by='DESC', $limit=[0, 100], $userId){
        $filters_clause = '';
        $params = [$userId, $topicId];
        if(count($filters)>0) {
            if (isset($filters['last_timestamp'])) {
                $stack[] = 'UNIX_TIMESTAMP(c.created_at) > ?';
                $params[] = $filters['last_timestamp'];
            } elseif (isset($filters['first_timestamp'])) {
                $stack[] = 'UNIX_TIMESTAMP(c.created_at) < ?';
                $params[] = $filters['first_timestamp'];
            }


            $filters_clause = 'AND ' . implode(' AND ', $stack);
        }


        $query=<<<EOS
SELECT 
    c.comment_id, 
    c.topic_id, 
    u.user_name,
    u.avatar_url, 
    co.company_name, 
    c.description, 
    c.created_at,
    UNIX_TIMESTAMP(c.created_at) created_timestamp,
    (SELECT COUNT(ext_user_id) FROM topics AS t WHERE t.token_id=u.token_id GROUP BY ext_user_id) AS user_topics_created,
    u.ext_user_id=? AS isPostOwner
FROM comments AS c
  JOIN userTokens AS u USING(token_id)
  JOIN companies AS co USING(local_company_id)
WHERE c.topic_id = ? $filters_clause
  GROUP BY c.comment_id
  ORDER BY c.created_at $order_by
  LIMIT $limit[0], $limit[1]
EOS;

        $queryCount =<<<EOS
SELECT 
    COUNT(c.comment_id) AS c
FROM comments AS c
  JOIN userTokens AS u USING(token_id)
  JOIN companies AS co USING(local_company_id)
WHERE c.topic_id = ? $filters_clause
EOS;
        $paramsCount = $params;
        array_shift($paramsCount);
        Dispatcher::setDebugData('CommentModel->getCommentsByTopicId()', [
            'query' => $query,
            'query_params' => $params,
            'query_count' => $queryCount,
            'count_params' => $paramsCount
        ]);
        $result = self::_query($query, $params);
        $return['data'] = $result->fetchAll();
        $return['limit_start'] = $limit[0];
        $return['limit_size'] = $limit[1];
        $return['total_records'] = self::_query($queryCount, $paramsCount)->fetch()['c'];
        $result->closeCursor();

        return $return;
    }
}