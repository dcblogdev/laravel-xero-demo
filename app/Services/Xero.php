<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Xero extends \Dcblogdev\Xero\Xero
{
    /**
     * Maximum number of retries for rate-limited requests
     */
    protected int $maxRetries = 5;

    /**
     * Base delay in seconds between retries (will be multiplied by 2^attempt for exponential backoff)
     */
    protected int $baseDelay = 1;

    /**
     * Cache for API responses to reduce duplicate calls
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $responseCache = [];

    /**
     * Time of the last API request to implement rate limiting (in milliseconds)
     */
    protected static ?float $lastRequestTime = null;

    /**
     * Minimum time between API requests in milliseconds
     */
    protected int $requestInterval = 100; // 100ms between requests

    /**
     * Override the guzzle method to add retry logic for rate limiting, caching, and request throttling
     *
     * @param  string  $type  The HTTP method (get, post, put, delete, etc.)
     * @param  string  $request  The API endpoint
     * @param  array<string, mixed>  $data  The request data
     * @param  bool  $raw  Whether to return the raw response body
     * @param  string  $accept  The Accept header value
     * @param  array<string, string>  $headers  Additional headers
     * @return array<string, mixed>|null The API response or null
     *
     * @throws Exception
     */
    protected function guzzle(string $type, string $request, array $data = [], bool $raw = false, string $accept = 'application/json', array $headers = []): ?array
    {
        if ($data === []) {
            $data = null;
        }

        // Generate a cache key for this request
        $cacheKey = $this->generateCacheKey($type, $request, $data, $raw, $accept, $headers);

        // For GET requests, check if we have a cached response
        if ($type === 'get' && isset(self::$responseCache[$cacheKey])) {
            Log::debug('Using cached response for Xero API request', [
                'request' => $request,
                'type' => $type,
            ]);

            return self::$responseCache[$cacheKey];
        }

        // Implement request throttling - ensure minimum time between requests
        $this->throttleRequest();

        $attempt = 0;
        $delay = $this->baseDelay;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::withToken($this->getAccessToken())
                    ->withHeaders(array_merge(['Xero-tenant-id' => $this->getTenantId()], $headers))
                    ->accept($accept)
                    ->$type(self::$baseUrl.$request, $data)
                    ->throw();

                $result = [
                    'body' => $raw ? $response->body() : $response->json(),
                    'headers' => $response->getHeaders(),
                ];

                // Cache the response for GET requests
                if ($type === 'get') {
                    self::$responseCache[$cacheKey] = $result;
                }

                return $result;
            } catch (Exception $e) {
                // Check if this is a rate limit error (HTTP 429)
                if (mb_strpos($e->getMessage(), '429') !== false) {
                    $attempt++;

                    if ($attempt >= $this->maxRetries) {
                        Log::error('Xero API rate limit exceeded after '.$this->maxRetries.' retries', [
                            'request' => $request,
                            'error' => $e->getMessage(),
                        ]);
                        throw new Exception('Xero API rate limit exceeded: '.$e->getMessage());
                    }

                    // Calculate delay with exponential backoff (2^attempt * base_delay)
                    $sleepTime = $delay * (2 ** ($attempt - 1));

                    Log::warning('Xero API rate limit hit, retrying in '.$sleepTime.' seconds', [
                        'attempt' => $attempt,
                        'max_retries' => $this->maxRetries,
                        'request' => $request,
                    ]);

                    // Sleep for the calculated time
                    sleep($sleepTime);

                    // Continue to the next iteration of the loop to retry
                    continue;
                }

                // For other errors, just throw the exception
                throw new Exception($e->getMessage());
            }
        }

        // This should never be reached due to the throw in the loop, but added for completeness
        throw new Exception('Maximum retries exceeded for Xero API request');
    }

    /**
     * Generate a cache key for a request
     *
     * @param  string  $type  The HTTP method
     * @param  string  $request  The API endpoint
     * @param  mixed  $data  The request data
     * @param  bool  $raw  Whether to return the raw response body
     * @param  string  $accept  The Accept header value
     * @param  array<string, string>  $headers  Additional headers
     * @return string The cache key
     */
    private function generateCacheKey(string $type, string $request, mixed $data, bool $raw, string $accept, array $headers): string
    {
        return md5($type.$request.json_encode($data).($raw ? '1' : '0').$accept.json_encode($headers));
    }

    /**
     * Throttle requests to avoid hitting rate limits
     */
    private function throttleRequest(): void
    {
        $now = microtime(true) * 1000; // Current time in milliseconds

        if (self::$lastRequestTime !== null) {
            $timeSinceLastRequest = $now - self::$lastRequestTime;

            // If we've made a request too recently, sleep for the remaining time
            if ($timeSinceLastRequest < $this->requestInterval) {
                $sleepTime = ($this->requestInterval - $timeSinceLastRequest) / 1000;
                usleep((int) ($sleepTime * 1000000)); // Convert to microseconds
            }
        }

        // Update the last request time
        self::$lastRequestTime = microtime(true) * 1000;
    }
}
