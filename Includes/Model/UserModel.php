<?php
namespace Model;

use Lib\BaseModel;

class UserModel extends BaseModel{

    /**
     * @param array $credentials
     * @throws \Exception
     * @return string
     */
    public static function getUserToken(array $credentials){
        $token_ttl = USER_TOKEN_TTL;
        $q=<<<EOS
SELECT token_id, token, local_branch_id, local_company_id FROM userTokens WHERE user_id=? AND forum_type=? AND token_ttl >= current_timestamp-($token_ttl*60)
EOS;
        $r = self::_query($q, [$credentials['user_id'], $credentials['forum_type']]);

        if($r->rowCount() == 1){
            $data = $r->fetch();
            $credentials['local_branch_id'] = self::_upsertBranch($credentials['ext_branch_id'], $credentials['branch_name'], $data['local_branch_id']);
            unset($credentials['branch_name'], $credentials['ext_branch_id']);

            $credentials['local_company_id']=self::_upsertCompany($credentials['ext_company_id'], $credentials['company_name'], $data['local_company_id']);
            unset($credentials['company_name'], $credentials['ext_company_id']);

            self::_update('userTokens', 'token_id', $data['token_id'], $credentials);
            $token = $data['token'];
        }else{
            $token = self::_generateToken($credentials['user_id'], $credentials['forum_type']);
            $credentials['token'] = $token;

            $credentials['local_branch_id'] = self::_upsertBranch($credentials['ext_branch_id'], $credentials['branch_name']);
            unset($credentials['branch_name'], $credentials['ext_branch_id']);

            $credentials['local_company_id'] = self::_upsertCompany($credentials['ext_company_id'], $credentials['company_name']);
            unset($credentials['company_name'], $credentials['ext_company_id']);

            if(self::_update('userTokens', 'user_id', $credentials['user_id'], $credentials) == 0) {
                self::_insert($credentials, 'userTokens');
            }
        }
        $r->closeCursor();
        return $token;

    }

    /**
     * @param $token
     * @return mixed
     * @throws \Exception
     */
    public static function authenticateToken($token){
        $token_ttl = USER_TOKEN_TTL;
        $q= <<<EOS
    SELECT * FROM userTokens WHERE token=? AND UNIX_TIMESTAMP(token_ttl) >= UNIX_TIMESTAMP()-($token_ttl*60)
EOS;
        $r = self::_query($q, [$token]);
        if($r->rowCount() == 1) {
            $return = $r->fetch();
            $r->closeCursor();
            return $return;
        }
        $q= <<<EOS
    SELECT * FROM userTokens WHERE token=?
EOS;
        $r = self::_query($q, [$token]);
        if($r->rowCount() == 1) {
            throw new \Exception("Token TTL reached", 403);
        }
        throw new \Exception("Invalid token", 403);
    }

    public function get($ext_user_id){
        $Q=<<<EOS
SELECT 
    u.forum_type,
    u.user_name,
    u.avatar_url, 
    co.company_name, 
    b.branch_name, 
    u.created_at,
    u.token_ttl AS last_login,
    COUNT(DISTINCT(c.category_id)) AS categories_created,
    COUNT(DISTINCT(t.topic_id)) AS topics_created,
    COUNT(DISTINCT(com.comment_id)) AS comments_created
FROM userTokens AS u 
  JOIN companies AS co USING(local_company_id)
  JOIN branches AS b USING(local_branch_id)
  LEFT JOIN categories AS c ON u.token_id=c.token_id
  LEFT JOIN topics AS t ON u.token_id=t.token_id
  LEFT JOIN comments AS com ON u.token_id=com.token_id
WHERE u.user_id=?
	GROUP BY u.token_id
EOS;
        $result = self::_query($Q, [$ext_user_id]);
        $data = $result->fetchall();
        $result->closeCursor();
        return $data;
    }

    /**
     * @param $userId
     * @param $type
     * @return string
     * @throws \Exception
     */
    protected static function _generateToken($userId, $type){
       return bin2hex(random_bytes(4) . hash('sha256', $userId . $type . RANDOM_SALT) . random_bytes(4));
    }

    /**
     * @param $extBranchId
     * @param $extBranchyName
     * @param null $branchIdLocal
     * @return null|string  (returns 0 on update!!!)
     * @throws \ErrorException
     */
    protected static function _upsertBranch($extBranchId, $extBranchyName, $branchIdLocal=null){
        if(isset($branchIdLocal)){
            self::_update('branches', 'local_branch_id', $branchIdLocal, ['ext_branch_id' => $extBranchId, 'branch_name' => $extBranchyName]);
            return $branchIdLocal;
        }
        $q=<<<EOS
INSERT INTO branches
    (ext_branch_id, branch_name)
VALUES
    (?, ?)
ON DUPLICATE KEY UPDATE
    ext_branch_id = ?,
    branch_name = ?
EOS;
        $result = self::_query($q, [$extBranchId, $extBranchyName, $extBranchId, $extBranchyName]);
        $result->closeCursor();
        $lastInsertId = self::getDb()->lastInsertId();
        if($lastInsertId > 0){
            return $lastInsertId;
        }
        $result = self::_query('SELECT local_branch_id FROM branches WHERE ext_branch_id=?', [$extBranchId]);
        $id = $result->fetch()['local_branch_id'];
        $result->closeCursor();
        return $id;
    }


    /**
     * @param $extCompanyId
     * @param $extCompanyName
     * @param null $companyIdLocal
     * @return null|string -> lastInsertId()
     * @throws \ErrorException
     */
    protected static function _upsertCompany($extCompanyId, $extCompanyName, $companyIdLocal=null){
        if(isset($companyIdLocal)){
            self::_update('companies', 'local_company_id', $companyIdLocal, ['ext_company_id' => $extCompanyId, 'company_name' => $extCompanyName]);
            return $companyIdLocal;
        }
        $q=<<<EOS
INSERT INTO companies
    (ext_company_id, company_name)
VALUES
    (?, ?)
ON DUPLICATE KEY UPDATE
    ext_company_id = ?,
    company_name = ?
EOS;
        $result = self::_query($q, [$extCompanyId, $extCompanyName, $extCompanyId, $extCompanyName]);
        $result->closeCursor();
        $lastInsertId = self::getDb()->lastInsertId();
        if($lastInsertId > 0){
            return $lastInsertId;
        }
        $result = self::_query('SELECT local_company_id FROM companies WHERE ext_company_id=?', [$extCompanyId]);
        $id = $result->fetch()['local_company_id'];
        $result->closeCursor();
        return $id;
    }



}
