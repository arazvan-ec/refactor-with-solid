<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Infrastructure\Service\MultimediaServiceInterface;
use Ec\Multimedia\Domain\Model\Multimedia;

/**
 * Transforms multimedia entities into API response format.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on multimedia data transformation.
 *
 * Delegates to MultimediaService for complex multimedia processing
 * (shots, crops, etc.) following the Open/Closed Principle.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class MultimediaTransformer implements MultimediaTransformerInterface
{
    public function __construct(
        private MultimediaServiceInterface $multimediaService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transform(object $multimedia): array
    {
        if (!$multimedia instanceof Multimedia) {
            return [];
        }

        return [
            'id' => $multimedia->id()->id(),
            'type' => $multimedia->multimediaType()->type(),
            'shots' => $this->multimediaService->getShotsLandscape($multimedia),
            'credits' => $multimedia->credits() ?? '',
            'caption' => $multimedia->description() ?? '',
        ];
    }
}
