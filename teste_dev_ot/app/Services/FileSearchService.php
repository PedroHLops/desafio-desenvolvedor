<?php

namespace App\Services;

use App\Models\FileUpload;

class FileSearchService
{
    public function searchFile($request)
    {
        $start_date = $request->header('start_date');
        $end_date = $request->header('end_date');
        $file_name = $request->header('file_name');

        try {
            $query = FileUpload::query();

            if ($start_date && $end_date) {
                $query->whereBetween('report_date', [$start_date, $end_date]);
            }

            if ($file_name) {
                $query->where('file_name', 'like', "%$file_name%");
            }

            $fileUpload = $query->get();
        } catch (\Exception $e) {

            throw new \Exception('Erro ao buscar o arquivo: ' . $e->getMessage());
        }

        return $fileUpload;
    }
}