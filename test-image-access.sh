#!/bin/bash

echo "=== Task 3.3: Testing Direct Access to Beneficiary Images ==="
echo ""

# Get beneficiary with photo
echo "Step 1: Finding beneficiary with photo..."
PHOTO_PATH=$(php artisan tinker --execute="echo \App\Models\Beneficiary::whereNotNull('photo')->first()?->photo ?? '';" 2>/dev/null | grep -v "Psy Shell" | grep -v "^$" | tail -1)

if [ -z "$PHOTO_PATH" ]; then
    echo "❌ No beneficiary with photo found"
    exit 1
fi

echo "✓ Found photo: $PHOTO_PATH"
echo ""

# Check file exists
echo "Step 2: Verifying file exists in storage..."
FULL_PATH="storage/app/public/$PHOTO_PATH"
if [ ! -f "$FULL_PATH" ]; then
    echo "❌ File not found at: $FULL_PATH"
    exit 1
fi
echo "✓ File exists at: $FULL_PATH"
echo ""

# Check symlink
echo "Step 3: Checking storage symlink..."
if [ ! -e "public/storage" ]; then
    echo "❌ Symlink not found at: public/storage"
    exit 1
fi
echo "✓ Symlink exists at: public/storage"
echo ""

# Get APP_URL
APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2)
PUBLIC_URL="$APP_URL/storage/$PHOTO_PATH"

echo "Step 4: Public URL generated..."
echo "✓ URL: $PUBLIC_URL"
echo ""

echo "Step 5: Verifying file accessible via public path..."
PUBLIC_FILE="public/storage/$PHOTO_PATH"
if [ ! -f "$PUBLIC_FILE" ]; then
    echo "❌ File not accessible at: $PUBLIC_FILE"
    exit 1
fi
echo "✓ File accessible via: $PUBLIC_FILE"
echo ""

echo "=== All Checks Passed! ==="
echo ""
echo "Manual Verification Steps:"
echo "1. Ensure dev server is running: php artisan serve"
echo "2. Open in browser: $PUBLIC_URL"
echo "3. Verify image displays (HTTP 200)"
