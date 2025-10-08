<?php

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

#[PreserveGlobalState(false)]
class ExampleTest extends DuskTestCase
{
    #[Test]
    public function example_browser_test(): void
    {
        $this->assertTrue(true);
    }
}
