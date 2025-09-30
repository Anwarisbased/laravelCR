<?php
namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Str;

/**
 * Content Service
 *
 * Handles fetching and formatting of standard content like pages.
 */
class ContentService {

    /**
     * Retrieves a page by its slug and formats it for the API.
     *
     * @param string $slug The slug of the page to retrieve.
     * @return array|null An array with page data or null if not found.
     */
    public function get_page_by_slug( string $slug ): ?array {
        // In a pure Laravel implementation, we'd query from a pages table
        $page = Page::where('slug', $slug)->where('status', 'publish')->first();

        if ( ! $page ) {
            return null; // Return null if no page is found.
        }

        // Simulate content processing that WordPress would do
        $content = $page->content ?? '';
        
        // Remove extra paragraphs that WordPress sometimes adds around content.
        $content = str_replace( ']]>', ']]&gt;', $content );

        // Return a clean, formatted array for the API response.
        return [
            'title'   => $page->title ?? '',
            'content' => $content,
        ];
    }
}