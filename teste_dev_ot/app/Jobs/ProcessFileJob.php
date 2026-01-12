<?php

namespace App\Jobs;

use App\Models\FileUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

class ProcessFileJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800; 
    public $tries = 3;
    public $maxExceptions = 5;
    public $backoff = [60, 300, 600];

    public function __construct(private FileUpload $fileUpload)
    {
    }

    public function handle(): void
    {
        ini_set('memory_limit', '512M');
        set_time_limit(1800);
        $upload = FileUpload::find($this->fileUpload->id);
        if (!$upload) {
            Log::error("FileUpload não encontrado: " . $this->fileUpload->id);
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

            $client = new Client($dsn, [
                'connectTimeoutMS' => 30000,
                'socketTimeoutMS' => 30000,
                'serverSelectionTimeoutMS' => 30000,
            ]);

            $collection = $client->selectDatabase($config['database'])
                ->selectCollection('instruments');

            if (!file_exists($upload->file_path)) {
                throw new \Exception("Arquivo não encontrado: " . $upload->file_path);
            }


            $totalLines = $this->countLines($upload->file_path);

            $handle = fopen($upload->file_path, 'r');
            if (!$handle) {
                throw new \Exception("Não foi possível abrir o arquivo");
            }

            $header = null;
            $batch = [];
            $batchSize = 5000; 
            $imported = 0;
            $errors = 0;
            $startTime = microtime(true);

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (isset($row[0]) && strpos($row[0], 'Status') === 0) {
                    continue;
                }

                if ($header === null) {
                    $header = $this->cleanHeader($row);
                    continue;
                }


                try {
                    $document = [
                        'file_upload_id' => (string) $upload->id,
                        'created_at' => new UTCDateTime(time() * 1000),
                        'updated_at' => new UTCDateTime(time() * 1000),
                    ];

                    $row = array_pad($row, count($header), null);
                    
                    if (count($header) !== count($row)) {
                        Log::warning("Header/row size mismatch. Header: " . count($header) . ", Row: " . count($row));
                        $errors++;
                        continue;
                    }

                    $data = @array_combine($header, $row);
                    if ($data === false) {
                        Log::warning("Falha no array_combine. Continuando...");
                        $errors++;
                        continue;
                    }

                    foreach ($data as $key => $value) {
                        if ($value !== null && $value !== '') {
                            $document[$key] = $this->fixEncoding($value);
                        }
                    }

                    $batch[] = $document;
                    $imported++;


                    if (count($batch) >= $batchSize) {
                        $this->insertBatch($collection, $batch);
                        $batch = [];
                        
                        // LIBERAR MEMÓRIA
                        if ($imported % 50000 === 0) {
                            gc_collect_cycles();
                        }
                    }

                } catch (\Exception $e) {
                    $errors++;
                    if ($errors % 100 === 0) {
                        Log::warning("Erro na linha {$imported}: " . $e->getMessage());
                    }
                    continue;
                }
            }

            if (!empty($batch)) {
                $this->insertBatch($collection, $batch);
            }

            fclose($handle);
            

            $upload->markAsCompleted();

        } catch (\Exception $e) {
            
            if (isset($upload)) {
                $upload->markAsFailed("Erro: " . $e->getMessage());
            }
            
            throw $e; // Para queue retry
        }
    }

    /**
     * Insere batch com tratamento de erro
     */
    private function insertBatch($collection, $batch): void
    {
        try {
            $result = $collection->insertMany($batch);
        } catch (\Exception $e) {
            
            // Tentar inserir um por um para identificar o problema
            foreach ($batch as $doc) {
                try {
                    $collection->insertOne($doc);
                } catch (\Exception $e2) {
                    Log::error("Documento falhou: " . json_encode(array_slice($doc, 0, 3)));
                }
            }
        }
    }

    /**
     * Conta linhas do arquivo de forma eficiente
     */
    private function countLines($filePath): int
    {
        $linecount = 0;
        $handle = fopen($filePath, 'r');
        
        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line !== false && trim($line) !== '') {
                $linecount++;
            }
        }
        
        fclose($handle);
        return $linecount - 1; // Subtrai header
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

        $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252'];
        
        foreach ($encodings as $encoding) {
            $utf8 = @iconv($encoding, 'UTF-8//IGNORE', $value);
            if ($utf8 !== false && mb_check_encoding($utf8, 'UTF-8')) {
                return $utf8;
            }
        }
        
        return $value;
    }
    
    /**
     * Método opcional para falha no job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job ProcessFileJob falhou: " . $exception->getMessage());
        
        $upload = FileUpload::find($this->fileUpload->id);
        if ($upload) {
            $upload->markAsFailed("Job falhou: " . $exception->getMessage());
        }
    }
}