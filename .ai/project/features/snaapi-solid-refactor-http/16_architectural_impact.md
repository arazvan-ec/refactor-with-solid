# Architectural Impact: SNAAPI SOLID Refactoring

## Summary

| Metric | Value |
|--------|-------|
| Layers affected | 4 (Domain, Application, Infrastructure, Presentation) |
| Modules touched | 6 existing, 8 new |
| Files to CREATE | 33 |
| Files to MODIFY | 8 |
| Files to DELETE | 2 (traits) |
| Estimated LOC added | ~1,500 |
| Estimated LOC modified | ~300 |
| Complexity | HIGH |

---

## Layer Analysis

| Layer | Impact Level | Changes Required |
|-------|--------------|------------------|
| **Domain** | LOW | 2 new port interfaces |
| **Application** | HIGH | 15 new classes, 3 modified |
| **Infrastructure** | MEDIUM | 8 new adapters, 2 modified |
| **Presentation** | LOW | 1 controller modified |

---

## Layers Affected Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ PRESENTATION                                                     │
│   [MODIFIED] EditorialController.php                             │
│              - Inject GetEditorialQuery instead of Orchestrator  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ APPLICATION                                                      │
│                                                                  │
│   [NEW] Query/                                                   │
│         └── GetEditorialQuery.php (Use Case)                     │
│                                                                  │
│   [NEW] Aggregator/                                              │
│         └── EditorialDataAggregator.php (Facade)                 │
│                                                                  │
│   [NEW] Fetcher/                                                 │
│         ├── DataFetcherInterface.php                             │
│         ├── SectionDataFetcher.php                               │
│         ├── TagDataFetcher.php                                   │
│         ├── MultimediaDataFetcher.php                            │
│         ├── MembershipDataFetcher.php                            │
│         ├── JournalistDataFetcher.php                            │
│         └── RecommendedDataFetcher.php                           │
│                                                                  │
│   [NEW] Visitor/                                                 │
│         ├── TransformableElement.php                             │
│         ├── ElementVisitorInterface.php                          │
│         └── JsonElementVisitor.php                               │
│                                                                  │
│   [NEW] Service/                                                 │
│         └── UrlGeneratorInterface.php                            │
│                                                                  │
│   [MODIFIED] DataTransformer/                                    │
│              ├── BodyDataTransformer.php (use visitor)           │
│              ├── BodyElementDataTransformerHandler.php           │
│              └── Apps/Body/*.php (implement TransformableElement)│
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ DOMAIN                                                           │
│                                                                  │
│   [NEW] Port/                                                    │
│         ├── MultimediaPortInterface.php                          │
│         └── PictureShotsPortInterface.php                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ INFRASTRUCTURE                                                   │
│                                                                  │
│   [NEW] Port/                                                    │
│         ├── HttpClientInterface.php                              │
│         └── PromiseResolverInterface.php                         │
│                                                                  │
│   [NEW] Adapter/                                                 │
│         ├── AsyncHttpClientAdapter.php                           │
│         ├── SyncHttpClientAdapter.php                            │
│         ├── GuzzlePromiseResolver.php                            │
│         └── ThumborPictureShotsAdapter.php                       │
│                                                                  │
│   [NEW] Factory/                                                 │
│         └── HttpClientFactory.php                                │
│                                                                  │
│   [NEW] Service/                                                 │
│         └── ThumborUrlGenerator.php                              │
│                                                                  │
│   [DELETE] Trait/                                                │
│            ├── MultimediaTrait.php                               │
│            └── UrlGeneratorTrait.php                             │
│                                                                  │
│   [MODIFIED] Client/Http/QueryLegacyClient.php                   │
│              - Use HttpClientInterface                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## Existing Modules Touched

| Module | Files Touched | Risk Level | Notes |
|--------|---------------|------------|-------|
| `src/Orchestrator/` | 2 files | HIGH | Core orchestration changes |
| `src/Application/DataTransformer/` | 25 files | MEDIUM | Add visitor interface |
| `src/Infrastructure/Trait/` | 2 files | LOW | Delete traits |
| `src/Infrastructure/Client/` | 1 file | MEDIUM | HTTP abstraction |
| `src/Controller/` | 1 file | LOW | Inject new use case |
| `tests/` | 20+ files | MEDIUM | Update mocks |

---

## Change Scope Estimation

```
╔══════════════════════════════════════════════════════════════════╗
║                    CHANGE SCOPE SUMMARY                          ║
╠══════════════════════════════════════════════════════════════════╣
║  Files to CREATE:     33                                         ║
║  Files to MODIFY:      8                                         ║
║  Files to DELETE:      2                                         ║
║  ────────────────────────────────────────────────                ║
║  Total files affected: 43                                        ║
║                                                                  ║
║  Estimated LOC added:   ~1,500                                   ║
║  Estimated LOC modified: ~300                                    ║
║  Estimated LOC deleted:  ~200 (traits)                           ║
║  ────────────────────────────────────────────────                ║
║  Net LOC change: +1,300                                          ║
║                                                                  ║
║  Complexity: HIGH                                                ║
║                                                                  ║
║  Tests to CREATE:      15                                        ║
║  Tests to MODIFY:       5                                        ║
╚══════════════════════════════════════════════════════════════════╝
```

---

## Dependency Changes

### Before: Tightly Coupled (15+ concrete deps)

```
EditorialOrchestrator
├── QueryLegacyClient
├── QueryEditorialClient
├── QuerySectionClient
├── QueryMultimediaClient
├── QueryTagClient
├── QueryMembershipClient
├── BodyDataTransformer
├── AppsDataTransformer
├── JournalistsDataTransformer
├── MultimediaOrchestratorHandler
├── UriFactoryInterface
├── LoggerInterface
├── MultimediaTrait (hidden: Thumbor)
└── UrlGeneratorTrait (hidden: deps)
```

### After: Loosely Coupled (5 interface deps)

```
GetEditorialQuery
├── EditorialDataAggregator
│   ├── DataFetcherInterface[] (via tagged services)
│   └── PromiseResolverInterface
├── BodyDataTransformer
│   └── ElementVisitorInterface
└── LoggerInterface
```

**Reduction**: 15+ concrete → 5 interface dependencies

---

## New Directory Structure

```
src/
├── Application/
│   ├── Aggregator/                    [NEW]
│   │   └── EditorialDataAggregator.php
│   ├── Fetcher/                       [NEW]
│   │   ├── DataFetcherInterface.php
│   │   ├── SectionDataFetcher.php
│   │   ├── TagDataFetcher.php
│   │   ├── MultimediaDataFetcher.php
│   │   ├── MembershipDataFetcher.php
│   │   ├── JournalistDataFetcher.php
│   │   └── RecommendedDataFetcher.php
│   ├── Query/                         [NEW]
│   │   └── GetEditorialQuery.php
│   ├── Service/                       [NEW]
│   │   └── UrlGeneratorInterface.php
│   ├── Visitor/                       [NEW]
│   │   ├── TransformableElement.php
│   │   ├── ElementVisitorInterface.php
│   │   └── JsonElementVisitor.php
│   └── DataTransformer/               [EXISTING - MODIFIED]
│
├── Domain/
│   └── Port/                          [NEW]
│       ├── MultimediaPortInterface.php
│       └── PictureShotsPortInterface.php
│
└── Infrastructure/
    ├── Adapter/                       [NEW]
    │   ├── AsyncHttpClientAdapter.php
    │   ├── SyncHttpClientAdapter.php
    │   ├── GuzzlePromiseResolver.php
    │   └── ThumborPictureShotsAdapter.php
    ├── Factory/                       [NEW]
    │   └── HttpClientFactory.php
    ├── Port/                          [NEW]
    │   ├── HttpClientInterface.php
    │   └── PromiseResolverInterface.php
    ├── Service/                       [NEW]
    │   └── ThumborUrlGenerator.php
    └── Trait/                         [DELETE]
```

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Response JSON differs | LOW | CRITICAL | Snapshot tests before ANY change |
| Performance regression | LOW | HIGH | Benchmark tests, async preserved |
| Test coverage drops | MEDIUM | MEDIUM | TDD - tests first |
| Hidden deps in traits | HIGH | HIGH | Extract traits FIRST phase |
| Breaking existing tests | HIGH | MEDIUM | Update mocks incrementally |
| Cyclic dependencies | LOW | HIGH | Layer architecture enforced |

---

## Migration Strategy

### Phase 1: Infrastructure Ports (Low Risk)
1. Create port interfaces
2. Create adapters
3. No existing code modified yet

### Phase 2: Extract Traits (Medium Risk)
1. Create UrlGeneratorInterface
2. Create ThumborUrlGenerator
3. Replace trait usage
4. Delete traits

### Phase 3: Data Fetchers (Medium Risk)
1. Create DataFetcherInterface
2. Extract fetchers one by one
3. Each fetcher is independently testable

### Phase 4: Visitor Pattern (Medium Risk)
1. Add TransformableElement to body elements
2. Create ElementVisitorInterface
3. Create JsonElementVisitor
4. Update BodyDataTransformer

### Phase 5: Orchestrator Slim-Down (High Risk)
1. Create EditorialDataAggregator
2. Create GetEditorialQuery
3. Update EditorialController
4. Deprecate old EditorialOrchestrator

### Phase 6: Integration Tests
1. Snapshot tests for response comparison
2. E2E tests for full flow
3. Performance benchmarks

---

## Rollback Points

| Phase | Rollback Strategy |
|-------|-------------------|
| 1 | Delete new files, no impact |
| 2 | Restore traits from git |
| 3 | Restore original fetching logic |
| 4 | Remove visitor methods from elements |
| 5 | Restore original orchestrator |
| 6 | N/A - tests only |

Each phase can be rolled back independently.

---

## Success Metrics

| Metric | Current | Target | Measurement |
|--------|---------|--------|-------------|
| SOLID Score | 8/25 | ≥22/25 | Manual review |
| Max class lines | 536 | ≤150 | PHPStan rule |
| Test mocks per class | 14+ | ≤5 | Code review |
| Response JSON | N/A | Identical | Snapshot tests |
| Latency p99 | X ms | ≤X ms | APM |
| MSI | 79% | ≥79% | Infection |
