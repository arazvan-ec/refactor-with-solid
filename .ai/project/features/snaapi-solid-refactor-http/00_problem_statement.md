# Problem Statement: SNAAPI SOLID Refactoring

## Request Analysis

**Original Request**: Refactoring del código para cumplir SOLID de manera rigurosa, con patrones de diseño apropiados para manejar peticiones HTTP async/sync y transformación a JSON.

**Request Type**: `refactor`

**Affected Areas**:
- `snaapi/src/Orchestrator/` - Orquestación de servicios externos
- `snaapi/src/Application/DataTransformer/` - Transformación de datos a JSON
- `snaapi/src/Infrastructure/Client/` - Clientes HTTP
- `snaapi/src/Ec/Snaapi/Infrastructure/` - Legacy clients

**Confidence Level**: 95%

---

## What We're Building

Un refactoring arquitectónico completo del API Gateway SNAAPI para:

1. **Cumplir SOLID de forma rigurosa** (score objetivo ≥22/25)
2. **Separar responsabilidades** de los componentes monolíticos actuales
3. **Aplicar patrones de diseño** apropiados para HTTP async/sync
4. **Mejorar la extensibilidad** sin modificar código existente
5. **Reducir acoplamiento** entre capas

---

## Why It's Needed

### Estado Actual - SOLID Score: ~8/25 (FAILING)

| Principio | Score Actual | Problemas |
|-----------|--------------|-----------|
| **SRP** | 1/5 | EditorialOrchestrator: 536 líneas, 15+ responsabilidades |
| **OCP** | 2/5 | `get_class()` lookups, Compiler Pass obligatorio |
| **LSP** | 2/5 | `instanceof` checks, jerarquía rota |
| **ISP** | 2/5 | Interfaces fat (write+read), mezcla de concerns |
| **DIP** | 1/5 | 15+ dependencias concretas, traits con dependencias ocultas |

### Impacto Actual

- **Testabilidad**: 14+ mocks necesarios para test de EditorialOrchestrator
- **Mantenibilidad**: Test de 1700 líneas para una sola clase
- **Extensibilidad**: Agregar nuevo tipo = modificar código existente
- **Comprensión**: Traits ocultan dependencias reales

---

## Who Benefits

| Stakeholder | Beneficio |
|-------------|-----------|
| **Desarrolladores** | Código más fácil de entender y modificar |
| **QA** | Tests más simples y enfocados |
| **Ops** | Mejor aislamiento de fallos |
| **Producto** | Nuevas features sin riesgo de regresiones |

---

## Constraints

### Technical
- **Stack**: Symfony 6.4, PHP 8.2+, PHPStan Level 9
- **Performance**: Mantener latencia actual (HTTP async)
- **Compatibilidad**: API responses deben ser idénticos (backward compatible)
- **Testing**: Mutation Score Index ≥79%

### Business
- **Zero downtime**: Refactoring incremental
- **No breaking changes**: API contract inviolable

### Team
- **Code review**: PHPStan level 9 obligatorio
- **TDD**: Tests primero, implementación después

---

## Success Criteria

### Obligatorios
1. [ ] SOLID Score ≥22/25 (actualmente ~8/25)
2. [ ] EditorialOrchestrator dividido en ≤5 clases de ≤150 líneas cada una
3. [ ] Cero `instanceof` checks en Orchestrator
4. [ ] Cero `get_class()` lookups en handlers
5. [ ] Todas las dependencias vía interfaces (DIP)
6. [ ] Tests sin más de 5 mocks por clase
7. [ ] PHPStan level 9 sin errores
8. [ ] MSI ≥79%

### Deseables
1. [ ] Documentación de patrones aplicados
2. [ ] ADRs (Architecture Decision Records) para decisiones clave
3. [ ] Benchmarks pre/post refactoring

---

## Current Architecture Analysis

### HTTP Client Layer
```
QueryLegacyClient ─────┐
QueryEditorialClient ──┼───► EditorialOrchestrator (God Class)
QuerySectionClient ────┤         │
QueryMultimediaClient ─┤         ├── 15+ responsibilities
QueryTagClient ────────┤         ├── 536 lines
QueryMembershipClient ─┘         └── Uses 2 traits with hidden deps
```

### Async/Sync Pattern (actual)
```php
// Current: Flag-based async (violates OCP)
$editorial = $this->queryEditorialClient->findEditorialById($id, $async = true);
// Returns Promise when async, blocks when sync
```

### Transformation Layer
```
BodyElement ──► BodyElementDataTransformerHandler ──► Specific Transformer
                      │
                      └── get_class($element) lookup (violates OCP)
```

---

## SOLID Violations Summary

### SRP Violations (CRITICAL)
| Class | Lines | Responsibilities |
|-------|-------|-----------------|
| EditorialOrchestrator | 536 | 15+ (fetch, transform, resolve promises, extract data, etc.) |
| BodyTagPictureDataTransformer | ~80 | 2 (transform + fetch shots) |

### OCP Violations (HIGH)
| Component | Issue |
|-----------|-------|
| BodyElementDataTransformerHandler | `get_class()` lookup requires modification |
| MediaDataTransformerHandler | Same pattern |
| MultimediaOrchestratorHandler | Type-based routing |

### LSP Violations (HIGH)
| Location | Issue |
|----------|-------|
| EditorialOrchestrator:469 | `if (!$multimedia instanceof MultimediaPhoto)` |
| EditorialOrchestrator:71 | `if ($multimedia instanceof Video \|\| $multimedia instanceof Widget)` |

### ISP Violations (MEDIUM)
| Interface | Issue |
|-----------|-------|
| BodyElementDataTransformer | Forces `write()` + `read()` |
| MediaDataTransformer | Mixes Opening with Multimedia |

### DIP Violations (CRITICAL)
| Class | Concrete Dependencies |
|-------|----------------------|
| EditorialOrchestrator | 15+ concrete classes |
| BodyTagPictureDataTransformer | PictureShots (concrete) |
| Traits | Thumbor (hidden) |

---

## Target Architecture (High Level)

```
┌─────────────────────────────────────────────────────────────────┐
│                         PRESENTATION                             │
│   EditorialController                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         APPLICATION                              │
│   ┌─────────────────┐    ┌─────────────────┐                    │
│   │ EditorialQuery  │    │ TransformerChain│                    │
│   │ (Use Case)      │    │ (Strategy)      │                    │
│   └────────┬────────┘    └────────┬────────┘                    │
│            │                      │                              │
│   ┌────────▼────────┐    ┌────────▼────────┐                    │
│   │ DataAggregator  │    │ ElementTransform│                    │
│   │ (Facade)        │    │ (Visitor)       │                    │
│   └─────────────────┘    └─────────────────┘                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       INFRASTRUCTURE                             │
│   ┌─────────────────┐    ┌─────────────────┐                    │
│   │ HttpClientPort  │◄───│ AsyncHttpClient │                    │
│   │ (Interface)     │    │ (Adapter)       │                    │
│   └─────────────────┘    └─────────────────┘                    │
│                                                                  │
│   ┌─────────────────┐    ┌─────────────────┐                    │
│   │ SyncHttpClient  │    │ PromiseResolver │                    │
│   │ (Adapter)       │    │ (Strategy)      │                    │
│   └─────────────────┘    └─────────────────┘                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## Next Steps

1. **PHASE 2**: Definir specs funcionales + análisis de integración
2. **PHASE 3**: Diseñar soluciones con patrones SOLID
3. **Task Breakdown**: Crear tareas específicas con TDD
