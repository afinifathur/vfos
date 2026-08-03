<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$columns = Schema::getColumnListing('inbox_messages');
echo "Columns in inbox_messages:\n" . implode(', ', $columns) . "\n\n";

$sample = \App\Models\InboxMessage::where('status', 'PARSED')->first();
if ($sample) {
    echo "Sample Message ID: " . $sample->id . "\n";
    echo "Subject: " . $sample->subject . "\n";
    echo "Raw payload keys: " . implode(', ', array_keys($sample->raw_payload ?? [])) . "\n";
    echo "Raw payload content (truncated): " . json_encode($sample->raw_payload) . "\n";
} else {
    echo "No parsed message found\n";
}
