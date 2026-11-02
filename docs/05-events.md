← [中文版本](./05-events_zh.md)

# Event System

The event system wraps PSR-14. `Sharky\Event\EventManager` is exposed via the `Event` facade and supports dispatching "by event name" (automatically wrapped into an `Event` object).

## Registering Listeners

```php
use Sharky\Facade\Event;

// Closure
Event::listen('user.registered', function (\Sharky\Event\Event $e) {
    $payload = $e->getPayload();   // array passed in at dispatch time
});

// [Class, method]
Event::listen('user.registered', [\App\Listeners\UserListener::class, 'onRegistered']);

// Class@method
Event::listen('user.registered', '\App\Listeners\UserListener@onRegistered');

// Class name only (resolved from the container when non-static, supports constructor dependency injection)
Event::listen('user.registered', \App\Listeners\UserListener::class);
```

- `Event::listen()` is equivalent to `Event::on()`.
- A listener can be a `callable`, `[class, method]`, `class@method`, or a callable class given as a class-name string.
- When dispatching, if the listener's method is non-static, the framework obtains its instance from the container (dependency injection is supported).

## Dispatching Events

```php
// By event name + payload array (automatically wrapped into an Event object)
$event = Event::dispatch('user.registered', ['uid' => 1]);

// Dispatch an existing event object
Event::dispatch($myEventObject);
```

- Returns the dispatched event object.
- The event object provides `getName()` and `getPayload()`.

## Listener Matching Rules

Listeners are matched by "event name (recommended)" or "event class name":

- If the event object has `getName()` → match by event name;
- otherwise fall back to `get_class($event)` matching (PSR-14 default behavior).

## Cancel / Cleanup

```php
Event::off('user.registered');   // remove all listeners for this event
Event::clear();                  // clear all listeners
```

## Built-in Framework Events

| Event Constant | Event Identifier | Description |
| ---- | ---- | ---- |
| `APP_START` | `app.start` | Application initialization startup, triggered at earliest |
| **Kernel Common (Both HTTP / Console kernels fire this event)** | | |
| `KERNEL_BOOTING` | `kernel.booting` | Before kernel booting, bootstrap loading phase |
| `KERNEL_BOOTED` | `kernel.booted` | Kernel bootstrap finished, services ready |
| `KERNEL_TERMINATING` | `kernel.terminating` | Kernel prepares to terminate, start cleanup work |
| `KERNEL_WORKER_START` | `kernel.worker_start` | Worker process spawned (Swoole coroutine scenario) |
| `KERNEL_WORKER_STOP` | `kernel.worker_stop` | Worker process stopped and destroyed |
| **HTTP Request Lifecycle** | | |
| `REQUEST_RECEIVE` | `request.receive` | HTTP request received |
| `REQUEST_ROUTE` | `request.route` | Route parsing & matching phase |
| `REQUEST_CONTROLLER` | `request.controller` | Before controller method executes |
| `REQUEST_RESPONSE` | `request.response` | Response object assembly completed |
| `REQUEST_RESPONSE_SENT` | `request.response_sent` | Response payload sent to client |
| `REQUEST_ERROR` | `request.error` | Exception caught inside request pipeline (prior to global exception render) |
| `REQUEST_FINISH` | `request.finish` | Whole request flow finished, cleanup |
| **Global Exception** | | |
| `EXCEPTION_THROW` | `exception.throw` | Fired on exception thrown (raw exception) |
| `EXCEPTION_RENDER` | `exception.render` | Exception render output phase |
| **Console Command Lifecycle** | | |
| `CONSOLE_RECEIVE` | `console.receive` | Console input received |
| `CONSOLE_MATCH` | `console.match` | Command lookup and match |
| `CONSOLE_START` | `console.start` | Command execution begins |
| `CONSOLE_FINISH` | `console.finish` | Command executed normally and finished |
| `CONSOLE_ERROR` | `console.error` | Error thrown while running command |

You can `Event::listen()` to these event names in your application code to attach hooks.

---

← [Back to Documentation Index](./README.md)
