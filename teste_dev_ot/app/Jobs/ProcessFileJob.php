<?php

namespace App\Jobs;

use App\Models\FileUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

class ProcessFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private FileUpload $fileUpload)
    {
    }

    public function handle(): void
    {
        $upload = FileUpload::find($this->fileUpload->id);
        if (!$upload) {
            return;
        }

        try {
            $upload->markAsProcessing();

           $config = [
                'host' => env('MONGO_DB_HOST'),
                'port' => env('MONGO_DB_PORT'),
                'database' => env('MONGO_DB_DATABASE'),
                'user' => env('MONGO_DB_USERNAME'),
                'password' => env('MONGO_DB_PASSWORD'),
            ];

            $dsn = sprintf(
                'mongodb://%s:%s@%s:%s/%s?authSource=admin',
                urlencode($config['user']),
                urlencode($config['password']),
                $config['host'],
                $config['port'],
                $config['database']
            );


            $client = new Client($dsn);

            $client->listDatabases();

            $collection = $client->selectDatabase($config['database'])->selectCollection('instruments');


            if (!file_exists($upload->file_path)) {
                throw new \Exception("Arquivo não encontrado: " . $upload->file_path);
            }


            $handle = fopen($upload->file_path, 'r');
            if (!$handle) {
                throw new \Exception("Não foi possível abrir o arquivo");
            }

            $header = null;
            $batch = [];
            $batchSize = 50;


            while (($row = fgetcsv($handle, 10000, ';')) !== false) {
                if (strpos($row[0] ?? '', 'Status') === 0) {
                    continue;
                }

                if ($header === null) {
                    $header = $this->cleanHeader($row);
                    continue;
                }

                $document = [
                    'file_upload_id' => (string) $upload->id,
                    'created_at' => new UTCDateTime(time() * 1000),
                    'updated_at' => new UTCDateTime(time() * 1000),
                ];

                $row = array_pad($row, count($header), null);

                try {
                    $data = array_combine($header, $row);


                    foreach ($data as $key => $value) {
                        if ($value !== null && $value !== '') {
                            $document[$key] = $this->fixEncoding($value);
                        }
                    }

                    $batch[] = $document;

                    if (count($batch) >= $batchSize) {
                        $collection->insertMany($batch);
                        \Log::info("Inserido lote de " . count($batch) . " registros");
                        $batch = [];
                    }

                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!empty($batch)) {
                $collection->insertMany($batch);
            }

            fclose($handle);

            $upload->markAsCompleted();

        } catch (\Exception $e) {

            $upload->markAsFailed("Erro: " . $e->getMessage());
        }
    }

    private function cleanHeader(array $header): array
    {
        return array_map(function ($item, $index) {
            if (empty($item)) {
                return 'coluna_' . ($index + 1);
            }

            $item = preg_replace('/[^\w\s]/', '', $item);
            $item = preg_replace('/\s+/', '_', $item);
            $item = trim($item, '_');

            return $item ?: 'coluna_' . ($index + 1);
        }, $header, array_keys($header));
    }

    private function fixEncoding($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $utf8 = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $value);

        return $utf8 !== false ? $utf8 : $value;
    }
}