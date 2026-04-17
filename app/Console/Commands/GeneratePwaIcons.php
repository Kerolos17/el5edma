<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePwaIcons extends Command
{
    protected $signature   = 'pwa:generate-icons';
    protected $description = 'Generate PWA icons in all required sizes';

    // All sizes required by manifest.json
    private array $sizes = [72, 96, 128, 144, 152, 192, 384, 512];

    // Brand colors
    private string $bgColor = '#0073A3';
    private string $fgColor = '#FFFFFF';

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('PHP GD extension is not installed. Run: sudo apt-get install php-gd');

            return self::FAILURE;
        }

        $iconsDir = public_path('icons');

        if (! is_dir($iconsDir)) {
            mkdir($iconsDir, 0755, true);
            $this->line("Created directory: $iconsDir");
        }

        foreach ($this->sizes as $size) {
            $this->generateIcon($iconsDir, $size);
        }

        // Generate apple-touch-icon (180x180)
        $this->generateIcon($iconsDir, 180, 'apple-touch-icon.png');

        $this->newLine();
        $this->info('All PWA icons generated successfully in public/icons/');

        return self::SUCCESS;
    }

    private function generateIcon(string $dir, int $size, ?string $filename = null): void
    {
        $filename ??= "icon-{$size}x{$size}.png";
        $path = "$dir/$filename";

        $img = imagecreatetruecolor($size, $size);
        imageantialias($img, true);

        // Parse brand colors
        [$bgR, $bgG, $bgB] = $this->hexToRgb($this->bgColor);
        [$fgR, $fgG, $fgB] = $this->hexToRgb($this->fgColor);

        $bg = imagecolorallocate($img, $bgR, $bgG, $bgB);
        $fg = imagecolorallocate($img, $fgR, $fgG, $fgB);

        // Rounded background (simulate with filled circle corners)
        imagefill($img, 0, 0, $bg);

        // Draw a simple cross symbol (appropriate for ministry app)
        $this->drawCross($img, $size, $fg);

        imagepng($img, $path);
        imagedestroy($img);

        $this->line("  ✓ Generated: $filename ({$size}x{$size})");
    }

    private function drawCross($img, int $size, $color): void
    {
        $center    = (int) ($size / 2);
        $armLength = (int) ($size * 0.30);
        $thickness = (int) max(2, $size * 0.07);

        // Vertical arm
        imagefilledrectangle(
            $img,
            $center - $thickness,
            $center - $armLength,
            $center + $thickness,
            $center + $armLength,
            $color,
        );

        // Horizontal arm
        imagefilledrectangle(
            $img,
            $center - $armLength,
            $center - $thickness,
            $center + $armLength,
            $center + $thickness,
            $color,
        );
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
