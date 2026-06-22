<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\GrapesJS\Upload;

use Exception;
use GdImage;

final class ResizeImage
{
    public const MODE_EXACT = 'exact';
    public const MODE_MAX_WIDTH = 'maxwidth';
    public const MODE_MAX_HEIGHT = 'maxheight';
    public const MODE_PROPORTIONAL = 'proportional';
    public const MODE_AUTO = 'auto';

    private string $mimeType;

    private GdImage $sourceImage;

    private ?GdImage $resizedImage = null;

    private int $originalWidth;

    private int $originalHeight;

    /**
     * @throws Exception
     */
    public function __construct(private readonly string $filename)
    {
        if (!is_file($filename)) {
            throw new Exception(sprintf(
                'Image file "%s" does not exist.',
                $filename
            ));
        }

        $this->loadImage();
    }

    /**
     * @throws Exception
     */
    private function loadImage(): void
    {
        $imageInfo = getimagesize($this->filename);

        if ($imageInfo === false) {
            throw new Exception('Invalid image file.');
        }

        $this->mimeType = $imageInfo['mime'];

        $this->sourceImage = match ($this->mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($this->filename),
            'image/png'               => imagecreatefrompng($this->filename),
            'image/gif'               => imagecreatefromgif($this->filename),
            default => throw new Exception(
                sprintf('Unsupported image type: %s', $this->mimeType)
            ),
        };

        $this->originalWidth = imagesx($this->sourceImage);
        $this->originalHeight = imagesy($this->sourceImage);
    }

    /**
     * Resize the image.
     */
    public function resize(
        int $width,
        int $height,
        string $mode = self::MODE_AUTO
    ): self {
        [$targetWidth, $targetHeight] = $this->calculateDimensions(
            $width,
            $height,
            $mode
        );

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (
            $this->mimeType === 'image/png' ||
            $this->mimeType === 'image/gif'
        ) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);

            $transparent = imagecolorallocatealpha(
                $canvas,
                255,
                255,
                255,
                127
            );

            imagefilledrectangle(
                $canvas,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $transparent
            );
        }

        imagecopyresampled(
            $canvas,
            $this->sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $this->originalWidth,
            $this->originalHeight
        );

        if ($this->resizedImage !== null) {
            imagedestroy($this->resizedImage);
        }

        $this->resizedImage = $canvas;

        return $this;
    }

    /**
     * Save the resized image.
     *
     * @throws Exception
     */
    public function save(string $destination, int $quality = 90): void
    {
        if ($this->resizedImage === null) {
            throw new Exception('No resized image available.');
        }

        $success = match ($this->mimeType) {
            'image/jpeg', 'image/jpg' =>
                imagejpeg(
                    $this->resizedImage,
                    $destination,
                    max(0, min(100, $quality))
                ),

            'image/png' =>
                imagepng(
                    $this->resizedImage,
                    $destination,
                    $this->convertPngQuality($quality)
                ),

            'image/gif' =>
                imagegif(
                    $this->resizedImage,
                    $destination
                ),

            default => false,
        };

        if (!$success) {
            throw new Exception(
                sprintf('Failed to save image to "%s".', $destination)
            );
        }
    }

    private function calculateDimensions(
        int $width,
        int $height,
        string $mode
    ): array {
        return match (strtolower($mode)) {
            self::MODE_EXACT => [$width, $height],

            self::MODE_MAX_WIDTH => [
                $width,
                $this->heightFromWidth($width),
            ],

            self::MODE_MAX_HEIGHT => [
                $this->widthFromHeight($height),
                $height,
            ],

            self::MODE_PROPORTIONAL => $this->proportionalDimensions(
                $width,
                $height
            ),

            default => $this->automaticDimensions(
                $width,
                $height
            ),
        };
    }

    private function proportionalDimensions(
        int $maxWidth,
        int $maxHeight
    ): array {
        $ratio = $this->originalWidth / $this->originalHeight;

        if (($maxWidth / $maxHeight) > $ratio) {
            return [
                (int) round($maxHeight * $ratio),
                $maxHeight,
            ];
        }

        return [
            $maxWidth,
            (int) round($maxWidth / $ratio),
        ];
    }

    private function automaticDimensions(
        int $maxWidth,
        int $maxHeight
    ): array {
        if (
            $this->originalWidth <= $maxWidth &&
            $this->originalHeight <= $maxHeight
        ) {
            return [
                $this->originalWidth,
                $this->originalHeight,
            ];
        }

        return $this->proportionalDimensions(
            $maxWidth,
            $maxHeight
        );
    }

    private function heightFromWidth(int $width): int
    {
        return (int) round(
            ($this->originalHeight / $this->originalWidth) * $width
        );
    }

    private function widthFromHeight(int $height): int
    {
        return (int) round(
            ($this->originalWidth / $this->originalHeight) * $height
        );
    }

    private function convertPngQuality(int $quality): int
    {
        $quality = max(0, min(100, $quality));

        return 9 - (int) round(($quality / 100) * 9);
    }

    public function __destruct()
    {
        if (isset($this->sourceImage)) {
            imagedestroy($this->sourceImage);
        }

        if ($this->resizedImage !== null) {
            imagedestroy($this->resizedImage);
        }
    }
}
