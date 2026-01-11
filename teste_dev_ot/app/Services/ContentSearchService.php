<?php

namespace App\Services;

use Illuminate\Http\Request;
use MongoDB\Client;
use Illuminate\Support\Facades\Log;

class ContentSearchService
{
    public function search(Request $request): array
    {
        try {
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

            $collection = $client
                ->selectDatabase($config['database'])
                ->selectCollection('instruments');


            $filterHeaders = [
                'RptDt',
                'TckrSymb',
                'MktNm',
                'SctyCtgyNm',
                'ISIN',
                'CrpnNm',
            ];

            $filters = [];
            $filtersCount = 0;

            foreach ($filterHeaders as $header) {
                $value = $request->headers->get($header);
                if (!empty($value)) {
                    $filters[$header] = $value;
                    $filtersCount++;
                }
            }


            $page = max((int) $request->headers->get('page', 1), 1);
            $perPage = min((int) $request->headers->get('per_page', 5000), 10000);


            if ($filtersCount >= 2) {
                return [
                    'data' => [],
                    'meta' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'pages' => 0,
                        'reason' => 'Consulta bloqueada: muitos filtros enviados',
                    ],
                ];
            }

            $cursor = $collection->find($filters, [
                'limit' => $perPage,
                'skip' => ($page - 1) * $perPage,
            ]);

            $data = iterator_to_array($cursor, false);
            $total = $collection->countDocuments($filters);

            return [
                'data' => $data,
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'pages' => (int) ceil($total / $perPage),
                ],
            ];

        } catch (\Throwable $e) {
            throw new \Exception(
                'Erro ao buscar o conteúdo: ' . $e->getMessage()
            );
        }
    }
}
