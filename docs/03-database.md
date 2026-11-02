← [中文版本](./03-database_zh.md)

# Database

The database package provides three layers of capability: **connection management** (`Database`), **query builder** (`QueryBuilder`), and **ActiveRecord model** (`Model` + `ActiveQuery` + `Collection`). Usage through models is recommended; raw queries and the query builder are also publicly available.

## 1. Binding a Connection

Before a model is used, you must bind the database connection to the model (performed once at application startup):

```php
use Sharky\Database\Database;
use Sharky\Database\Model;
use Sharky\Facade\Config;

$connection = Database::connect('master');   // Facade: default connection = first node of hosts
Model::setConnection($connection);
```

You can also instantiate the manager directly:

```php
$db = new Database(Config::get('database.default'));
$connection = $db->connect('master');
```

Connection configuration is in `configs/database.php` (`type` / `driver` / `hosts.{master,slave}` / `prefix` / `charset`, etc.).

## 2. Model (ActiveRecord)

Define a model by extending `Sharky\Database\Model`:

```php
namespace App\Models;

use Sharky\Database\Model;

class User extends Model
{
    public const TABLE  = 'users';   // Without prefix; if not set, inferred from class name (User -> user)
    public const PREFIX = null;      // Override the configured prefix; null inherits the connection config

    protected array $fillable = ['name', 'email'];   // Whitelist; empty array = allow all table fields
    protected array $hidden   = ['password'];         // Hidden when serialized
}
```

- Table name inference: `UserModel` → `user` (strip the `Model`/entity suffix and convert to snake_case).
- Query entry point: `User::query()` returns an `ActiveQuery` (already bound to the model, connection, and table name).

### Common Queries

```php
use App\Models\User;

User::find(1);                       // Find by primary key, single row -> User|null
User::all();                         // All rows -> Collection

User::query()
    ->select(['id', 'name'])
    ->where(['id' => ['>', 1]])
    ->orderBy(['id' => 'desc'])
    ->limit(10)
    ->get();                         // -> Collection

User::query()->where(['status' => 1])->first();        // First row -> User|null
User::query()->where(['age' => ['>=', 18]])->count();  // Count
User::query()->where(['id' => 1])->paginate(1, 20);    // Pagination array
```

### where Syntax

`where(callable|array $conditions)` and `orWhere(...)` support multiple forms:

```php
->where(['id' => 1])                              // Equal
->where(['id' => ['>', 1]])                        // Operator + value
->where(['id', '=', 1])                            // Triple
->where([['id', '=', 1], ['name', '!=', 'x']])      // Multiple AND
->where(function ($q) {                             // Closure = nested parenthesis grouping
    $q->where(['a' => 1])->orWhere(['b' => 2]);
})
```

Supported operators: `= != <> > < >= <= & | ^ ~ << >> LIKE NOT LIKE`, as well as `IN / NOT IN`, `IS / IS NOT`, `BETWEEN / NOT BETWEEN`. When the value is `null`, `=` / `!=` automatically convert to `IS NULL / IS NOT NULL`.

### Write Operations

```php
$u = new User(['name' => 'Tom']);
$u->email = 'tom@example.com';     // Magic attribute / setAttribute
$u->save();                        // Insert; returns affected row count, auto-fills the auto-increment primary key

$u->name = 'Jerry';
$u->save();                        // Existing primary key -> update changed fields

User::query()->where(['id' => 1])->update(['name' => 'X']);  // Update -> int
User::query()->where(['id' => 1])->delete();                 // Delete -> int
User::query()->insert(['name' => 'A']);                      // Insert single/multiple rows -> int
User::query()->upsert($data, ['name']);                      // INSERT ... ON DUPLICATE KEY UPDATE
$u->delete();                                                // Delete the current record
```

- `save()` automatically chooses insert / update based on whether a primary key exists and whether the record is in the database, and only writes the changed fields.
- `$fillable` controls which fields `save()` allows to be written via mass assignment.

### Model Objects

- Reading attributes: `$u->name` or `$u->getAttribute('name')`; `$u['name']` (ArrayAccess).
- Serialization: `$u->toArray()` (hides `$hidden` fields); implements `JsonSerializable`, so it can be `json_encode`d directly.
- Constructing from a result row: `User::newFromRow($row)` (preserves join columns for union queries).
- Meta info: `$u->getFields()`, `$u->getPrimarys()`, `$u->getTable()`, `$u->getFullTable()`.

## 3. Collection

`Model::query()->get()` / `all()` return `Sharky\Database\Collection`:

- `toArray()`: Convert to an array (models are automatically `toArray`d)
- `map(callable)`: Map to a new collection
- `add($item)` / `all()` / `count()` (also implements `Iterator` and `JsonSerializable`, so it can be iterated directly and `json_encode`d)

## 4. Query Builder (Raw / Low-level)

When you need to work without a model, use `QueryBuilder` directly (no container dependency):

```php
use Sharky\Database\QueryBuilder;
use Sharky\Database\Database;

$builder = (new QueryBuilder('users', Database::connect('master')))
    ->where(['status' => 1])
    ->orderBy('id desc')
    ->limit(10);

[$sql, $params] = $builder->toSql();   // Compile only, do not execute
$rows = $builder->run();               // Execute SELECT -> 2D array
$count = $builder->count();            // Count
$page = $builder->paginate(1, 20);     // Paginate ['total','data','current_page',...]
$builder->insert($data)->run();        // Write operations require run()
```

## 5. Transactions

Based on the connection manager (or a connection instance):

```php
use Sharky\Database\Database;

Database::beginTransaction();
try {
    Database::execute('UPDATE ...', [...]);
    Database::commit();
} catch (\Throwable $e) {
    Database::rollback();
}

// More concise closure form:
Database::runTransaction(function () {
    // Code executed within the transaction
});
```

> The row-level exclusive lock `forUpdate()` / `forUpdate(true)` (SKIP LOCKED) must be used within a transaction.

## 6. Raw SQL

Execute arbitrary SQL via a connection instance (parameter binding prevents injection):

```php
$conn = Database::connect('master');
$rows  = $conn->query('SELECT VERSION() AS v');         // SELECT -> 2D array
$rows  = $conn->query('SHOW TABLES');
$affected = $conn->execute('DELETE FROM t WHERE id = ?', [1]);   // Write -> affected row count
$id = $conn->lastInsertId();
```

---

← [Back to Documentation Index](./README.md)
