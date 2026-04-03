<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Subcategory;

$sourceEmail = 'afini.fathurrozi@gmail.com';
$targetEmail = 'alifianinulil@gmail.com';

$sourceUser = User::where('email', $sourceEmail)->first();
$targetUser = User::where('email', $targetEmail)->first();

if (!$sourceUser || !$targetUser) {
    die("Error: Source ($sourceEmail) or Target ($targetEmail) user not found.\n");
}

echo "Duplicating categories from {$sourceEmail} (ID: {$sourceUser->id}) to {$targetEmail} (ID: {$targetUser->id})...\n";

// Fetch source categories without global scope RBAC
$sourceCategories = Category::withoutGlobalScopes()->where('user_id', $sourceUser->id)->get();

foreach ($sourceCategories as $sourceCat) {
    // Check if category already exists for target user
    $targetCat = Category::withoutGlobalScopes()
        ->where('user_id', $targetUser->id)
        ->where('name', $sourceCat->name)
        ->first();

    if (!$targetCat) {
        $targetCat = Category::create([
            'user_id'    => $targetUser->id,
            'owner'      => 'pacar', // Target user is partner
            'name'       => $sourceCat->name,
            'type'       => $sourceCat->type,
            'is_active'  => $sourceCat->is_active,
            'is_ignored' => $sourceCat->is_ignored,
        ]);
        echo "Created Category: [{$targetCat->type}] {$targetCat->name}\n";
    } else {
        echo "Category already exists: {$targetCat->name} (Skipping)\n";
    }

    // Duplicate Subcategories
    $sourceSubcats = Subcategory::where('category_id', $sourceCat->id)->get();
    foreach ($sourceSubcats as $sourceSub) {
        $exists = Subcategory::where('category_id', $targetCat->id)
            ->where('name', $sourceSub->name)
            ->exists();

        if (!$exists) {
            Subcategory::create([
                'category_id' => $targetCat->id,
                'name'        => $sourceSub->name,
                'is_active'   => $sourceSub->is_active,
            ]);
            echo "  - Added Subcategory: {$sourceSub->name}\n";
        }
    }
}

echo "\nDone! Categories and subcategories have been duplicated.\n";
