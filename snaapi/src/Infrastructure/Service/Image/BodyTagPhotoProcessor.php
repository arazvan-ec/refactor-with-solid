<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Image;

use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureDefault;
use Snaapi\Infrastructure\Service\Image\ImageSizeConfigurationInterface;

/**
 * Processes body tag photos to generate responsive image variants.
 *
 * This class has a single responsibility: orchestrating the generation of
 * responsive image shots for body tag pictures. It follows the SRP by
 * delegating aspect ratio mapping and size configuration to specialized classes.
 *
 * It uses composition to combine:
 * - AspectRatioMapper: Maps orientations to aspect ratios
 * - ImageSizeConfigurationInterface: Provides size configurations
 * - Thumbor: Generates cropped image URLs
 *
 * @author SNAAPI Team
 */
final class BodyTagPhotoProcessor
{
    private const WIDTH = 'width';
    private const HEIGHT = 'height';

    /**
     * Creates a new body tag photo processor.
     *
     * @param ImageSizeConfigurationInterface $sizeConfiguration Provides responsive size configurations
     * @param AspectRatioMapper $aspectRatioMapper Maps orientations to aspect ratios
     * @param Thumbor $thumbor Generates cropped image URLs via Thumbor service
     */
    public function __construct(
        private readonly ImageSizeConfigurationInterface $sizeConfiguration,
        private readonly AspectRatioMapper $aspectRatioMapper,
        private readonly Thumbor $thumbor,
    ) {
    }

    /**
     * Processes a body tag photo and generates responsive image shots.
     *
     * Takes photo metadata from GraphQL resolve data and generates a complete
     * set of responsive image URLs for all viewport sizes based on the
     * picture's orientation and crop coordinates.
     *
     * @param array<string, mixed> $resolveData GraphQL resolve data containing photo information
     * @param BodyTagPictureDefault $bodyTagPicture The body tag picture entity with crop coordinates
     *
     * @return array<string, string> Map of viewport sizes to image URLs (e.g., ['1440w' => 'https://...'])
     *                               Empty array if photo file is not found
     */
    public function processBodyPhoto(array $resolveData, BodyTagPictureDefault $bodyTagPicture): array
    {
        $photoFile = $this->extractPhotoFile($resolveData, $bodyTagPicture);

        if (empty($photoFile)) {
            return [];
        }

        return $this->generateResponsiveSizes($photoFile, $bodyTagPicture);
    }

    /**
     * Generates responsive image URLs for all viewport sizes.
     *
     * Creates cropped image variants for each viewport size defined in the
     * configuration for the picture's aspect ratio. Each variant uses the
     * crop coordinates from the body tag picture.
     *
     * @param string $fileName Original photo file name
     * @param BodyTagPictureDefault $bodyTagPicture Body tag with orientation and crop coordinates
     *
     * @return array<string, string> Map of viewport sizes to cropped image URLs
     */
    public function generateResponsiveSizes(string $fileName, BodyTagPictureDefault $bodyTagPicture): array
    {
        $aspectRatio = $this->aspectRatioMapper->mapToStandard($bodyTagPicture->orientation());
        $sizesForRatio = $this->sizeConfiguration->getSizesByAspectRatio($aspectRatio);

        $responsiveShots = [];

        foreach ($sizesForRatio as $viewport => $dimensions) {
            $responsiveShots[$viewport] = $this->thumbor->retriveCropBodyTagPicture(
                $fileName,
                $dimensions[self::WIDTH],
                $dimensions[self::HEIGHT],
                $bodyTagPicture->topX(),
                $bodyTagPicture->topY(),
                $bodyTagPicture->bottomX(),
                $bodyTagPicture->bottomY()
            );
        }

        return $responsiveShots;
    }

    /**
     * Extracts photo file name from GraphQL resolve data.
     *
     * Searches for the photo file associated with the body tag picture ID
     * in the pre-fetched photo data from GraphQL resolvers.
     *
     * @param array<string, mixed> $resolveData GraphQL resolve data
     * @param BodyTagPictureDefault $bodyTagPicture Body tag picture entity
     *
     * @return string Photo file name, or empty string if not found
     */
    private function extractPhotoFile(array $resolveData, BodyTagPictureDefault $bodyTagPicture): string
    {
        if (!isset($resolveData['photoFromBodyTags'])) {
            return '';
        }

        /** @var array<string, object> $photoFromBodyTags */
        $photoFromBodyTags = $resolveData['photoFromBodyTags'];
        $pictureId = $bodyTagPicture->id()->id();

        if (!isset($photoFromBodyTags[$pictureId])) {
            return '';
        }

        $photo = $photoFromBodyTags[$pictureId];

        // @phpstan-ignore method.nonObject
        return method_exists($photo, 'file') ? $photo->file() : '';
    }

    /**
     * Validates if a body tag picture has valid crop coordinates.
     *
     * Checks if all required crop coordinates (topX, topY, bottomX, bottomY)
     * are set and form a valid rectangular area.
     *
     * @param BodyTagPictureDefault $bodyTagPicture Body tag picture to validate
     *
     * @return bool True if coordinates are valid, false otherwise
     */
    public function hasValidCropCoordinates(BodyTagPictureDefault $bodyTagPicture): bool
    {
        $topX = $bodyTagPicture->topX();
        $topY = $bodyTagPicture->topY();
        $bottomX = $bodyTagPicture->bottomX();
        $bottomY = $bodyTagPicture->bottomY();

        return $topX !== null
            && $topY !== null
            && $bottomX !== null
            && $bottomY !== null
            && $bottomX > $topX
            && $bottomY > $topY;
    }

    /**
     * Generates a single cropped image for specific dimensions.
     *
     * Helper method for generating a single image variant with custom dimensions.
     * Useful for special cases or one-off image requirements.
     *
     * @param string $fileName Original photo file name
     * @param BodyTagPictureDefault $bodyTagPicture Body tag with crop coordinates
     * @param string $width Desired image width
     * @param string $height Desired image height
     *
     * @return string Cropped image URL
     */
    public function generateSingleSize(
        string $fileName,
        BodyTagPictureDefault $bodyTagPicture,
        string $width,
        string $height
    ): string {
        return $this->thumbor->retriveCropBodyTagPicture(
            $fileName,
            $width,
            $height,
            $bodyTagPicture->topX(),
            $bodyTagPicture->topY(),
            $bodyTagPicture->bottomX(),
            $bodyTagPicture->bottomY()
        );
    }
}
