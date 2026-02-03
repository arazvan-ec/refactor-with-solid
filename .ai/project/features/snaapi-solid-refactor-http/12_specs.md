# Functional Specs: SNAAPI SOLID Refactoring

> **Nota**: Este documento define QUÉ debe hacer el sistema (requisitos funcionales).
> Los patrones y soluciones técnicas (CÓMO) se definen en `15_solutions.md`.

---

## SPEC-F01: HTTP Client Abstraction

**Description**: El sistema debe poder realizar peticiones HTTP a servicios externos de forma abstracta, sin conocer los detalles de implementación (sync vs async).

**Acceptance Criteria**:
- [ ] Caller puede solicitar datos sin especificar si es sync o async
- [ ] El sistema decide la estrategia de ejecución basándose en configuración
- [ ] Las respuestas tienen el mismo formato independientemente del modo de ejecución
- [ ] Errores HTTP se manejan de forma uniforme

**Verification**: Test unitario que verifica mismo resultado con diferentes estrategias.

---

## SPEC-F02: Promise Resolution

**Description**: El sistema debe resolver múltiples promesas HTTP de forma eficiente, agregando resultados de varios microservicios.

**Acceptance Criteria**:
- [ ] Múltiples requests pueden ejecutarse en paralelo
- [ ] Los resultados se agregan en un único objeto de respuesta
- [ ] Fallos parciales no bloquean la respuesta completa
- [ ] Timeout configurable por request

**Verification**: Test de integración con múltiples servicios mockeados.

---

## SPEC-F03: Editorial Data Aggregation

**Description**: El sistema debe agregar datos de editorial desde múltiples fuentes (Editorial, Section, Tag, Multimedia, Membership, Journalist).

**Acceptance Criteria**:
- [ ] Editorial básico se obtiene de QueryEditorialClient
- [ ] Sección se enriquece desde QuerySectionClient
- [ ] Tags se resuelven desde QueryTagClient
- [ ] Multimedia se obtiene desde QueryMultimediaClient
- [ ] Membership links se resuelven desde QueryMembershipClient
- [ ] Journalist info se obtiene para firmas

**Verification**: Test de integración verificando agregación completa.

---

## SPEC-F04: Body Element Transformation

**Description**: El sistema debe transformar body elements a formato JSON apropiado para apps móviles.

**Acceptance Criteria**:
- [ ] Cada tipo de body element produce JSON válido
- [ ] Nuevos tipos de elementos se pueden agregar sin modificar código existente
- [ ] Transformación es extensible vía configuración
- [ ] Elementos desconocidos producen output genérico (no error)

**Body Element Types**:
| Tipo | Output Esperado |
|------|-----------------|
| Paragraph | `{type: "paragraph", content: "..."}` |
| SubHead | `{type: "subhead", content: "..."}` |
| Picture | `{type: "picture", shots: [...], url: "..."}` |
| Video | `{type: "video", url: "...", provider: "..."}` |
| InsertedNews | `{type: "inserted_news", editorial: {...}}` |
| MembershipCard | `{type: "membership_card", links: [...]}` |
| Html | `{type: "html", content: "..."}` |
| List (ordered/unordered) | `{type: "list", items: [...], ordered: bool}` |

**Verification**: Test unitario por cada tipo de elemento.

---

## SPEC-F05: Multimedia Type Handling

**Description**: El sistema debe manejar diferentes tipos de multimedia (Photo, Video, Widget, EmbedVideo) de forma polimórfica.

**Acceptance Criteria**:
- [ ] Todos los tipos de multimedia implementan contrato común
- [ ] Opening multimedia se procesa correctamente
- [ ] Photos incluyen shots procesados
- [ ] Videos incluyen URL y provider
- [ ] Widgets incluyen HTML/embed code

**Verification**: Test unitario por tipo de multimedia.

---

## SPEC-F06: Response JSON Structure

**Description**: El sistema debe producir JSON con estructura específica para consumo de apps móviles.

**Acceptance Criteria**:
- [ ] Response sigue schema OpenAPI definido
- [ ] Campos obligatorios siempre presentes
- [ ] Campos opcionales omitidos cuando vacíos (no null)
- [ ] Arrays vacíos se representan como `[]`
- [ ] Fechas en formato ISO 8601

**Response Structure**:
```json
{
  "id": "string",
  "title": "string",
  "standfirst": "string",
  "body": [...],
  "section": {...},
  "tags": [...],
  "signatures": [...],
  "multimedia": {...},
  "recommended": [...],
  "meta": {...}
}
```

**Verification**: Contract test contra schema OpenAPI.

---

## SPEC-F07: Error Handling

**Description**: El sistema debe manejar errores de servicios externos de forma graceful.

**Acceptance Criteria**:
- [ ] 404 de editorial retorna 404 al cliente
- [ ] 404 de servicios secundarios (tags, multimedia) no bloquea respuesta
- [ ] Timeouts tienen fallback definido
- [ ] Errores se loggean con contexto suficiente
- [ ] No se exponen detalles internos en respuestas de error

**Verification**: Test de integración con servicios que fallan.

---

## SPEC-F08: Caching Strategy

**Description**: El sistema debe cachear respuestas de servicios externos apropiadamente.

**Acceptance Criteria**:
- [ ] Cache headers respetados de servicios upstream
- [ ] Invalidación de cache funciona correctamente
- [ ] Cache miss no causa error
- [ ] TTL configurable por tipo de contenido

**Verification**: Test de integración verificando cache hits/misses.

---

## SPEC-F09: Picture Shots Processing

**Description**: El sistema debe procesar shots de fotos para diferentes resoluciones/crops.

**Acceptance Criteria**:
- [ ] Shots se obtienen para cada foto en body
- [ ] Thumbor URLs se generan correctamente
- [ ] Fallback a URL original si shots no disponibles
- [ ] Orientation preservada

**Verification**: Test unitario de PictureShots service.

---

## SPEC-F10: Backward Compatibility

**Description**: El refactoring NO debe cambiar el contrato de API existente.

**Acceptance Criteria**:
- [ ] Response JSON idéntico pre/post refactoring
- [ ] HTTP status codes idénticos
- [ ] Cache headers idénticos
- [ ] Error responses idénticos

**Verification**: Snapshot tests comparando outputs.

---

## Non-Functional Requirements

### NFR-01: Performance
- Latencia p99 ≤ 500ms
- No regresión de performance post-refactoring

### NFR-02: Maintainability
- Cada clase ≤ 150 líneas
- Cada método ≤ 20 líneas
- Cyclomatic complexity ≤ 10

### NFR-03: Testability
- Cada clase testeable con ≤ 5 mocks
- Test coverage ≥ 80%
- MSI ≥ 79%

### NFR-04: Documentation
- PHPDoc en interfaces públicas
- README actualizado con nueva arquitectura
