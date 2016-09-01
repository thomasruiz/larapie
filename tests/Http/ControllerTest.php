<?php

namespace LarapieTests\Http;

use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Larapie\Http\Controller;
use Larapie\Http\ModelResource;
use Larapie\Http\RequestResolver;
use Larapie\Http\ResponseFactory;
use LarapieTests\TestCase;
use Mockery;

class ControllerTest extends TestCase
{
    private $config;

    private $responseFactory;

    private $requestResolver;

    private $request;

    public function setUp()
    {
        parent::setUp();
        $this->request = Mockery::mock(Request::class);
        $this->config = Mockery::mock(Repository::class);
        $this->responseFactory = Mockery::mock(ResponseFactory::class);
        $this->requestResolver = Mockery::mock(RequestResolver::class);
        $this->requestResolver->shouldReceive('resolve')->once()->withNoArgs()->andReturn($this->request);
        $this->request->shouldReceive('route')->with('model_stub')->andReturn(1);
    }

    public function testIndexWithSimpleResource()
    {
        $this->mockConfig(['resources' => ['model_stub' => ['model' => ModelStub::class]]]);
        $this->mockRequestResource(new ModelResource([], ModelStub::class, 'model_stub'));
        $this->mockResponse($expected = 'all');

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->index();

        $this->assertSame($expected, $response);
    }

    public function testIndexWithNestedResource()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub'            => ['model' => ModelStub::class],
                'model_stub.model_stub' => ['model' => ModelStub::class],
            ],
        ]);

        $this->mockRequestResource(new ModelResource(['model_stub'], ModelStub::class, 'model_stub'));
        $this->mockResponse($expected = 'children');

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->index();

        $this->assertSame($expected, $response);
    }

    public function testShowWithSimpleResource()
    {
        $this->mockConfig(['resources' => ['model_stub' => ['model' => ModelStub::class]]]);
        $this->mockRequestResource(new ModelResource([], ModelStub::class, 'model_stub'));
        $this->mockResponse($expected = Mockery::type(ModelStub::class));

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->show();

        $this->assertSame($expected, $response);
    }

    public function testShowWithNestedResource()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub'            => ['model' => ModelStub::class],
                'model_stub.model_stub' => ['model' => ModelStub::class],
            ],
        ]);
        $this->mockRequestResource(new ModelResource(['model_stub'], ModelStub::class, 'model_stub'));
        $this->mockResponse($expected = Mockery::type(ModelStub::class));

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->show();

        $this->assertSame($expected, $response);
    }

    public function testShowNotFound()
    {
        $this->mockConfig(['resources' => ['model_stub' => ['model' => NotFoundModelStub::class]]]);
        $this->mockRequestResource(new ModelResource([], NotFoundModelStub::class, 'model_stub'));
        $this->mockResponse($expected = ['error' => 'Not Found'], 404);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->show();

        $this->assertSame($expected, $response);
    }

    public function testStoreWithSimpleResource()
    {
        $this->mockConfig(['resources' => ['model_stub' => ['model' => ModelStub::class]]]);
        $this->mockRequestResource(new ModelResource([], ModelStub::class, 'model_stub'));
        $this->mockResponse($expected = 'new model', 201);
        $this->mockRequestAll([]);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->store();

        $this->assertSame($expected, $response);
    }

    public function testStoreWithNestedResource()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub'            => ['model' => ModelStub::class],
                'model_stub.model_stub' => ['model' => ModelStub::class],
            ],
        ]);

        $this->mockResponse($expected = 'new model', 201);
        $this->mockRequestResource(new ModelResource(['model_stub'], ModelStub::class, 'model_stub'));
        $this->mockRequestAll([]);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->store();

        $this->assertSame($expected, $response);
    }

    public function testStoreParentNotFound()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub'      => ['model' => NotFoundModelStub::class],
                'model_stub.stub' => ['model' => ModelStub::class],
            ],
        ]);

        $this->mockRequestResource(new ModelResource(['model_stub'], ModelStub::class, 'stub'));
        $this->mockResponse($expected = ['error' => 'Not Found'], 404);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->store();

        $this->assertSame($expected, $response);
    }

    public function testUpdateWithSimpleResource()
    {
        $this->mockConfig(['resources' => ['model_stub' => ['model' => ModelStub::class]]]);
        $this->mockRequestResource(new ModelResource([], ModelStub::class, 'model_stub'));
        $this->mockResponse($expected = Mockery::type(ModelStub::class));
        $this->mockRequestAll([]);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->update();

        $this->assertSame($expected, $response);
    }

    public function testUpdateWithNestedResource()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub'            => ['model' => ModelStub::class],
                'model_stub.model_stub' => ['model' => ModelStub::class],
            ],
        ]);

        $this->mockResponse($expected = Mockery::type(ModelStub::class));
        $this->mockRequestResource(new ModelResource(['model_stub'], ModelStub::class, 'model_stub'));
        $this->mockRequestAll([]);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->update();

        $this->assertSame($expected, $response);
    }

    public function testUpdateParentNotFound()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub'      => ['model' => NotFoundModelStub::class],
                'model_stub.stub' => ['model' => ModelStub::class],
            ],
        ]);

        $this->mockRequestResource(new ModelResource(['model_stub'], ModelStub::class, 'stub'));
        $this->mockResponse($expected = ['error' => 'Not Found'], 404);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->update();

        $this->assertSame($expected, $response);
    }

    public function testUpdateNotFound()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub' => ['model' => NotFoundModelStub::class],
            ],
        ]);

        $this->mockRequestResource(new ModelResource([], NotFoundModelStub::class, 'model_stub'));
        $this->mockResponse($expected = ['error' => 'Not Found'], 404);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->update();

        $this->assertSame($expected, $response);
    }

    public function testDestroyWithSimpleResource()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub' => ['model' => ModelStub::class],
            ],
        ]);

        $this->mockRequestResource(new ModelResource([], ModelStub::class, 'model_stub'));
        $this->mockResponse($expected = null, 204);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->destroy();

        $this->assertSame($expected, $response);
    }

    public function testDestroyWithMultipleResource()
    {
        $this->mockConfig([
            'resources' => [
                'stub'            => ['model' => ModelStub::class],
                'stub.model_stub' => ['model' => ModelStub::class],
            ],
        ]);

        $this->mockRequestResource(new ModelResource([], ModelStub::class, 'model_stub'));
        $this->mockResponse($expected = null, 204);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->destroy();

        $this->assertSame($expected, $response);
    }

    public function testDestroyNotFound()
    {
        $this->mockConfig([
            'resources' => [
                'model_stub' => ['model' => NotFoundModelStub::class],
            ],
        ]);

        $this->mockRequestResource(new ModelResource([], NotFoundModelStub::class, 'model_stub'));
        $this->mockResponse($expected = ['error' => 'Not Found'], 404);

        $controller = new Controller($this->config, $this->responseFactory, $this->requestResolver);
        $response = $controller->destroy();

        $this->assertSame($expected, $response);
    }

    protected function mockConfig($config)
    {
        return $this->config->shouldReceive('get')->with('larapie')->andReturn($config);
    }

    protected function mockResponse($expected, $code = 200)
    {
        return $this->responseFactory->shouldReceive('respond')->with($expected, $code)->once()->andReturn($expected);
    }

    protected function mockRequestAll($expected)
    {
        return $this->request->shouldReceive('all')->withNoArgs()->once()->andReturn($expected);
    }

    protected function mockRequestResource($expected)
    {
        return $this->requestResolver->shouldReceive('getResource')->withNoArgs()->once()->andReturn($expected);
    }
}

class ModelStub
{
    public $model_stubs = 'children';

    public function model_stubs()
    {
        return new self;
    }

    public static function all()
    {
        return 'all';
    }

    public static function find()
    {
        return new self;
    }

    public static function create()
    {
        return 'new model';
    }

    public static function update()
    {
    }

    public static function delete()
    {
    }
}

class NotFoundModelStub
{
    public static function find()
    {
        return null;
    }
}
