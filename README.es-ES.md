

# YoutubeLaravelApi

[![Tests](https://github.com/alchemyguy/YoutubeLaravelApi/actions/workflows/tests.yml/badge.svg)](https://github.com/alchemyguy/YoutubeLaravelApi/actions/workflows/tests.yml)
[![Static analysis](https://github.com/alchemyguy/YoutubeLaravelApi/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/alchemyguy/YoutubeLaravelApi/actions/workflows/static-analysis.yml)
[![Latest version](https://img.shields.io/packagist/v/alchemyguy/youtube-laravel-api.svg)](https://packagist.org/packages/alchemyguy/youtube-laravel-api)
[![License](https://img.shields.io/packagist/l/alchemyguy/youtube-laravel-api.svg)](LICENSE)

Wrapper moderno para Laravel de la YouTube Data API v3 con OAuth, control de transmisiones en vivo, gestión de canales y carga de videos reanudables.

**Documentación:** https://alchemyguy.github.io/YoutubeLaravelApi/

## Requisitos

- PHP 8.3 o superior
- Laravel 11.x o 12.x

## Instalación

```bash
composer require alchemyguy/youtube-laravel-api
php artisan vendor:publish --tag=youtube-config
```

Agrega las credenciales a tu `.env`:

```dotenv
YOUTUBE_APP_NAME="My App"
YOUTUBE_CLIENT_ID="your-client-id.apps.googleusercontent.com"
YOUTUBE_CLIENT_SECRET="your-client-secret"
YOUTUBE_API_KEY="your-server-api-key"
YOUTUBE_REDIRECT_URL="https://yourapp.test/oauth/youtube/callback"
```

## Ejemplo rápido

```php
use Alchemyguy\YoutubeLaravelApi\Services\AuthenticateService;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\LiveStreamService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;

// 1. OAuth
$auth = app(AuthenticateService::class);
$url = $auth->getLoginUrl('creator@example.com', 'channel-id');
// ... redirect, handle callback ...
$result = $auth->authenticateWithCode($code);

// 2. Schedule a live broadcast
$live = app(LiveStreamService::class);
$broadcast = $live->broadcast($result['token'], new BroadcastData(
    title: 'My Stream',
    description: 'Live coding',
    scheduledStartTime: new DateTimeImmutable('+10 minutes'),
));
```

## Documentación

La guía completa, la referencia de la API y los ejemplos están disponibles en **[alchemyguy.github.io/YoutubeLaravelApi](https://alchemyguy.github.io/YoutubeLaravelApi/)**.

- [Instalación](https://alchemyguy.github.io/YoutubeLaravelApi/guide/installation)
- [Autenticación](https://alchemyguy.github.io/YoutubeLaravelApi/guide/authentication)
- [Transmisiones en vivo](https://alchemyguy.github.io/YoutubeLaravelApi/guide/live-streaming)
- [Canales](https://alchemyguy.github.io/YoutubeLaravelApi/guide/channels)
- [Videos](https://alchemyguy.github.io/YoutubeLaravelApi/guide/videos)
- [Manejo de errores](https://alchemyguy.github.io/YoutubeLaravelApi/guide/error-handling)
- [Actualización desde 1.x](https://alchemyguy.github.io/YoutubeLaravelApi/upgrading/from-1.x)

## Actualización desde 1.x

Si utilizas la versión anterior, consulta [UPGRADE.md](UPGRADE.md) — hay varios cambios que rompen la compatibilidad.

## Contribuir

Agradecemos las contribuciones — consulta [CONTRIBUTING.md](CONTRIBUTING.md). Por favor, ejecuta `composer lint` y `composer test:unit` antes de abrir un PR.

## Seguridad

Si descubres una vulnerabilidad de seguridad, por favor envía un correo electrónico directamente al mantenedor en lugar de abrir un issue.

## Licencia

Licencia MIT (MIT). Consulta [LICENSE](LICENSE) para más detalles.

## Créditos

- Creado por [Mukesh Chandra](https://github.com/alchemyguy)
- Modernización a la versión 2.0 a cargo de los colaboradores
