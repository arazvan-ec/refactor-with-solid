<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor\Strategy;

use App\Orchestrator\Extractor\IdExtractorStrategyInterface;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Signature;

/**
 * Extracts all signature (journalist alias) IDs from an editorial.
 *
 * Signatures represent the authors/journalists who wrote the editorial.
 * This extractor collects all alias IDs for parallel journalist data fetching.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class SignatureIdExtractor implements IdExtractorStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * @return array<int, string> Array of journalist alias IDs
     */
    public function extract(Editorial $editorial): array
    {
        $signatureIds = [];

        /** @var Signature $signature */
        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $signatureIds[] = $signature->id()->id();
        }

        return $signatureIds;
    }

    /**
     * {@inheritDoc}
     */
    public function getManifestKey(): string
    {
        return 'signatureIds';
    }
}
