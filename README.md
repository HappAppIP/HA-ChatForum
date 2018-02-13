**Basic REST API for the forum**

**Installation notes:**

1.) Create a database using the ```/schema.sql``` file.
2.) Go to ```/Includes/Config/Database.php``` and change the credentials accordingly.
3.) On dev, you will want to run composer and make sure the database user is allowed to create a new database.
4.) Change the apache/nginx config to redirect every request too ```/public/index.php```
5.) Remove ```/public/demo.html```
6.) Remove ```/public/phpunit```
7.) Good luck :)


**Unittests**
In the directory ```/Includes/Test``` are a range of unittests located.
These tests are testing the most important internals of the framework.
These tests should continues be extended everytime one adds a new feature.
There is not a 100% coverage, neither is that required. 

**When you find a bug**
*write a test... fix the test.*

```/public/unittest```  contains a html web structure that shows you the current coverage of the test.
These coverage report is updated every time someone runs the tests.


**URL structure**
The logic behind this framework is rather simple.
The url is broken into parts that will eventually end up in a controller. (```/Inlcuded/Controller/*.php```)
The specifics can be seen in ```/Inlcudes/Lib/Dispatcher.php``` and offcourse ```/Test/DispatcherTest.php```.
Long story short, ```/users/authenticate``` will end up in the controller ```/Includes/Controllers/UserController.php```

There might me multiple ``` authenticateAction``` methods, as there are different types of request methods.
POST,  PUT,  GET, DELETE are beeing used in this framework and they will all have their own action method within the controller.
```
POST = postAuthenticateAction     -> post is used to create stuff (using a RAW POST body!)
PUT = putAuthenticateAction       -> put is used to update stuff (using a RAW POST body!)
GET = getAuthenticateAction       -> get is used to get stuff (using url params ?bla=-haha)
DELETE = deleteAuthenticateAction -> delete is used to delete stuff (using url params ?bla=haha)
```
Too know what parameters are expected within a request one can simply open the controller.
On the top of the document there will be public array properties that list the full acceptable parameter tree.
This is used for your needs.. but also for validation:

A simple ```getUserAction``` might use the following parameter:
```
/**
 * @var array
 */
 public $paramsGet=[
   'user_id' => [
      'type' => 'int',
       'required' => true
    ]
 ];
```

** Example**
Url: /user?user_id=1

As the url starts with /user, we know we should look at ```/Includes/Controller/UserController.php```
As there is no action-name specified it is safe to asume ```Index``` will be used.
Also we are using url parameters, so we can conclude our full action method name will be: ```\Controller\UserController->getIndexAction()```
Within the controller we can validate that the user_id is indeed a required parameter and should be of type int.

This url request will so return you the data/profile for the user with the id 1.

