<?php
namespace Test;
use Lib\Dispatcher;
use PHPUnit\Framework\TestCase;

class DispatcherTest extends TestCase{


    /**
     * returns the value of a protected or private property.
     *
     * @param $property
     * @param string $object
     * @return mixed
     * @throws ReflectionException
     */
    public function getProtectedPropertyValue($property, $object){
        $property= new \ReflectionProperty(get_class($object), $property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    /**
     * @param $url
     * @param $requestMethod
     * @param $expectedControllerName
     * @param $expectedActionName
     * @throws ReflectionException
     * @throws \Exception
     *
     * @dataProvider parseUrlData
     */
    public function testParseUrl($url, $requestMethod, $expectedControllerName, $expectedActionName){
        $dispatcher = Dispatcher::getInstance();
        $method = new \ReflectionMethod(get_class($dispatcher), '_parseUrl');
        $method->setAccessible(true);
        $method->invoke($dispatcher, $url, $requestMethod);

        $controllerName = $this->getProtectedPropertyValue('_controllerName', $dispatcher);
        $actionName = $this->getProtectedPropertyValue('_actionName', $dispatcher);

        $this->assertEquals($expectedControllerName, $controllerName, 'ControllerName do not match');
        $this->assertEquals($expectedActionName, $actionName, 'ActionName do not match');
    }

    public function parseUrlData(){
        return [
            'Empty ()' => ['url' => '', 'requestMethod' => 'get', 'expectedControllerName' => '\Controller\IndexController', 'expectedActionName' => 'getIndexAction'],
            'Root (/)' => ['url' => '/', 'requestMethod' => 'get', 'expectedControllerName' => '\Controller\IndexController', 'expectedActionName' => 'getIndexAction'],
            'Index (/index)' => ['url' => '/index', 'requestMethod' => 'post', 'expectedControllerName' => '\Controller\IndexController', 'expectedActionName' => 'postIndexAction'],
            'only controller' => ['url' => '/user', 'requestMethod' => 'put', 'expectedControllerName' => '\Controller\UserController', 'expectedActionName' => 'putIndexAction'],
            'Single' => ['url' => '/user/profile', 'requestMethod' => 'put', 'expectedControllerName' => '\Controller\UserController', 'expectedActionName' => 'putProfileAction'],
            'Long' => ['url' => '/no/idea/where/this/goes', 'requestMethod' => 'put', 'expectedControllerName' => '\Controller\NoIdeaWhereThisController', 'expectedActionName' => 'putGoesAction'],
            'Get (?bla=bla)' => ['url' => '/user/profile?blaa=blaa', 'requestMethod' => 'put', 'expectedControllerName' => '\Controller\UserController', 'expectedActionName' => 'putProfileAction'],
            'Get (??bla=bla&a=a)' => ['url' => '/user/profile??blaa=blaa&a=a', 'requestMethod' => 'put', 'expectedControllerName' => '\Controller\UserController', 'expectedActionName' => 'putProfileAction'],
            'other (#)' => ['url' => '/user/profile#', 'requestMethod' => 'put', 'expectedControllerName' => '\Controller\UserController', 'expectedActionName' => 'putProfileAction'],
            'other (#test)' => ['url' => '/user/profile#test', 'requestMethod' => 'put', 'expectedControllerName' => '\Controller\UserController', 'expectedActionName' => 'putProfileAction'],
            'leet (../)' => ['url' => '/user/..///./../profile', 'requestMethod' => 'put', 'expectedControllerName' => '\Controller\UserController', 'expectedActionName' => 'putProfileAction'],
        ];
    }

    /**
     * @param $url
     * @param $expectException
     * @throws \Exception
     *
     * @dataProvider loadControllerData(
     */
    public function testLoadController($url, $expectException){
        $dispatcher = Dispatcher::getInstance();
        $dispatcher->setRequest($url, 'get');
        try{
            $dispatcher->loadController();
            $this->assertFalse($expectException, 'Method should trigger an exception');
        }catch(\Exception $e){
            $this->assertTrue($expectException, 'Method should not trigger an exception');
            $this->assertEquals(404, $e->getCode());
        }
    }

    /**
     * @return array
     */
    public function loadControllerData(){
        return [
                'existing url' => ['url' => '/Category', 'expectException' => false],
                'non-existing url' => ['url' => '/top-secret', 'expectException' => true],
                ];
    }
}
