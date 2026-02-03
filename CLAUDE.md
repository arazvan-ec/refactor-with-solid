# CLAUDE.md

## Quick Context (read this first)

**SNAAPI** = Symfony 6.4 API Gateway que agrega contenido de microservicios para apps móviles. NO persiste datos, todo se obtiene via HTTP clients.

**Ubicación código**: `snaapi/`
**Ubicación workflows**: `.ai/`

## Comandos Esenciales

```bash
# En directorio snaapi/
make tests           # Suite completa (CS, YAML, container, unit, stan, mutation)
make test_unit       # Solo PHPUnit
make test_stan       # PHPStan level 9
make test_cs         # Fix code style
make up / make down  # Docker containers
make cli             # Shell en contenedor PHP
```

## Arquitectura en 30 segundos

```
Controller -> OrchestratorChainHandler -> EditorialOrchestrator -> External Clients -> DataTransformers -> Response
```

**Patrones clave**:
- **Chain of Responsibility**: Routing por tipo de contenido via Compiler Passes
- **Strategy**: Transformadores de body elements
- **Hexagonal + CQRS** (solo queries)

**Bounded Contexts** (cada uno = cliente externo):
Editorial | Multimedia | Membership | Journalist | Section | Tag

## Reglas de Código Obligatorias

| Regla | Valor |
|-------|-------|
| PHPStan | Level 9 (sin negociación) |
| PHP | 8.2+ con strict_types |
| Injection | Solo constructor (no @required, no setter) |
| MSI Mutation | >= 79% |
| Code Style | PSR-12 + Symfony |

**SOLID obligatorio**: Cada PR debe respetar SRP, OCP, LSP, ISP, DIP.

## Workflow System (.ai/)

Cuando trabajes en features complejas, usa el sistema de workflows:

```bash
# Workflows disponibles
task-breakdown        # Para features complejas (genera 10 docs)
default              # Ciclo completo: plan -> implement -> review
implementation-only  # Después de task-breakdown
```

**Roles definidos**: Planner, Backend, Frontend, QA (ver `.ai/workflow/roles/`)

## Features Activas

| Feature | Estado | Ubicación |
|---------|--------|-----------|
| snaapi-solid-refactor-http | Planning Complete | `.ai/project/features/snaapi-solid-refactor-http/` |
| workflow-improvements-2026 | In Progress | `.ai/project/features/workflow-improvements-2026/` |

## Estructura de Directorios

```
.
├── CLAUDE.md              # <- Este archivo (onboarding rápido)
├── snaapi/                # Código fuente del API Gateway
│   ├── src/
│   │   ├── Controller/    # Entry points HTTP
│   │   ├── Application/   # Use cases, DTOs, Transformers
│   │   ├── Orchestrator/  # Agregación de servicios
│   │   ├── Infrastructure/# Clientes externos, caching
│   │   └── DependencyInjection/  # Compiler passes
│   ├── tests/             # 63 archivos de test
│   ├── Makefile           # Comandos de desarrollo
│   └── docker-compose.yml
└── .ai/                   # Sistema de workflows multi-agente
    ├── project/           # Configuración y features
    ├── workflow/          # Roles, reglas, scripts
    └── extensions/        # Plugins adicionales
```

## Qué NO hacer

- No uses `mixed` types si puedes evitarlo
- No modifiques código sin leer primero
- No hagas PRs sin pasar `make tests`
- No uses setter injection
- No crees archivos nuevos si puedes editar existentes

## Referencia Rápida de Archivos Clave

Cuando necesites detalles específicos:
- **Arquitectura detallada**: `snaapi/CLAUDE.md`
- **Plan de refactoring SOLID**: `.ai/project/features/snaapi-solid-refactor-http/15_solutions.md`
- **Specs funcionales**: `.ai/project/features/snaapi-solid-refactor-http/12_specs.md`
- **Tareas backend**: `.ai/project/features/snaapi-solid-refactor-http/30_tasks_backend.md`
- **Configuración proyecto**: `.ai/project/config.yaml`
