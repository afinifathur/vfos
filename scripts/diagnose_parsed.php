<?php
// Diagnose why PARSED messages fail to produce parsedTransaction
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parsed = \App\Models\InboxMessage::where('status', 'PARSED')->get();
$parserManager = app(\App\Services\Parsers\ParserManager::class);

echo "=== Diagnosing " . count($parsed) . " PARSED messages ===\n\n";

foreach ($parsed as $m) {
    echo "--- ID={$m->id} subject={$m->subject} ---\n";
    try {
        $context = new \App\DTO\ParserContext($m->connector);
        $result = $parserManager->parse($m, $context);
        echo "  supported={$result->supported}\n";
        echo "  parsedTransaction=" . ($result->parsedTransaction ? 'YES' : 'NO') . "\n";
        if ($result->parsedMessage) {
            echo "  provider={$result->parsedMessage->provider}\n";
        }
        if ($result->parsedTransaction) {
            $pt = $result->parsedTransaction;
            echo "  amount={$pt->amount} flow={$pt->flow} type={$pt->type}\n";
            echo "  date=" . ($pt->transactionDate ? $pt->transactionDate->toDateString() : 'NULL') . "\n";
            echo "  merchant={$pt->merchant}\n";
            echo "  srcAccount={$pt->sourceAccount} dstAccount={$pt->destinationAccount}\n";
        } else {
            echo "  *** NO parsedTransaction - message will stay PARSED forever! ***\n";
            // Extract and show what fields were found
            $extractor = app(\App\Services\Parsers\Extractor\BcaDocumentExtractor::class);
            if ($extractor->supports($m)) {
                $doc = $extractor->extract($m);
                $fields = $doc->fields->all();
                echo "  Fields extracted: " . json_encode($fields) . "\n";
                echo "  Body snippet: " . substr($doc->body, 0, 300) . "\n";
            }
        }
        if (!empty($result->errors)) {
            echo "  errors: " . implode(', ', $result->errors) . "\n";
        }
    } catch (\Exception $e) {
        echo "  EXCEPTION: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
