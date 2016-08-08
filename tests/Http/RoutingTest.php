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
        $configNormalizerMock = new ConfigNormalizer();

        $routing = new Routing($routerMock, $configNormalizerMock);
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
        $configNormalizerMock = new ConfigNormalizer();

        $routing = new Routing($routerMock, $configNormalizerMock);
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
        $routerMock = Mockery::mock(Router::class);
        $configNormalizerMock = new ConfigNormalizer();

        $routing = new Routing($routerMock, $configNormalizerMock);
        $normalizedConfig = $routing->registerRoutes($config);

        $this->assertSame($config, $normalizedConfig);
    }

    protected function mockRouter($config)
    {
        $routerMock = Mockery::mock(Router::class);

        foreach ($config['resources'] as $resource => $value) {
            $routerMock->shouldReceive('resource')
                       ->with($resource, Controller::class, ['except' => ['create', 'edit']])
                       ->once();
        }

        return $routerMock;
    }
}
