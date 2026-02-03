---
name: workflows:help
description: "Quick help and navigation for the Multi-Agent Workflow plugin. Shows available commands, concepts, and resources."
argument_hint: [topic]
---

# /workflows:help - Ayuda Rápida y Navegación

**Version**: 1.0.0
**Category**: Reference
**Priority**: High utility command

---

## Purpose

Proporciona ayuda rápida, navegación y referencia para el plugin Multi-Agent Workflow. Diseñado para acceso rápido cuando el usuario necesita orientación.

## Invocation

```bash
# Ayuda general
/workflows:help

# Ayuda sobre tema específico
/workflows:help commands
/workflows:help agents
/workflows:help concepts
/workflows:help troubleshooting
```

## Execution Protocol

### Default (No Arguments): Show Quick Reference Card

```markdown
╔══════════════════════════════════════════════════════════════════════╗
║              Multi-Agent Workflow - Ayuda Rápida                     ║
╚══════════════════════════════════════════════════════════════════════╝

## Comandos Más Usados

| Comando             | Descripción                    | Ejemplo                    |
|---------------------|--------------------------------|----------------------------|
| `/workflows:plan`   | Planificar una feature         | `/workflows:plan user-auth`|
| `/workflows:work`   | Implementar código             | `/workflows:work user-auth --role=backend` |
| `/workflows:review` | Revisar calidad                | `/workflows:review user-auth` |
| `/workflows:status` | Ver estado actual              | `/workflows:status user-auth` |
| `/workflows:route`  | ¿No sabes qué hacer? Pregunta  | `/workflows:route "necesito..."` |

## Flujo Básico

```
/workflows:plan → /workflows:work → /workflows:review → /workflows:compound
      80%              15%               4%                   1%
```

## ¿Qué Necesitas?

| Si quieres...                | Usa...                        |
|------------------------------|-------------------------------|
| Empezar algo nuevo           | `/workflows:plan`             |
| Continuar trabajo            | `/workflows:status` + `/workflows:work` |
| Revisar antes de merge       | `/workflows:review`           |
| No sabes qué comando usar    | `/workflows:route`            |
| Primera vez con el plugin    | `/workflows:onboarding`       |
| Entender un término          | Ver GLOSSARY.md               |
| Ver todos los comandos       | `/workflows:help commands`    |
| Ver todos los agentes        | `/workflows:help agents`      |

## Recursos

| Recurso | Descripción |
|---------|-------------|
| [QUICKSTART.md](../../QUICKSTART.md) | Inicio en 5 minutos |
| [TUTORIAL.md](../../TUTORIAL.md) | Ejemplo completo paso a paso |
| [GLOSSARY.md](../../GLOSSARY.md) | Definiciones de términos |
| [INDEX.md](../../INDEX.md) | Mapa del repositorio |
| [README.md](../../README.md) | Documentación completa |

---
Para más detalles: `/workflows:help [topic]`
Topics: commands, agents, concepts, troubleshooting
```

### Topic: commands

```markdown
## Todos los Comandos Disponibles

### Core Workflow (usa estos principalmente)

| Comando | Descripción | Cuándo usar |
|---------|-------------|-------------|
| `/workflows:plan` | Planificar feature completa | Inicio de cualquier feature |
| `/workflows:work` | Implementar código | Después de planificar |
| `/workflows:review` | Revisión de calidad | Antes de merge |
| `/workflows:compound` | Capturar learnings | Después de aprobar |

### Coordinación

| Comando | Descripción | Cuándo usar |
|---------|-------------|-------------|
| `/workflows:route` | Router inteligente | No sabes qué hacer |
| `/workflows:role` | Trabajar como rol específico | Desarrollo enfocado |
| `/workflows:status` | Ver estado actual | Verificar progreso |
| `/workflows:sync` | Sincronizar estado | Entre sesiones |

### Sesión y Estado

| Comando | Descripción | Cuándo usar |
|---------|-------------|-------------|
| `/workflows:checkpoint` | Guardar progreso | Antes de pausa |
| `/workflows:snapshot` | Captura completa del estado | Sesiones largas |
| `/workflows:restore` | Restaurar estado | Nueva sesión |
| `/workflows:reload` | Recargar contexto | Contexto corrupto |

### Calidad

| Comando | Descripción | Cuándo usar |
|---------|-------------|-------------|
| `/workflows:validate` | Validar contra specs | Verificar implementación |
| `/workflows:tdd` | Modo Test-Driven | Desarrollo con tests primero |
| `/workflows:criteria` | Ver criterios de aceptación | Revisar requisitos |
| `/workflows:solid-refactor` | Refactorizar para SOLID | Mejorar arquitectura |

### Avanzados

| Comando | Descripción | Cuándo usar |
|---------|-------------|-------------|
| `/workflows:parallel` | Trabajo paralelo multi-agente | Features complejas |
| `/workflows:monitor` | Monitorear progreso | Supervisión |
| `/workflows:metrics` | Ver métricas del proyecto | Análisis |
| `/workflows:interview` | Entrevista de requisitos | Requisitos complejos |
| `/workflows:comprehension` | Verificar comprensión | Evitar malentendidos |

### Utilidades

| Comando | Descripción |
|---------|-------------|
| `/workflows:help` | Esta ayuda |
| `/workflows:onboarding` | Experiencia de primera vez |
| `/workflows:progress` | Progreso detallado |
| `/workflows:trust` | Nivel de confianza por archivo |
| `/workflows:deepen-plan` | Profundizar planificación |
| `/workflows:heal-skill` | Reparar skills |
```

### Topic: agents

```markdown
## Agentes Disponibles

### Roles Principales (4)

| Agente | Función | Cuándo se activa |
|--------|---------|------------------|
| **Planner** | Planifica y diseña features | `/workflows:plan` |
| **Backend** | Implementa APIs y servidor | `/workflows:work --role=backend` |
| **Frontend** | Implementa UI y componentes | `/workflows:work --role=frontend` |
| **QA** | Testing y validación | `/workflows:review` |

### Agentes de Review (7)

| Agente | Especialidad |
|--------|--------------|
| Security Review | OWASP, vulnerabilidades |
| Performance Review | N+1, memory leaks, optimización |
| DDD Compliance | Arquitectura Domain-Driven |
| Code Review TS | Estándares TypeScript |
| Agent-Native Reviewer | Código compatible con IA |
| Code Simplicity Reviewer | Simplicidad y legibilidad |
| Pattern Recognition | Detecta anti-patterns |

### Agentes de Research (5)

| Agente | Especialidad |
|--------|--------------|
| Codebase Analyzer | Análisis de estructura |
| Git Historian | Historia y decisiones |
| Dependency Auditor | Seguridad de dependencias |
| Learnings Researcher | Patrones exitosos previos |
| Best Practices Researcher | Mejores prácticas externas |

### Agentes de Workflow (4)

| Agente | Especialidad |
|--------|--------------|
| Bug Reproducer | Reproducir y documentar bugs |
| Spec Analyzer | Validar vs especificaciones |
| Style Enforcer | Estándares de código |
| Comprehension Guardian | Evitar malentendidos |

### Agentes de Design (4)

| Agente | Especialidad |
|--------|--------------|
| API Designer | Contratos RESTful |
| UI Verifier | Validación UI/UX |
| SOLID Architecture Generator | Diseño SOLID |
| Architecture Criteria Analyst | Evaluación arquitectónica |

> Los agentes se invocan automáticamente según el workflow.
> Normalmente no necesitas invocarlos directamente.
```

### Topic: concepts

```markdown
## Conceptos Clave

### Metodologías

| Concepto | Qué es | Por qué importa |
|----------|--------|-----------------|
| **Compound Engineering** | Cada tarea hace las siguientes más fáciles | Efecto bola de nieve positivo |
| **Ralph Wiggum Loop** | Auto-corrección hasta 10 intentos | Menos intervención manual |
| **80/20 Rule** | 80% planificar, 20% ejecutar | Previene 80% de bugs |
| **TDD** | Tests antes que código | Código más confiable |
| **DDD** | Diseño guiado por dominio | Arquitectura escalable |

### Archivos Importantes

| Archivo | Propósito | Ubicación |
|---------|-----------|-----------|
| `50_state.md` | Estado actual (fuente de verdad) | `.ai/project/features/{feature}/` |
| `20_api_contracts.md` | Contratos de API | `.ai/project/features/{feature}/` |
| `30_tasks_*.md` | Tareas por rol | `.ai/project/features/{feature}/` |
| `FEATURE_*.md` | Definición de feature | `.ai/project/features/{feature}/` |

### Estados de Tareas

```
PENDING → IN_PROGRESS → COMPLETED → APPROVED
              ↓                        ↓
           BLOCKED                 REJECTED
              ↓                        ↓
         [resolver]              [arreglar y re-review]
```

### Flujo de 4 Fases

```
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│  PLAN   │───▶│  WORK   │───▶│ REVIEW  │───▶│COMPOUND │
│  80%    │    │  15%    │    │   4%    │    │   1%    │
└─────────┘    └─────────┘    └─────────┘    └─────────┘
  Planner      Backend/FE        QA          Learnings
```

> Ver [GLOSSARY.md](../../GLOSSARY.md) para definiciones completas.
```

### Topic: troubleshooting

```markdown
## Solución de Problemas Comunes

### "No sé por dónde empezar"

```bash
# Opción 1: Onboarding guiado
/workflows:onboarding

# Opción 2: Dejar que el router decida
/workflows:route "descripción de lo que necesitas"

# Opción 3: Empezar con planificación
/workflows:plan nombre-feature
```

### "El contexto se volvió muy largo"

```bash
# 1. Guardar estado actual
/workflows:sync

# 2. Iniciar nueva sesión de Claude

# 3. Restaurar contexto
/workflows:restore
```

### "Algo falló y no sé qué"

```bash
# 1. Ver estado actual
/workflows:status mi-feature

# 2. Si hay BLOCKED, ver detalles en:
cat .ai/project/features/mi-feature/50_state.md
```

### "El frontend está esperando el backend"

```bash
# 1. Verificar estado del backend
/workflows:status mi-feature

# 2. Si backend aún no está listo, frontend usa mocks
# Esto es normal - estado WAITING_API

# 3. Cuando backend termine, sincronizar
/workflows:sync
```

### "Los tests no pasan"

```bash
# El Ralph Wiggum Loop intentará hasta 10 veces
# Si aún falla:

# 1. Ver el estado de QA
/workflows:status mi-feature

# 2. Si está BLOCKED, revisar manualmente
# 3. Arreglar y re-run review
/workflows:review mi-feature
```

### "Necesito volver a un estado anterior"

```bash
# Si hiciste snapshot antes:
/workflows:restore --snapshot=nombre-snapshot

# Si no, usar git:
git log --oneline  # encontrar commit
git checkout <commit> -- .ai/
```

### "No entiendo un término/concepto"

```bash
# Ver glosario
# Archivo: GLOSSARY.md

# O preguntar directamente describiendo tu confusión
```

### "¿Cómo personalizo el workflow?"

```
1. Reglas globales: plugins/multi-agent-workflow/rules/global_rules.md
2. Agentes: plugins/multi-agent-workflow/agents/
3. Skills: plugins/multi-agent-workflow/skills/

Edita los archivos .md correspondientes.
```

---

¿Problema no listado? Describe tu situación y te ayudaré.
```

## Related Commands

- `/workflows:onboarding` - Full onboarding experience
- `/workflows:route` - Intelligent workflow routing
- `/workflows:status` - Check current state
