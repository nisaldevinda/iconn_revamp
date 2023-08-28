<?php

namespace Tests;

use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Tests\Traits\InteractsWithExceptionHandling;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithExceptionHandling;
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }
}
