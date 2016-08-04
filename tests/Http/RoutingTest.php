<?php

namespace LarapieTests\Http;

use Illuminate\Routing\Router;
use Larapie\Http\Controller;
use Larapie\Http\Routing;
use Larapie\LarapieException;
use LarapieTests\TestCase;
use Mockery;
use stdClass;

class RoutingTest extends TestCase
{
    public function testRegisterSimpleResources()
    {
        $config = ['resources' => ['user' => stdClass::class, 'foo' => stdClass::class]];
        $routerMock = Mockery::mock(Router::class);
        $routerMock->shouldReceive('resource')->with('user', Controller::class, [])->once();
        $routerMock->shouldReceive('resource')->with('foo', Controller::class, [])->once();

        $routing = new Routing($routerMock);
        $normalizedConfig = $routing->registerRoutes($config);

        $this->assertEquals(
            [
                'user' => ['model' => stdClass::class, 'router_options' => [], 'disable_routing' => false],
                'foo'  => ['model' => stdClass::class, 'router_options' => [], 'disable_routing' => false],
            ],
            $normalizedConfig['resources']
        );
    }

    public function testRegisterNestedResource()
    {
        $config = ['resources' => ['user' => stdClass::class, 'user.foo' => stdClass::class]];
        $routerMock = Mockery::mock(Router::class);
        $routerMock->shouldReceive('resource')->with('user', Controller::class, [])->once();
        $routerMock->shouldReceive('resource')->with('user.foo', Controller::class, [])->once();

        $routing = new Routing($routerMock);
        $normalizedConfig = $routing->registerRoutes($config);

        $this->assertEquals(
            [
                'user'     => ['model' => stdClass::class, 'router_options' => [], 'disable_routing' => false],
                'user.foo' => ['model' => stdClass::class, 'router_options' => [], 'disable_routing' => false],
            ],
            $normalizedConfig['resources']
        );
    }

    public function testDisableRoutingForResourceDoesntCallRouter()
    {
        $config = [
            'resources' => [
                'user' => [
                    'model'           => stdClass::class,
                    'router_options'  => [],
                    'disable_routing' => true,
                ],
            ],
        ];
        $routerMock = Mockery::mock(Router::class);

        $routing = new Routing($routerMock);
        $normalizedConfig = $routing->registerRoutes($config);

        $this->assertSame($config, $normalizedConfig);
    }

    public function testCustomNamedRoutesAreDisabled()
    {
        $config = ['resources' => ['user' => ['model' => stdClass::class, 'router_options' => ['names' => []]]]];

        $routerMock = Mockery::mock(Router::class);
        $routerMock->shouldReceive('resource')->with('user', Controller::class, [])->once();

        $routing = new Routing($routerMock);
        $normalizedConfig = $routing->registerRoutes($config);

        $this->assertSame([], $normalizedConfig['resources']['user']['router_options']);
    }

    public function testRegisterNestedResourceWithUnknownParent()
    {
        $this->expectException(LarapieException::class);
        $this->expectExceptionMessage('Unable to register nested resource: unknown parent `user`. You can add it to ' .
                                      'the configuration file with the option `disable_routing` set to true if you ' .
                                      'don\'t need the routes.');

        $config = ['resources' => ['user.foo' => stdClass::class]];
        $routerMock = Mockery::mock(Router::class);

        $routing = new Routing($routerMock);
        $routing->registerRoutes($config);
    }
}
