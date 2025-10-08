<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class ErrorHandlerManager
{
    public static function restore(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }
}

class ErrorHandlerManagerOld extends TestCase
{
    /** @var mixed */
    private static $originalErrorHandler;

    /** @var mixed */
    private static $originalExceptionHandler;

    private static int $originalErrorReporting = 0;

    private static bool $initialized = false;

    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$originalErrorReporting = error_reporting();
        self::$originalErrorHandler = set_error_handler(null);
        self::$originalExceptionHandler = set_exception_handler(null);

        if (self::$originalErrorHandler) {
            set_error_handler(self::$originalErrorHandler);
        }
        if (self::$originalExceptionHandler) {
            set_exception_handler(self::$originalExceptionHandler);
        }

        self::$initialized = true;
    }

    public static function restore(): void
    {
        if (! self::$initialized) {
            return;
        }

        error_reporting(self::$originalErrorReporting);

        if (self::$originalErrorHandler) {
            set_error_handler(self::$originalErrorHandler);
        }
        if (self::$originalExceptionHandler) {
            set_exception_handler(self::$originalExceptionHandler);
        }

        self::$initialized = false;
    }

    public static function setErrorHandler(callable $handler): void
    {
        if (! self::$initialized) {
            self::initialize();
        }
        set_error_handler($handler);
    }

    public static function setExceptionHandler(callable $handler): void
    {
        if (! self::$initialized) {
            self::initialize();
        }
        set_exception_handler($handler);
    }

    public static function getOriginalErrorHandler(): mixed
    {
        return self::$originalErrorHandler;
    }

    public static function getOriginalExceptionHandler(): mixed
    {
        return self::$originalExceptionHandler;
    }
}
