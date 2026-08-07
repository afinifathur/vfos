<?php

namespace App\Services\Finance\Identity;

use App\Models\Account;

class AccountProviderResolver
{
    /**
     * Infer the financial provider (bank/institution) name from account metadata (e.g. name).
     */
    public function resolveProvider(Account $account): ?string
    {
        $name = strtoupper($account->name);

        if (str_contains($name, 'BCA')) {
            return 'BCA';
        }

        if (str_contains($name, 'MANDIRI')) {
            return 'Mandiri';
        }

        if (str_contains($name, 'BNI')) {
            return 'BNI';
        }

        if (str_contains($name, 'BRI')) {
            return 'BRI';
        }

        if (str_contains($name, 'CIMB') || str_contains($name, 'NIAGA')) {
            return 'CIMB Niaga';
        }

        if (str_contains($name, 'GOPAY') || str_contains($name, 'GOJEK')) {
            return 'GoPay';
        }

        if (str_contains($name, 'OVO')) {
            return 'OVO';
        }

        if (str_contains($name, 'SHOPEEPAY') || str_contains($name, 'SHOPEE')) {
            return 'ShopeePay';
        }

        return null;
    }

    /**
     * Normalize a provider string value to canonical casing/format.
     */
    public function normalizeProvider(?string $provider): ?string
    {
        if ($provider === null) {
            return null;
        }

        $upper = strtoupper(trim($provider));

        switch ($upper) {
            case 'BCA':
                return 'BCA';
            case 'MANDIRI':
                return 'Mandiri';
            case 'BNI':
                return 'BNI';
            case 'BRI':
                return 'BRI';
            case 'CIMB':
            case 'CIMB NIAGA':
            case 'NIAGA':
                return 'CIMB Niaga';
            case 'GOPAY':
            case 'GO-PAY':
                return 'GoPay';
            case 'OVO':
                return 'OVO';
            case 'SHOPEE':
            case 'SHOPEEPAY':
                return 'ShopeePay';
            default:
                return trim($provider);
        }
    }
}
