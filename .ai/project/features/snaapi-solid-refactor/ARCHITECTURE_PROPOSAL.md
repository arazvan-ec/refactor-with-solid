# Arquitectura Propuesta: SNAAPI Optimizado

**Fecha**: 2026-02-03
**Versión**: 2.0.0
**Enfoque**: SOLID + Paralelización HTTP

---

## Contexto del Negocio

SNAAPI es una API REST que:
1. Recibe un ID de editorial
2. Valida si está publicada
3. Extrae IDs de agregadores del body/metadata
4. Hace peticiones HTTP a microservicios (algunas bloqueantes, otras no)
5. Transforma y devuelve JSON completo

**Problema actual**: Muchas peticiones HTTP son secuenciales cuando podrían ser paralelas.

---

## Análisis de Flujo HTTP Actual

### Peticiones Identificadas (9 clientes)

| Cliente | Microservicio | Async Soportado | Uso Actual |
|---------|---------------|-----------------|------------|
| QueryEditorialClient | Editorial | ❌ | Secuencial |
| QuerySectionClient | Section | ❌ | Secuencial |
| QueryMultimediaClient | Multimedia | ✅ | **Paralelo** |
| QueryTagClient | Tag | ❌ | Secuencial (loop) |
| QueryJournalistClient | Journalist | ❌ | Secuencial (loop) |
| QueryMembershipClient | Membership | ✅ | **Paralelo** |
| QueryLegacyClient | Legacy | ✅ | Mixto |
| QueryWidgetClient | Widget | ❌ | Secuencial |
| QueryMultimediaOpeningClient | Multimedia/Media | ❌ | Secuencial |

### Grafo de Dependencias HTTP

```
NIVEL 0: Entrada
    └── Editorial ID (del request)

NIVEL 1: Fetch Principal (BLOQUEANTE - necesario)
    └── Editorial ──► QueryEditorialClient.findEditorialById()
                      ↓
                      Valida isVisible()
                      Extrae: sectionId, body, multimedia, tags, signatures, etc.

NIVEL 2: Sin dependencias entre sí (PARALELIZABLE)
    ┌── Section ──────► QuerySectionClient.findSectionById(sectionId)
    ├── Tags[N] ──────► QueryTagClient.findTagById(tagId) × N
    ├── Signatures[N] ► QueryJournalistClient.findJournalistByAliasId() × N
    ├── Multimedia ───► QueryMultimediaClient.findMultimediaById() [YA ASYNC]
    ├── Opening ──────► QueryMultimediaOpeningClient.findMultimediaById()
    ├── MetaImage ────► QueryMultimediaOpeningClient.findMultimediaById()
    ├── Comments ─────► QueryLegacyClient.findCommentsByEditorialId()
    └── Membership ───► QueryMembershipClient.getMembershipUrl() [YA ASYNC]

NIVEL 3: Depende de body parsing (PARALELIZABLE internamente)
    ┌── InsertedNews[N] ────► Para cada noticia insertada:
    │   ├── Editorial ──────► QueryEditorialClient.findEditorialById()
    │   ├── Section ────────► QuerySectionClient.findSectionById()
    │   ├── Journalists[M] ─► QueryJournalistClient × M
    │   └── Multimedia ─────► QueryMultimediaClient [ASYNC]
    │
    ├── RecommendedEditorials[N] ► Para cada recomendada:
    │   ├── Editorial ──────► QueryEditorialClient.findEditorialById()
    │   ├── Section ────────► QuerySectionClient.findSectionById()
    │   ├── Journalists[M] ─► QueryJournalistClient × M
    │   └── Multimedia ─────► QueryMultimediaClient [ASYNC]
    │
    └── BodyPhotos[N] ──────► QueryMultimediaClient.findPhotoById() × N

NIVEL 4: Transformación (SECUENCIAL - depende de datos)
    └── DataTransformers ──► Transformar a JSON
```

---

## Oportunidades de Paralelización

### Estado Actual vs Propuesto

| Operación | Actual | Propuesto | Ganancia |
|-----------|--------|-----------|----------|
| Section | Secuencial | Paralelo L2 | ✅ |
| Tags (×N) | Loop secuencial | Batch paralelo | ✅✅ |
| Journalists (×N) | Loop secuencial | Batch paralelo | ✅✅ |
| Comments | Secuencial | Paralelo L2 | ✅ |
| Opening/MetaImage | Secuencial | Paralelo L2 | ✅ |
| InsertedNews (×N) | Loop parcial async | Full parallel | ✅✅ |
| RecommendedEditorials (×N) | Loop parcial async | Full parallel | ✅✅ |
| BodyPhotos (×N) | Loop secuencial | Batch paralelo | ✅✅ |

**Estimación de mejora**: 40-60% reducción en tiempo de respuesta

---

## Arquitectura Propuesta

### Principio de Diseño: Pipeline de Agregación

```
┌─────────────────────────────────────────────────────────────────┐
│                     REQUEST: GET /editorial/{id}                 │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ FASE 1: FETCH & VALIDATE (Bloqueante necesario)                 │
│                                                                  │
│  EditorialFetcher                                                │
│  ├── Fetch editorial por ID                                      │
│  ├── Validar isVisible()                                         │
│  └── Retornar Editorial + AggregatorIds                          │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ FASE 2: EXTRACT AGGREGATOR IDS (CPU-only, rápido)               │
│                                                                  │
│  AggregatorIdExtractor                                           │
│  ├── extractSectionId(editorial) → SectionId                     │
│  ├── extractTagIds(editorial) → TagId[]                          │
│  ├── extractSignatureIds(editorial) → AliasId[]                  │
│  ├── extractMultimediaIds(editorial) → MultimediaId[]            │
│  ├── extractInsertedNewsIds(body) → EditorialId[]                │
│  ├── extractRecommendedIds(editorial) → EditorialId[]            │
│  ├── extractBodyPhotoIds(body) → PhotoId[]                       │
│  └── extractMembershipLinks(body) → Uri[]                        │
│                                                                  │
│  Output: AggregatorManifest {                                    │
│    sectionId, tagIds[], signatureIds[], multimediaIds[],         │
│    insertedNewsIds[], recommendedIds[], photoIds[], links[]      │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ FASE 3: PARALLEL HTTP FETCH (Máxima paralelización)             │
│                                                                  │
│  ParallelDataFetcher (usando Promises)                           │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ BATCH 1: Nivel 2 - Datos directos de editorial              ││
│  │                                                              ││
│  │  Promise.all([                                               ││
│  │    sectionClient.find(sectionId),           // 1 call       ││
│  │    tagClient.findBatch(tagIds),             // 1 batch call ││
│  │    journalistClient.findBatch(aliasIds),    // 1 batch call ││
│  │    multimediaClient.findBatch(mmIds),       // 1 batch call ││
│  │    multimediaClient.findOpening(openingId), // 1 call       ││
│  │    legacyClient.findComments(editorialId),  // 1 call       ││
│  │    membershipClient.getUrls(links),         // 1 call       ││
│  │  ])                                                          ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ BATCH 2: Nivel 3 - Noticias insertadas (en paralelo)        ││
│  │                                                              ││
│  │  Promise.all(insertedNewsIds.map(id =>                       ││
│  │    fetchInsertedNewsWithDeps(id)  // Cada una en paralelo   ││
│  │  ))                                                          ││
│  │                                                              ││
│  │  Donde fetchInsertedNewsWithDeps(id) hace:                   ││
│  │    Promise.all([                                             ││
│  │      editorialClient.find(id),                               ││
│  │      // Después de obtener editorial:                        ││
│  │      sectionClient.find(sectionId),                          ││
│  │      journalistClient.findBatch(signatureIds),               ││
│  │      multimediaClient.find(mmId),                            ││
│  │    ])                                                        ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ BATCH 3: Nivel 3 - Recomendadas (mismo patrón)              ││
│  │                                                              ││
│  │  Promise.all(recommendedIds.map(id =>                        ││
│  │    fetchRecommendedWithDeps(id)                              ││
│  │  ))                                                          ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ BATCH 4: Fotos del body                                     ││
│  │                                                              ││
│  │  Promise.all(photoIds.map(id =>                              ││
│  │    multimediaClient.findPhoto(id)                            ││
│  │  ))                                                          ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  Output: ResolvedAggregatorData {                                │
│    section, tags[], journalists[], multimedia[],                 │
│    insertedNews[], recommended[], photos[], membershipUrls[]     │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ FASE 4: TRANSFORM (Composición de transformadores)              │
│                                                                  │
│  EditorialResponseComposer                                       │
│  ├── SectionTransformer.transform(section)                       │
│  ├── TagsTransformer.transform(tags[])                           │
│  ├── SignaturesTransformer.transform(journalists[])              │
│  ├── MultimediaTransformer.transform(multimedia)                 │
│  ├── BodyTransformer.transform(body, photos[], membership[])     │
│  ├── InsertedNewsTransformer.transform(insertedNews[])           │
│  └── RecommendedTransformer.transform(recommended[])             │
│                                                                  │
│  Output: EditorialResponse (JSON-ready)                          │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                     RESPONSE: JsonResponse                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## Estructura de Clases Propuesta

### Capa de Orquestación (Refactorizada)

```
src/Orchestrator/
├── EditorialOrchestrator.php          # Coordinador principal (simplificado)
├── Pipeline/
│   ├── EditorialPipeline.php          # Ejecuta las 4 fases
│   ├── PipelineContext.php            # Contexto compartido entre fases
│   └── PipelineStage.php              # Interface para cada fase
├── Fetcher/
│   ├── EditorialFetcherInterface.php  # DIP
│   ├── EditorialFetcher.php           # Fase 1
│   └── LegacyEditorialFetcher.php     # Fallback legacy
├── Extractor/
│   ├── AggregatorIdExtractorInterface.php
│   ├── AggregatorIdExtractor.php      # Fase 2
│   ├── AggregatorManifest.php         # Value Object
│   └── Strategies/
│       ├── TagIdExtractor.php
│       ├── SignatureIdExtractor.php
│       ├── MultimediaIdExtractor.php
│       ├── InsertedNewsIdExtractor.php
│       ├── RecommendedIdExtractor.php
│       ├── BodyPhotoIdExtractor.php
│       └── MembershipLinkExtractor.php
├── ParallelFetcher/
│   ├── ParallelDataFetcherInterface.php
│   ├── ParallelDataFetcher.php        # Fase 3
│   ├── BatchRequest.php               # Value Object
│   ├── ResolvedAggregatorData.php     # Value Object
│   └── Resolvers/
│       ├── SectionResolver.php
│       ├── TagBatchResolver.php
│       ├── JournalistBatchResolver.php
│       ├── MultimediaBatchResolver.php
│       ├── InsertedNewsBatchResolver.php
│       ├── RecommendedBatchResolver.php
│       └── BodyPhotoBatchResolver.php
└── Composer/
    ├── EditorialResponseComposerInterface.php
    ├── EditorialResponseComposer.php  # Fase 4
    └── EditorialResponse.php          # DTO
```

### Capa de Transformación (Refactorizada)

```
src/Application/DataTransformer/
├── TransformerInterface.php           # Interface segregada
├── Composite/
│   └── CompositeTransformer.php       # Compone múltiples transformadores
├── Editorial/
│   ├── SectionTransformer.php         # SRP: Solo sección
│   ├── TagsTransformer.php            # SRP: Solo tags
│   ├── SignaturesTransformer.php      # SRP: Solo firmas
│   ├── TitlesTransformer.php          # SRP: Solo títulos
│   └── StandfirstTransformer.php      # SRP: Solo entradilla
├── Multimedia/
│   ├── MultimediaTransformerInterface.php
│   ├── PhotoTransformer.php
│   ├── VideoTransformer.php
│   ├── WidgetTransformer.php
│   └── Strategy/
│       └── MultimediaTransformerStrategy.php  # Strategy pattern
├── Body/
│   ├── BodyTransformerInterface.php
│   ├── BodyTransformer.php
│   └── BodyTag/
│       ├── ParagraphTransformer.php
│       ├── PictureTransformer.php
│       ├── InsertedNewsTransformer.php
│       ├── MembershipCardTransformer.php
│       └── ... (un transformador por tipo de tag)
└── Related/
    ├── InsertedNewsTransformer.php
    └── RecommendedEditorialsTransformer.php
```

### Interfaces Clave (DIP)

```php
// Capa de Fetching
interface EditorialFetcherInterface {
    public function fetch(EditorialId $id): Editorial;
}

interface ParallelDataFetcherInterface {
    public function fetchAll(AggregatorManifest $manifest): ResolvedAggregatorData;
}

// Capa de Extracción
interface AggregatorIdExtractorInterface {
    public function extract(Editorial $editorial): AggregatorManifest;
}

interface IdExtractorStrategyInterface {
    public function extract(Editorial $editorial): array;
    public function supports(string $aggregatorType): bool;
}

// Capa de Transformación
interface TransformerInterface {
    public function transform(mixed $data): array;
}

interface EditorialResponseComposerInterface {
    public function compose(
        Editorial $editorial,
        ResolvedAggregatorData $data
    ): EditorialResponse;
}
```

---

## Patrones de Diseño Aplicados

### 1. Pipeline Pattern (Orquestación)

```php
class EditorialPipeline {
    private array $stages;

    public function process(EditorialId $id): EditorialResponse {
        $context = new PipelineContext($id);

        foreach ($this->stages as $stage) {
            $context = $stage->execute($context);
        }

        return $context->getResponse();
    }
}
```

### 2. Strategy Pattern (Extracción de IDs)

```php
class AggregatorIdExtractor {
    /** @var IdExtractorStrategyInterface[] */
    private array $strategies;

    public function extract(Editorial $editorial): AggregatorManifest {
        $manifest = new AggregatorManifest();

        foreach ($this->strategies as $strategy) {
            $ids = $strategy->extract($editorial);
            $manifest->add($strategy->getType(), $ids);
        }

        return $manifest;
    }
}
```

### 3. Batch Resolver Pattern (Paralelización)

```php
class ParallelDataFetcher {
    public function fetchAll(AggregatorManifest $manifest): ResolvedAggregatorData {
        $promises = [];

        // Nivel 2: Datos directos
        $promises['section'] = $this->sectionResolver->resolve($manifest->sectionId);
        $promises['tags'] = $this->tagResolver->resolveBatch($manifest->tagIds);
        $promises['journalists'] = $this->journalistResolver->resolveBatch($manifest->signatureIds);
        $promises['multimedia'] = $this->multimediaResolver->resolveBatch($manifest->multimediaIds);
        $promises['comments'] = $this->commentsResolver->resolve($manifest->editorialId);
        $promises['membership'] = $this->membershipResolver->resolve($manifest->membershipLinks);

        // Nivel 3: Editoriales relacionadas
        $promises['insertedNews'] = $this->insertedNewsResolver->resolveBatch($manifest->insertedNewsIds);
        $promises['recommended'] = $this->recommendedResolver->resolveBatch($manifest->recommendedIds);

        // Nivel 3: Fotos del body
        $promises['bodyPhotos'] = $this->photoResolver->resolveBatch($manifest->photoIds);

        // Resolver todo en paralelo
        $results = Utils::settle($promises)->wait(true);

        return ResolvedAggregatorData::fromResults($results);
    }
}
```

### 4. Composite Pattern (Transformación)

```php
class EditorialResponseComposer {
    public function compose(
        Editorial $editorial,
        ResolvedAggregatorData $data
    ): EditorialResponse {
        return new EditorialResponse(
            id: $editorial->id()->id(),
            url: $this->urlTransformer->transform($editorial),
            titles: $this->titlesTransformer->transform($editorial),
            section: $this->sectionTransformer->transform($data->section),
            tags: $this->tagsTransformer->transform($data->tags),
            signatures: $this->signaturesTransformer->transform($data->journalists),
            multimedia: $this->multimediaTransformer->transform($data->multimedia),
            body: $this->bodyTransformer->transform($editorial->body(), $data),
            insertedNews: $this->insertedNewsTransformer->transform($data->insertedNews),
            recommended: $this->recommendedTransformer->transform($data->recommended),
            countComments: $data->commentsCount,
        );
    }
}
```

---

## Implementación de Batch Clients

Para maximizar la paralelización, los clientes necesitan soporte de batch:

```php
// Propuesta: Extender QueryTagClient
interface BatchTagClientInterface {
    /** @return Promise<Tag[]> */
    public function findBatch(array $tagIds): Promise;
}

class BatchTagClient implements BatchTagClientInterface {
    public function __construct(
        private QueryTagClient $client,
    ) {}

    public function findBatch(array $tagIds): Promise {
        $promises = array_map(
            fn(TagId $id) => $this->client->findTagByIdAsync($id),
            $tagIds
        );

        return Utils::all($promises);
    }
}
```

---

## Plan de Migración

### Fase 1: Crear Infraestructura Base

1. Crear interfaces (DIP)
2. Crear Value Objects (AggregatorManifest, ResolvedAggregatorData)
3. Crear BatchClients para clientes existentes
4. Tests unitarios para cada componente

### Fase 2: Implementar Pipeline

1. Crear EditorialFetcher
2. Crear AggregatorIdExtractor con estrategias
3. Crear ParallelDataFetcher con resolvers
4. Crear EditorialResponseComposer
5. Tests de integración

### Fase 3: Migrar EditorialOrchestrator

1. Refactorizar para usar Pipeline
2. Mantener compatibilidad con respuesta actual
3. Tests E2E para validar respuestas idénticas
4. Benchmarks antes/después

### Fase 4: Refactorizar Transformadores

1. Segregar interfaces
2. Crear transformadores SRP
3. Aplicar Strategy donde corresponda
4. Tests unitarios

---

## Métricas de Éxito

| Métrica | Actual | Objetivo |
|---------|--------|----------|
| Tiempo respuesta P50 | ~500ms | <300ms |
| Tiempo respuesta P95 | ~1500ms | <800ms |
| Peticiones HTTP secuenciales | ~15 | <5 |
| Líneas en EditorialOrchestrator | 536 | <100 |
| Dependencias en Orchestrator | 20 | <5 |
| Score SOLID | ~10/25 | ≥20/25 |
| Cobertura tests | - | ≥80% |

---

## Diagrama de Secuencia Optimizado

```
Request    Pipeline    Fetcher    Extractor    ParallelFetcher    Composer
   │          │           │           │              │               │
   │──GET────►│           │           │              │               │
   │          │──fetch───►│           │              │               │
   │          │◄──editorial──│        │              │               │
   │          │           │           │              │               │
   │          │──extract─────────────►│              │               │
   │          │◄──manifest────────────│              │               │
   │          │           │           │              │               │
   │          │──fetchAll────────────────────────────►│              │
   │          │           │           │              │               │
   │          │           │           │    ┌─────────┴─────────┐    │
   │          │           │           │    │ PARALLEL PROMISES │    │
   │          │           │           │    │ - Section         │    │
   │          │           │           │    │ - Tags[]          │    │
   │          │           │           │    │ - Journalists[]   │    │
   │          │           │           │    │ - Multimedia[]    │    │
   │          │           │           │    │ - InsertedNews[]  │    │
   │          │           │           │    │ - Recommended[]   │    │
   │          │           │           │    │ - Photos[]        │    │
   │          │           │           │    │ - Comments        │    │
   │          │           │           │    │ - Membership      │    │
   │          │           │           │    └─────────┬─────────┘    │
   │          │           │           │              │               │
   │          │◄──resolvedData────────────────────────│              │
   │          │           │           │              │               │
   │          │──compose────────────────────────────────────────────►│
   │          │◄──response──────────────────────────────────────────│
   │          │           │           │              │               │
   │◄──JSON───│           │           │              │               │
```

---

## Conclusión

Esta arquitectura:

1. **Maximiza paralelización**: Todas las peticiones HTTP independientes se ejecutan en paralelo
2. **Cumple SOLID**:
   - **SRP**: Cada clase una responsabilidad
   - **OCP**: Extensible via estrategias e interfaces
   - **LSP**: Todas las implementaciones son sustituibles
   - **ISP**: Interfaces pequeñas y específicas
   - **DIP**: Dependencias via interfaces
3. **Mejora mantenibilidad**: Componentes pequeños y testeables
4. **Reduce latencia**: Estimación 40-60% de mejora
