<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Contract;

use Ec\Editorial\Domain\Model\Opening\Photo;
use Ec\Editorial\Domain\Model\Opening\Video;

/**
 * Transformer interface for media content (photos and videos).
 *
 * This segregated interface follows ISP by defining specific contracts
 * for multimedia transformations without forcing implementations to
 * support both types if not needed.
 *
 * @author SOLID Refactoring Team
 */
interface MediaTransformerInterface
{
    /**
     * Transforms a Photo domain object into an API-ready array.
     *
     * Includes thumbnail URLs, alt text, credits, and aspect ratios.
     *
     * @param Photo $photo The photo to transform
     *
     * @return array<string, mixed> The transformed photo data
     */
    public function transformPhoto(Photo $photo): array;

    /**
     * Transforms a Video domain object into an API-ready array.
     *
     * Includes video URL, thumbnail, duration, and provider metadata.
     *
     * @param Video $video The video to transform
     *
     * @return array<string, mixed> The transformed video data
     */
    public function transformVideo(Video $video): array;
}
