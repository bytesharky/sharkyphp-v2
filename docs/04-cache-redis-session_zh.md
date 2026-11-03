← [English version](./04-cache-redis-session.md)

# 缓存 / Redis / 会话

三者 API 风格一致：对外暴露管理器，便捷方法统一走「默认驱动 / 默认连接」（由配置决定），并可通过 `driver()` / `connect()` 显式切换节点。

## 缓存（Cache）

`Cache` 门面对应 `Sharky\Cache\Cache`。配置见 `configs/config.php` 的 `cache` 节点（`driver`: `file` | `redis`，以及 `path` / `prefix`）。

```php
use Sharky\Facade\Cache;

Cache::set('foo', 'bar', 60);          // 写入，过期秒数（null = 永不过期）
$val = Cache::get('foo', 'default');   // 读取，缺省值
Cache::exists('foo');
Cache::delete('foo');
Cache::clear();

Cache::driver('redis')->get('foo');    // 显式使用指定驱动
```

## Redis

`Redis` 门面对应 `Sharky\Redis\Redis`。配置见 `configs/redis.php` 的 `default` 节点（`hosts.{master,slave}` / `prefix` / `database` 等）。所有便捷命令统一走「默认连接」（hosts 第一个节点）。

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
Redis::setnx('lock', '1');             // 仅当不存在时设置
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
Redis::zRange('rank', 0, -1, true);    // 带分数
Redis::zRevRange('rank', 0, -1);

// 事务 / 运维
Redis::multi(); /* ... */ Redis::exec();   // exec() 返回结果数组
Redis::discard();
Redis::select(1);
Redis::info();

// 指定节点（默认连接外的 master/slave 等）
$conn = Redis::connect('slave');        // -> 连接实例（Sharky\Redis\Contracts\ConnectionInterface）
$conn->get('foo');

// connect() 不传参也会解析为 hosts 第一个节点（即默认连接）
$conn = Redis::connect();
```

## 会话（Session）

`Session` 门面对应 `Sharky\Session\Session`。配置见 `configs/config.php` 的 `session` 节点（`driver`: `file` | `redis`，以及 `path` / `prefix` / `cookie` / `cookie_path` / `ttl` / `secure`）。

```php
use Sharky\Facade\Session;

Session::start();                      // 启动（首次 get/set 会自动启动）
Session::set('uid', 1);                // 写入（内存标记 dirty）
$uid = Session::get('uid', null);       // 读取
Session::has('uid');
Session::delete('uid');
Session::all();                        // 全部数据
Session::clear();                      // 清空数据（保留 id）
$id = Session::getId();
Session::regenerate();                  // 重新生成 id（防会话固定）
Session::destroy();                     // 销毁当前会话
```

- 数据在内存中操作，**析构时（或显式 `save()`）自动写回**存储驱动，仅在数据变更时回写。
- 使用 `redis` 驱动时需保证 Redis 已配置；Cookie 名、TTL、secure 由配置控制。
- **`path` 与 `cookie_path` 不要混用**：`path` 是会话数据的存储目录（如 `runtime/sessions`），`cookie_path` 才是浏览器侧 Cookie 的作用路径（默认 `/`）。若把存储目录当成 Cookie path，浏览器会丢弃该 Cookie，会话将永远无法延续。

---

← [返回文档索引](./README_zh.md)
