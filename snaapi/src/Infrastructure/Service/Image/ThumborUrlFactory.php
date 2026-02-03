<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Image;

use Snaapi\Infrastructure\Service\Image\ImageProcessorInterface;
use Thumbor\Url\BuilderFactory;

/**
 * Factory for generating Thumbor image URLs.
 *
 * This class coordinates ImagePathBuilder and ImageFilterApplier to generate
 * complete Thumbor URLs. It implements ImageProcessorInterface, providing
 * a unified API for image processing operations.
 *
 * Responsibilities:
 * - Coordinate path building and filter application
 * - Implement the ImageProcessorInterface contract
 * - Manage Thumbor BuilderFactory lifecycle
 *
 * This class follows the Facade pattern, providing a simplified interface
 * to the complex subsystem of path building, URL generation, and filtering.
 *
 * @author SNAAPI Team
 */
class ThumborUrlFactory implements ImageProcessorInterface
{
    /**
     * Default file extension when none can be detected.
     */
    private const DEFAULT_EXTENSION = 'jpg';

    /**
     * @param BuilderFactory $thumborFactory Thumbor URL builder factory
     * @param ImagePathBuilder $pathBuilder Builds image paths with S3 structure
     * @param ImageFilterApplier $filterApplier Applies visual filters to URLs
     */
    public function __construct(
        private readonly BuilderFactory $thumborFactory,
        private readonly ImagePathBuilder $pathBuilder,
        private readonly ImageFilterApplier $filterApplier
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function generateUrl(string $path, array $options = []): string
    {
        $directory = $options['directory'] ?? 'original';
        $fullPath = $this->pathBuilder->buildOriginalPath($path, $directory);

        $builder = $this->thumborFactory->url($fullPath);

        if (isset($options['width'], $options['height'])) {
            $builder->resize((int) $options['width'], (int) $options['height']);
        }

        if (isset($options['filters']) && is_array($options['filters'])) {
            $this->filterApplier->applyFilters($builder, $options['filters']);
        }

        return (string) $builder;
    }

    /**
     * {@inheritDoc}
     */
    public function applyFilters(string $url, array $filters): string
    {
        // For applying filters to an existing URL, we need to rebuild it
        // This is a limitation of the current Thumbor library design
        // In practice, filters should be applied during URL generation
        $builder = $this->thumborFactory->url($url);
        $this->filterApplier->applyFilters($builder, $filters);

        return (string) $builder;
    }

    /**
     * {@inheritDoc}
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
    ): string {
        $extension = $this->extractExtension($path);
        $fullPath = $this->pathBuilder->buildCroppedPath($path);

        $builder = $this->thumborFactory->url($fullPath);
        $builder->resize($width, $height);
        $builder->crop($topX, $topY, $bottomX, $bottomY);

        // Apply default filters for cropped images
        $defaultFilters = [
            'fill' => 'white',
            'format' => $extension,
        ];

        // Merge with any additional filters from options
        $filters = array_merge($defaultFilters, $options['filters'] ?? []);
        $this->filterApplier->applyFilters($builder, $filters);

        return (string) $builder;
    }

    /**
     * {@inheritDoc}
     */
    public function generateJournalistImageUrl(string $fileName): string
    {
        $fullPath = $this->pathBuilder->buildJournalistPath($fileName);
        $builder = $this->thumborFactory->url($fullPath);

        return (string) $builder;
    }

    /**
     * Extracts the file extension from a filename.
     *
     * @param string $fileName The filename to extract extension from
     *
     * @return string The extracted extension or default extension
     */
    private function extractExtension(string $fileName): string
    {
        $pattern = '/^.*\.(?<extension>.*)$/m';

        if (preg_match($pattern, $fileName, $matches) && !empty($matches['extension'])) {
            return $matches['extension'];
        }

        return self::DEFAULT_EXTENSION;
    }
}
