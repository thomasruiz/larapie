<?php

namespace LarapieTests\Http;

use Illuminate\Routing\Router;
use Larapie\Config\ConfigNormalizer;
use Larapie\Http\Controller;
use Larapie\Http\Routing;
use LarapieTests\TestCase;
use Mockery;
use stdClass;

class RoutingTest extends TestCase
{
    public function testRegisterSimpleResources()
    {
        $config = ['resources' => ['user' => stdClass::class, 'foo' => stdClass::class]];
        $routerMock = $this->mockRouter($config);
        $configNormalizer = new ConfigNormalizer();

        $routing = new Routing($routerMock, $configNormalizer);
        $normalizedConfig = $routing->registerRoutes($config);

        $this->assertEquals(
            [
                'user' => [
                    'model'           => stdClass::class,
                    'router_options'  => ['except' => ['create', 'edit']],
                    'disable_routing' => false,
                ],
                'foo'  => [
                    'model'           => stdClass::class,
                    'router_options'  => ['except' => ['create', 'edit']],
                    'disable_routing' => false,
                ],
            ],
            $normalizedConfig['resources']
        );
    }

    public function testRegisterNestedResource()
    {
        $config = ['resources' => ['user' => stdClass::class, 'user.foo' => stdClass::class]];
        $routerMock = $this->mockRouter($config);
        $configNormalizer = new ConfigNormalizer();

        $routing = new Routing($routerMock, $configNormalizer);
        $normalizedConfig = $routing->registerRoutes($config);

        $this->assertEquals(
            [
                'user'     => [
                    'model'           => stdClass::class,
                    'router_options'  => ['except' => ['create', 'edit']],
                    'disable_routing' => false,
                ],
                'user.foo' => [
                    'model'           => stdClass::class,
                    'router_options'  => ['except' => ['create', 'edit']],
                    'disable_routing' => false,
                ],
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
                    'router_options'  => ['except' => ['create', 'edit']],
                    'disable_routing' => true,
                ],
            ],
        ];

        $configNormalizer = new ConfigNormalizer();
        $routerMock = Mockery::mock(Router::class);
        $this->mockRouterGroup($routerMock, $config);

        $routing = new Routing($routerMock, $configNormalizer);
        $normalizedConfig = $routing->registerRoutes($config);

        $this->assertEquals($config + ['group' => []], $normalizedConfig);
    }

    protected function mockRouter($config)
    {
        $routerMock = Mockery::mock(Router::class);
        $this->mockRouterGroup($routerMock, $config);
        $this->mockRouterResources($config, $routerMock);

        return $routerMock;
    }

    protected function mockRouterGroup($routerMock, $config)
    {
        $routerMock->shouldReceive('group')
                   ->with(isset($config['group']) ? $config['group'] : [], Mockery::on(
                       function ($callback) use ($config) {
                           $callback($config);

                           return true;
                       })
                   )
                   ->once();
    }

    protected function mockRouterResources($config, $routerMock)
    {
        foreach ($config['resources'] as $resource => $value) {
            $routerMock->shouldReceive('resource')
                       ->with($resource, Controller::class, ['except' => ['create', 'edit']])
                       ->once();
        }
    }
}
