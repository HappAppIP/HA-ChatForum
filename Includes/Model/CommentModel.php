<?php
namespace Model;

use Lib\BaseModel;

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

    public static function get(array $data){
        if(isset($data['comment_id'])){
            return self::getCommentById($data['comment_id']);
        }else{
            return self::getCommentsByTopicId($data['topic_id']);
        }
    }

    /**
     * @param $commentId
     * @return mixed
     */
    public function getCommentById($commentId){
        $query=<<<EOS
SELECT 
    c.comment_id, 
    c.topic_id, 
    u.user_name, 
    co.company_name, 
    c.description, 
    c.created_at
FROM comments AS c
  JOIN userTokens AS u USING(token_id)
  JOIN companies AS co USING(local_company_id)
WHERE c.comment_id = ?
  ORDER BY c.created_at DESC
EOS;

        $result = self::_query($query, [$commentId]);
        $data = $result->fetch();
        $result->closeCursor();
        return $data;
    }

    /**
     * @param $topicId
     * @return mixed
     */
    public function getCommentsByTopicId($topicId){
        $query=<<<EOS
SELECT 
    c.comment_id, 
    c.topic_id, 
    u.user_name, 
    co.company_name, 
    c.description, 
    c.created_at
FROM comments AS c
  JOIN userTokens AS u USING(token_id)
  JOIN companies AS co USING(local_company_id)
WHERE c.topic_id = ?
  ORDER BY c.created_at DESC
EOS;

        $result = self::_query($query, [$topicId]);
        $data = $result->fetchAll();
        $result->closeCursor();
        return $data;
    }
}