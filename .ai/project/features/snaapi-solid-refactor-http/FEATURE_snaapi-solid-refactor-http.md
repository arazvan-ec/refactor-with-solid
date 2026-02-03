# Feature: SNAAPI SOLID Refactoring HTTP

## Quick Summary

Refactoring arquitectónico del API Gateway SNAAPI para cumplir **SOLID de forma rigurosa** (score ≥22/25), con patrones de diseño apropiados para manejar peticiones HTTP async/sync y transformación a JSON.

---

## Problem

El código actual tiene un **SOLID score de ~8/25**:
- `EditorialOrchestrator`: 536 líneas, 15+ responsabilidades (SRP violation)
- `get_class()` lookups para transformers (OCP violation)
- `instanceof` checks para multimedia (LSP violation)
- Interfaces fat que mezclan concerns (ISP violation)
- 15+ dependencias concretas, traits con deps ocultas (DIP violation)

---

## Solution

| Patrón | Problema Resuelto | SOLID |
|--------|-------------------|-------|
| **Strategy + Adapter** | HTTP async/sync flag | OCP, DIP |
| **Visitor** | `get_class()` lookups | OCP, LSP |
| **Facade + Extract Class** | God class orchestrator | SRP |
| **Polymorphism** | `instanceof` checks | LSP |
| **Port/Adapter** | Concrete dependencies | DIP |

---

## Scope

| Metric | Value |
|--------|-------|
| Files to CREATE | 33 |
| Files to MODIFY | 8 |
| Files to DELETE | 2 |
| Tasks | 30 |
| Estimated units | 53 |

---

## Success Criteria

- [ ] SOLID Score ≥22/25 (from 8/25)
- [ ] EditorialOrchestrator split into ≤5 classes
- [ ] Zero `instanceof` in orchestration
- [ ] Zero `get_class()` in handlers
- [ ] All dependencies via interfaces
- [ ] Tests with ≤5 mocks per class
- [ ] API response IDENTICAL (backward compatible)

---

## Documents

| Document | Description |
|----------|-------------|
| [00_problem_statement.md](./00_problem_statement.md) | Problem, constraints, criteria |
| [12_specs.md](./12_specs.md) | Functional specs (WHAT) |
| [13_integration_analysis.md](./13_integration_analysis.md) | Integration impact |
| [15_solutions.md](./15_solutions.md) | SOLID patterns (HOW) |
| [16_architectural_impact.md](./16_architectural_impact.md) | Layers, files, risks |
| [30_tasks_backend.md](./30_tasks_backend.md) | 30 tasks with TDD |
| [50_state.md](./50_state.md) | Progress tracking |

---

## Implementation Phases

1. **Infrastructure Ports** - HTTP abstraction, Promise resolution
2. **Trait Elimination** - Extract to explicit services
3. **Data Fetchers** - SRP extraction from orchestrator
4. **Visitor Pattern** - Body element transformation
5. **Multimedia Polymorphism** - Remove instanceof
6. **Integration** - Snapshot tests, benchmarks

---

## Commands

```bash
# Start implementation
/workflows:work snaapi-solid-refactor-http --phase=1

# Check progress
/workflows:status snaapi-solid-refactor-http

# Run tests
cd snaapi && make tests
```
