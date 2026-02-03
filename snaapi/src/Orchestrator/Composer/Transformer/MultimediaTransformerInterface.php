<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

/**
 * Interface for transforming multimedia entities.
 *
 * Responsible for converting Multimedia domain entities
 * into their API response representation.
 *
 * @author SNAAPI Refactoring Team
 */
interface MultimediaTransformerInterface
{
    /**
     * Transforms a multimedia entity into array representation.
     *
     * @param object $multimedia The multimedia entity (Photo, Video, or Widget)
     *
     * @return array<string, mixed> Multimedia data ready for JSON response
     */
    public function transform(object $multimedia): array;
}
