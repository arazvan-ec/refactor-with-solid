<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Contract;

use Ec\Widget\Domain\Model\Widget;

/**
 * Null Object implementation for widget transformation.
 *
 * This class implements the Null Object Pattern to avoid LSP violations
 * and eliminate null checks throughout the codebase.
 *
 * Instead of returning empty arrays or throwing exceptions when no specific
 * transformer is available, this transformer provides a safe default behavior.
 *
 * Use this when:
 * - A widget type has no specific transformer implementation
 * - You want to gracefully handle unknown widget types
 * - Testing scenarios requiring no-op transformations
 *
 * @author SOLID Refactoring Team
 */
final readonly class NullWidgetTransformer implements WidgetTransformerInterface
{
    /**
     * Transforms a widget into a minimal default structure.
     *
     * Returns a safe default structure that won't break API consumers
     * but signals that no specific transformation was performed.
     *
     * @param Widget $widget The widget to transform (not used in this implementation)
     *
     * @return array<string, mixed> Empty array or minimal structure
     */
    public function transformWidget(Widget $widget): array
    {
        // Return empty array as safe default
        // Alternative: return ['type' => 'unknown', 'data' => null];
        return [];
    }
}
