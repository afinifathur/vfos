<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BareksaService
{
    /**
     * Fetch the latest NAB (Net Asset Value) from Bareksa product page.
     *
     * @param string $url
     * @return float|null
     */
    public function getNavFromBareksa(string $url)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            ])->timeout(10)->get($url);

            if ($response->successful()) {
                $html = $response->body();
                
                // Extract NAB from: <span class="fS40">2.251,14</span>
                preg_match('/<span[^>]*class=["\']?[^"\']*fS40[^>]*>(.*?)<\/span>/i', $html, $matches);
                
                if (isset($matches[1])) {
                    $navStr = trim(strip_tags($matches[1]));
                    
                    // Convert Indonesian number format to float
                    // E.g., '2.251,14' -> '2251.14'
                    $navStr = str_replace('.', '', $navStr); // Remove thousands separator
                    $navStr = str_replace(',', '.', $navStr); // Replace decimal separator
                    
                    $nav = (float) $navStr;
                    return $nav > 0 ? $nav : null;
                }
            } else {
                Log::error("Bareksa Scraper error for {$url}: HTTP " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Bareksa Scraper failed for {$url}: " . $e->getMessage());
        }

        return null;
    }
}
