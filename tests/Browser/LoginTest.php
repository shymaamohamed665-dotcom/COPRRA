<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    /** @test */
    public function login_page_loads_and_has_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Login')
                ->assertPresent('input[name=email]')
                ->assertPresent('input[name=password]')
                ->assertPresent('button[type=submit]');
        });
    }
}
