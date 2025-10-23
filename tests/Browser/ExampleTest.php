<?php

declare(strict_types=1);

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    #[Test]
    public function example_browser_test(): void
    {
        $this->assertTrue(true);
    }
}
