<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KontanService
{
    /**
     * Fetch the latest NAB (Net Asset Value) from Kontan.id product page.
     *
     * @param string $url
     * @return float|null
     */
    public function getNavFromKontan(string $url)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->timeout(10)->get($url);

            if ($response->successful()) {
                $html = $response->body();
                
                // Regex to extract NAB based on the structure validated:
                // <div class="wdt_nabreturn"> ... <div class="wrn_tbal t-uppercase">1949.16</div>
                preg_match('/<div class="wdt_nabreturn">.*?<div class="wrn_tbal t-uppercase">(.*?)<\/div>/s', $html, $matches);
                
                if (isset($matches[1])) {
                    $nav = (float) trim($matches[1]);
                    return $nav > 0 ? $nav : null;
                }
            } else {
                Log::error("Kontan Scraper error for {$url}: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Kontan Scraper failed for {$url}: " . $e->getMessage());
        }

        return null;
    }
}
