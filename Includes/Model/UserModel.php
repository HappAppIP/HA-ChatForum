<?php
namespace Model;

use Lib\BaseModel;
use Lib\Dispatcher;

/**
 * Class UserModel
 * @package Model
 */
class UserModel extends BaseModel{

    /**
     * @var null
     */
    private static $_currentUserData = null;

    /**
     * @param null $key
     * @return mixed
     */
    public static function getCurrentUserData($key=null){
        if ($key === null) {
            return self::$_currentUserData;
        }
        return self::$_currentUserData[$key];
    }

    /**
     * @param $credentials
     */
    public static function setCurrentUserData($credentials){
        self::$_currentUserData=$credentials;
    }

    /**
     * @param $localBranchId
     * @param null $localCompanyId
     * @param null $localOfficeId
     * @return bool
     * @throws \Exception
     *
     */
    public static function isAllowed($localBranchId, $localCompanyId=null, $localOfficeId=null){
        if(self::getCurrentUserData('branch_restricted')===true){
            if(self::getCurrentUserData('local_branch_id') != $localBranchId){
                throw new \Exception(AUTH_INVALID_BRANCH, 403);
            }
        }
        if(self::getCurrentUserData('company_restricted')===true){
            if(self::getCurrentUserData('local_company_id') != $localCompanyId){
                throw new \Exception(AUTH_INVALID_COMPANY, 403);
            }
        }
        if(self::getCurrentUserData('office_restricted')===true){
            if(self::getCurrentUserData('local_office_id') != $localOfficeId){
                throw new \Exception(AUTH_INVALID_OFFICE, 403);
            }
        }
        return true;

    }

    /**
     * @param array $credentials
     * @throws \Exception
     * @return string
     */
    public static function getUserToken(array $credentials){
        $token_ttl = USER_TOKEN_TTL;
        $q=<<<EOS
SELECT token_id, token, local_branch_id, local_company_id, local_office_id, (UNIX_TIMESTAMP(token_ttl) >= UNIX_TIMESTAMP()-($token_ttl*60)) AS hasValidToken FROM userTokens WHERE ext_user_id=? AND forum_type=? 
EOS;
        Dispatcher::setDebugData('UserModel->getUserToken()', ['query' => $q, 'params' => [$credentials['ext_user_id'], $credentials['forum_type']]]);
        $r = self::_query($q, [$credentials['ext_user_id'], $credentials['forum_type']]);

        if($r->rowCount() == 1){
            $data = $r->fetch();
            $credentials['local_branch_id'] = self::_upsertBranch($credentials['ext_branch_id'], $credentials['branch_name'], $data['local_branch_id']);
            unset($credentials['branch_name'], $credentials['ext_branch_id']);

            $credentials['local_company_id']=self::_upsertCompany($credentials['ext_company_id'], $credentials['company_name'], $credentials['local_branch_id'], $data['local_company_id']);
            unset($credentials['company_name'], $credentials['ext_company_id']);

            $credentials['local_office_id']=self::_upsertOffice($credentials['ext_office_id'], $credentials['office_name'], $data['local_office_id']);
            unset($credentials['office_name'], $credentials['ext_office_id']);

            self::_upsertAcls($credentials['office_restricted'], $credentials['company_restricted'], $credentials['branch_restricted'], $data['token_id']);

            unset($credentials['office_restricted'], $credentials['company_restricted'], $credentials['branch_restricted']);
            if($data['hasValidToken']==0){
                $data['token'] = $credentials['token'] = self::_generateToken($credentials['ext_user_id'], $credentials['forum_type']);;
            }
            self::_update('userTokens', 'token_id', $data['token_id'], $credentials);
            $token = $data['token'];
        }else{

            $token = self::_generateToken($credentials['ext_user_id'], $credentials['forum_type']);
            $credentials['token'] = $token;

            $credentials['local_branch_id'] = self::_upsertBranch($credentials['ext_branch_id'], $credentials['branch_name']);
            unset($credentials['branch_name'], $credentials['ext_branch_id']);

            $credentials['local_company_id'] = self::_upsertCompany($credentials['ext_company_id'], $credentials['company_name'], $credentials['local_branch_id']);
            unset($credentials['company_name'], $credentials['ext_company_id']);

            $credentials['local_office_id']=self::_upsertOffice($credentials['ext_office_id'], $credentials['office_name'], $credentials['local_company_id']);
            unset($credentials['office_name'], $credentials['ext_office_id']);

            $acls = $credentials;
            unset($credentials['office_restricted'], $credentials['company_restricted'], $credentials['branch_restricted']);
            $tokenId = self::_insert($credentials, 'userTokens');


            self::_upsertAcls($acls['office_restricted'], $acls['company_restricted'], $acls['branch_restricted'], $tokenId);
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
    SELECT * FROM userTokens JOIN tokenAcls USING(token_id) WHERE token=? AND UNIX_TIMESTAMP(token_ttl) >= UNIX_TIMESTAMP()-($token_ttl*60)
EOS;
        $r = self::_query($q, [$token]);
        if($r->rowCount() == 1) {
            $return = $r->fetch();
            $r->closeCursor();
            self::setCurrentUserData($return);
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


    /**
     * @param $ext_user_id
     * @throws \Exception (ACL)
     * @return array
     */
    public static function get($ext_user_id){
        $Q=<<<EOS
SELECT 
    u.forum_type,
    u.user_name,
    u.avatar_url, 
    co.company_name, 
    u.local_branch_id,
    u.local_company_id,
    u.local_office_id,
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
WHERE u.ext_user_id=?
	GROUP BY u.token_id
EOS;
        $result = self::_query($Q, [$ext_user_id]);
        $data = $result->fetchall();
        $result->closeCursor();
        self::isAllowed($data['local_branch_id'], $data['local_company_id'], $data['local_office_id']);
        return $data;
    }

    /**
     * @param $ext_userId
     * @param $type
     * @return string
     * @throws \Exception
     */
    protected static function _generateToken($ext_userId, $type){
       return bin2hex(random_bytes(4) . hash('sha256', $ext_userId . $type . RANDOM_SALT) . random_bytes(4));
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
     * @param $localBranchId
     * @param null $companyIdLocal
     * @return true
     * @throws \ErrorException
     */
    protected static function _upsertCompany($extCompanyId, $extCompanyName, $localBranchId, $companyIdLocal=null){
        if(isset($companyIdLocal)){
            self::_update('companies', 'local_company_id', $companyIdLocal, ['ext_company_id' => $extCompanyId, 'company_name' => $extCompanyName, 'local_branch_id' => $localBranchId]);
            return $companyIdLocal;
        }
        $q=<<<EOS
INSERT INTO companies
    (ext_company_id, company_name, local_branch_id)
VALUES
    (?, ?, ?)
ON DUPLICATE KEY UPDATE
    ext_company_id = ?,
    company_name = ?,
    local_branch_id =?
EOS;
        $result = self::_query($q, [$extCompanyId, $extCompanyName, $localBranchId, $extCompanyId, $extCompanyName, $localBranchId]);
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

    /**
     * @param $extOfficeId
     * @param $officeName
     * @param $localCompanyId
     * @param null $localOfficeId
     * @return null|string
     * @throws \ErrorException
     */
    protected function _upsertOffice($extOfficeId, $officeName, $localCompanyId, $localOfficeId=null){
        if(isset($localOfficeId)){
            self::_update('offices', 'local_office_id', $localOfficeId, ['ext_office_id' => $extOfficeId, 'office_name' => $officeName, 'local_company_id' => $localCompanyId]);
            return $localOfficeId;
        }
        $q=<<<EOS
INSERT INTO offices
    (ext_office_id, office_name, local_company_id)
VALUES
    (?, ?, ?)
ON DUPLICATE KEY UPDATE
    ext_office_id = ?,
    office_name = ?,
    local_office_id =?
EOS;
        $result = self::_query($q, [$extOfficeId, $officeName, $localCompanyId, $extOfficeId, $officeName, $localCompanyId]);
        $result->closeCursor();
        $lastInsertId = self::getDb()->lastInsertId();
        if($lastInsertId > 0){
            return $lastInsertId;
        }
        $result = self::_query('SELECT local_office_id FROM offices WHERE ext_office_id=?', [$extOfficeId]);
        $id = $result->fetch()['local_office_id'];
        $result->closeCursor();
        return $id;
    }

    /**
     * @param $officeRestricted
     * @param $companyRestricted
     * @param $branchRestricted
     * @param $tokenId
     * @return bool
     */
    protected static function _upsertAcls($officeRestricted, $companyRestricted, $branchRestricted, $tokenId)
    {
        $q = <<<EOS
INSERT INTO tokenAcls
    (token_id, office_restricted, company_restricted, branch_restricted)
VALUES
    (?, ?,?,?)
ON DUPLICATE KEY UPDATE
    office_restricted = ?,
    company_restricted = ?,
    branch_restricted = ?
EOS;
        $result = self::_query($q, [$tokenId, $officeRestricted, $companyRestricted, $branchRestricted, $officeRestricted, $companyRestricted, $branchRestricted]);
        $result->closeCursor();
        return true;
    }
}
