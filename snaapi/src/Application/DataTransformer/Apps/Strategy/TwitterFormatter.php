<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Strategy;

use Ec\Editorial\Domain\Model\Editorial;

/**
 * Formats editorial data for Twitter Card metadata.
 *
 * Responsible for:
 * - Extracting Twitter-specific metadata from Editorial
 * - Formatting titles and descriptions for Twitter cards
 * - Generating Twitter card type information
 *
 * This transformer prepares data for Twitter meta tags like:
 * - twitter:card
 * - twitter:title
 * - twitter:description
 * - twitter:image
 *
 * @author Claude AI <assistant@anthropic.com>
 */
final class TwitterFormatter implements TransformerStrategyInterface
{
    /**
     * Transforms editorial data into Twitter Card format.
     *
     * Expected input data structure:
     * - 'editorial': Editorial - The editorial domain model
     *
     * @param array<string, mixed> $data Must contain 'editorial' key with Editorial object
     *
     * @return array{
     *     card: string,
     *     title: string,
     *     description: string,
     *     url: string
     * } Twitter card metadata
     */
    public function transform(array $data): array
    {
        if (!isset($data['editorial']) || !$data['editorial'] instanceof Editorial) {
            return [];
        }

        $editorial = $data['editorial'];

        // Use mobile title for Twitter if available, fallback to main title
        $title = $editorial->editorialTitles()->mobileTitle()
            ?: $editorial->editorialTitles()->title();

        // Use lead as description
        $description = $editorial->lead();

        // Use editorial URL if provided
        $url = $data['url'] ?? '';

        return [
            'card' => 'summary_large_image',
            'title' => $this->sanitizeText($title),
            'description' => $this->sanitizeText($description),
            'url' => $url,
        ];
    }

    /**
     * Sanitizes text for Twitter cards by removing HTML and truncating.
     *
     * @param string $text The text to sanitize
     *
     * @return string Sanitized text suitable for Twitter metadata
     */
    private function sanitizeText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Trim whitespace
        $text = trim($text);

        // Twitter recommends max 200 characters for description
        if (strlen($text) > 200) {
            $text = substr($text, 0, 197) . '...';
        }

        return $text;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'twitter';
    }
}
