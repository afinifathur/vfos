<?php
// Show full body samples of different myBCA email types
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$extractor = app(\App\Services\Parsers\Extractor\BcaDocumentExtractor::class);

$parsed = \App\Models\InboxMessage::where('status', 'PARSED')->get();

// Group by subject to show unique types
$shown = [];
foreach ($parsed as $m) {
    if (isset($shown[$m->subject])) continue;
    $shown[$m->subject] = true;
    echo "=== ID={$m->id} Subject: {$m->subject} ===\n";
    $doc = $extractor->extract($m);
    echo $doc->body . "\n";
    echo str_repeat('=', 80) . "\n\n";
}
