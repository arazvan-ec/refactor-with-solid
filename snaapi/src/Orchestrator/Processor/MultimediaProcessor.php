<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Processor;

use App\Application\DataTransformer\Apps\Media\MediaDataTransformerHandler;
use App\Application\DataTransformer\Apps\MultimediaDataTransformer;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use Ec\Infrastructure\Client\Exceptions\InvalidBodyException;
use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;

/**
 * Processes multimedia assets associated with the editorial.
 *
 * This processor handles all multimedia-related data:
 * - Main editorial multimedia (photos, videos, widgets)
 * - Opening multimedia
 * - Meta image (fallback)
 * - Photos from body tags (BodyTagPicture, BodyTagMembershipCard)
 *
 * Responsibilities:
 * - Fetch multimedia asynchronously for performance
 * - Process opening multimedia via specialized handlers
 * - Extract and fetch photos embedded in body tags
 * - Transform multimedia data for API response
 * - Handle multiple multimedia types (photos, videos, widgets)
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class MultimediaProcessor implements ProcessorInterface
{
    private const ASYNC = true;
    private const UNWRAPPED = true;

    public function __construct(
        private QueryMultimediaClient $queryMultimediaClient,
        private QueryMultimediaOpeningClient $queryMultimediaOpeningClient,
        private MultimediaOrchestratorHandler $multimediaTypeOrchestratorHandler,
        private MediaDataTransformerHandler $mediaDataTransformerHandler,
        private MultimediaDataTransformer $multimediaDataTransformer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param Editorial $editorial The editorial to process
     * @param array<string, mixed> $context Context data (unused for this processor)
     *
     * @return array{multimedia: array<string, AbstractMultimedia>, multimediaOpening: array<string, mixed>, photoFromBodyTags: array<string, mixed>}
     */
    public function process(Editorial $editorial, array $context): array
    {
        $result = [
            'multimedia' => [],
            'multimediaOpening' => [],
            'photoFromBodyTags' => [],
        ];

        // Process opening multimedia
        $result = $this->processOpening($editorial, $result);

        // Process main editorial multimedia
        $result = $this->processMainMultimedia($editorial, $result);

        // Resolve async multimedia promises
        if (!empty($result['multimedia']) && !($editorial->multimedia() instanceof Widget)) {
            $result['multimedia'] = Utils::settle($result['multimedia'])
                ->then($this->createCallback([$this, 'fulfilledMultimedia']))
                ->wait(self::UNWRAPPED);
        }

        // Extract photos from body tags
        $result['photoFromBodyTags'] = $this->retrievePhotosFromBodyTags($editorial->body());

        return $result;
    }

    public function supports(Editorial $editorial): bool
    {
        // This processor always runs to handle multimedia, opening, and body photos
        return true;
    }

    /**
     * Process the opening multimedia for the editorial.
     *
     * @param Editorial $editorial The editorial
     * @param array<string, mixed> $result Current result array
     *
     * @return array<string, mixed> Updated result with opening multimedia data
     */
    private function processOpening(Editorial $editorial, array $result): array
    {
        /** @var NewsBase $editorial */
        $opening = $editorial->opening();

        if (empty($opening->multimediaId())) {
            return $result;
        }

        try {
            /** @var AbstractMultimedia $multimedia */
            $multimedia = $this->queryMultimediaOpeningClient->findMultimediaById($opening->multimediaId());
            $result['multimediaOpening'] = $this->multimediaTypeOrchestratorHandler->handler($multimedia);
        } catch (OrchestratorTypeNotExistException|InvalidBodyException $exception) {
            $this->logger->warning(
                'Failed to process opening multimedia',
                [
                    'editorialId' => $editorial->id()->id(),
                    'openingMultimediaId' => $opening->multimediaId(),
                    'error' => $exception->getMessage(),
                ]
            );
        }

        return $result;
    }

    /**
     * Process the main multimedia for the editorial.
     *
     * @param Editorial $editorial The editorial
     * @param array<string, mixed> $result Current result array
     *
     * @return array<string, mixed> Updated result with multimedia data
     */
    private function processMainMultimedia(Editorial $editorial, array $result): array
    {
        $multimediaId = $this->getMultimediaId($editorial->multimedia());

        if (null !== $multimediaId) {
            $result['multimedia'][] = $this->queryMultimediaClient->findMultimediaById(
                $multimediaId,
                self::ASYNC
            );
        }

        return $result;
    }

    /**
     * Get multimedia ID from a Multimedia object.
     *
     * @param Multimedia $multimedia The multimedia object
     *
     * @return string|null The multimedia ID, or null if empty
     */
    private function getMultimediaId(Multimedia $multimedia): ?string
    {
        $id = $multimedia->id()->id();

        return !empty($id) ? $id : null;
    }

    /**
     * Retrieve photos from body tags (BodyTagPicture and BodyTagMembershipCard).
     *
     * @param Body $body The editorial body
     *
     * @return array<string, mixed> Array of photos indexed by ID
     */
    private function retrievePhotosFromBodyTags(Body $body): array
    {
        $result = [];

        /** @var BodyTagPicture[] $arrayOfBodyTagPicture */
        $arrayOfBodyTagPicture = $body->bodyElementsOf(BodyTagPicture::class);
        foreach ($arrayOfBodyTagPicture as $bodyTagPicture) {
            $result = $this->addPhotoToArray($bodyTagPicture->id()->id(), $result);
        }

        /** @var BodyTagMembershipCard[] $arrayOfBodyTagMembershipCard */
        $arrayOfBodyTagMembershipCard = $body->bodyElementsOf(BodyTagMembershipCard::class);
        foreach ($arrayOfBodyTagMembershipCard as $bodyTagMembershipCard) {
            $id = $bodyTagMembershipCard->bodyTagPictureMembership()->id()->id();
            $result = $this->addPhotoToArray($id, $result);
        }

        return $result;
    }

    /**
     * Add a photo to the result array.
     *
     * @param string $id Photo ID
     * @param array<string, mixed> $result Current result array
     *
     * @return array<string, mixed> Updated result array
     */
    private function addPhotoToArray(string $id, array $result): array
    {
        try {
            $photo = $this->queryMultimediaClient->findPhotoById($id);
            $result[$id] = $photo;
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Failed to fetch photo from body tag',
                [
                    'photoId' => $id,
                    'error' => $throwable->getMessage(),
                ]
            );
        }

        return $result;
    }

    /**
     * Create a callback wrapper for promise resolution.
     *
     * @param callable $callable The callback function
     * @param array<string, string> ...$parameters Additional parameters
     *
     * @return \Closure The wrapped callback
     */
    private function createCallback(callable $callable, ...$parameters): \Closure
    {
        return static function ($element) use ($callable, $parameters) {
            return $callable($element, ...$parameters);
        };
    }

    /**
     * Process fulfilled multimedia promises.
     *
     * @param array<string, mixed> $promises The resolved promises
     *
     * @return array<string, AbstractMultimedia> Fulfilled multimedia indexed by ID
     */
    public function fulfilledMultimedia(array $promises): array
    {
        $result = [];

        /** @var array{state: string, value?: AbstractMultimedia} $promise */
        foreach ($promises as $promise) {
            if (Promise::FULFILLED === $promise['state']) {
                /** @var AbstractMultimedia $multimedia */
                $multimedia = $promise['value'];
                $result[$multimedia->id()] = $multimedia;
            }
        }

        return $result;
    }

    /**
     * Transform multimedia data for the API response.
     *
     * This method handles both opening multimedia and regular multimedia,
     * applying the appropriate transformer based on the type.
     *
     * @param Editorial $editorial The editorial
     * @param array<string, array<string, mixed>> $resolveData The resolved multimedia data
     *
     * @return array<string, mixed>|null Transformed multimedia data, or null if no multimedia
     *
     * @throws \Ec\Editorial\Exceptions\MultimediaDataTransformerNotFoundException
     */
    public function transformMultimedia(Editorial $editorial, array $resolveData): ?array
    {
        /** @var NewsBase $editorial */
        if (!empty($resolveData['multimediaOpening'])) {
            return $this->mediaDataTransformerHandler->execute(
                $resolveData['multimediaOpening'],
                $editorial->opening()
            );
        }

        if (!empty($resolveData['multimedia'])) {
            return $this->multimediaDataTransformer
                ->write($resolveData['multimedia'], $editorial->multimedia())
                ->read();
        }

        return null;
    }
}
