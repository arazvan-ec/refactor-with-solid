<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Image;

use Thumbor\Url\Builder;

/**
 * Applies visual filters to image URLs using Thumbor.
 *
 * Single Responsibility: Apply visual filters (fill color, format conversion,
 * brightness, contrast, etc.) to Thumbor Builder instances.
 *
 * This class is responsible solely for filter application logic,
 * separated from path construction and URL generation concerns.
 *
 * @author SNAAPI Team
 */
class ImageFilterApplier
{
    /**
     * List of supported filter types by Thumbor.
     */
    private const SUPPORTED_FILTERS = [
        'fill',
        'format',
        'quality',
        'blur',
        'brightness',
        'contrast',
        'sharpen',
        'grayscale',
    ];

    /**
     * Applies a single filter to a Thumbor URL Builder.
     *
     * @param Builder $builder The Thumbor URL builder instance
     * @param string $filterName The name of the filter to apply
     * @param mixed $filterValue The value/parameter for the filter
     *
     * @return Builder The builder with filter applied (for method chaining)
     */
    public function applyFilter(Builder $builder, string $filterName, mixed $filterValue): Builder
    {
        $builder->addFilter($filterName, $filterValue);

        return $builder;
    }

    /**
     * Applies multiple filters to a Thumbor URL Builder.
     *
     * Filters are applied in the order they appear in the array.
     *
     * @param Builder $builder The Thumbor URL builder instance
     * @param array<string, mixed> $filters Array of filters where key is filter name
     *                                      and value is the filter parameter
     *
     * Example:
     * [
     *     'fill' => 'white',
     *     'format' => 'jpg',
     *     'quality' => 85
     * ]
     *
     * @return Builder The builder with all filters applied
     */
    public function applyFilters(Builder $builder, array $filters): Builder
    {
        foreach ($filters as $filterName => $filterValue) {
            $this->applyFilter($builder, $filterName, $filterValue);
        }

        return $builder;
    }

    /**
     * Returns the list of filters supported by this applier.
     *
     * @return array<int, string> Array of supported filter names
     */
    public function getSupportedFilters(): array
    {
        return self::SUPPORTED_FILTERS;
    }

    /**
     * Checks if a given filter is supported.
     *
     * @param string $filterName The filter name to check
     *
     * @return bool True if the filter is supported, false otherwise
     */
    public function isFilterSupported(string $filterName): bool
    {
        return in_array($filterName, self::SUPPORTED_FILTERS, true);
    }
}
