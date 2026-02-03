# Task Breakdown: SNAAPI SOLID Refactoring

> **Methodology**: TDD (Red-Green-Refactor)
> **SOLID Score Target**: ≥22/25

---

## Phase 1: Infrastructure Ports (Foundation)

### BE-001: Create HttpClientInterface

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 1 task unit

**Functional Requirement** (SPEC-F01):
- Abstract HTTP operations for async/sync execution

**SOLID Requirements**:
- **DIP**: High-level code depends on this interface, not concrete clients
- **OCP**: New HTTP strategies implement this interface

**Tests to Write FIRST**:
```php
#[Test]
public function test_interface_defines_request_method(): void
{
    $reflection = new ReflectionClass(HttpClientInterface::class);
    $this->assertTrue($reflection->hasMethod('request'));
}
```

**Acceptance Criteria**:
- [ ] Interface in `src/Infrastructure/Port/HttpClientInterface.php`
- [ ] Method signature supports both sync and async returns
- [ ] PHPDoc with `@template` for type safety
- [ ] PHPStan level 9 passes

**File**: `src/Infrastructure/Port/HttpClientInterface.php`

---

### BE-002: Create AsyncHttpClientAdapter

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F01, SPEC-F02):
- Implement async HTTP requests using PSR-18 HttpAsyncClient

**SOLID Requirements**:
- **SRP**: Only handles async HTTP execution
- **DIP**: Implements HttpClientInterface

**Tests to Write FIRST**:
```php
#[Test]
public function test_request_returns_promise(): void
{
    $adapter = new AsyncHttpClientAdapter($mockClient, $mockFactory);
    $result = $adapter->request('GET', 'http://example.com', [], fn($r) => $r);
    $this->assertInstanceOf(PromiseInterface::class, $result);
}

#[Test]
public function test_transformer_is_applied_to_response(): void
{
    // Arrange: mock client returns response
    // Act: request with transformer
    // Assert: transformer was called with response
}
```

**Acceptance Criteria**:
- [ ] Class in `src/Infrastructure/Adapter/AsyncHttpClientAdapter.php`
- [ ] Implements `HttpClientInterface`
- [ ] Uses `HttpAsyncClient` from PSR
- [ ] Returns `PromiseInterface`
- [ ] Test coverage ≥90%

**File**: `src/Infrastructure/Adapter/AsyncHttpClientAdapter.php`

---

### BE-003: Create SyncHttpClientAdapter

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F01):
- Implement sync HTTP requests using PSR-18 ClientInterface

**SOLID Requirements**:
- **SRP**: Only handles sync HTTP execution
- **DIP**: Implements HttpClientInterface
- **LSP**: Can substitute for AsyncHttpClientAdapter where interface expected

**Tests to Write FIRST**:
```php
#[Test]
public function test_request_returns_transformed_result_directly(): void
{
    $adapter = new SyncHttpClientAdapter($mockClient, $mockFactory);
    $result = $adapter->request('GET', 'http://example.com', [], fn($r) => ['data' => 'test']);
    $this->assertEquals(['data' => 'test'], $result);
}
```

**Acceptance Criteria**:
- [ ] Class in `src/Infrastructure/Adapter/SyncHttpClientAdapter.php`
- [ ] Implements `HttpClientInterface`
- [ ] Uses `ClientInterface` from PSR-18
- [ ] Returns transformed result directly (not Promise)
- [ ] Test coverage ≥90%

**File**: `src/Infrastructure/Adapter/SyncHttpClientAdapter.php`

---

### BE-004: Create PromiseResolverInterface

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 1 task unit

**Functional Requirement** (SPEC-F02):
- Abstract promise resolution for multiple concurrent requests

**SOLID Requirements**:
- **DIP**: Orchestrator depends on this, not Guzzle directly
- **SRP**: Only defines promise resolution contract

**Tests to Write FIRST**:
```php
#[Test]
public function test_interface_defines_settle_all_method(): void
{
    $reflection = new ReflectionClass(PromiseResolverInterface::class);
    $this->assertTrue($reflection->hasMethod('settleAll'));
    $this->assertTrue($reflection->hasMethod('fulfilledOnly'));
}
```

**Acceptance Criteria**:
- [ ] Interface in `src/Infrastructure/Port/PromiseResolverInterface.php`
- [ ] `settleAll(array $promises): array`
- [ ] `fulfilledOnly(array $results): array`
- [ ] PHPStan level 9 passes

**File**: `src/Infrastructure/Port/PromiseResolverInterface.php`

---

### BE-005: Create GuzzlePromiseResolver

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F02):
- Implement promise resolution using Guzzle Utils

**SOLID Requirements**:
- **SRP**: Only resolves promises
- **DIP**: Implements PromiseResolverInterface

**Tests to Write FIRST**:
```php
#[Test]
public function test_settle_all_waits_for_all_promises(): void
{
    $resolver = new GuzzlePromiseResolver();
    $promise1 = new FulfilledPromise(['data' => 1]);
    $promise2 = new FulfilledPromise(['data' => 2]);

    $results = $resolver->settleAll(['p1' => $promise1, 'p2' => $promise2]);

    $this->assertCount(2, $results);
}

#[Test]
public function test_fulfilled_only_filters_rejected(): void
{
    $resolver = new GuzzlePromiseResolver();
    $results = [
        'p1' => ['state' => 'fulfilled', 'value' => 'ok'],
        'p2' => ['state' => 'rejected', 'reason' => 'error'],
    ];

    $fulfilled = $resolver->fulfilledOnly($results);

    $this->assertCount(1, $fulfilled);
}
```

**Acceptance Criteria**:
- [ ] Class in `src/Infrastructure/Adapter/GuzzlePromiseResolver.php`
- [ ] Implements `PromiseResolverInterface`
- [ ] Uses `GuzzleHttp\Promise\Utils::settle()`
- [ ] Test coverage ≥90%

**File**: `src/Infrastructure/Adapter/GuzzlePromiseResolver.php`

---

## Phase 2: Trait Elimination (Clean Dependencies)

### BE-006: Create UrlGeneratorInterface

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 1 task unit

**Functional Requirement** (SPEC-F09):
- Abstract URL generation for multimedia and editorials

**SOLID Requirements**:
- **SRP**: Only URL generation contract
- **DIP**: Replace hidden trait dependencies

**Acceptance Criteria**:
- [ ] Interface in `src/Application/Service/UrlGeneratorInterface.php`
- [ ] `generateMultimediaUrl(Multimedia $multimedia): string`
- [ ] `generateEditorialUrl(Editorial $editorial): string`

**File**: `src/Application/Service/UrlGeneratorInterface.php`

---

### BE-007: Create ThumborUrlGenerator

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F09):
- Implement URL generation using Thumbor

**SOLID Requirements**:
- **SRP**: Only generates Thumbor URLs
- **DIP**: Implements UrlGeneratorInterface

**Tests to Write FIRST**:
```php
#[Test]
public function test_generates_multimedia_url_with_thumbor(): void
{
    $thumbor = $this->createMock(Thumbor::class);
    $thumbor->expects($this->once())
        ->method('generateUrl')
        ->willReturn('https://thumbor.example.com/image.jpg');

    $generator = new ThumborUrlGenerator($thumbor);
    $url = $generator->generateMultimediaUrl($multimedia);

    $this->assertStringContainsString('thumbor', $url);
}
```

**Acceptance Criteria**:
- [ ] Class in `src/Infrastructure/Service/ThumborUrlGenerator.php`
- [ ] Implements `UrlGeneratorInterface`
- [ ] Injects `Thumbor` via constructor
- [ ] Test coverage ≥90%

**File**: `src/Infrastructure/Service/ThumborUrlGenerator.php`

---

### BE-008: Remove MultimediaTrait

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**SOLID Requirements**:
- **DIP**: Replace trait with injected service
- **SRP**: Remove hidden dependencies

**Steps**:
1. Find all usages of `MultimediaTrait`
2. Replace with `UrlGeneratorInterface` injection
3. Update tests
4. Delete trait file

**Acceptance Criteria**:
- [ ] `MultimediaTrait` deleted
- [ ] All usages replaced with `UrlGeneratorInterface`
- [ ] No `use MultimediaTrait` in codebase
- [ ] Tests pass

**File to DELETE**: `src/Infrastructure/Trait/MultimediaTrait.php`

---

### BE-009: Remove UrlGeneratorTrait

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 1 task unit

**SOLID Requirements**:
- **DIP**: Replace trait with injected service

**Acceptance Criteria**:
- [ ] `UrlGeneratorTrait` deleted
- [ ] All usages replaced
- [ ] Tests pass

**File to DELETE**: `src/Infrastructure/Trait/UrlGeneratorTrait.php`

---

## Phase 3: Data Fetchers (SRP Extraction)

### BE-010: Create DataFetcherInterface

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 1 task unit

**SOLID Requirements**:
- **SRP**: Contract for single-purpose data fetching
- **OCP**: New fetchers implement this interface

**Acceptance Criteria**:
- [ ] Interface in `src/Application/Fetcher/DataFetcherInterface.php`
- [ ] `supports(string $dataType): bool`
- [ ] `fetch(Editorial $editorial, array $context): mixed`

**File**: `src/Application/Fetcher/DataFetcherInterface.php`

---

### BE-011: Create SectionDataFetcher

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F03):
- Fetch section data for editorial

**SOLID Requirements**:
- **SRP**: Only fetches section data
- **DIP**: Implements DataFetcherInterface

**Tests to Write FIRST**:
```php
#[Test]
public function test_supports_section_data_type(): void
{
    $fetcher = new SectionDataFetcher($mockClient);
    $this->assertTrue($fetcher->supports('section'));
    $this->assertFalse($fetcher->supports('tags'));
}

#[Test]
public function test_fetches_section_by_editorial_section_id(): void
{
    $client = $this->createMock(QuerySectionClientInterface::class);
    $client->expects($this->once())
        ->method('findSectionById')
        ->with('section-123');

    $fetcher = new SectionDataFetcher($client);
    $fetcher->fetch($editorial, []);
}
```

**Acceptance Criteria**:
- [ ] Class in `src/Application/Fetcher/SectionDataFetcher.php`
- [ ] Only 1 dependency: `QuerySectionClientInterface`
- [ ] ≤50 lines
- [ ] Test coverage ≥90%

**File**: `src/Application/Fetcher/SectionDataFetcher.php`

---

### BE-012: Create TagDataFetcher

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F03):
- Fetch tags for editorial

**SOLID Requirements**:
- **SRP**: Only fetches tag data

**Acceptance Criteria**:
- [ ] Class in `src/Application/Fetcher/TagDataFetcher.php`
- [ ] Test coverage ≥90%

---

### BE-013: Create MultimediaDataFetcher

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F03, SPEC-F05):
- Fetch multimedia data for editorial

**Acceptance Criteria**:
- [ ] Class in `src/Application/Fetcher/MultimediaDataFetcher.php`
- [ ] Handles opening multimedia
- [ ] Test coverage ≥90%

---

### BE-014: Create MembershipDataFetcher

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F03):
- Fetch membership links for editorial

**Acceptance Criteria**:
- [ ] Class in `src/Application/Fetcher/MembershipDataFetcher.php`
- [ ] Test coverage ≥90%

---

### BE-015: Create JournalistDataFetcher

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F03):
- Fetch journalist info for signatures

**Acceptance Criteria**:
- [ ] Class in `src/Application/Fetcher/JournalistDataFetcher.php`
- [ ] Test coverage ≥90%

---

### BE-016: Create EditorialDataAggregator

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 3 task units

**Functional Requirement** (SPEC-F03):
- Coordinate all data fetchers and aggregate results

**SOLID Requirements**:
- **SRP**: Only coordinates fetchers
- **OCP**: New fetchers added via tagged services
- **DIP**: Depends on interfaces, not concrete fetchers

**Tests to Write FIRST**:
```php
#[Test]
public function test_aggregates_data_from_all_fetchers(): void
{
    $fetcher1 = $this->createMock(DataFetcherInterface::class);
    $fetcher1->method('fetch')->willReturn(['section' => 'data']);

    $fetcher2 = $this->createMock(DataFetcherInterface::class);
    $fetcher2->method('fetch')->willReturn(new FulfilledPromise(['tags' => []]));

    $resolver = $this->createMock(PromiseResolverInterface::class);
    $resolver->method('settleAll')->willReturn([...]);

    $aggregator = new EditorialDataAggregator([$fetcher1, $fetcher2], $resolver);
    $result = $aggregator->aggregate($editorial);

    $this->assertArrayHasKey('section', $result);
}
```

**Acceptance Criteria**:
- [ ] Class in `src/Application/Aggregator/EditorialDataAggregator.php`
- [ ] Injects `iterable<DataFetcherInterface>`
- [ ] Injects `PromiseResolverInterface`
- [ ] ≤100 lines
- [ ] ≤3 mocks needed for tests
- [ ] Test coverage ≥90%

**File**: `src/Application/Aggregator/EditorialDataAggregator.php`

---

## Phase 4: Visitor Pattern (Body Elements)

### BE-017: Create TransformableElement Interface

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 1 task unit

**SOLID Requirements**:
- **OCP**: Elements visitable without modification
- **LSP**: All elements implement same contract

**Acceptance Criteria**:
- [ ] Interface in `src/Application/Visitor/TransformableElement.php`
- [ ] `accept(ElementVisitorInterface $visitor): array`

---

### BE-018: Create ElementVisitorInterface

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**SOLID Requirements**:
- **OCP**: New element types = new visit methods
- **SRP**: Each visit method handles one element type

**Acceptance Criteria**:
- [ ] Interface in `src/Application/Visitor/ElementVisitorInterface.php`
- [ ] One `visit*` method per body element type
- [ ] Return type `array` for all methods

---

### BE-019: Create JsonElementVisitor

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 4 task units

**Functional Requirement** (SPEC-F04, SPEC-F06):
- Transform all body elements to JSON format

**SOLID Requirements**:
- **SRP**: Only JSON transformation
- **DIP**: Injects `PictureShotsPortInterface` for photos

**Tests to Write FIRST** (one per element type):
```php
#[Test]
public function test_visit_paragraph_returns_correct_structure(): void
{
    $visitor = new JsonElementVisitor($mockPictureShots);
    $paragraph = new Paragraph('Test content');

    $result = $visitor->visitParagraph($paragraph);

    $this->assertEquals([
        'type' => 'paragraph',
        'content' => 'Test content',
    ], $result);
}

#[Test]
public function test_visit_picture_includes_shots(): void
{
    $pictureShots = $this->createMock(PictureShotsPortInterface::class);
    $pictureShots->method('getShotsForPhoto')
        ->willReturn(['thumb' => 'url1', 'full' => 'url2']);

    $visitor = new JsonElementVisitor($pictureShots);
    $result = $visitor->visitPicture($bodyTagPicture);

    $this->assertArrayHasKey('shots', $result);
    $this->assertCount(2, $result['shots']);
}
```

**Acceptance Criteria**:
- [ ] Class in `src/Application/Visitor/JsonElementVisitor.php`
- [ ] Implements `ElementVisitorInterface`
- [ ] One test per visit method
- [ ] Test coverage ≥95%

**File**: `src/Application/Visitor/JsonElementVisitor.php`

---

### BE-020: Add TransformableElement to Body Elements

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 3 task units

**Files to Modify**:
- `Paragraph.php`
- `SubHead.php`
- `BodyTagPicture.php`
- `BodyTagVideo.php`
- `BodyTagInsertedNews.php`
- `BodyTagMembershipCard.php`
- `BodyTagHtml.php`
- `GenericList.php`
- (all body element classes)

**Acceptance Criteria**:
- [ ] All body elements implement `TransformableElement`
- [ ] Each `accept()` calls appropriate visitor method
- [ ] Existing tests still pass

---

### BE-021: Update BodyDataTransformer to Use Visitor

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 2 task units

**SOLID Requirements**:
- **OCP**: No more `get_class()` lookups
- **DIP**: Depends on `ElementVisitorInterface`

**Acceptance Criteria**:
- [ ] `BodyDataTransformer` injects `ElementVisitorInterface`
- [ ] Uses `element->accept(visitor)` instead of lookup
- [ ] Snapshot test confirms identical output

---

## Phase 5: Multimedia Polymorphism

### BE-022: Create MultimediaPortInterface

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 1 task unit

**SOLID Requirements**:
- **DIP**: Abstract multimedia operations
- **LSP**: All multimedia types usable via this port

**Acceptance Criteria**:
- [ ] Interface in `src/Domain/Port/MultimediaPortInterface.php`

---

### BE-023: Create PictureShotsPortInterface

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 1 task unit

**SOLID Requirements**:
- **DIP**: Abstract shots retrieval

**Acceptance Criteria**:
- [ ] Interface in `src/Domain/Port/PictureShotsPortInterface.php`
- [ ] `getShotsForPhoto(string $photoId, array $context): array`

---

### BE-024: Create ThumborPictureShotsAdapter

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 2 task units

**SOLID Requirements**:
- **SRP**: Only generates Thumbor shot URLs
- **DIP**: Implements `PictureShotsPortInterface`

**Acceptance Criteria**:
- [ ] Class in `src/Infrastructure/Adapter/ThumborPictureShotsAdapter.php`
- [ ] Test coverage ≥90%

---

### BE-025: Update BodyTagPictureDataTransformer

**Role**: Backend Engineer
**Priority**: P1 - High
**Estimated**: 1 task unit

**SOLID Requirements**:
- **DIP**: Inject `PictureShotsPortInterface` instead of concrete `PictureShots`

**Acceptance Criteria**:
- [ ] Constructor uses interface
- [ ] Tests use mock interface
- [ ] Existing behavior preserved

---

## Phase 6: Integration & Testing

### BE-026: Create Snapshot Tests

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 2 task units

**Functional Requirement** (SPEC-F10):
- Verify response JSON is identical before/after refactoring

**Acceptance Criteria**:
- [ ] Snapshot test in `tests/Integration/EditorialResponseSnapshotTest.php`
- [ ] Captures full response for known editorial IDs
- [ ] Runs before and after refactoring

---

### BE-027: Create GetEditorialQuery Use Case

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 3 task units

**SOLID Requirements**:
- **SRP**: Single use case for getting editorial
- **DIP**: Depends on aggregator and transformer interfaces

**Acceptance Criteria**:
- [ ] Class in `src/Application/Query/GetEditorialQuery.php`
- [ ] ≤100 lines
- [ ] ≤5 dependencies
- [ ] Test coverage ≥90%

---

### BE-028: Update EditorialController

**Role**: Backend Engineer
**Priority**: P0 - Critical Path
**Estimated**: 1 task unit

**Acceptance Criteria**:
- [ ] Inject `GetEditorialQuery` instead of `OrchestratorChainHandler`
- [ ] Response identical
- [ ] Snapshot test passes

---

### BE-029: Performance Benchmark

**Role**: Backend Engineer
**Priority**: P2 - Medium
**Estimated**: 1 task unit

**Acceptance Criteria**:
- [ ] Benchmark script comparing latency before/after
- [ ] No regression >5%

---

### BE-030: Documentation Update

**Role**: Backend Engineer
**Priority**: P2 - Medium
**Estimated**: 1 task unit

**Acceptance Criteria**:
- [ ] `snaapi/CLAUDE.md` updated with new architecture
- [ ] ADR for SOLID refactoring decisions

---

## Task Summary

| Phase | Tasks | Priority | Estimated Units |
|-------|-------|----------|-----------------|
| 1. Infrastructure Ports | BE-001 to BE-005 | P0 | 8 |
| 2. Trait Elimination | BE-006 to BE-009 | P1 | 6 |
| 3. Data Fetchers | BE-010 to BE-016 | P0-P1 | 14 |
| 4. Visitor Pattern | BE-017 to BE-021 | P0-P1 | 12 |
| 5. Multimedia | BE-022 to BE-025 | P1 | 5 |
| 6. Integration | BE-026 to BE-030 | P0-P2 | 8 |
| **Total** | **30 tasks** | | **53 units** |

---

## Definition of Done (per task)

- [ ] Tests written FIRST (TDD)
- [ ] Implementation passes tests
- [ ] PHPStan level 9 passes
- [ ] Code style (PSR-12) passes
- [ ] SOLID score maintained/improved
- [ ] No new `instanceof` or `get_class()` usage
- [ ] ≤5 mocks in tests
- [ ] Documentation updated if public API
