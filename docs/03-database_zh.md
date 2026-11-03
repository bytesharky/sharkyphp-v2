← [English version](./03-database.md)

# 数据库

数据库包提供三层能力：**连接管理**（`Database`）、**查询构造器**（`QueryBuilder`）、**ActiveRecord 模型**（`Model` + `ActiveQuery` + `Collection`）。推荐通过模型使用，原生查询与构造器也对外可用。

## 1. 绑定连接

模型使用前必须先把数据库连接绑定到模型（应用启动时执行一次）：

```php
use Sharky\Database\Database;
use Sharky\Database\Model;
use Sharky\Facade\Config;

$connection = Database::connect();   // 不传参 = hosts 第一个节点；传名称可指定具体节点
Model::setConnection($connection);
```

也可直接实例化管理器：

```php
$db = new Database(Config::get('database.default'));
$connection = $db->connect();   // 默认节点解析同上
```

连接配置见 `configs/database.php`（`type` / `driver` / `hosts.{master,slave}` / `prefix` / `charset` 等）。

> **连接韧性**：长连接失效（server has gone away、连接断开、broken pipe 等）时会自动重连，PDO 与 MySQLi 两个驱动均已支持。处于事务中不重连，以保原子性。

## 2. 模型（ActiveRecord）

定义模型，继承 `Sharky\Database\Model`：

```php
namespace App\Models;

use Sharky\Database\Model;

class User extends Model
{
    public const TABLE  = 'users';   // 不含前缀；不设置则按类名推断（User -> user）
    public const PREFIX = null;      // 覆盖配置前缀；null 沿用连接配置

    protected array $fillable = ['name', 'email'];   // 白名单；空数组 = 允许全部表字段
    protected array $hidden   = ['password'];         // 序列化时隐藏
}
```

- 表名推断：`UserModel` → `user`（去掉 `Model`/实体后缀并转 snake_case）。
- 查询入口：`User::query()` 返回 `ActiveQuery`（已绑定模型、连接、表名）。

### 常用查询

```php
use App\Models\User;

User::find(1);                       // 按主键查单条 -> User|null
User::all();                         // 全部 -> Collection

User::query()
    ->select(['id', 'name'])
    ->where(['id' => ['>', 1]])
    ->orderBy(['id' => 'desc'])
    ->limit(10)
    ->get();                         // -> Collection

User::query()->where(['status' => 1])->first();        // 首条 -> User|null
User::query()->where(['age' => ['>=', 18]])->count();  // 计数
User::query()->where(['id' => 1])->paginate(1, 20);    // 分页数组
```

### where 写法

`where(callable|array $conditions)` 与 `orWhere(...)` 支持多种写法：

```php
->where(['id' => 1])                              // 等于
->where(['id' => ['>', 1]])                        // 运算符 + 值
->where(['id', '=', 1])                            // 三元组
->where([['id', '=', 1], ['name', '!=', 'x']])      // 多条 AND
->where(function ($q) {                             // 闭包 = 嵌套括号分组
    $q->where(['a' => 1])->orWhere(['b' => 2]);
})
->whereOr([['c' => 1], ['d' => 2]])                 // orWhere 的别名
->whereIf($keyword !== '', ['name' => ['like', "%{$keyword}%"]])  // 仅当条件为真才追加
```

- `where([])`（空数组）**静默忽略**——不生成 `WHERE` 子句，也不抛异常。这让常见的「可选过滤」写法（`$cond ? ['col' => $cond] : []`）无需先判空即可安全使用。
- `whereIf($condition, $clause)` 仅当 `$condition` 为真才追加 `$clause`，适合可选过滤；`whereOr(...)` 是 `orWhere(...)` 的别名。

支持的运算符：`= != <> > < >= <= & | ^ ~ << >> LIKE NOT LIKE`，以及 `IN / NOT IN`、`IS / IS NOT`、`BETWEEN / NOT BETWEEN`。值为 `null` 时 `=` / `!=` 自动转为 `IS NULL / IS NOT NULL`。

### 写操作

```php
$u = new User(['name' => 'Tom']);
$u->email = 'tom@example.com';     // 魔法属性 / setAttribute
$u->save();                        // 插入；返回受影响行数，自增主键自动回填

$u->name = 'Jerry';
$u->save();                        // 已存在主键 -> 更新变更字段

User::query()->where(['id' => 1])->update(['name' => 'X']);  // 更新 -> int
User::query()->where(['id' => 1])->delete();                 // 删除 -> int
User::query()->insert(['name' => 'A']);                      // 插入单行/多行 -> int
User::query()->upsert($data, ['name']);                      // INSERT ... ON DUPLICATE KEY UPDATE
$u->delete();                                                // 删除当前记录
```

- `save()` 根据是否存在主键与库中记录，自动选择 插入 / 更新，仅写入变化字段。
- `$fillable` 控制 `save()` 批量赋值允许写入的字段。
- 写入还会按**真实表字段**二次裁剪（不止 `$fillable`）：凡非实际表字段的键都会在生成 SQL 前被丢弃，多余的键不会再引发 `Unknown column` 错误。

### 模型对象

- 属性读取：`$u->name` 或 `$u->getAttribute('name')`；`$u['name']`（ArrayAccess）。
- 序列化：`$u->toArray()`（隐藏 `$hidden` 字段）；实现 `JsonSerializable`，可直接 `json_encode`。
- 从结果行构造：`User::newFromRow($row)`（联合查询时保留关联列）。
- 元信息：`$u->getFields()`、`$u->getPrimarys()`、`$u->getTable()`、`$u->getFullTable()`。

### 关联查询（join）

`leftJoin` / `join` 接受 `[表名, 别名]` 形式。表名可以是 **模型类名**，会被自动解析为真实表名（含前缀），因此跨前缀 join 无需手工拼表名：

```php
use App\Models\Order;
use App\Models\User;

Order::query()
    ->alias('o')
    ->leftJoin([User::class, 'u'], 'u.id = o.user_id')
    ->where(['u.status' => 1])
    ->get();
```

误传非模型类名会给出清晰报错，而不是生成非法 SQL 表名。

## 3. Collection

`Model::query()->get()` / `all()` 返回 `Sharky\Database\Collection`：

- `toArray()`：转为数组（模型自动 `toArray`）
- `map(callable)`：映射为新集合
- `add($item)` / `all()` / `count()`（也实现了 `Iterator` 与 `JsonSerializable`，可直接遍历与 `json_encode`）

## 4. 查询构造器（原生 / 底层）

需要脱离模型时，直接用 `QueryBuilder`（不依赖容器）：

```php
use Sharky\Database\QueryBuilder;
use Sharky\Database\Database;

$builder = (new QueryBuilder('users', Database::connect('master')))
    ->where(['status' => 1])
    ->orderBy('id desc')
    ->limit(10);

[$sql, $params] = $builder->toSql();   // 仅编译，不执行
$rows = $builder->run();               // 执行 SELECT -> 二维数组
$count = $builder->count();            // 计数
$page = $builder->paginate(1, 20);     // 分页 ['total','data','current_page',...]
$builder->insert($data)->run();        // 写操作需 run()
```

## 5. 事务

基于连接管理器（或连接实例）：

```php
use Sharky\Database\Database;

Database::beginTransaction();
try {
    Database::execute('UPDATE ...', [...]);
    Database::commit();
} catch (\Throwable $e) {
    Database::rollback();
}

// 更简洁的闭包式：
Database::runTransaction(function () {
    // 在事务内执行的代码
});
```

> 行级排他锁 `forUpdate()` / `forUpdate(true)`（SKIP LOCKED）须在事务内使用。

## 6. 原生 SQL

通过连接实例执行任意 SQL（参数绑定防注入）：

```php
$conn = Database::connect('master');
$rows  = $conn->query('SELECT VERSION() AS v');         // SELECT -> 二维数组
$rows  = $conn->query('SHOW TABLES');
$affected = $conn->execute('DELETE FROM t WHERE id = ?', [1]);   // 写 -> 受影响行数
$id = $conn->lastInsertId();
```

---

← [返回文档索引](./README_zh.md)
