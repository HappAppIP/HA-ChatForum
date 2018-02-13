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
        BaseModel::_truncate('userTokens');
        BaseModel::_truncate('companies');
        BaseModel::_truncate('branches');
    }

    /**
     *
     */
    public function tearDown(){
        // Delete all records from db.
        BaseModel::_truncate('userTokens');
        BaseModel::_truncate('companies');
        BaseModel::_truncate('branches');
    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     */

    public function testGetUserToken(){
        // test simple insert
        $credentials = [
            "user_name" => "g.e. prak",
            "user_id" => '66',
            "company_name" => "prak bv.",
            "ext_company_id" => '66',
            "forum_type" => "forum",
            "branch_name" => "Fysio",
            "ext_branch_id" => '66'
        ];

        $token = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(1, $result['c'], 'Usertoken table did not return 1 row');

        // Check second insert, and actual data in the database.
        $token_2 = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(1, $result['c'], 'Usertoken table returned multiple rows after second login');
        $this->assertEquals($token, $token_2, 'Tokens do not match');


        $q=<<<EOS
SELECT user_name, user_id, company_name, ext_company_id, forum_type, branch_name, ext_branch_id 
    FROM userTokens AS u
      JOIN companies USING(local_company_id)
      JOIN branches USING(local_branch_id)
  WHERE u.token = ? AND u.user_id = ?
EOS;

        $result = BaseModel::_query($q, [$token, $credentials['user_id']])->fetch();
        unset($result[0], $result[1], $result[2], $result[3], $result[4], $result[5], $result[6]);
        $this->assertEquals($credentials, $result);

        // Test changing credentials (credentials should be changed without changing record ids)
        $credentials = [
            "user_name" => "g.e broken",
            "user_id" => '66',
            "company_name" => "stuk bv",
            "ext_company_id" => '66',
            "forum_type" => "forum",
            "branch_name" => "Fysio-therapie",
            "ext_branch_id" => '66'
        ];

        $token_3 = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(1, $result['c'], 'Usertoken table returned multiple rows after second login');
        $this->assertEquals($token, $token_3, 'Tokens do not match');

        $q=<<<EOS
SELECT user_name, user_id, company_name, ext_company_id, forum_type, branch_name, ext_branch_id 
    FROM userTokens AS u
      JOIN companies USING(local_company_id)
      JOIN branches USING(local_branch_id)
  WHERE u.token = ? AND u.user_id = ?
EOS;

        $result = BaseModel::_query($q, [$token, $credentials['user_id']])->fetch();
        foreach($credentials as $k => $v) {
            $this->assertEquals($v, $result[$k], 'key' . $k . ' Does not match!');
        }


        // Test regeneration of token without re-creating a record.
        $rowcount = BaseModel::_update('userTokens', 'token', $token, ['token_ttl' => '1970-01-01 00:00:01']);
        $this->assertEquals(1, $rowcount, 'rowcount does not work on update');

        $token_4 = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(1, $result['c'], 'Usertoken table returned multiple rows after second login');
        $this->assertNotEquals($token, $token_4, 'Tokens should not match');

        $q=<<<EOS
SELECT user_name, user_id, company_name, ext_company_id, forum_type, branch_name, ext_branch_id 
    FROM userTokens AS u
      JOIN companies USING(local_company_id)
      JOIN branches USING(local_branch_id)
  WHERE u.token = ? AND u.user_id = ?
EOS;

        $result = BaseModel::_query($q, [$token_4, $credentials['user_id']])->fetch();
        foreach($credentials as $k => $v) {
            $this->assertEquals($v, $result[$k], 'key ' . $k . ' Does not match!');
        }



        // Test different user for same branch/company
        $credentials = [
            "user_name" => "test upsert",
            "user_id" => '88',
            "company_name" => "stuk bv",
            "ext_company_id" => '66',
            "forum_type" => "forum",
            "branch_name" => "Fysio-therapie",
            "ext_branch_id" => '66'
        ];

        $token_new = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(2, $result['c'], 'Usertoken table did not return 2 rows');


        $q=<<<EOS
SELECT user_name, user_id, company_name, ext_company_id, forum_type, branch_name, ext_branch_id 
    FROM userTokens AS u
      JOIN companies USING(local_company_id)
      JOIN branches USING(local_branch_id)
  WHERE u.token = ? AND u.user_id = ?
EOS;

        $result = BaseModel::_query($q, [$token_new, $credentials['user_id']])->fetch();
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
            "user_id" => '66',
            "company_name" => "prak bv.",
            "ext_company_id" => '66',
            "forum_type" => "forum",
            "branch_name" => "Fysio",
            "ext_branch_id" => '66'
        ];

        $token = UserModel::getUserToken($credentials);
        $result = BaseModel::_query("SELECT COUNT(token_id) AS c FROM userTokens")->fetch();
        $this->assertEquals(1, $result['c'], 'Usertoken table did not return 1 row');


        $return = UserModel::authenticateToken($token);
        $this->assertArraySubset([
            'forum_type' => 'forum',
            'local_branch_id' => '1',
            'local_company_id' => '1',
            'user_id' => '66',
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
}