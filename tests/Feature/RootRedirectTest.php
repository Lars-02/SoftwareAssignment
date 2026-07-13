<?php

namespace Tests\Feature;

use Tests\TestCase;

class RootRedirectTest extends TestCase
{
    public function test_the_root_url_redirects_to_the_equipments_page(): void
    {
        $this->get('/')->assertRedirect('/equipments');
    }
}

