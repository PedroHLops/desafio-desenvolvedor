<?php

namespace App\Jobs;

use App\Models\FileUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use MongoDB\Client as MongoClient;
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

            // Configuração com autenticação Sail
            $username = env('MONGODB_USERNAME', 'sail');
            $password = env('MONGODB_PASSWORD', 'password');
            $host = env('MONGODB_HOST', 'localhost');
            $port = env('MONGODB_PORT', '27017');
            $database = env('MONGODB_DATABASE', 'laravel');
            $authDatabase = env('MONGODB_AUTH_DATABASE', 'admin');

            // String de conexão com autenticação
            $connectionString = "mongodb://{$username}:{$password}@{$host}:{$port}/{$authDatabase}";


            $client = new MongoClient($connectionString);

            // Testa a conexão
            $client->listDatabases();

            $collection = $client->selectDatabase($database)->selectCollection('instruments');

            // Verifica se o arquivo existe
            if (!file_exists($upload->file_path)) {
                throw new \Exception("Arquivo não encontrado: " . $upload->file_path);
            }

            // Abre o arquivo
            $handle = fopen($upload->file_path, 'r');
            if (!$handle) {
                throw new \Exception("Não foi possível abrir o arquivo");
            }

            $header = null;
            $imported = 0;
            $batch = [];
            $batchSize = 100;


            while (($row = fgetcsv($handle, 10000, ';')) !== false) {
                // Pula linha de status
                if (strpos($row[0] ?? '', 'Status') === 0) {
                    continue;
                }

                // Primeira linha válida é o header
                if ($header === null) {
                    $header = $this->cleanHeader($row);
                    continue;
                }

                // Cria documento
                $document = [
                    'file_upload_id' => (string) $upload->id,
                    'created_at' => new UTCDateTime(time() * 1000),
                    'updated_at' => new UTCDateTime(time() * 1000),
                ];

                // Combina com header
                $row = array_pad($row, count($header), null);

                try {
                    $data = array_combine($header, $row);

                    // Adiciona todos os campos ao documento
                    foreach ($data as $key => $value) {
                        if ($value !== null && $value !== '') {
                            $document[$key] = $this->fixEncoding($value);
                        }
                    }

                    $batch[] = $document;
                    $imported++;

                    // Insere em lote quando atingir o tamanho
                    if (count($batch) >= $batchSize) {
                        $collection->insertMany($batch);
                        // \Log::info("Inserido lote de " . count($batch) . " registros");
                        $batch = [];
                    }

                } catch (\Exception $e) {
                    continue;
                }
            }

            // Insere o último lote se houver
            if (!empty($batch)) {
                $collection->insertMany($batch);
            }

            fclose($handle);

            $upload->markAsCompleted();

        } catch (\Exception $e) {

            $upload->markAsFailed("Erro: " . $e->getMessage());
        }
    }

    /**
     * Limpa os nomes das colunas
     */
    private function cleanHeader(array $header): array
    {
        return array_map(function ($item, $index) {
            if (empty($item)) {
                return 'coluna_' . ($index + 1);
            }

            // Remove caracteres especiais
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