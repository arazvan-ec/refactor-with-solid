# Plan: Cache con Couchbase + Invalidación por Eventos

## Resumen Ejecutivo

Implementar Couchbase como capa de caché para SNAAPI con invalidación automática mediante eventos de los microservicios.

```
┌─────────────┐     HTTP      ┌─────────────────────────────────┐
│   Mobile    │ ─────────►   │           SNAAPI                │
│    App      │              │  ┌─────────────────────────┐    │
└─────────────┘              │  │    CouchbaseCache       │    │
                             │  │  (PSR-16 SimpleCache)   │    │
                             │  └───────────┬─────────────┘    │
                             │              │                   │
                             │     HIT? ────┼──── MISS?        │
                             │       │      │        │          │
                             │       ▼      │        ▼          │
                             │    Return    │    HTTP Call      │
                             │              │    + Store        │
                             └──────────────┼───────────────────┘
                                            │
         ┌──────────────────────────────────┼──────────────────────────────────┐
         │                                  │ Invalidation Events              │
         ▼                                  ▼                                  ▼
┌─────────────────┐              ┌─────────────────┐              ┌─────────────────┐
│ Editorial       │              │ Multimedia      │              │ Section         │
│ Microservice    │              │ Microservice    │              │ Microservice    │
│                 │              │                 │              │                 │
│ emit: editorial │              │ emit: multimedia│              │ emit: section   │
│ ::updated       │              │ ::updated       │              │ ::updated       │
└─────────────────┘              └─────────────────┘              └─────────────────┘
```

---

## Estado Actual (Ya lo tienen)

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| Symfony Messenger | ✅ Configurado | `config/packages/messenger.yaml` |
| RabbitMQ transport | ✅ Funcionando | `exchange::events` |
| Event routing | ✅ EventEditorial | `snaapi` transport |
| Warmup transport | ✅ Configurado | `exchange::commands` |
| PSR-16 en clientes | ✅ CacheInterface | `QueryLegacyClient.php` |

---

## Fases de Implementación

### Fase 1: Adapter Couchbase PSR-16
**Objetivo**: Crear adapter que implemente `Psr\SimpleCache\CacheInterface`

#### 1.1 Instalar SDK de Couchbase
```bash
# En Dockerfile o sistema
pecl install couchbase

# En composer
composer require couchbase/couchbase
```

#### 1.2 Crear CouchbaseCacheAdapter
```
snaapi/src/Infrastructure/Cache/
├── CouchbaseCacheAdapter.php      # Implementa PSR-16
├── CouchbaseConnection.php        # Factory de conexión
└── CacheKeyGenerator.php          # Genera keys consistentes
```

**Estructura de documento en Couchbase:**
```json
{
  "_type": "cache",
  "key": "editorial:4433",
  "value": { /* datos del editorial */ },
  "metadata": {
    "created_at": "2026-02-03T10:00:00Z",
    "ttl": 7200,
    "source": "editorial-service",
    "version": "v1"
  }
}
```

**Estrategia de keys:**
```
snaapi:editorial:{id}           → Editorial completo
snaapi:editorial:{id}:section   → Sección del editorial
snaapi:editorial:{id}:tags      → Tags del editorial
snaapi:multimedia:{id}          → Multimedia
snaapi:section:{id}             → Sección
snaapi:tag:{id}                 → Tag
snaapi:journalist:{id}          → Periodista
```

#### 1.3 Configuración Symfony
```yaml
# config/packages/couchbase.yaml
parameters:
    couchbase_connection_string: '%env(COUCHBASE_CONNECTION_STRING)%'
    couchbase_username: '%env(COUCHBASE_USERNAME)%'
    couchbase_password: '%env(COUCHBASE_PASSWORD)%'
    couchbase_bucket: '%env(COUCHBASE_BUCKET)%'
    couchbase_scope: '%env(COUCHBASE_SCOPE)%'
    couchbase_collection: 'cache'

services:
    App\Infrastructure\Cache\CouchbaseConnection:
        arguments:
            $connectionString: '%couchbase_connection_string%'
            $username: '%couchbase_username%'
            $password: '%couchbase_password%'
            $bucket: '%couchbase_bucket%'

    App\Infrastructure\Cache\CouchbaseCacheAdapter:
        arguments:
            $connection: '@App\Infrastructure\Cache\CouchbaseConnection'
            $scope: '%couchbase_scope%'
            $collection: '%couchbase_collection%'
            $defaultTtl: 7200

    # Alias para PSR-16
    Psr\SimpleCache\CacheInterface:
        alias: App\Infrastructure\Cache\CouchbaseCacheAdapter
```

---

### Fase 2: Integrar Cache en Clientes HTTP
**Objetivo**: Los clientes usen Couchbase transparentemente

#### 2.1 Modificar inyección en clientes
```yaml
# config/packages/editorial/infrastructure.yaml
services:
    Ec\Editorial\Infrastructure\Client\Http\QueryEditorialClient:
        arguments:
            $cacheAdapter: '@Psr\SimpleCache\CacheInterface'
```

#### 2.2 Cache-Aside Pattern en ServiceClient
El patrón ya existe en `ServiceClient.execute()`. Solo necesita el adapter correcto.

```php
// Flujo existente (no modificar)
public function execute(Request $request, bool $async, bool $cached, int $ttl): Promise
{
    if ($cached && $this->cacheAdapter) {
        $key = $this->generateCacheKey($request);

        if ($data = $this->cacheAdapter->get($key)) {
            return new FulfilledPromise($data);  // Cache HIT
        }

        // Cache MISS: fetch + store
        return $this->doRequest($request)->then(function ($response) use ($key, $ttl) {
            $this->cacheAdapter->set($key, $response, $ttl);
            return $response;
        });
    }

    return $this->doRequest($request);
}
```

---

### Fase 3: Event Handlers para Invalidación
**Objetivo**: Escuchar eventos y limpiar cache correspondiente

#### 3.1 Estructura de eventos esperados
```php
// Eventos que emiten los microservicios
namespace Ec\Editorial\Domain\Model;

class EventEditorial {
    public function __construct(
        public readonly string $eventType,  // 'created', 'updated', 'deleted'
        public readonly string $editorialId,
        public readonly ?array $affectedFields = null,
    ) {}
}

// Similar para otros dominios
class EventMultimedia { /* ... */ }
class EventSection { /* ... */ }
class EventTag { /* ... */ }
```

#### 3.2 Crear Message Handlers
```
snaapi/src/Application/MessageHandler/CacheInvalidation/
├── EditorialCacheInvalidationHandler.php
├── MultimediaCacheInvalidationHandler.php
├── SectionCacheInvalidationHandler.php
├── TagCacheInvalidationHandler.php
└── JournalistCacheInvalidationHandler.php
```

**Ejemplo handler:**
```php
<?php

declare(strict_types=1);

namespace App\Application\MessageHandler\CacheInvalidation;

use App\Infrastructure\Cache\CouchbaseCacheAdapter;
use App\Infrastructure\Cache\CacheKeyGenerator;
use Ec\Editorial\Domain\Model\EventEditorial;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final class EditorialCacheInvalidationHandler
{
    public function __construct(
        private readonly CouchbaseCacheAdapter $cache,
        private readonly CacheKeyGenerator $keyGenerator,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(EventEditorial $event): void
    {
        $keys = $this->keyGenerator->getEditorialRelatedKeys($event->editorialId);

        foreach ($keys as $key) {
            $this->cache->delete($key);
            $this->logger->info('Cache invalidated', [
                'key' => $key,
                'event_type' => $event->eventType,
                'editorial_id' => $event->editorialId,
            ]);
        }

        // Si el editorial tiene multimedia, invalidar esas keys también
        if ($event->affectedFields && in_array('multimedia', $event->affectedFields, true)) {
            $this->invalidateRelatedMultimedia($event->editorialId);
        }
    }

    private function invalidateRelatedMultimedia(string $editorialId): void
    {
        // Obtener IDs de multimedia desde metadata en cache o hacer query
        $pattern = $this->keyGenerator->getPattern("editorial:{$editorialId}:multimedia:*");
        $this->cache->deleteByPattern($pattern);
    }
}
```

#### 3.3 Configurar routing de mensajes
```yaml
# config/packages/messenger.yaml (añadir)
framework:
    messenger:
        routing:
            # Existentes
            Ec\Editorial\Domain\Model\EventEditorial: snaapi

            # Nuevos eventos para invalidación
            Ec\Multimedia\Domain\Model\EventMultimedia: snaapi
            Ec\Section\Domain\Model\EventSection: snaapi
            Ec\Tag\Domain\Model\EventTag: snaapi
            Ec\Journalist\Domain\Model\EventJournalist: snaapi

        transports:
            snaapi:
                options:
                    queues:
                        queue::event::snaapi:
                            binding_keys:
                                - "editorial::event"
                                - "multimedia::event"    # Nuevo
                                - "section::event"       # Nuevo
                                - "tag::event"           # Nuevo
                                - "journalist::event"    # Nuevo
```

---

### Fase 4: Cache Warming (Opcional pero recomendado)
**Objetivo**: Pre-calentar cache para contenido popular

#### 4.1 Crear comando de warmup
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'snaapi:cache:warmup',
    description: 'Pre-load popular editorials into cache'
)]
final class CacheWarmupCommand extends Command
{
    // Obtiene top editorials y los carga en cache
}
```

#### 4.2 Handler de warmup via Messenger
```php
// Ya tienen el transport configurado: warmup
// Crear handler que responda a Ec\Cqrs\Messages\CommandNotification
```

---

### Fase 5: Observabilidad
**Objetivo**: Monitorear hit rate, latencia, invalidaciones

#### 5.1 Métricas a exponer
```php
// Prometheus metrics
snaapi_cache_hits_total{type="editorial"}
snaapi_cache_misses_total{type="editorial"}
snaapi_cache_invalidations_total{type="editorial", reason="event"}
snaapi_cache_latency_seconds{operation="get"}
snaapi_cache_latency_seconds{operation="set"}
snaapi_event_processing_seconds{type="editorial"}
```

#### 5.2 Logs estructurados
```json
{
  "level": "info",
  "message": "cache_invalidated",
  "context": {
    "key": "snaapi:editorial:4433",
    "event_type": "updated",
    "source_service": "editorial-service",
    "processing_time_ms": 2.3
  }
}
```

---

## Estructura de Archivos Final

```
snaapi/src/
├── Infrastructure/
│   ├── Cache/
│   │   ├── CouchbaseCacheAdapter.php       # PSR-16 implementation
│   │   ├── CouchbaseConnection.php         # Connection factory
│   │   ├── CacheKeyGenerator.php           # Key generation strategy
│   │   └── CacheMetrics.php                # Prometheus metrics
│   │
│   └── Command/
│       └── CacheWarmupCommand.php          # CLI warmup
│
├── Application/
│   └── MessageHandler/
│       └── CacheInvalidation/
│           ├── EditorialCacheInvalidationHandler.php
│           ├── MultimediaCacheInvalidationHandler.php
│           ├── SectionCacheInvalidationHandler.php
│           ├── TagCacheInvalidationHandler.php
│           └── JournalistCacheInvalidationHandler.php
│
└── config/
    └── packages/
        └── couchbase.yaml                   # Couchbase config
```

---

## Variables de Entorno Requeridas

```bash
# .env
COUCHBASE_CONNECTION_STRING=couchbase://couchbase.internal:11210
COUCHBASE_USERNAME=snaapi
COUCHBASE_PASSWORD=secret
COUCHBASE_BUCKET=snaapi-cache
COUCHBASE_SCOPE=_default
```

---

## Configuración Couchbase (Bucket)

```json
{
  "name": "snaapi-cache",
  "bucketType": "couchbase",
  "ramQuotaMB": 1024,
  "replicaNumber": 1,
  "evictionPolicy": "valueOnly",
  "maxTTL": 86400,
  "compressionMode": "passive"
}
```

**Índices recomendados:**
```sql
-- Para queries de invalidación por patrón
CREATE INDEX idx_cache_type ON `snaapi-cache`._default.cache(_type)
WHERE _type = "cache";

CREATE INDEX idx_cache_prefix ON `snaapi-cache`._default.cache(
    SUBSTR(key, 0, POSITION(key, ":"))
) WHERE _type = "cache";
```

---

## Cronograma Estimado

| Fase | Descripción | Dependencias |
|------|-------------|--------------|
| 1 | Adapter Couchbase PSR-16 | SDK instalado |
| 2 | Integrar en clientes | Fase 1 |
| 3 | Event handlers invalidación | Fase 2 + Eventos de microservicios |
| 4 | Cache warming | Fase 3 |
| 5 | Observabilidad | Fase 3 |

---

## Rollback Strategy

1. **Feature flag** para habilitar/deshabilitar cache:
```yaml
parameters:
    cache_enabled: '%env(bool:CACHE_ENABLED)%'
```

2. **Fallback a filesystem** si Couchbase no responde:
```php
class CouchbaseCacheAdapter implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return $this->couchbase->get($key);
        } catch (CouchbaseException $e) {
            $this->logger->error('Couchbase unavailable, skipping cache');
            return $default;  // Cache MISS, continúa con HTTP call
        }
    }
}
```

---

## Consideraciones de Consistencia

### Escenario: Race Condition
```
T1: Usuario actualiza editorial
T2: Request llega a SNAAPI (cache HIT con dato viejo)
T3: Evento de invalidación llega
T4: Cache invalidado
T5: Siguiente request obtiene dato nuevo
```

**Mitigación**: TTL corto (60-120 seg) + invalidación por eventos = ventana de inconsistencia pequeña.

### Escenario: Evento perdido
**Mitigación**:
- TTL garantiza que datos viejos expiran
- Dead letter queue para reintentos
- Monitoreo de lag en RabbitMQ

---

## Métricas de Éxito

| Métrica | Objetivo | Medición |
|---------|----------|----------|
| Cache Hit Rate | > 80% | Prometheus |
| P95 Latency (cache hit) | < 10ms | APM |
| P95 Latency (cache miss) | < 200ms | APM |
| Event processing lag | < 5s | RabbitMQ metrics |
| Inconsistency window | < 10s | Logs correlation |

---

## Siguiente Paso

1. Crear el `CouchbaseCacheAdapter.php` con implementación PSR-16
2. Tests unitarios para el adapter
3. Configurar en entorno de desarrollo
4. Probar invalidación con eventos mock
