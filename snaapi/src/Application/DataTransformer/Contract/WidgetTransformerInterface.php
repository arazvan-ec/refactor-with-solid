<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Contract;

use Ec\Widget\Domain\Model\Widget;

/**
 * Transformer interface for widgets (embeddable content).
 *
 * This segregated interface follows ISP by defining a minimal contract
 * for widget transformations.
 *
 * Widgets include: Twitter embeds, Instagram posts, YouTube videos,
 * HTML iframes, and custom interactive content.
 *
 * @author SOLID Refactoring Team
 */
interface WidgetTransformerInterface
{
    /**
     * Transforms a Widget domain object into an API-ready array.
     *
     * The transformation includes widget type, URL, embed parameters,
     * and aspect ratio calculations.
     *
     * @param Widget $widget The widget to transform
     *
     * @return array<string, mixed> The transformed widget data
     */
    public function transformWidget(Widget $widget): array;
}
