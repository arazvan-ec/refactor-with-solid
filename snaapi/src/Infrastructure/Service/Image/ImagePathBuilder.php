<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Image;

/**
 * Builds image paths following the AWS S3 directory structure.
 *
 * Single Responsibility: Construct paths/URLs base for images using
 * the bucket's subdirectory structure (3-char segments from filename).
 *
 * This class follows the SRP principle by focusing solely on path construction
 * logic without concerning itself with URL generation or filter application.
 *
 * @author SNAAPI Team
 */
class ImagePathBuilder
{
    /**
     * @param string $awsBucket The AWS S3 bucket name
     */
    public function __construct(
        private readonly string $awsBucket
    ) {
    }

    /**
     * Builds the full path for an original image.
     *
     * The path structure follows: bucket/directory/XXX/YYY/ZZZ/filename
     * where XXX, YYY, ZZZ are 3-character segments from the filename.
     *
     * Example:
     * - fileName: "abc123xyz.jpg"
     * - directory: "original"
     * - Result: "bucket/original/abc/123/xyz/abc123xyz.jpg"
     *
     * @param string $fileName The image filename
     * @param string $directory The directory type (e.g., 'original', 'journalist')
     *
     * @return string The constructed full path
     */
    public function buildOriginalPath(string $fileName, string $directory): string
    {
        $path1 = substr($fileName, 0, 3);
        $path2 = substr($fileName, 3, 3);
        $path3 = substr($fileName, 6, 3);

        return "{$this->awsBucket}/{$directory}/{$path1}/{$path2}/{$path3}/{$fileName}";
    }

    /**
     * Builds the path for a cropped image.
     *
     * Uses the same subdirectory structure as original images,
     * with 'original' as the default directory for cropped content.
     *
     * @param string $fileName The image filename
     *
     * @return string The constructed path for cropped image
     */
    public function buildCroppedPath(string $fileName): string
    {
        return $this->buildOriginalPath($fileName, 'original');
    }

    /**
     * Builds the path for a journalist profile image.
     *
     * Journalist images are stored in a dedicated 'journalist' directory
     * following the same 3-segment subdirectory structure.
     *
     * @param string $fileName The journalist image filename
     *
     * @return string The constructed path for journalist image
     */
    public function buildJournalistPath(string $fileName): string
    {
        return $this->buildOriginalPath($fileName, 'journalist');
    }
}
