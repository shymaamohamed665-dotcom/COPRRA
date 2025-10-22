<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;

class ErrorHandlerManagerTest extends TestCase
{
    #[Test]
    public function test_can_initialize(): void
    {
        ErrorHandlerManager::initialize();
        $this->assertTrue(true, 'ErrorHandlerManager should initialize without errors');
    }

    #[Test]
    public function test_can_restore(): void
    {
        ErrorHandlerManager::initialize();
        ErrorHandlerManager::restore();
        $this->assertTrue(true, 'ErrorHandlerManager should restore without errors');
    }

    #[Test]
    public function test_can_set_error_handlers(): void
    {
        ErrorHandlerManager::setErrorHandler(function () {
            return true;
        });
        ErrorHandlerManager::setExceptionHandler(function () {
            return true;
        });
        $this->assertTrue(true, 'Error handlers should be set without errors');
    }

    #[Test]
    public function test_can_get_original_handlers(): void
    {
        $errorHandler = ErrorHandlerManager::getOriginalErrorHandler();
        $exceptionHandler = ErrorHandlerManager::getOriginalExceptionHandler();

        $this->assertTrue(true, 'Original handlers should be retrievable');
    }
}
