<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansClientInterface;
use Tests\Stubs\StubOnlyFansClient;

abstract class TestCase extends BaseTestCase
{
    protected StubOnlyFansClient $onlyFans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->onlyFans = new StubOnlyFansClient;
        $this->app->instance(OnlyFansClientInterface::class, $this->onlyFans);
    }
}
