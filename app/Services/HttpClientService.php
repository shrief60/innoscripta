<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

class HttpClientService
{
    /**
     * Http Client GET request
     *
     * @param string $url
     * @param array $params
     * @param integer $timeout
     * @return Response|null
     */
    public function get(string $url, array $params = [], int $timeout = 30): ?Response
    {
        try {
            $response = Http::timeout($timeout)->get($url, $params);

            if ($response->failed()) {
                Log::warning('HTTP GET request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'params' => $params,
                ]);
                return null;
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('HTTP GET request exception', [
                'url' => $url,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

