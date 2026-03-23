<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_terms_page(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('legal/terms'));
    }

    public function test_guest_can_view_privacy_page(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('legal/privacy'));
    }
}
