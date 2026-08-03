<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InboxMessage;
use App\Services\Parsers\ParserManager;

$parsed = InboxMessage::where('status', 'PARSED')->get();
$parserManager = app(ParserManager::class);

$withTx = 0;
$withoutTx = 0;

foreach ($parsed as $m) {
    $context = new \App\DTO\ParserContext($m->connector);
    $result = $parserManager->parse($m, $context);
    if ($result->parsedTransaction) {
        $withTx++;
    } else {
        $withoutTx++;
    }
}

echo "PARSED messages with parsedTransaction: " . $withTx . "\n";
echo "PARSED messages without parsedTransaction: " . $withoutTx . "\n";
