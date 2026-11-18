# IAM Boilerplate

<p align="center">
  <a href="https://github.com/Mii-17/Boilerplate/actions/workflows/ci.yml">
    <img src="https://img.shields.io/github/actions/workflow/status/Mii-17/Boilerplate/ci.yml?branch=main&label=CI%2FCD&logo=github" alt="CI/CD">
  </a>
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white" alt="PHP 8.4">
</p>

> El badge `CI/CD` muestra el estado real del workflow `Continuous Integration` en `main` y enlaza con su historial de ejecuciones.

Boilerplate Laravel preparado para construir APIs y aplicaciones web con arquitectura hexagonal, DDD y TDD. Incluye autenticación IAM con Sanctum, verificación de correo, idempotencia, correlation IDs, documentación OpenAPI generada con Scramble y una colección Bruno ejecutable.

Estado actual de calidad: **93.5% de cobertura**, Pint y PHPStan nivel 9 en verde, 236 tests y 598 assertions.

## Arranque rápido

```sh
docker compose up -d --build
```

| Recurso | URL |
| --- | --- |
| Aplicación / API | http://localhost:8080 |
| Health check | http://localhost:8080/up |
| Documentación OpenAPI | http://localhost:8080/docs/api |
| Especificación JSON | http://localhost:8080/docs/api.json |
| Mailpit UI | http://localhost:8025 |
| Mailpit SMTP | `localhost:1025` |

También están versionados en el repo el archivo [api.json](api.json) y la colección [bruno/](bruno/).

## Arquitectura hexagonal

```mermaid
flowchart LR
  subgraph Clients
    Web[Web Blade]
    API[API / Bruno]
  end

  subgraph Interface
    Trace[TraceCorrelationIdMiddleware]
    Idem[IdempotencyMiddleware api]
    Forms[FormRequests]
    Ctrl[Controllers]
    Res[Resources]
  end

  subgraph Application
    UC[UseCases]
    DTO[Commands y DTOs]
    Ports[Ports]
  end

  subgraph Domain
    Ent[Entities]
    Events[Domain Events]
  end

  subgraph Infrastructure
    SQL[(PostgreSQL)]
    Redis[(Redis)]
    Queue[Queue worker]
    Mail[(Mailpit)]
    Docs[Scramble docs]
  end

  Web --> Trace
  API --> Trace
  Trace --> Forms
  Trace --> Idem
  Idem --> Forms
  Forms --> Ctrl
  Ctrl --> UC
  UC --> DTO
  UC --> Ports
  Ports --> Ent
  Ent --> Events
  Ports --> SQL
  Ports --> Redis
  UC --> Events
  Events --> Queue
  Queue --> Mail
  UC --> Res
  Res --> Web
  Res --> API
  Docs --> API
```

Mapa de código: [Vistas Blade](resources/views/iam/auth), [rutas API](src/Iam/User/Infrastructure/Routes/api.php), [middleware compartido](src/Shared/Infrastructure/Http/Middleware), [controllers](src/Iam/User/Infrastructure/Http/Controllers), [casos de uso](src/Iam/User/Application/UseCases), [puertos](src/Iam/User/Application/Ports), [dominio](src/Iam/User/Domain), [persistencia](src/Iam/User/Infrastructure/Persistence), [api.json](api.json).

## Flujo de casos de uso

```mermaid
flowchart TD
  Start[Request HTTP] --> Trace[TraceCorrelationIdMiddleware global]
  ErrorResponse[JSON 4xx + X-Correlation-ID]
  Flash[Flash en web + X-Correlation-ID]

  Trace --> Cond{Ruta api con Idempotency-Key?}
  Cond -- si --> Idem[IdempotencyMiddleware]
  Idem --> Store[IdempotencyService / Redis]
  Store -- replay --> Response[JSON o redirect + X-Correlation-ID]
  Store -- 409/425 --> ErrorResponse
  Store -- nueva --> Validate[FormRequest validation]
  Cond -- no --> Validate

  Validate --> Valid{Validación OK?}
  Valid -- no --> VRender{API o expectsJson?}
  VRender -- si --> ErrorResponse
  VRender -- no --> Flash
  Valid -- si --> Ctrl[Controller]

  Ctrl --> UC[UseCase]
  UC --> DTO[Response DTO]
  UC --> Write{Es use case de escritura?}
  Write -- si --> Tx[TransactionManagerInterface]
  Tx --> Repo[Repository]
  Repo --> DB[(PostgreSQL)]
  UC --> Emit{Este caso emite eventos?}
  Emit -- si --> Events[EventDispatcherInterface]
  Events --> DomainEvents[Domain Events]
  DomainEvents --> Listeners[Listeners]
  Listeners --> Queue[Queue Redis]
  Queue --> Worker[Worker]
  Worker --> Mail[(Mailpit SMTP 1025)]

  DTO --> Resource[JsonResource o respuesta web]
  Resource --> Response

  UC --> DomainError{El dominio lanza DomainException?}
  DomainError -- si --> DRender{API o expectsJson?}
  DRender -- si --> Mapper[DomainExceptionMapper]
  Mapper --> ErrorResponse
  DRender -- no --> Flash
  DomainError -- no --> Response
```

Mapa de código: [TraceCorrelationIdMiddleware](src/Shared/Infrastructure/Http/Middleware/TraceCorrelationIdMiddleware.php), [IdempotencyMiddleware](src/Shared/Infrastructure/Http/Middleware/IdempotencyMiddleware.php), [IdempotencyService](src/Shared/Application/Idempotency/IdempotencyService.php), [TransactionManagerInterface](src/Iam/User/Application/Ports/TransactionManagerInterface.php), [EloquentUserRepository](src/Iam/User/Infrastructure/Persistence/EloquentUserRepository.php), [listeners](src/Iam/User/Infrastructure/Listeners), [WelcomeNotification](src/Iam/User/Infrastructure/Notifications/WelcomeNotification.php), [DomainExceptionMapper](src/Shared/Infrastructure/Http/DomainExceptionMapper.php), [UserServiceProvider](src/Iam/User/Infrastructure/Providers/UserServiceProvider.php).

## Decisiones y trade-offs

- **DDD:** el contexto IAM separa dominio, aplicación e infraestructura. El coste es más estructura y más clases; el beneficio son reglas de negocio testeables y desacopladas de Laravel.
- **TDD:** los casos de uso se cubren con Pest desde la capa de aplicación hacia afuera. La inversión inicial es mayor, pero las regresiones se detectan antes; la cobertura actual es 93.5%.
- **Unit of Work:** `TransactionManagerInterface` y `DatabaseTransactionManager` encapsulan `DB::transaction()` para los casos de uso de escritura. El login no usa transacción; el coste es una indirección, pero la capa de aplicación queda limpia.
- **Rate Limiting:** `throttle:auth` limita login a 5/min por IP y `throttle:verification` limita verificación a 10/min. Protege contra abuso con configuración explícita en `UserServiceProvider`.
- **Roles:** el middleware `role` usa `EnsureUserHasRole` para autorizar rutas por rol sin mezclar autorización con lógica de negocio.
- **Mailpit:** los correos locales se encolan en Redis, un worker los envía por SMTP a `localhost:1025` y la UI se consulta en `http://localhost:8025`. Así no depende de un proveedor SMTP real en desarrollo.
- **Correlation IDs:** `TraceCorrelationIdMiddleware` acepta o genera `X-Correlation-ID` y lo propaga en respuestas normales y errores; en errores también interviene el hook global de excepciones. Facilita trazabilidad sin acoplar dominio a logging.

## Calidad y documentación

```sh
docker compose exec -T app composer check
docker compose exec -T -e XDEBUG_MODE=coverage app php artisan test --coverage --min=90 --compact
docker compose exec -T app php artisan scramble:export
```

La colección [bruno/](bruno/) está generada desde [api.json](api.json), usa el entorno `local` y ejecuta el flujo `register → login → get-user → change-email`. La verificación de correo queda documentada porque requiere una URL firmada real.

## Licencia

MIT. El texto completo está en [LICENSE](LICENSE).
