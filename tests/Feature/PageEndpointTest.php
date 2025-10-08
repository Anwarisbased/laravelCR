<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PageEndpointTest extends TestCase
{
    /**
     * Test fetching a page that exists using integration testing.
     */
    public function test_can_fetch_an_existing_page(): void
    {
        // ARRANGE: Ensure the pages table exists and create a page
        if (Schema::hasTable('pages')) {
            Page::create([
                'title' => 'About Our Company',
                'slug' => 'about-us',
                'content' => '<p>We are a cool company.</p>',
                'status' => 'publish'
            ]);
        }

        // ACT
        $response = $this->getJson('/api/rewards/v2/pages/about-us');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('title', 'About Our Company');
        $response->assertJsonPath('content', '<p>We are a cool company.</p>');
    }

    /**
     * Test fetching a page that does not exist.
     */
    public function test_returns_404_for_non_existent_page(): void
    {
        // ACT
        $response = $this->getJson('/api/rewards/v2/pages/this-page-does-not-exist');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Page not found.');
    }
}