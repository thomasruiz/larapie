<?php

namespace LarapieTests\Http;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
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
        $config = new Repository($this->defaultConfig([
            'model' => 'Foo',
            'authorization' => true,
        ]));

        $this->request->shouldReceive('route->getName')->andReturn('resource.index');

        $resolver = new RequestResolver($this->containerMock, $config);
        $request = $resolver->resolve();

        $this->assertSame($this->request, $request);
        $this->assertEquals(new ModelResource([], 'Foo', 'resource', true), $resolver->getResource());
    }

    public function testResolveRequestWithFormRequest()
    {
        $config = new Repository($this->defaultConfig([
            'model'   => 'Foo',
            'request' => FooRequest::class,
        ]));

        $this->request->shouldReceive('route->getName')->andReturn('resource.store');
        $this->containerMock->shouldReceive('make')->once()->with(FooRequest::class)->andReturn(new FooRequest);

        $resolver = new RequestResolver($this->containerMock, $config);
        $request = $resolver->resolve();

        $this->assertInstanceOf(FooRequest::class, $request);
    }

    public function testResolveRequestWithFormRequestShouldAuthorizeIt()
    {
        $this->expectException(UnauthorizedException::class);

        $config = new Repository($this->defaultConfig([
            'model'   => 'Foo',
            'request' => FooRequest::class,
        ]));

        $this->request->shouldReceive('route->getName')->andReturn('resource.store');
        $this->containerMock->shouldReceive('make')->once()->with(FooRequest::class)->andReturn(new FooRequest(false));

        $resolver = new RequestResolver($this->containerMock, $config);
        $request = $resolver->resolve();

        $this->assertInstanceOf(FooRequest::class, $request);
    }

    public function testResolveRequestWithFormRequestShouldValidateIt()
    {
        $this->expectException(ValidationException::class);

        $config = new Repository($this->defaultConfig([
            'model'   => 'Foo',
            'request' => FooRequest::class,
        ]));

        $this->request->shouldReceive('route->getName')->andReturn('resource.store');
        $this->containerMock->shouldReceive('make')->once()->with(FooRequest::class)->andReturn(new FooRequest(true, false));

        $resolver = new RequestResolver($this->containerMock, $config);
        $request = $resolver->resolve();

        $this->assertInstanceOf(FooRequest::class, $request);
    }

    protected function defaultConfig($config)
    {
        return [
            'larapie' => [
                'resources' => [
                    'resource' => $config,
                ],
            ],
        ];
    }
}

class FooRequest implements ValidatesWhenResolved
{
    use ValidatesWhenResolvedTrait;

    private $authorize;

    private $passes;

    public function __construct($authorize = true, $passes = true)
    {
        $this->authorize = $authorize;
        $this->passes = $passes;
    }

    public function authorize()
    {
        return $this->authorize;
    }

    public function validator()
    {
        return new ValidatorStub($this->passes);
    }
}

class ValidatorStub extends Validator
{
    private $passes;

    public function __construct($passes)
    {
        $this->passes = $passes;
    }

    public function passes()
    {
        return $this->passes;
    }
}
