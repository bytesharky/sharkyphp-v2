← [中文版本](./02-controllers-requests_zh.md)

# Controllers, Requests and Responses

## Controllers

A controller is a plain PHP class whose methods (actions) receive a request and return a response. Method parameters support dependency injection:

- Declaring `Psr\Http\Message\ServerRequestInterface $request`: the current request is injected automatically.
- Declaring a parameter with the same name as a route parameter: the path-matched value is filled in automatically.
- Other non-built-in type parameters: resolved from the container.

```php
namespace App\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Sharky\Facade\Http;

class SharkyDemo extends Controller
{
    // Inject the request object
    public function index(ServerRequestInterface $request)
    {
        return Http::response('hello');
    }

    // Inject the route parameter $id (with route '/(id:\d+)')
    public function show(ServerRequestInterface $request, $id)
    {
        return Http::json(['id' => $id]);
    }
}
```

> It is recommended to use a base `Controller` class that encapsulates common capabilities (such as template rendering, translation, etc.). The example project's `app/Http/Controllers/Controller.php` provides a `render()` example.

## Building Responses

All actions **must return `Psr\Http\Message\ResponseInterface`**. The easiest way is to use the `Http` facade or the global helpers:

```php
use Sharky\Facade\Http;

Http::response($body = '', $status = 200, $headers = []);   // Plain text/HTML response
Http::json($data, $status = 200, $headers = []);            // JSON response (auto-serializes arrays/objects)
Http::xml($data, $status = 200, $headers = []);             // XML response
Http::html($body, $status = 200, $headers = []);            // HTML response
Http::createResponse($status);                              // Empty response
Http::redirect($url, $status = 302);                        // Redirect response
```

- `Http::json()` accepts: arrays, `__toString` objects, `toArray()`/`toJson()` objects, `JsonSerializable` objects, and automatically sets `application/json; charset=utf-8`.
- `Http::response()` returns `HttpFactory` when called with no arguments (usable with chained `->setHeader()->setStatus()`); when arguments are passed it returns a `ResponseInterface` directly.
- The global helpers `response(...)`, `redirect(...)` are equivalent to the above (defined in `helpers.php`).

### Manipulating Responses Manually (PSR‑7 + Extensions)

`Sharky\Http\Message\Response` implements PSR‑7 and is immutable (`withXxx()` returns a new instance), and comes with convenience methods:

```php
use Sharky\Facade\Http;

$resp = Http::createResponse(200)
    ->setStatus(201)                       // Set the status code (extension method, returns itself in place)
    ->setHeader(['X-Foo' => 'bar'])        // Set/overwrite response headers (accepts an array for batch)
    ->addedHeader('X-Bar', 'baz')          // Append a response header
    ->setContentType('application/json')   // Set Content-Type
    ->setBody($stream);                    // Set the message body (StreamInterface)

$resp->getStatusCode();
$resp->getHeaderLine('Content-Type');      // application/json
(string) $resp;                            // Render as a complete HTTP message
```

- `setContentType($type, $charset = '')`: when the second argument is non-empty it appends `; charset=xxx`; if `$type` already contains `charset=` (e.g. `'text/html; charset=utf-8'`) it is used as-is — no duplicated charset and no trailing `;`.
- `Http::json()` / `Http::xml()` / `Http::html()` already set the correct `Content-Type`, so you rarely need to set it yourself.

## Reading Requests

The request object implements the PSR‑7 `ServerRequestInterface` and extends several convenience methods:

```php
$request->getMethod();                 // Request method
$request->getUri();                    // PSR‑7 Uri
$request->getQuery($key, $default);    // Check GET first, then POST
$request->getCookie($key, $default);   // Read a cookie
$request->getClientIP();               // Client IP
$request->getProxys();                 // Proxy chain (X-Forwarded-For)
$request->isAjax();                    // Whether it is an XMLHttpRequest
$request->getParsedBody();             // Parsed request body (JSON / form)
$request->getUploadedFiles();          // Array of uploaded files (UploadedFileInterface)
$request->getHeaderLine('Accept');     // PSR‑7 header reading
$request->getAttribute($name, $default); // Custom attribute
```

You can also construct a request manually (e.g. for testing): `Http::serverRequestCapture()` (based on global variables) or `Http::createServerRequest($method, $uri)`.

## File Uploads

`$request->getUploadedFiles()` returns objects implementing `Psr\Http\Message\UploadedFileInterface`:

```php
foreach ($request->getUploadedFiles() as $file) {
    if ($file->getError() === UPLOAD_ERR_OK) {
        $file->moveTo(base_path('public/uploads/' . $file->getClientFilename()));
    }
}
```

## Cookies

`Sharky\Http\Cookie\Cookie` provides static methods (based on PHP's native `setcookie`):

```php
Cookie::set($key, $value, $expires = 3600, $path = '', $domain = '', $secure = false, $httponly = true);
Cookie::get($key, $default = null);
Cookie::has($key);
Cookie::delete($key, $path = '/', $domain = '');
```

## Middleware

Implement `Sharky\Http\Middleware\MiddlewareInterface` (it extends the empty marker `Sharky\Contracts\MiddlewareInterface`) with a `process` method. Return `$next($request)` to continue to the next middleware/controller; returning a `ResponseInterface` directly short-circuits.

```php
namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sharky\Http\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $token = $request->getHeaderLine('Authorization');
        if ($token === '') {
            // response() is a global helper; calling it without arguments returns the factory
            return response('Unauthorized', 401);
        }
        // Pass through: hand the (possibly modified) request to the next
        return $next($request);
    }
}
```

Middleware is registered via the route or route group's `middleware()` (see [Routing](./01-routing.md)).

Run `php sharky make:middleware AuthCheck` to scaffold an HTTP middleware. Console middleware is a separate kind (its input is `Input`); scaffold it with `make:console:middleware`, see [Console](./08-console.md#two-kinds-of-middleware).

## Template Rendering (Quick)

If you use a template engine, you can encapsulate it in the base controller (see [Template Engine](./07-template.md)):

```php
protected function render(string $template, array $variables = []): ResponseInterface
{
    $engine = new \Sharky\Template\TemplateEngine(Config::get('template'));
    $context = $engine->render("SharkyDemo/{$template}", $variables);
    return \Sharky\Facade\Http::html((string) $context);
}
```

---

← [Back to Documentation Index](./README.md)
