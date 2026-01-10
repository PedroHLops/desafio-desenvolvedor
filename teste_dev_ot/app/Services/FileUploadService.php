<?php

namespace App\Services;

use App\Models\FileUpload;
use DomainException;


class FileUploadService
{
    public function processUpload($file, ?int $userId)
    {
        $hash = hash_file('sha256', $file->getRealPath());

        if (FileUpload::where('file_hash', $hash)->exists()) {
            throw new DomainException('Arquivo já foi enviado.');
        }

        $uploadPath = storage_path('uploads');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $storage_name = $hash . '.' . $file->getClientOriginalExtension();
        $file_size = $file->getSize();
        $path = $file->move($uploadPath, $storage_name);


        return FileUpload::create([
            'file_name' => $file->getClientOriginalName(),
            'storage_name' => $storage_name,
            'file_path' => $path,
            'file_size' => $file_size,
            'extension' => $file->getClientOriginalExtension(),
            'status' => FileUpload::STATUS_PENDING,
            'error_message' => null,
            'uploaded_by' => 1,
            'report_date' => now(),
            'file_hash' => $hash
        ]);
    }
}