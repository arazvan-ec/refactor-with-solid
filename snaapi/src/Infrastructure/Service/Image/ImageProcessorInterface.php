<?php

declare(strict_types=1);

namespace Snaapi\Infrastructure\Service\Image;

/**
 * Interface for image processing operations.
 *
 * This interface abstracts the underlying image processing service (e.g., Thumbor)
 * allowing different implementations while maintaining a consistent API.
 * Follows the Dependency Inversion Principle (SOLID) by depending on abstractions.
 *
 * @author SNAAPI Team
 */
interface ImageProcessorInterface
{
    /**
     * Generates a URL for an image with specified options.
     *
     * This method constructs a URL for accessing an image through the processing service.
     * Options may include dimensions, quality, format, and other processing parameters.
     *
     * @param string $path The path to the source image
     * @param array<string, mixed> $options Processing options such as:
     *                                      - 'width': int - Target width in pixels
     *                                      - 'height': int - Target height in pixels
     *                                      - 'directory': string - Source directory (e.g., 'journalist', 'original')
     *                                      - 'format': string - Output format (e.g., 'jpg', 'png', 'webp')
     *                                      - 'quality': int - Image quality (1-100)
     *
     * @return string The generated image URL
     */
    public function generateUrl(string $path, array $options = []): string;

    /**
     * Applies visual filters to an image URL.
     *
     * Filters modify the appearance of the image (e.g., fill color, format conversion,
     * brightness, contrast). This method returns a modified URL with filters applied.
     *
     * @param string $url The base image URL
     * @param array<string, mixed> $filters Array of filters to apply:
     *                                      - 'fill': string - Fill color for transparent areas
     *                                      - 'format': string - Output format conversion
     *                                      - 'quality': int - Image quality adjustment
     *                                      - 'blur': int - Blur intensity
     *                                      - 'brightness': int - Brightness adjustment
     *                                      - 'contrast': int - Contrast adjustment
     *
     * @return string The URL with filters applied
     */
    public function applyFilters(string $url, array $filters): string;

    /**
     * Generates a cropped and resized image URL.
     *
     * This method creates a URL for an image with specific crop coordinates and target dimensions.
     * The crop coordinates define the region of interest in the source image.
     *
     * @param string $path The path to the source image
     * @param int $width Target width in pixels
     * @param int $height Target height in pixels
     * @param int $topX Top-left X coordinate for crop
     * @param int $topY Top-left Y coordinate for crop
     * @param int $bottomX Bottom-right X coordinate for crop
     * @param int $bottomY Bottom-right Y coordinate for crop
     * @param array<string, mixed> $options Additional processing options
     *
     * @return string The generated image URL with crop and resize applied
     */
    public function generateCroppedUrl(
        string $path,
        int $width,
        int $height,
        int $topX,
        int $topY,
        int $bottomX,
        int $bottomY,
        array $options = []
    ): string;

    /**
     * Generates a URL for a journalist image.
     *
     * Convenience method for creating journalist profile images.
     * This abstracts the specific directory structure and defaults for journalist photos.
     *
     * @param string $fileName The image filename
     *
     * @return string The generated journalist image URL
     */
    public function generateJournalistImageUrl(string $fileName): string;
}
