<?php
// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$groups = \App\Models\InboxMessage::selectRaw('status, count(*) as cnt')->groupBy('status')->get();
echo "=== InboxMessage Status Distribution ===\n";
foreach ($groups as $g) {
    echo $g->status . ': ' . $g->cnt . "\n";
}

echo "\n=== Sample PARSED messages ===\n";
$parsed = \App\Models\InboxMessage::where('status', 'PARSED')->take(3)->get(['id','subject','from','status','connector','plain_body','html_body']);
foreach ($parsed as $m) {
    echo "ID={$m->id} subject={$m->subject}\n";
    echo "  from={$m->from} connector={$m->connector}\n";
    echo "  plain_body_len=" . strlen($m->plain_body ?? '') . " html_body_len=" . strlen($m->html_body ?? '') . "\n";
    echo "  First 200 chars of plain_body: " . substr($m->plain_body ?? $m->html_body ?? '', 0, 200) . "\n\n";
}

echo "\n=== Transaction count ===\n";
echo \App\Models\Transaction::count() . " transactions\n";

echo "\n=== Account list ===\n";
foreach (\App\Models\Account::all(['id','name','account_number','user_id']) as $acc) {
    echo "ID={$acc->id} user={$acc->user_id} name={$acc->name} number={$acc->account_number}\n";
}
