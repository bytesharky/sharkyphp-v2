← [中文版本](./04-cache-redis-session_zh.md)

# Cache / Redis / Session

All three share a consistent API style: a manager is exposed to the outside, convenience methods uniformly use the "default driver / default connection" (determined by configuration), and nodes can be switched explicitly via `driver()` / `connect()`.

## Cache

The `Cache` facade corresponds to `Sharky\Cache\Cache`. Configuration is defined under the `cache` node in `configs/config.php` (`driver`: `file` | `redis`, along with `path` / `prefix`).

```php
use Sharky\Facade\Cache;

Cache::set('foo', 'bar', 60);          // write, expiration in seconds (null = never expires)
$val = Cache::get('foo', 'default');   // read, default value
Cache::exists('foo');
Cache::delete('foo');
Cache::clear();

Cache::driver('redis')->get('foo');    // explicitly use the specified driver
```

## Redis

The `Redis` facade corresponds to `Sharky\Redis\Redis`. Configuration is defined under the `default` node in `configs/redis.php` (`hosts.{master,slave}` / `prefix` / `database`, etc.). All convenience commands uniformly use the "default connection" (the first node in hosts).

```php
use Sharky\Facade\Redis;

Redis::set('foo', 'bar', 60);
Redis::get('foo');
Redis::delete('foo');
Redis::exists('foo');
Redis::ttl('foo');
Redis::expire('foo', 100);
Redis::incr('counter');               // +1
Redis::incr('counter', 5);
Redis::decr('counter');
Redis::setnx('lock', '1');             // set only if it does not exist
Redis::mset(['a' => 1, 'b' => 2]);
Redis::mget(['a', 'b']);

// Hash
Redis::hSet('user:1', 'name', 'Tom');
Redis::hGet('user:1', 'name');
Redis::hMSet('user:1', ['age' => 18]);
Redis::hGetAll('user:1');
Redis::hDel('user:1', 'age');
Redis::hIncrBy('user:1', 'score', 10);

// List
Redis::lPush('queue', $item);
Redis::rPush('queue', $item);
Redis::lPop('queue');
Redis::rPop('queue');
Redis::lLen('queue');
Redis::lRange('queue', 0, -1);

// Set / Sorted Set
Redis::sAdd('tags', 'php');
Redis::sRem('tags', 'php');
Redis::sMembers('tags');
Redis::sIsMember('tags', 'php');
Redis::zAdd('rank', 100, 'Tom');
Redis::zRange('rank', 0, -1, true);    // with scores
Redis::zRevRange('rank', 0, -1);

// Transaction / maintenance
Redis::multi(); /* ... */ Redis::exec();   // exec() returns an array of results
Redis::discard();
Redis::select(1);
Redis::info();

// Specify a node (master/slave other than the default connection)
$conn = Redis::connect('slave');        // -> connection instance (Sharky\Redis\Contracts\ConnectionInterface)
$conn->get('foo');
```

## Session

The `Session` facade corresponds to `Sharky\Session\Session`. Configuration is defined under the `session` node in `configs/config.php` (`driver`: `file` | `redis`, along with `path` / `prefix` / `cookie` / `cookie_path` / `ttl` / `secure`).

```php
use Sharky\Facade\Session;

Session::start();                      // start (first get/set auto-starts)
Session::set('uid', 1);                // write (marks dirty in memory)
$uid = Session::get('uid', null);       // read
Session::has('uid');
Session::delete('uid');
Session::all();                        // all data
Session::clear();                      // clear data (keeps id)
$id = Session::getId();
Session::regenerate();                  // regenerate id (defends against session fixation)
Session::destroy();                     // destroy the current session
```

- Data is manipulated in memory and **automatically written back to the storage driver on destruction (or an explicit `save()`)**, and is only written back when the data changes.
- When using the `redis` driver, ensure Redis is configured; the cookie name, TTL, and secure flag are controlled by configuration.
- **Do not confuse `path` with `cookie_path`**: `path` is the storage directory for session data (e.g. `runtime/sessions`), while `cookie_path` is the browser-side cookie scope (defaults to `/`). Using the storage directory as the cookie path makes browsers drop the cookie, so the session can never continue.

---

← [Back to Documentation Index](./README.md)
