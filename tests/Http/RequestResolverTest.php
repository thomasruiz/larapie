<?php

namespace LarapieTests\Http;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Larapie\Http\ModelResource;
use Larapie\Http\RequestResolver;
use LarapieTests\TestCase;
use Mockery;

class RequestResolverTest extends TestCase
{
    private $request;

    private $containerMock;

    public function setUp()
    {
        parent::setUp();
        $this->request = Mockery::mock(Request::class);
        $this->containerMock = Mockery::mock(Container::class);
        $this->containerMock->shouldReceive('make')->with('request')->andReturn($this->request);
    }

    public function testResolveRequest()
    {
        $config = new Repository([
            'larapie' => [
                'resources' => [
                    'resource' => [
                        'model' => 'Foo',
                    ],
                ],
            ],
        ]);

        $this->request->shouldReceive('route->getName')->andReturn('resource.index');
        $resolver = new RequestResolver($this->containerMock, $config);
        $request = $resolver->resolve();

        $this->assertSame($this->request, $request);
        $this->assertEquals(new ModelResource([], 'Foo', 'resource'), $resolver->getResource());
    }
}
