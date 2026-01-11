<?php

namespace App\Http\Controllers;

use App\Services\ContentSearchService;
use Illuminate\Http\Request;

class SearchContentController extends Controller
{
    public function __construct(ContentSearchService $contentSearchService)
    {
        $this->contentSearchService = $contentSearchService;
    }

    public function search(Request $request)
    {
        try {
            $contentSearch = $this->contentSearchService->search($request);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar o conteúdo: ' . $e->getMessage()], 500);
        }

        return response()->json($contentSearch, 200);
    }
}
