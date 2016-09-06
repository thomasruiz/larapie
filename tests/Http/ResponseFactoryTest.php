<?php

namespace LarapieTests\Http;

use Illuminate\Contracts\Routing\ResponseFactory as LaravelResponseFactory;
use Illuminate\Support\Collection;
use Larapie\Contracts\DirectTransformableContract;
use Larapie\Contracts\TransformableContract;
use Larapie\Contracts\TransformerContract;
use Larapie\Http\ResponseFactory;
use LarapieTests\TestCase;
use Mockery;

class ResponseFactoryTest extends TestCase
{
    /** @var Mockery\Mock|\Illuminate\Contracts\Routing\ResponseFactory */
    private $factoryMock;

    public function setUp()
    {
        parent::setUp();

        $this->factoryMock = Mockery::mock(LaravelResponseFactory::class);
    }

    public function testRespondWithAnArray()
    {
        $input = ['foo' => 'bar'];
        $expected = 'foobar';

        $this->mockLaravelResponse($input, 200, $expected);

        $responseFactory = new ResponseFactory($this->factoryMock);
        $response = $responseFactory->respond($input);

        $this->assertSame($response, $expected);
    }

    public function testRespondWithATransformable()
    {
        $input = new TransformableStub();
        $expected = 'transformed';

        $this->mockLaravelResponse(['model' => 'transformed'], 200, $expected);

        $responseFactory = new ResponseFactory($this->factoryMock);
        $response = $responseFactory->respond($input);

        $this->assertSame($response, $expected);
    }

    public function testRespondWithADirectTransformable()
    {
        $input = new DirectTransformableStub();
        $expected = 'transformed';

        $this->mockLaravelResponse(['transformed'], 200, $expected);

        $responseFactory = new ResponseFactory($this->factoryMock);
        $response = $responseFactory->respond($input);

        $this->assertSame($response, $expected);
    }

    public function testRespondWithASimpleCollection()
    {
        $input = new Collection(['collection', 'of', 'stuff']);
        $expected = 'collection of stuff';

        $this->mockLaravelResponse(
            Mockery::on(function (Collection $arg) use ($input) {
                return $arg->toArray() === $input->toArray();
            }),
            200,
            $expected
        );

        $responseFactory = new ResponseFactory($this->factoryMock);
        $response = $responseFactory->respond($input);

        $this->assertSame($response, $expected);
    }

    public function testRespondWithACollectionOfTransformable()
    {
        $input = new Collection([new TransformableStub(), new TransformableStub()]);
        $expected = 'collection of stuff';

        $this->mockLaravelResponse(
            Mockery::on(function (Collection $arg) use ($input) {
                return $arg->toArray() === [['model' => 'transformed'], ['model' => 'transformed']];
            }),
            200,
            $expected
        );

        $responseFactory = new ResponseFactory($this->factoryMock);
        $response = $responseFactory->respond($input);

        $this->assertSame($response, $expected);
    }

    public function testRespondWithADifferentStatus()
    {
        $input = [];
        $status = 201;
        $expected = 'ok';

        $this->mockLaravelResponse($input, $status, $expected);

        $responseFactory = new ResponseFactory($this->factoryMock);
        $response = $responseFactory->respond($input, $status);

        $this->assertSame($expected, $response);
    }

    protected function mockLaravelResponse($input, $status, $expected)
    {
        $this->factoryMock->shouldReceive('json')
                          ->once()
                          ->with($input, $status)
                          ->andReturn($expected);
    }
}

class TransformableStub implements TransformableContract
{
    public function getTransformerClass()
    {
        return new TransformerStub;
    }
}

class DirectTransformableStub implements DirectTransformableContract
{
    public function directTransform()
    {
        return ['transformed'];
    }
}

class TransformerStub implements TransformerContract
{
    public function transform(TransformableContract $model)
    {
        return ['model' => 'transformed'];
    }
}
