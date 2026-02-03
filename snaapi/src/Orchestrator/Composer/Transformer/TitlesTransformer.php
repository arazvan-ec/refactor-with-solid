<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use Ec\Editorial\Domain\Model\Editorial;

/**
 * Transforms editorial titles into normalized format.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on title extraction and normalization.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class TitlesTransformer implements TitlesTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function transform(Editorial $editorial): array
    {
        $titles = $editorial->editorialTitles();

        return [
            'web' => $titles->title() ?? '',
            'mobile' => $titles->mobileTitle() ?? '',
            'seo' => $titles->title() ?? '', // SEO uses web title as fallback
        ];
    }
}
