<?php
/**
 * SharkyPHP Foundation Library
 *
 * 数据库门面
 *
 * @method static \Sharky\Database\Contracts\ConnectionInterface connect(?string $connect = null)
 * @method static mixed                                       query(string $sql, array $params = [])
 * @method static int                                        execute(string $sql, array $params = [])
 * @method static bool                                       beginTransaction()
 * @method static bool                                       commit()
 * @method static bool                                       rollback()
 * @method static mixed                                      runTransaction(callable $callBack)
 * @method static int                                        lastInsertId()
 * @method static void                                       closeAll()
 * @method static void                                       disconnect(string $connect)
 * @method static string[]                                   getConnectedNames()
 *
 * @package SharkyPHP\\Facade
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Facade;

use Sharky\Database\Database as DatabaseService;

class Database extends AbstractFacade
{
    protected static function getFacadeAccessor(): string
    {
        return DatabaseService::class;
    }
}
