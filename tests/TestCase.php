<?php

namespace LarapiTests;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function tearDown()
    {
        Mockery::close();
    }
}
