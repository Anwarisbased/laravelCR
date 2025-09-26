<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    private ContentService $contentService;
    
    public function __construct(ContentService $contentService)
    {
        $this->contentService = $contentService;
    }

    public function getPage(string $slug): JsonResponse
    {
        $pageData = $this->contentService->get_page_by_slug($slug);

        if (!$pageData) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $pageData]);
    }
}
