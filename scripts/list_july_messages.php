<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\InboxMessage;

// We need to look at messages from July 2026.
// internal_date is milliseconds since epoch.
// July 2026 is from 2026-07-01 to 2026-07-31.
$start = Carbon::parse('2026-07-01 00:00:00', 'Asia/Jakarta')->timestamp * 1000;
$end = Carbon::parse('2026-07-31 23:59:59', 'Asia/Jakarta')->timestamp * 1000;

$messages = InboxMessage::whereBetween('internal_date', [$start, $end])->get();

echo "Total messages in July 2026: " . $messages->count() . "\n";
foreach ($messages as $m) {
    $dateStr = Carbon::createFromTimestampMs($m->internal_date, 'Asia/Jakarta')->toDateTimeString();
    echo "ID={$m->id} | Date={$dateStr} | Status={$m->status} | From={$m->from} | Subject={$m->subject}\n";
}
