# Solutions with SOLID Patterns: SNAAPI Refactoring

> **CONSTRAINT**: Todas las soluciones DEBEN cumplir SOLID.
> Score objetivo: ≥22/25. Score actual: ~8/25.

---

## SOLID Baseline Analysis

### Current Score: 8/25

| Principle | Current | Target | Gap |
|-----------|---------|--------|-----|
| SRP | 1/5 | 5/5 | -4 |
| OCP | 2/5 | 4/5 | -2 |
| LSP | 2/5 | 4/5 | -2 |
| ISP | 2/5 | 4/5 | -2 |
| DIP | 1/5 | 5/5 | -4 |
| **Total** | **8/25** | **22/25** | **-14** |

---

## Solution 1: HTTP Client Abstraction (SPEC-F01, SPEC-F02)

### Problem
```php
// Current: Flag-based async (violates OCP, DIP)
$editorial = $this->queryEditorialClient->findEditorialById($id, $async = true);
```

### Solution: Strategy + Adapter Pattern

**SOLID Compliance**:

| Principle | How Addressed | Pattern |
|-----------|---------------|---------|
| **SRP** | Separar HTTP execution de business logic | Adapter |
| **OCP** | Nuevas estrategias sin modificar existente | Strategy |
| **DIP** | Depender de HttpClientInterface, no concretos | Port/Adapter |

**Design**:

```php
// Port (Abstraction)
interface HttpClientInterface
{
    /**
     * @template T
     * @param callable(ResponseInterface): T $transformer
     * @return T|PromiseInterface<T>
     */
    public function request(
        string $method,
        string $uri,
        array $options,
        callable $transformer
    ): mixed;
}

// Adapter 1: Async
final readonly class AsyncHttpClientAdapter implements HttpClientInterface
{
    public function __construct(
        private HttpAsyncClient $client,
        private RequestFactoryInterface $requestFactory,
    ) {}

    public function request(...): PromiseInterface
    {
        $request = $this->requestFactory->createRequest($method, $uri);
        return $this->client->sendAsyncRequest($request)
            ->then($transformer);
    }
}

// Adapter 2: Sync
final readonly class SyncHttpClientAdapter implements HttpClientInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
    ) {}

    public function request(...): mixed
    {
        $request = $this->requestFactory->createRequest($method, $uri);
        $response = $this->client->sendRequest($request);
        return $transformer($response);
    }
}

// Factory (decides strategy based on config)
final readonly class HttpClientFactory
{
    public function create(bool $async = true): HttpClientInterface
    {
        return $async
            ? new AsyncHttpClientAdapter(...)
            : new SyncHttpClientAdapter(...);
    }
}
```

**Files**:
- `Infrastructure/Port/HttpClientInterface.php`
- `Infrastructure/Adapter/AsyncHttpClientAdapter.php`
- `Infrastructure/Adapter/SyncHttpClientAdapter.php`
- `Infrastructure/Factory/HttpClientFactory.php`

**Expected SOLID Score**: 5/5 for this component

---

## Solution 2: Promise Resolution (SPEC-F02)

### Problem
```php
// Current: Direct Guzzle coupling
GuzzleHttp\Promise\Utils::settle($promises);
```

### Solution: Strategy Pattern

**SOLID Compliance**:

| Principle | How Addressed | Pattern |
|-----------|---------------|---------|
| **SRP** | Promise resolution isolated | Extract Class |
| **OCP** | New resolvers without modification | Strategy |
| **DIP** | Interface for resolution | Dependency Injection |

**Design**:

```php
interface PromiseResolverInterface
{
    /**
     * @param array<string, PromiseInterface> $promises
     * @return array<string, mixed> Results keyed by promise name
     */
    public function settleAll(array $promises): array;

    /**
     * Filter fulfilled promises only
     */
    public function fulfilledOnly(array $results): array;
}

final readonly class GuzzlePromiseResolver implements PromiseResolverInterface
{
    public function settleAll(array $promises): array
    {
        return Utils::settle($promises)->wait();
    }

    public function fulfilledOnly(array $results): array
    {
        return array_filter(
            $results,
            static fn($r) => $r['state'] === 'fulfilled'
        );
    }
}
```

**Files**:
- `Infrastructure/Port/PromiseResolverInterface.php`
- `Infrastructure/Adapter/GuzzlePromiseResolver.php`

**Expected SOLID Score**: 5/5

---

## Solution 3: Editorial Orchestrator Decomposition (SPEC-F03)

### Problem
```php
// Current: God class with 536 lines, 15+ responsibilities
class EditorialOrchestrator
{
    // 15+ injected dependencies
    // 15+ private methods
    // Mixed concerns: fetch, transform, resolve, extract
}
```

### Solution: Facade + SRP Extraction

**SOLID Compliance**:

| Principle | How Addressed | Pattern |
|-----------|---------------|---------|
| **SRP** | Split into 6 focused fetchers | Extract Class |
| **OCP** | New fetchers via interface | Strategy |
| **DIP** | Depend on DataFetcherInterface | Dependency Injection |

**Design**:

```php
// Single responsibility interface
interface DataFetcherInterface
{
    public function supports(string $dataType): bool;

    /**
     * @return array<string, mixed>|PromiseInterface
     */
    public function fetch(Editorial $editorial, array $context): mixed;
}

// SRP: Only fetches section data
final readonly class SectionDataFetcher implements DataFetcherInterface
{
    public function __construct(
        private QuerySectionClientInterface $client,
    ) {}

    public function supports(string $dataType): bool
    {
        return $dataType === 'section';
    }

    public function fetch(Editorial $editorial, array $context): array
    {
        return $this->client->findSectionById($editorial->sectionId());
    }
}

// SRP: Only fetches tag data
final readonly class TagDataFetcher implements DataFetcherInterface
{
    public function __construct(
        private QueryTagClientInterface $client,
    ) {}

    public function supports(string $dataType): bool
    {
        return $dataType === 'tags';
    }

    public function fetch(Editorial $editorial, array $context): array
    {
        return $this->client->findTagsByIds($editorial->tagIds());
    }
}

// Facade: Coordinates all fetchers
final readonly class EditorialDataAggregator
{
    /**
     * @param iterable<DataFetcherInterface> $fetchers
     */
    public function __construct(
        private iterable $fetchers,
        private PromiseResolverInterface $promiseResolver,
    ) {}

    public function aggregate(Editorial $editorial): array
    {
        $promises = [];

        foreach ($this->fetchers as $fetcher) {
            $result = $fetcher->fetch($editorial, []);
            if ($result instanceof PromiseInterface) {
                $promises[$fetcher::class] = $result;
            }
        }

        return $this->promiseResolver->settleAll($promises);
    }
}
```

**Files**:
- `Application/Port/DataFetcherInterface.php`
- `Application/Fetcher/SectionDataFetcher.php`
- `Application/Fetcher/TagDataFetcher.php`
- `Application/Fetcher/MultimediaDataFetcher.php`
- `Application/Fetcher/MembershipDataFetcher.php`
- `Application/Fetcher/JournalistDataFetcher.php`
- `Application/Fetcher/RecommendedDataFetcher.php`
- `Application/Aggregator/EditorialDataAggregator.php`

**Before**: 1 class, 536 lines, 15 responsibilities
**After**: 8 classes, ~80 lines each, 1 responsibility each

**Expected SOLID Score**: 5/5

---

## Solution 4: Body Element Transformation (SPEC-F04)

### Problem
```php
// Current: get_class() lookup (violates OCP)
$transformer = $this->dataTransformers[get_class($bodyElement)];
```

### Solution: Visitor Pattern

**SOLID Compliance**:

| Principle | How Addressed | Pattern |
|-----------|---------------|---------|
| **SRP** | Each transformer handles one element type | Visitor |
| **OCP** | New elements = new visitor methods, no modification | Visitor |
| **LSP** | All elements implement same visitable interface | Visitor |
| **DIP** | Depend on ElementVisitorInterface | Dependency Injection |

**Design**:

```php
// Visitable element
interface TransformableElement
{
    public function accept(ElementVisitorInterface $visitor): array;
}

// Visitor interface with method per element type
interface ElementVisitorInterface
{
    public function visitParagraph(Paragraph $element): array;
    public function visitSubHead(SubHead $element): array;
    public function visitPicture(BodyTagPicture $element): array;
    public function visitVideo(BodyTagVideo $element): array;
    public function visitInsertedNews(BodyTagInsertedNews $element): array;
    public function visitMembershipCard(BodyTagMembershipCard $element): array;
    public function visitHtml(BodyTagHtml $element): array;
    public function visitList(GenericList $element): array;
    // ... one method per element type
}

// Concrete visitor for JSON transformation
final readonly class JsonElementVisitor implements ElementVisitorInterface
{
    public function __construct(
        private PictureShotsPortInterface $pictureShots,
    ) {}

    public function visitParagraph(Paragraph $element): array
    {
        return [
            'type' => 'paragraph',
            'content' => $element->content(),
        ];
    }

    public function visitPicture(BodyTagPicture $element): array
    {
        $shots = $this->pictureShots->getShotsForPhoto($element->photoId());
        return [
            'type' => 'picture',
            'shots' => $shots,
            'url' => $shots[0] ?? null,
            'caption' => $element->caption(),
            'alternate' => $element->alternate(),
            'orientation' => $element->orientation(),
        ];
    }

    // ... other visit methods
}

// Usage - no more get_class()!
final readonly class BodyDataTransformer
{
    public function __construct(
        private ElementVisitorInterface $visitor,
    ) {}

    public function transform(array $bodyElements): array
    {
        return array_map(
            fn(TransformableElement $el) => $el->accept($this->visitor),
            $bodyElements
        );
    }
}
```

**Why Visitor over Strategy here**:
- Strategy: Algorithm varies, data is same
- Visitor: Data types vary, algorithm is same (transform to JSON)

Body elements have MANY types but ONE operation (JSON transform), so Visitor is correct.

**Files**:
- `Application/Visitor/TransformableElement.php`
- `Application/Visitor/ElementVisitorInterface.php`
- `Application/Visitor/JsonElementVisitor.php`
- Modify existing body element classes to implement `TransformableElement`

**Expected SOLID Score**: 5/5

---

## Solution 5: Multimedia Type Handling (SPEC-F05)

### Problem
```php
// Current: instanceof checks (violates LSP)
if ($multimedia instanceof MultimediaPhoto) { ... }
if ($multimedia instanceof Video || $multimedia instanceof Widget) { ... }
```

### Solution: Polymorphism + Template Method

**SOLID Compliance**:

| Principle | How Addressed | Pattern |
|-----------|---------------|---------|
| **SRP** | Each multimedia type handles its own logic | Polymorphism |
| **OCP** | New types implement interface | Template Method |
| **LSP** | All types substitutable via interface | Interface |
| **DIP** | Depend on MultimediaInterface | Dependency Injection |

**Design**:

```php
// Common interface - no instanceof needed
interface MultimediaInterface
{
    public function getType(): string;
    public function getResourceId(): ?string;
    public function hasPhoto(): bool;
    public function getPhotoId(): ?string;

    // Template method - each type implements
    public function toArray(MultimediaResolverInterface $resolver): array;
}

// Each type implements its own transformation
final readonly class MultimediaPhoto implements MultimediaInterface
{
    public function toArray(MultimediaResolverInterface $resolver): array
    {
        $resource = $resolver->resolvePhoto($this->getResourceId());
        return [
            'type' => 'photo',
            'resource' => $resource,
            'url' => $resource->url(),
        ];
    }
}

final readonly class MultimediaVideo implements MultimediaInterface
{
    public function toArray(MultimediaResolverInterface $resolver): array
    {
        return [
            'type' => 'video',
            'url' => $this->url(),
            'provider' => $this->provider(),
        ];
    }
}

// Usage - no instanceof!
final readonly class MultimediaTransformer
{
    public function __construct(
        private MultimediaResolverInterface $resolver,
    ) {}

    public function transform(MultimediaInterface $multimedia): array
    {
        // Polymorphism handles type-specific logic
        return $multimedia->toArray($this->resolver);
    }
}
```

**Expected SOLID Score**: 5/5

---

## Solution 6: Picture Shots Abstraction (SPEC-F09)

### Problem
```php
// Current: Concrete dependency (violates DIP)
private readonly PictureShots $pictureShots;
```

### Solution: Port/Adapter Pattern

**SOLID Compliance**:

| Principle | How Addressed | Pattern |
|-----------|---------------|---------|
| **SRP** | Shots retrieval isolated | Extract Interface |
| **DIP** | Depend on port, not concrete service | Port/Adapter |

**Design**:

```php
// Port (in Domain)
interface PictureShotsPortInterface
{
    /**
     * @return array<string, string> Shot name => URL
     */
    public function getShotsForPhoto(string $photoId, array $context = []): array;
}

// Adapter (in Infrastructure)
final readonly class ThumborPictureShotsAdapter implements PictureShotsPortInterface
{
    public function __construct(
        private Thumbor $thumbor,
        private array $shotConfigurations,
    ) {}

    public function getShotsForPhoto(string $photoId, array $context = []): array
    {
        $shots = [];
        foreach ($this->shotConfigurations as $name => $config) {
            $shots[$name] = $this->thumbor->generateUrl($photoId, $config);
        }
        return $shots;
    }
}
```

**Files**:
- `Domain/Port/PictureShotsPortInterface.php`
- `Infrastructure/Adapter/ThumborPictureShotsAdapter.php`

**Expected SOLID Score**: 5/5

---

## Solution 7: Trait Elimination

### Problem
```php
// Current: Hidden dependencies in traits (violates DIP, SRP)
use MultimediaTrait;  // Has $thumbor property!
use UrlGeneratorTrait;
```

### Solution: Extract to Services

**SOLID Compliance**:

| Principle | How Addressed | Pattern |
|-----------|---------------|---------|
| **SRP** | Each utility in own service | Extract Class |
| **DIP** | Inject via constructor | Dependency Injection |

**Design**:

```php
// Before: Hidden in trait
trait MultimediaTrait
{
    private Thumbor $thumbor;  // Where is this set?!

    private function getMultimediaUrl(): string { ... }
}

// After: Explicit service
interface UrlGeneratorInterface
{
    public function generateMultimediaUrl(Multimedia $multimedia): string;
    public function generateEditorialUrl(Editorial $editorial): string;
}

final readonly class ThumborUrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private Thumbor $thumbor,
        private UriFactoryInterface $uriFactory,
    ) {}

    public function generateMultimediaUrl(Multimedia $multimedia): string
    {
        return $this->thumbor->generateUrl($multimedia->resourceId());
    }
}

// Usage - explicit dependency
final readonly class SomeService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,  // Visible!
    ) {}
}
```

**Files**:
- `Application/Service/UrlGeneratorInterface.php`
- `Infrastructure/Service/ThumborUrlGenerator.php`
- Remove `MultimediaTrait` and `UrlGeneratorTrait`

**Expected SOLID Score**: 5/5

---

## Pattern Summary

| Problem | Pattern | SOLID Addressed |
|---------|---------|-----------------|
| Async/Sync flag | **Strategy + Adapter** | OCP, DIP, SRP |
| Promise resolution | **Strategy** | SRP, OCP, DIP |
| God class orchestrator | **Facade + Extract Class** | SRP, OCP, DIP |
| `get_class()` lookup | **Visitor** | OCP, LSP, DIP |
| `instanceof` checks | **Polymorphism + Template Method** | LSP, OCP |
| Concrete dependencies | **Port/Adapter** | DIP |
| Hidden trait deps | **Extract Class + DI** | SRP, DIP |

---

## Expected Final SOLID Score: 23/25

| Principle | Current | After | Improvement |
|-----------|---------|-------|-------------|
| SRP | 1/5 | 5/5 | +4 |
| OCP | 2/5 | 4/5 | +2 |
| LSP | 2/5 | 5/5 | +3 |
| ISP | 2/5 | 4/5 | +2 |
| DIP | 1/5 | 5/5 | +4 |
| **Total** | **8/25** | **23/25** | **+15** |

**Grade**: A - SOLID Compliant

---

## Implementation Order

1. **Phase 1**: Infrastructure ports (HTTP, Promise)
2. **Phase 2**: Data fetchers (SRP extraction)
3. **Phase 3**: Visitor pattern for body elements
4. **Phase 4**: Multimedia polymorphism
5. **Phase 5**: Trait elimination
6. **Phase 6**: Integration and snapshot tests

Each phase is independently deployable with backward compatibility.
