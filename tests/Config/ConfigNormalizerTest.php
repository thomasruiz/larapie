<?php

namespace LarapieTests\Config;

use Larapie\Config\ConfigNormalizer;
use Larapie\LarapieException;
use LarapieTests\TestCase;
use stdClass;

class ConfigNormalizerTest extends TestCase
{
    public function testCheckResourceName()
    {
        $this->expectException(LarapieException::class);
        $this->expectExceptionMessage('Unable to register nested resource: unknown parent `user`. You can add it to ' .
                                      'the configuration file with the option `disable_routing` set to true if you ' .
                                      'don\'t need the routes.');

        $configNormalizer = new ConfigNormalizer();

        $configNormalizer->checkResourceName('user.foo', []);
    }

    public function testModelNotSpecifiedThrowsException()
    {
        $this->expectException(LarapieException::class);
        $this->expectExceptionMessage('Unable to register the resource: model missing.');

        $configNormalizer = new ConfigNormalizer();

        $configNormalizer->normalizeResourceConfig([]);
    }

    public function testStringConfig()
    {
        $configNormalizer = new ConfigNormalizer();

        $config = $configNormalizer->normalizeResourceConfig(stdClass::class);

        $this->assertEquals($config, [
            'model'           => stdClass::class,
            'router_options'  => ['except' => ['create', 'edit']],
            'disable_routing' => false,
        ]);
    }

    public function testCustomNamedRoutesAreDisabled()
    {
        $configNormalizer = new ConfigNormalizer();

        $normalizedConfig = $configNormalizer->normalizeResourceConfig([
            'model'          => stdClass::class,
            'router_options' => ['names' => []],
        ]);

        $this->assertSame(['except' => ['create', 'edit']], $normalizedConfig['router_options']);
    }
}
