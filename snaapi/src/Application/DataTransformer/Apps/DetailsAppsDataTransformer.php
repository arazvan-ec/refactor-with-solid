<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps;

use App\Application\DataTransformer\Apps\Strategy\CompositeDetailsTransformer;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

/**
 * Backward compatibility wrapper for CompositeDetailsTransformer.
 *
 * This class maintains the original DetailsAppsDataTransformer interface
 * while delegating all transformation logic to the new CompositeDetailsTransformer
 * that follows SOLID principles and uses the Strategy pattern.
 *
 * Responsibilities:
 * - Maintain backward compatibility with existing code
 * - Delegate all transformation logic to CompositeDetailsTransformer
 * - Provide the same public API as the original implementation
 *
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 * @author Claude AI <assistant@anthropic.com> (SOLID refactoring)
 */
class DetailsAppsDataTransformer implements AppsDataTransformer
{
    public function __construct(
        private readonly CompositeDetailsTransformer $compositeTransformer,
    ) {
    }

    /**
     * @param Tag[] $tags
     */
    public function write(
        Editorial $editorial,
        Section $section,
        array $tags,
    ): self {
        $this->compositeTransformer->write($editorial, $section, $tags);

        return $this;
    }

    public function read(): array
    {
        return $this->compositeTransformer->read();
    }
}
