<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\Multimedia\Video;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia as MultimediaModel;
use Ec\Multimedia\Domain\Model\Photo\Photo;

/**
 * Service for multimedia processing operations.
 *
 * Handles extraction of multimedia IDs, generation of responsive image shots,
 * and configuration of standard image sizes for different display contexts.
 *
 * This service replaces the MultimediaTrait, following the Single Responsibility
 * Principle by encapsulating multimedia processing logic in a dedicated service
 * that can be injected, tested, and mocked independently.
 *
 * @author SNAAPI Team
 */
final class MultimediaService implements MultimediaServiceInterface
{
    /**
     * Standard image sizes for responsive layouts.
     *
     * @var array<string, array<string, string>>
     */
    private array $sizes = [
        '202w' => [
            'width' => '202',
            'height' => '152',
        ],
        '144w' => [
            'width' => '144',
            'height' => '108',
        ],
        '128w' => [
            'width' => '128',
            'height' => '96',
        ],
    ];

    /**
     * @param Thumbor $thumbor Service for generating image URLs with transformations
     */
    public function __construct(
        private readonly Thumbor $thumbor
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getMultimediaId(Multimedia $multimedia): ?MultimediaId
    {
        $multimediaId = null;

        if ($multimedia instanceof PhotoExist) {
            $multimediaId = $multimedia->id();
        }

        if (
            ($multimedia instanceof Video || $multimedia instanceof Widget)
            && ($multimedia->photo() instanceof PhotoExist)
        ) {
            $multimediaId = $multimedia->photo()->id();
        }

        return $multimediaId;
    }

    /**
     * {@inheritDoc}
     */
    public function getShotsLandscape(MultimediaModel $multimedia): array
    {
        $shots = [];
        $clippings = $multimedia->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_ARTICLE_4_3);

        foreach ($this->sizes() as $type => $size) {
            $shots[$type] = $this->thumbor->retriveCropBodyTagPicture(
                $multimedia->file(),
                $size['width'],
                $size['height'],
                $clipping->topLeftX(),
                $clipping->topLeftY(),
                $clipping->bottomRightX(),
                $clipping->bottomRightY()
            );
        }

        return $shots;
    }

    /**
     * {@inheritDoc}
     */
    public function getShotsLandscapeFromMedia(array $multimediaOpening): array
    {
        $shots = [];
        $clippings = $multimediaOpening['opening']->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_ARTICLE_4_3);

        foreach ($this->sizes() as $type => $size) {
            $shots[$type] = $this->thumbor->retriveCropBodyTagPicture(
                $multimediaOpening['resource']->file(),
                $size['width'],
                $size['height'],
                $clipping->topLeftX(),
                $clipping->topLeftY(),
                $clipping->bottomRightX(),
                $clipping->bottomRightY()
            );
        }

        return $shots;
    }

    /**
     * {@inheritDoc}
     */
    public function sizes(): array
    {
        return $this->sizes;
    }
}
