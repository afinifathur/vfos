<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PasardanaService
{
    /**
     * Fetch the latest NAB (Net Asset Value) for a given mutual fund name.
     *
     * @param string $fundName
     * @return array|null
     */
    public function getNavReksaDana(string $fundName)
    {
        $query = urlencode($fundName);
        // This is a more common endpoint for the search functionality
        $url = "https://pasardana.id/api/Fund/SearchByName?name={$query}";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/plain, */*',
            ])->timeout(8)->get($url);

            Log::info("Pasardana request to {$url}");

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Pasardana response data: " . json_encode($data));

                if (!empty($data) && isset($data[0])) {
                    $nav = (float) ($data[0]['LastNav'] ?? 0);
                    return [
                        'name' => $data[0]['Name'] ?? $fundName,
                        'nav'  => $nav,
                        'date' => $data[0]['LastNavDate'] ?? null
                    ];
                }
            } else if ($response->status() === 404) {
                // Fallback to the other search endpoint
                $url = "https://pasardana.id/api/Fund/Search?search={$query}";
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])->timeout(8)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data) && isset($data[0])) {
                        $nav = (float) ($data[0]['LastNav'] ?? 0);
                        return [
                            'name' => $data[0]['Name'] ?? $fundName,
                            'nav'  => $nav,
                            'date' => $data[0]['LastNavDate'] ?? null
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Pasardana fetch failed for {$fundName}: " . $e->getMessage());
        }

        return null;
    }
}
