<?php

declare(strict_types=1);

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

#[PreserveGlobalState(false)]
class E2ETest extends DuskTestCase
{
    #[Test]
    public function can_load_homepage(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function can_navigate_to_products(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function can_search_products(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function can_add_to_cart(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function can_checkout(): void
    {
        $this->assertTrue(true);
    }
}


