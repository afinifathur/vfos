<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KemenanganSignatureService
{
    protected string $url = 'https://kemenangansignature.com/buyback';
    protected ?array $cachedPrices = null;

    /**
     * Get the latest buyback price for a given Karat (e.g., '17K', '24K').
     *
     * @param string $karat
     * @return float|null
     */
    public function getPrice(string $karat)
    {
        $prices = $this->getAllPrices();
        
        // Normalize input: '17k' -> '17K', '24k+' -> '24K+'
        $karat = strtoupper($karat);
        
        return $prices[$karat] ?? null;
    }

    /**
     * Fetch and parse all prices from the website.
     * Use internal memory cache first, then Laravel cache.
     *
     * @return array
     */
    protected function getAllPrices(): array
    {
        if ($this->cachedPrices !== null) {
            return $this->cachedPrices;
        }

        // Cache the raw prices for 1 hour to avoid excessive scraping
        $this->cachedPrices = Cache::remember('kemenangan_signature_prices', 3600, function () {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])->timeout(15)->get($this->url);

                if (!$response->successful()) {
                    Log::error("KemenanganSignatureService: HTTP error " . $response->status());
                    return [];
                }

                $html = $response->body();
                $prices = [];

                // Extract prices and karat labels from data attributes
                // Example: <div class="price-card" data-karat="593000" data-name="6K (25% - 29%) - Rp 593.000/gram">
                // We use a regex that looks specifically for data-karat value and the karst label in data-name
                if (preg_match_all('/data-karat=["\'](\d+)["\']\s+data-name=["\'](\d+K\+?)/i', $html, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $price = (float) $match[1];
                        $label = strtoupper($match[2]);
                        $prices[$label] = $price;
                    }
                }
                
                if (empty($prices)) {
                    Log::warning("KemenanganSignatureService: No prices found in HTML. Check if site structure changed.");
                }

                return $prices;

            } catch (\Exception $e) {
                Log::error("KemenanganSignatureService: Scraping failed: " . $e->getMessage());
                return [];
            }
        });

        return $this->cachedPrices;
    }
}
