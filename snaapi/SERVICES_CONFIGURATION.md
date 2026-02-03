# Services Configuration Summary - SOLID Refactoring

## Overview
This document summarizes the service configuration changes made to `config/services.yaml` as part of the SOLID refactoring initiative for the SNAAPI project.

**Date**: 2026-02-03
**Configuration File**: `/home/user/refactor-with-solid/snaapi/config/services.yaml`
**YAML Syntax**: ✅ Valid

---

## 1. Interface Bindings

### Infrastructure Services
The following interfaces have been bound to their concrete implementations:

| Interface | Implementation | Purpose |
|-----------|---------------|---------|
| `App\Infrastructure\Service\MultimediaServiceInterface` | `MultimediaService` | Handles multimedia ID extraction and responsive image generation |
| `App\Infrastructure\Service\UrlGeneratorServiceInterface` | `UrlGeneratorService` | Generates URLs with site-specific format patterns |
| `Snaapi\Infrastructure\Service\Image\ImageProcessorInterface` | `Thumbor` | Image processing facade for Thumbor operations |
| `Snaapi\Infrastructure\Service\Image\ImageSizeConfigurationInterface` | `DefaultImageSizeConfiguration` | Provides predefined responsive image sizes |

---

## 2. External Dependencies

### Thumbor BuilderFactory
Configured the Thumbor URL builder factory with environment variables:

```yaml
Thumbor\Url\BuilderFactory:
    arguments:
        $server: '%env(THUMBOR_SERVER_URL)%'
        $secret: '%env(THUMBOR_SECRET_KEY)%'
```

**Environment Variables Required**:
- `THUMBOR_SERVER_URL`: Thumbor server endpoint
- `THUMBOR_SECRET_KEY`: Secret key for signing Thumbor URLs

---

## 3. Orchestrator Processors

All processor classes are in the excluded `src/Orchestrator` directory and require explicit registration.

| Processor | Responsibility |
|-----------|----------------|
| `InsertedNewsProcessor` | Processes inserted news references in editorial body tags |
| `RecommendedEditorialsProcessor` | Handles recommended editorials data |
| `MultimediaProcessor` | Processes all multimedia assets (photos, videos, widgets, opening) |
| `MembershipLinksProcessor` | Processes membership card links embedded in body tags |

**Configuration**: All processors are autowired and autoconfigured.

---

## 4. Editorial Orchestrators

### Main Editorial Orchestrator
| Service | Tag | Purpose |
|---------|-----|---------|
| `EditorialOrchestrator` | `app.orchestrators` | Main orchestrator for editorial content aggregation |
| `OrchestratorChainHandler` | - | Routes requests to registered orchestrators via Chain of Responsibility |
| `OrchestratorChain` | - | Orchestrator chain infrastructure |

**Compiler Pass**: `EditorialOrchestratorCompiler` registers all services tagged with `app.orchestrators`.

---

## 5. Multimedia Orchestrators

### Multimedia Type Handlers
| Service | Tag | Media Type |
|---------|-----|------------|
| `MultimediaPhotoOrchestrator` | `app.multimedia.orchestrators` | Photo processing |
| `MultimediaEmbedVideoOrchestrator` | `app.multimedia.orchestrators` | Embedded video processing |
| `MultimediaWidgetOrchestrator` | `app.multimedia.orchestrators` | Widget processing |

### Supporting Services
| Service | Purpose |
|---------|---------|
| `MultimediaOrchestratorHandler` | Routes multimedia processing by media type |
| `MultimediaOrchestratorChain` | Multimedia orchestrator chain infrastructure |

**Compiler Pass**: `MultimediaOrchestratorCompiler` registers all services tagged with `app.multimedia.orchestrators`.

---

## 6. Orchestrator Handlers

| Handler | Purpose |
|---------|---------|
| `GenericChainHandler` | Generic Chain of Responsibility implementation with pluggable type identification |

**Note**: `AbstractChainHandler` is an abstract base class and is not registered as a service.

---

## 7. Auto-Configured Services

The following services are **automatically loaded** via Symfony's service auto-discovery because they reside in included directories:

### Image Services (in `src/Infrastructure/Service/Image/`)
- `ImagePathBuilder` - Constructs S3-style image paths
- `ImageFilterApplier` - Applies visual filters to Thumbor URLs
- `ThumborUrlFactory` - Factory for generating Thumbor image URLs
- `AspectRatioMapper` - Maps orientations to standard aspect ratios
- `BodyTagPhotoProcessor` - Generates responsive image variants for body photos

### Transformation Strategies (in `src/Application/DataTransformer/Apps/Strategy/`)
- `SectionTransformer` - Transforms Section domain models to API format
- `TagTransformer` - Transforms Tag domain models to API format
- `HierarchyTransformer` - Builds section hierarchy chains
- `TwitterFormatter` - Formats editorial data for Twitter Card metadata
- `CompositeDetailsTransformer` - Orchestrates multiple transformation strategies

**Auto-Discovery**: These services are autowired and autoconfigured via the `App\` namespace configuration.

---

## 8. Existing Bindings

The following parameter bindings were already configured and are used by the new services:

| Parameter | Environment Variable | Used By |
|-----------|---------------------|---------|
| `$extension` | `URL_ENV` | `UrlGeneratorService` |
| `$thumborServerUrl` | `THUMBOR_SERVER_URL` | (Legacy compatibility) |
| `$thumborSecret` | `THUMBOR_SECRET_KEY` | (Legacy compatibility) |
| `$awsBucket` | `STATICS_AWS_BUCKET` | `ImagePathBuilder` |

---

## 9. Interfaces Without Implementation

The following interfaces were created as part of the refactoring but **do not yet have implementations**:

- `ClockInterface` - Abstraction for date/time operations
- `EnvironmentCheckerInterface` - Environment detection abstraction
- `SiteRegistryInterface` - Site registry service abstraction
- `LegacyEditorialClientInterface` - Legacy editorial client abstraction

**Status**: These interfaces are defined but not currently used in the codebase. They will be implemented in future phases of the refactoring.

---

## 10. Directory Exclusions

The following directories are **excluded** from Symfony's auto-discovery and require explicit service registration:

```yaml
exclude:
    - '../src/Orchestrator'            # Orchestrators registered explicitly
    - '../src/DependencyInjection/'    # Compiler passes
    - '../src/Entity/'                 # Domain entities
    - '../src/Kernel.php'              # Application kernel
    - '../src/Controller/V1/'          # Controllers (registered separately)
    - '../src/Ec/Snaapi/Infrastructure/' # Legacy infrastructure
    - '../src/Application/DataTransformer/Apps/Body/'
    - '../src/Application/DataTransformer/Apps/Media/DataTransformers/'
```

---

## 11. Validation

### YAML Syntax
✅ **Valid** - No syntax errors detected

### Validation Commands
```bash
# Validate YAML syntax
make test_yaml

# Validate DI container
make test_container
```

**Note**: These commands require Docker to be running. For manual validation:
```bash
python3 -c "import yaml; yaml.safe_load(open('config/services.yaml'))"
```

---

## 12. Architecture Benefits

This configuration supports the SOLID principles:

- **Single Responsibility**: Each service has a focused, well-defined purpose
- **Open/Closed**: Services are open for extension via interfaces, closed for modification
- **Liskov Substitution**: All implementations can be substituted for their interfaces
- **Interface Segregation**: Small, focused interfaces (e.g., `ProcessorInterface`, `TransformerStrategyInterface`)
- **Dependency Inversion**: Services depend on abstractions (interfaces) rather than concrete implementations

---

## 13. Next Steps

1. **Test the configuration**: Run the test suite to ensure all services are properly wired
   ```bash
   make tests
   ```

2. **Implement missing interfaces**:
   - `ClockInterface` → `SystemClock`
   - `EnvironmentCheckerInterface` → `SymfonyEnvironmentChecker`
   - `SiteRegistryInterface` → `InMemorySiteRegistry`
   - `LegacyEditorialClientInterface` → Implementation TBD

3. **Review and refactor**: Use the `/code-simplifier` skill to review recently modified code

4. **Documentation**: Update API documentation to reflect new service architecture

---

## Troubleshooting

### Service Not Found
If you encounter "Service not found" errors:
1. Check that the service is in an included directory OR explicitly registered in `services.yaml`
2. Verify autowire is enabled for the service
3. Check that all constructor dependencies are available

### Interface Binding Issues
If injection fails for an interface:
1. Verify the interface binding is declared in `services.yaml`
2. Ensure the implementation class exists and is loadable
3. Check that the implementation is not in an excluded directory

### Circular Dependencies
If you encounter circular dependency errors:
1. Review the dependency graph for cycles
2. Consider breaking dependencies with events or lazy loading
3. Use setter injection as a last resort (discouraged in this project)

---

**Maintained by**: SNAAPI Team
**Last Updated**: 2026-02-03
