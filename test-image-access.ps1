# Task 3.3: Testing Direct Access to Beneficiary Images
# Requirements: 2.4

Write-Host "=== Task 3.3: Testing Direct Access to Beneficiary Images ===" -ForegroundColor Cyan
Write-Host ""

# Step 1: Find beneficiary with photo
Write-Host "Step 1: Finding beneficiary with photo..." -ForegroundColor Yellow
$output = php artisan tinker --execute="echo \App\Models\Beneficiary::whereNotNull('photo')->first()?->photo ?? '';" 2>&1
$photoPath = ($output | Select-String -Pattern "beneficiaries/photos/").ToString().Trim()

if ([string]::IsNullOrEmpty($photoPath)) {
    Write-Host "X No beneficiary with photo found" -ForegroundColor Red
    exit 1
}

Write-Host "OK Found photo: $photoPath" -ForegroundColor Green
Write-Host ""

# Step 2: Check file exists in storage
Write-Host "Step 2: Verifying file exists in storage..." -ForegroundColor Yellow
$fullPath = "storage\app\public\$photoPath"
if (-not (Test-Path $fullPath)) {
    Write-Host "X File not found at: $fullPath" -ForegroundColor Red
    exit 1
}
Write-Host "OK File exists at: $fullPath" -ForegroundColor Green
Write-Host ""

# Step 3: Check symlink exists
Write-Host "Step 3: Checking storage symlink..." -ForegroundColor Yellow
if (-not (Test-Path "public\storage")) {
    Write-Host "X Symlink not found at: public\storage" -ForegroundColor Red
    Write-Host "   Run: php artisan storage:link" -ForegroundColor Yellow
    exit 1
}
Write-Host "OK Symlink exists at: public\storage" -ForegroundColor Green
Write-Host ""

# Step 4: Generate public URL
Write-Host "Step 4: Generating public URL..." -ForegroundColor Yellow
$appUrl = (Get-Content .env | Select-String "^APP_URL=").ToString().Split("=")[1].Trim()
$publicUrl = "$appUrl/storage/$photoPath"
Write-Host "OK Public URL: $publicUrl" -ForegroundColor Green
Write-Host ""

# Step 5: Verify file accessible via public path
Write-Host "Step 5: Verifying file accessible via public path..." -ForegroundColor Yellow
$publicFile = "public\storage\$photoPath"
if (-not (Test-Path $publicFile)) {
    Write-Host "X File not accessible at: $publicFile" -ForegroundColor Red
    exit 1
}
Write-Host "OK File accessible via: $publicFile" -ForegroundColor Green
Write-Host ""

# Summary
Write-Host "=== Test Summary ===" -ForegroundColor Cyan
Write-Host "OK All file system checks passed!" -ForegroundColor Green
Write-Host "OK Image should be accessible at: $publicUrl" -ForegroundColor Green
Write-Host ""
Write-Host "Manual Verification:" -ForegroundColor Yellow
Write-Host "1. Ensure dev server is running: php artisan serve" -ForegroundColor White
Write-Host "2. Open in browser: $publicUrl" -ForegroundColor White
Write-Host "3. Verify image displays (HTTP 200)" -ForegroundColor White
