<?php

/**
 * Manual Test Script for Task 3.3: اختبار الوصول المباشر للصور
 *
 * This script tests direct access to beneficiary images via public URLs
 * Run with: php tests/Manual/test-image-access.php
 *
 * Requirements: 2.4
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Direct Access to Beneficiary Images ===\n\n";

// Debug: Check database connection
echo "Debug: DB Connection: " . config('database.default') . "\n";
echo "Debug: Total beneficiaries: " . \App\Models\Beneficiary::count() . "\n\n";

// Step 1: Find a beneficiary with a photo
echo "Step 1: Finding beneficiary with photo...\n";
$beneficiary = \App\Models\Beneficiary::whereNotNull('photo')->first();

if (! $beneficiary) {
    echo "❌ No beneficiary with photo found in database\n";
    exit(1);
}

echo "✓ Found beneficiary ID: {$beneficiary->id}\n";
echo "  Photo path: {$beneficiary->photo}\n\n";

// Step 2: Verify file exists physically
echo "Step 2: Verifying file exists in storage...\n";
$fullPath = storage_path('app/public/' . $beneficiary->photo);
if (! file_exists($fullPath)) {
    echo "❌ File does not exist at: {$fullPath}\n";
    exit(1);
}
echo "✓ File exists at: {$fullPath}\n\n";

// Step 3: Check symlink exists
echo "Step 3: Checking storage symlink...\n";
$symlinkPath = public_path('storage');
if (! file_exists($symlinkPath)) {
    echo "❌ Symlink does not exist at: {$symlinkPath}\n";
    echo "   Run: php artisan storage:link\n";
    exit(1);
}
echo "✓ Symlink exists at: {$symlinkPath}\n";

if (is_link($symlinkPath)) {
    $target = readlink($symlinkPath);
    echo "  Points to: {$target}\n";
}
echo "\n";

// Step 4: Generate public URL
echo "Step 4: Generating public URL...\n";
$appUrl    = config('app.url');
$publicUrl = $appUrl . '/storage/' . $beneficiary->photo;
echo "✓ Public URL: {$publicUrl}\n\n";

// Step 5: Test URL accessibility using Storage facade
echo "Step 5: Testing Storage::url() generation...\n";
$storageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($beneficiary->photo);
echo "✓ Storage URL: {$storageUrl}\n\n";

// Step 6: Verify public path exists
echo "Step 6: Verifying public path...\n";
$publicFilePath = public_path('storage/' . $beneficiary->photo);
if (! file_exists($publicFilePath)) {
    echo "❌ File not accessible via public path: {$publicFilePath}\n";
    exit(1);
}
echo "✓ File accessible via public path\n\n";

// Summary
echo "=== Test Summary ===\n";
echo "✓ All checks passed!\n";
echo "✓ Image should be accessible at: {$publicUrl}\n\n";
echo "Manual verification:\n";
echo "1. Start the development server: php artisan serve\n";
echo "2. Open in browser: {$publicUrl}\n";
echo "3. Verify the image displays (HTTP 200)\n";
