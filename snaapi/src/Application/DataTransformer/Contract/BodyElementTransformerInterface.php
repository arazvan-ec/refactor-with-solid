<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Contract;

use Ec\Editorial\Domain\Model\Body\BodyElement;

/**
 * Transformer interface specific to body elements.
 *
 * This segregated interface follows ISP by defining a contract only for
 * transformers that work with BodyElement domain objects.
 *
 * Body elements include: Paragraph, SubHead, BodyTagPicture, BodyTagVideo,
 * BodyTagHtml, Lists, etc.
 *
 * @author SOLID Refactoring Team
 */
interface BodyElementTransformerInterface
{
    /**
     * Transforms a BodyElement domain object into an API-ready array.
     *
     * @param BodyElement $element The body element to transform
     *
     * @return array<string, mixed> The transformed element data
     */
    public function transform(BodyElement $element): array;
}
