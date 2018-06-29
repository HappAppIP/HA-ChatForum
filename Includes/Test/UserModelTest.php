<?php
namespace Test;
use Lib\BaseModel;
use Model\UserModel;
use PHPUnit\Framework\TestCase;

class USerModelTest extends TestCase
{

    /**
     *
     */
    public function setUp(){
        // Delete all records from db.
        BaseModel::_truncateAll();
    }

    /**
     *
     */
    public function tearDown(){
        // Delete all records from db.
        BaseModel::_truncateAll();
    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     */

    public function testGetUserToken(){
        // test simple insert
        $credentials = [
            "user_name" => "g.e. prak",
            "ext_user_id" => '66',
            "company_name" => "prak bv.",
            "ext_company_id" => '66',
            "forum_type" => "FORUM",
            "branch_name" => "Fysio",
            "ext_branch_id" => '66',
            "office_name" => "Office name",
            "ext_office_id" => "80",
            "branch_restricted" => 1,
            "company_restricted" => 0,
            "office_restricted" => 0,
        ];

        $token = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens WHERE forum_type <> 'SYSTEM' ")->fetch();
        $this->assertEquals(1, $result['c'], 'Usertoken table did not return 1 row');

        // Check second insert, and actual data in the database.
        $token_2 = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens WHERE forum_type <> 'SYSTEM'")->fetch();
        $this->assertEquals(1, $result['c'], 'Usertoken table returned multiple rows after second login');
        $this->assertEquals($token, $token_2, 'Tokens do not match');


        $q=<<<EOS
SELECT user_name, ext_user_id, company_name, ext_company_id, forum_type, branch_name, ext_branch_id, office_name, ext_office_id, branch_restricted, company_restricted, office_restricted
    FROM userTokens AS u
      JOIN offices AS o USING(local_office_id)
      JOIN companies AS c ON u.local_company_id=c.local_company_id
      JOIN branches AS b ON u.local_branch_id = b.local_branch_id
      JOIN tokenAcls AS a USING(token_id)
  WHERE u.token = ? AND u.ext_user_id = ?
EOS;

        $result = BaseModel::_query($q, [$token, $credentials['ext_user_id']])->fetch();
        unset($result[0], $result[1], $result[2], $result[3], $result[4], $result[5], $result[6]);
        $this->assertEquals($credentials, $result);

        // Test changing credentials (credentials should be changed without changing record ids)
        $credentials = [
            "user_name" => "g.e broken",
            "ext_user_id" => '66',
            "company_name" => "stuk bv",
            "ext_company_id" => '66',
            "forum_type" => "FORUM",
            "branch_name" => "Fysio-therapie",
            "ext_branch_id" => '66',
            "office_name" => "Office name",
            "ext_office_id" => "80",
            "branch_restricted" => 1,
            "company_restricted" => 0,
            "office_restricted" => 0,
        ];

        $token_3 = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(2, $result['c'], 'Usertoken table returned multiple rows after second login');
        $this->assertEquals($token, $token_3, 'Tokens do not match');

        $q=<<<EOS
SELECT user_name, ext_user_id, company_name, ext_company_id, forum_type, branch_name, ext_branch_id, office_name, ext_office_id, branch_restricted, company_restricted, office_restricted
    FROM userTokens AS u
      JOIN offices AS o USING(local_office_id)
      JOIN companies AS c ON u.local_company_id=c.local_company_id
      JOIN branches AS b ON u.local_branch_id = b.local_branch_id
      JOIN tokenAcls AS a USING(token_id)
  WHERE u.token = ? AND u.ext_user_id = ?
EOS;

        $result = BaseModel::_query($q, [$token, $credentials['ext_user_id']])->fetch();
        foreach($credentials as $k => $v) {
            $this->assertEquals($v, $result[$k], 'key' . $k . ' Does not match!');
        }


        // Test regeneration of token without re-creating a record.
        $rowcount = BaseModel::_update('userTokens', 'token', $token, ['token_ttl' => '1970-01-02']);
        $this->assertEquals(1, $rowcount, 'rowcount does not work on update');

        $token_4 = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(2, $result['c'], 'Usertoken table returned multiple rows after second login');
        $this->assertNotEquals($token, $token_4, 'Tokens should not match as the old one should be expired.');

        $q=<<<EOS
SELECT user_name, ext_user_id, company_name, ext_company_id, forum_type, branch_name, ext_branch_id, office_name, ext_office_id, branch_restricted, company_restricted, office_restricted
    FROM userTokens AS u
      JOIN offices AS o USING(local_office_id)
      JOIN companies AS c ON u.local_company_id=c.local_company_id
      JOIN branches AS b ON u.local_branch_id = b.local_branch_id
      JOIN tokenAcls AS a USING(token_id)
  WHERE u.token = ? AND u.ext_user_id = ?
EOS;

        $result = BaseModel::_query($q, [$token_4, $credentials['ext_user_id']])->fetch();
        foreach($credentials as $k => $v) {
            $this->assertEquals($v, $result[$k], 'key ' . $k . ' Does not match!');
        }



        // Test different user for same branch/company
        $credentials = [
            "user_name" => "test upsert",
            "ext_user_id" => '88',
            "company_name" => "stuk bv",
            "ext_company_id" => '66',
            "forum_type" => "FORUM",
            "branch_name" => "Fysio-therapie",
            "ext_branch_id" => '66',
            "office_name" => "Office name",
            "ext_office_id" => "80",
            "branch_restricted" => 1,
            "company_restricted" => 0,
            "office_restricted" => 0,
        ];

        $token_new = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens WHERE forum_type <> 'SYSTEM'")->fetch();
        $this->assertEquals(2, $result['c'], 'Usertoken table did not return 2 rows');


        $q=<<<EOS
SELECT user_name, ext_user_id, company_name, ext_company_id, forum_type, branch_name, ext_branch_id, office_name, ext_office_id, branch_restricted, company_restricted, office_restricted
    FROM userTokens AS u
      JOIN offices AS o USING(local_office_id)
      JOIN companies AS c ON u.local_company_id=c.local_company_id
      JOIN branches AS b ON u.local_branch_id = b.local_branch_id
      JOIN tokenAcls AS a USING(token_id)
  WHERE u.token = ? AND u.ext_user_id = ?
EOS;

        $result = BaseModel::_query($q, [$token_new, $credentials['ext_user_id']])->fetch();
        foreach($credentials as $k => $v) {
            $this->assertEquals($v, $result[$k], 'key ' . $k . ' does not match!');
        }

    }
    /**
     * @throws \Exception
     */
    public function testAuthenticateToken(){
        // test simple insert
        $credentials = [
            "user_name" => "g.e. prak",
            "ext_user_id" => '66',
            "company_name" => "prak bv.",
            "ext_company_id" => '66',
            "forum_type" => "FORUM",
            "branch_name" => "Fysio",
            "ext_branch_id" => '66',
            "office_name" => "Office name",
            "ext_office_id" => "80",
            "branch_restricted" => 1,
            "company_restricted" => 0,
            "office_restricted" => 0,
        ];

        $token = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(2, $result['c'], 'Usertoken table did not return 2 rows');


        $return = UserModel::authenticateToken($token);
        $this->assertArraySubset([
            'forum_type' => 'FORUM',
            'local_branch_id' => '2',
            'local_company_id' => '2',
            'ext_user_id' => '66',
            'user_name' => 'g.e. prak',
            'token' => $token
        ], $return);

        try{
            UserModel::authenticateToken('some unknown token');
            $this->assertTrue(false, 'method call should throw exception');
        }catch(\Exception $e){
            $this->assertEquals(403, $e->getCode(), 'Exception code is invalid');
        }

    }

    public function testGetUSerCredentials(){
        $credentials = [
            "local_branch_id" => 1,
            "local_company_id" => 1,
            "local_office_id" => 1,
            "branch_restricted" => true,
            "company_restricted" => false,
            "office_restricted" => false,
            'SpecificKey' => 'specialValue'
        ];

        UserModel::setCurrentUserData($credentials);

        $this->assertEquals($credentials, UserModel::getCurrentUserData());

        $this->assertEquals($credentials['SpecificKey'], UserModel::getCurrentUserData('SpecificKey'));

        $this->expectException(\InvalidArgumentException::class);
        UserModel::getCurrentUserData('NonExistentKey');


    }

    public function testIsAllowed(){
        $credentials = [
            "local_branch_id" => 1,
            "local_company_id" => 1,
            "local_office_id" => 1,
            "branch_restricted" => true,
            "company_restricted" => false,
            "office_restricted" => false,
        ];

        UserModel::setCurrentUserData($credentials);

        $this->assertTrue(UserModel::isAllowed(1, 1, 1));
        $this->assertTrue(UserModel::isAllowed(1, 2, 2));
        try{
            UserModel::isAllowed(2,1,1);
            $this->assertFalse(true, ' exeception should be triggered.');
            return;
        }catch(\Exception $e){
            $this->assertEquals(ACL_BRANCH_RESTRICTED, $e->getMessage());
        }

        $credentials = [
            "local_branch_id" => 1,
            "local_company_id" => 1,
            "local_office_id" => 1,
            "branch_restricted" => false,
            "company_restricted" => true,
            "office_restricted" => false,
        ];

        UserModel::setCurrentUserData($credentials);
        $this->assertTrue(UserModel::isAllowed(1, 1, 1));

        $this->assertTrue(UserModel::isAllowed(2, 1, 2));
        try{
            UserModel::isAllowed(1,2,1);
            $this->assertFalse(true, ' exeception should be triggered.');
            return;
        }catch(\Exception $e){
            $this->assertEquals(ACL_COMPANY_RESTRICTED, $e->getMessage());
        }

        $credentials = [
            "local_branch_id" => 1,
            "local_company_id" => 1,
            "local_office_id" => 1,
            "branch_restricted" => false,
            "company_restricted" => false,
            "office_restricted" => true,
        ];

        UserModel::setCurrentUserData($credentials);
        $this->assertTrue(UserModel::isAllowed(1, 1, 1));

        $this->assertTrue(UserModel::isAllowed(2, 2, 1));
        try{
            UserModel::isAllowed(1,1,2);
            $this->assertFalse(true, ' exeception should be triggered.');
        }catch(\Exception $e){
            $this->assertEquals(ACL_OFFICE_RESTRICTED, $e->getMessage());
        }



    }
}