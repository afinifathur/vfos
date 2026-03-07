<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Account;

$id = 4;
$initial_balance = 5580710;

$account = Account::find($id);
if ($account) {
    $account->initial_balance = $initial_balance;
    $account->total_balance = $account->calculateBalance();
    $account->save();
    echo "Account #{$id} ({$account->name}) updated.\n";
    echo "Initial Balance: " . number_format($account->initial_balance, 0, ',', '.') . "\n";
    echo "Total Balance: " . number_format($account->total_balance, 0, ',', '.') . "\n";
} else {
    echo "Account #{$id} not found.\n";
}
