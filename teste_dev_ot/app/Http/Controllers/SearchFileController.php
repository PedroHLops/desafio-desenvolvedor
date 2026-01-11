<?php

namespace App\Http\Controllers;

use App\Services\FileSearchService;
use Illuminate\Http\Request;

class SearchFileController extends Controller
{

    public function __construct(FileSearchService $fileSearchService)
    {
        $this->fileSearchService = $fileSearchService;
    }


    public function search(Request $request)
    {

        try {
            $uploadData = $this->fileSearchService->searchFile(
                $request
            );

            return response()->json([
                'message' => 'Busca realizada com sucesso.',
                'data' => $uploadData
            ], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'Erro ao buscar o arquivo: ' . $e->getMessage()], 500);
        }


        return response()->json([
            'message' => 'Busca realizada com sucesso.'
        ], 200);
    }
}
