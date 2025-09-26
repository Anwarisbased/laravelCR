<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ContentService;

class PageEndpointTest extends TestCase
{
    /**
     * Test fetching a page that exists by mocking the ContentService.
     */
    public function test_can_fetch_an_existing_page(): void
    {
        // ARRANGE: Mock the ContentService to control its output
        $this->mock(ContentService::class, function ($mock) {
            $mock->shouldReceive('get_page_by_slug')
                 ->with('about-us')
                 ->andReturn([
                     'title' => 'About Our Company',
                     'content' => '<p>We are a cool company.</p>'
                 ]);
        });

        // ACT
        $response = $this->getJson('/api/rewards/v2/pages/about-us');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'About Our Company');
        $response->assertJsonPath('data.content', '<p>We are a cool company.</p>');
    }

    /**
     * Test fetching a page that does not exist.
     */
    public function test_returns_404_for_non_existent_page(): void
    {
        // ARRANGE: Mock the ContentService to return null for a specific slug
        $this->mock(ContentService::class, function ($mock) {
            $mock->shouldReceive('get_page_by_slug')
                 ->with('this-page-does-not-exist')
                 ->andReturn(null);
        });

        // ACT
        $response = $this->getJson('/api/rewards/v2/pages/this-page-does-not-exist');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Page not found.');
    }
}