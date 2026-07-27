<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePwaIcons extends Command
{
    protected $signature = 'pwa:icons
                            {--source= : Path to a square source image (defaults to the configured app logo, then the bundled brand source)}';

    protected $description = 'Generate the PWA / home-screen icon set from the app logo';

    /**
     * Output size => scale of the source inside the square canvas.
     *
     * Maskable icons must keep their artwork inside the inner 80% "safe zone",
     * because Android crops them to a platform-defined shape.
     */
    private const TARGETS = [
        'icon-192.png' => [192, 0.90],
        'icon-512.png' => [512, 0.90],
        'icon-maskable-192.png' => [192, 0.62],
        'icon-maskable-512.png' => [512, 0.62],
        'apple-touch-icon.png' => [180, 0.86],
    ];

    public function handle(): int
    {
        if (!extension_loaded('gd')) {
            $this->error('The GD extension is required to generate icons.');

            return self::FAILURE;
        }

        $source = $this->resolveSource();

        if ($source === null) {
            $this->error('No source image found. Pass one with --source=path/to/logo.png');

            return self::FAILURE;
        }

        $this->info("Source: {$source}");

        $image = $this->loadImage($source);

        if ($image === null) {
            $this->error("Unable to read '{$source}' as a PNG, JPEG or WebP image.");

            return self::FAILURE;
        }

        $image = $this->trim($image);

        $outputDir = public_path('assets/images/pwa');

        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
            $this->error("Unable to create {$outputDir}");
            imagedestroy($image);

            return self::FAILURE;
        }

        foreach (self::TARGETS as $filename => [$size, $scale]) {
            $canvas = $this->render($image, $size, $scale);
            imagepng($canvas, "{$outputDir}/{$filename}", 9);
            imagedestroy($canvas);
            $this->line("  wrote {$filename} ({$size}x{$size})");
        }

        imagedestroy($image);

        $this->info('Icon set generated in public/assets/images/pwa.');

        return self::SUCCESS;
    }

    private function resolveSource(): ?string
    {
        $candidates = [];

        if ($option = $this->option('source')) {
            $candidates[] = str_starts_with($option, '/') ? $option : base_path($option);
        }

        if ($logo = getSetting('app_logo')) {
            $candidates[] = storage_path('app/public/' . $logo);
        }

        $candidates[] = public_path('assets/images/pwa/icon-source.png');

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function loadImage(string $path): ?\GdImage
    {
        $data = @file_get_contents($path);

        if ($data === false) {
            return null;
        }

        $image = @imagecreatefromstring($data);

        return $image === false ? null : $image;
    }

    /**
     * Crop the uniform border around the artwork so the icon scales fill their canvas.
     *
     * Logos are usually exported with generous padding, which would otherwise stack
     * with the padding this command adds and leave the mark looking tiny.
     */
    private function trim(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $background = imagecolorat($image, 0, 0);
        $tolerance = 12;

        $matchesBackground = function (int $x, int $y) use ($image, $background, $tolerance): bool {
            $a = imagecolorsforindex($image, imagecolorat($image, $x, $y));
            $b = imagecolorsforindex($image, $background);

            if (($a['alpha'] ?? 0) > 100 && ($b['alpha'] ?? 0) > 100) {
                return true;
            }

            return abs($a['red'] - $b['red']) <= $tolerance
                && abs($a['green'] - $b['green']) <= $tolerance
                && abs($a['blue'] - $b['blue']) <= $tolerance;
        };

        $top = 0;
        $bottom = $height - 1;
        $left = 0;
        $right = $width - 1;

        while ($top < $bottom && $this->rowIsUniform($width, $top, $matchesBackground)) {
            $top++;
        }

        while ($bottom > $top && $this->rowIsUniform($width, $bottom, $matchesBackground)) {
            $bottom--;
        }

        while ($left < $right && $this->columnIsUniform($height, $left, $matchesBackground)) {
            $left++;
        }

        while ($right > $left && $this->columnIsUniform($height, $right, $matchesBackground)) {
            $right--;
        }

        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;

        if ($cropWidth < 8 || $cropHeight < 8 || ($cropWidth === $width && $cropHeight === $height)) {
            return $image;
        }

        $cropped = imagecrop($image, ['x' => $left, 'y' => $top, 'width' => $cropWidth, 'height' => $cropHeight]);

        if ($cropped === false) {
            return $image;
        }

        imagedestroy($image);
        $this->line("  trimmed source to {$cropWidth}x{$cropHeight}");

        return $cropped;
    }

    private function rowIsUniform(int $width, int $y, callable $matchesBackground): bool
    {
        for ($x = 0; $x < $width; $x++) {
            if (!$matchesBackground($x, $y)) {
                return false;
            }
        }

        return true;
    }

    private function columnIsUniform(int $height, int $x, callable $matchesBackground): bool
    {
        for ($y = 0; $y < $height; $y++) {
            if (!$matchesBackground($x, $y)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Draw the source centred on an opaque white square, scaled to fit.
     */
    private function render(\GdImage $source, int $size, float $scale): \GdImage
    {
        $canvas = imagecreatetruecolor($size, $size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $box = (int) round($size * $scale);

        $ratio = min($box / $sourceWidth, $box / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $ratio));
        $height = max(1, (int) round($sourceHeight * $ratio));

        imagecopyresampled(
            $canvas,
            $source,
            (int) round(($size - $width) / 2),
            (int) round(($size - $height) / 2),
            0,
            0,
            $width,
            $height,
            $sourceWidth,
            $sourceHeight
        );

        return $canvas;
    }
}
