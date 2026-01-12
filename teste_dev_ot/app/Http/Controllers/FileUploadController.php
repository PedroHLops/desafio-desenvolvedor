<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFileJob;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use DomainException;

class FileUploadController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function upload(Request $request)
    {
        \Log::info("Upload iniciado - Tamanho do arquivo: " . ($request->file('file')->getSize() / 1024 / 1024) . " MB");

        try {
            $upload = $this->fileUploadService->processUpload(
                $request->file('file')
            );

            ProcessFileJob::dispatch($upload);

            return response()->json([
                'message' => 'Arquivo enviado com sucesso. Processamento em andamento.',
                'data' => $upload
            ], 201);

        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao enviar o arquivo: ' . $e->getMessage()], 500);
        }
    }
}
