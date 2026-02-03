<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Multimedia\Domain\Model\Multimedia as MultimediaModel;
use Ec\Multimedia\Domain\Model\Photo\Photo;

/**
 * Service interface for multimedia processing operations.
 *
 * This service handles:
 * - Extraction of MultimediaId from different Multimedia types
 * - Generation of image shots in multiple sizes using Thumbor
 * - Configuration of standard image sizes for responsive layouts
 *
 * @author SNAAPI Team
 */
interface MultimediaServiceInterface
{
    /**
     * Extracts the MultimediaId from a Multimedia object.
     *
     * Supports PhotoExist, Video, and Widget types. For Video and Widget,
     * attempts to extract the ID from their associated photo.
     *
     * @param Multimedia $multimedia The multimedia object to extract ID from
     *
     * @return MultimediaId|null The extracted ID or null if not available
     */
    public function getMultimediaId(Multimedia $multimedia): ?MultimediaId;

    /**
     * Generates landscape shots in multiple sizes from a MultimediaModel.
     *
     * Uses the ARTICLE_4_3 clipping type and generates images for all
     * configured sizes (202w, 144w, 128w).
     *
     * @param MultimediaModel $multimedia The multimedia model containing file and clippings
     *
     * @return array<string, string> Array of shot URLs keyed by size ('202w', '144w', '128w')
     */
    public function getShotsLandscape(MultimediaModel $multimedia): array;

    /**
     * Generates landscape shots from media opening data structure.
     *
     * Similar to getShotsLandscape but accepts a specific array structure
     * containing both opening and resource data.
     *
     * @param array{opening: MultimediaModel\MultimediaPhoto, resource: Photo} $multimediaOpening
     *                                                                                              The multimedia opening data containing clipping info and photo resource
     *
     * @return array<string, string> Array of shot URLs keyed by size ('202w', '144w', '128w')
     */
    public function getShotsLandscapeFromMedia(array $multimediaOpening): array;

    /**
     * Returns the configured image sizes for responsive layouts.
     *
     * @return array<string, array<string, string>> Array of sizes with width/height configuration
     *                                              Example: ['202w' => ['width' => '202', 'height' => '152'], ...]
     */
    public function sizes(): array;
}
