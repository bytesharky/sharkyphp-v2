<?php
/**
 * SharkyPHP Log —— PSR-3 合规独立测试（无需 phpunit）
 *
 * 运行：/d/develop/PHP-8.2/php.exe framework/packages/Log/tests/psr3_test.php
 */
declare (strict_types = 1);

// ---- 简易 PSR-4 自动加载 ----
spl_autoload_register(function (string $class): void {
    $maps = [
        'Sharky\\Log\\' => __DIR__ . '/../src/',
        'Psr\\Log\\'    => (getenv('PSR_LOG_SRC') ?: __DIR__ . '/../../../../project/vendor/psr/log/src') . '/',
    ];
    foreach ($maps as $prefix => $base) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $rel = substr($class, strlen($prefix));
            $file = $base . str_replace('\\', '/', $rel) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

use Psr\Log\LoggerInterface;
use Sharky\Log\Logger;
use Sharky\Log\LogLevel;
use Sharky\Log\Exceptions\InvalidArgumentException as LogInvalidArgumentException;
use Sharky\Log\Handler\TestHandler;
use Sharky\Log\Handler\StreamHandler;

$passed = 0;
$failed = 0;

function assert_ok(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  PASS  {$label}\n";
    } else {
        $failed++;
        echo "  FAIL  {$label}\n";
    }
}

function section(string $title): void
{
    echo "\n== {$title} ==\n";
}

$fixedTime = new \DateTimeImmutable('2026-10-31 22:09:43');
$timeProvider = static fn(): \DateTimeImmutable => $fixedTime;

// ----------------------------------------------------------------------
section('基础：实现 LoggerInterface');
$h = new TestHandler();
$logger = new Logger($h, 'app', LogLevel::DEBUG, $timeProvider);
assert_ok($logger instanceof LoggerInterface, 'Logger 是 Psr\Log\LoggerInterface 实例');

// ----------------------------------------------------------------------
section('8 个标准级别方法');
$logger->emergency('e'); $logger->alert('a'); $logger->critical('c');
$logger->error('er'); $logger->warning('w'); $logger->notice('n');
$logger->info('i'); $logger->debug('d');
assert_ok($h->hasRecords(), '调用 8 个级别方法后产生记录');
assert_ok(count($h->getRecords()) === 8, '恰好捕获 8 条记录（实际 ' . count($h->getRecords()) . '）');
$levelsSeen = array_map(static fn($r) => $r['level'], $h->getRecords());
assert_ok($levelsSeen === ['emergency','alert','critical','error','warning','notice','info','debug'],
    '级别顺序与名称正确：' . implode(',', $levelsSeen));

// ----------------------------------------------------------------------
section('任意级别 log()');
$h->clear();
$logger->log(LogLevel::INFO, 'arbitrary');
assert_ok(count($h->getRecords()) === 1 && $h->getRecords()[0]['level'] === 'info', 'log(INFO,...) 记录 info');
assert_ok($h->getRecords()[0]['level_name'] === 'INFO', 'level_name 为大写 INFO');

// ----------------------------------------------------------------------
section('无效级别抛 Psr\Log\InvalidArgumentException');
$threw = false;
try {
    $logger->log('nonsense', 'x');
} catch (LogInvalidArgumentException $e) {
    $threw = true;
    assert_ok($e instanceof \InvalidArgumentException, '同时也是内置 \InvalidArgumentException');
    assert_ok($e instanceof \Psr\Log\InvalidArgumentException, '也是 \Psr\Log\InvalidArgumentException');
}
assert_ok($threw, 'log() 对未定义级别抛出 InvalidArgumentException');

// ----------------------------------------------------------------------
section('消息占位符插值（PSR-3）');
$h->clear();
$logger->info('User {name} id {id} score {score}', ['name' => 'bob', 'id' => 7, 'score' => 9.5]);
$msg = $h->getRecords()[0]['message'];
assert_ok($msg === 'User bob id 7 score 9.5', "插值结果正确：{$msg}");

// ----------------------------------------------------------------------
section('exception 上下文附加堆栈');
$h->clear();
$ex = new \RuntimeException('boom at service');
$logger->error('call failed', ['exception' => $ex]);
$full = $h->getRecords()[0]['message'];
assert_ok(str_contains($full, 'call failed'), '原始消息保留');
assert_ok(str_contains($full, 'boom at service'), '包含异常消息');
assert_ok(str_contains($full, 'RuntimeException'), '包含异常类名（堆栈）');

// ----------------------------------------------------------------------
section('全局级别门槛过滤');
$h->clear();
$logger2 = new Logger($h, 'app', LogLevel::WARNING, $timeProvider);
$logger2->debug('d'); $logger2->info('i'); $logger2->notice('n');
$logger2->warning('w'); $logger2->error('e'); $logger2->critical('c');
$captured = array_map(static fn($r) => $r['level'], $h->getRecords());
assert_ok($captured === ['warning','error','critical'],
    '低于 warning 的被丢弃，仅保留 warning+：' . implode(',', $captured));

// ----------------------------------------------------------------------
section('Handler 级别门槛（handler 独立过滤）');
$hInfo = new TestHandler(LogLevel::ERROR); // 仅 error 及以上
$logger3 = new Logger($hInfo, 'app', LogLevel::DEBUG, $timeProvider);
$logger3->info('i'); $logger3->warning('w'); $logger3->error('e'); $logger3->critical('c');
$cap = array_map(static fn($r) => $r['level'], $hInfo->getRecords());
assert_ok($cap === ['error','critical'], 'handler 仅捕获 error/critical：' . implode(',', $cap));

// ----------------------------------------------------------------------
section('withName 频道副本');
$h->clear();
$sub = $logger->withName('billing');
$sub->info('charged');
assert_ok($h->getRecords()[0]['channel'] === 'billing', 'withName 改变频道名');
assert_ok($logger->getName() === 'app', '原 logger 频道名不受影响（不可变）');

// ----------------------------------------------------------------------
section('多 Handler 分发');
$hA = new TestHandler();
$hB = new TestHandler();
$multi = new Logger([$hA, $hB], 'app', LogLevel::DEBUG, $timeProvider);
$multi->warning('both');
assert_ok(count($hA->getRecords()) === 1 && count($hB->getRecords()) === 1, '一条日志同时送达两个 handler');

// ----------------------------------------------------------------------
section('StreamHandler 文件写入');
$tmp = sys_get_temp_dir() . '/sharky_log_test_' . uniqid() . '.log';
$sf = new StreamHandler($tmp, LogLevel::DEBUG, '[{datetime}] {channel}.{level_name}: {message}');
$fileLogger = new Logger($sf, 'api', LogLevel::DEBUG, $timeProvider);
$fileLogger->info('hello {who}', ['who' => 'world']);
$sf->close();
$content = is_file($tmp) ? file_get_contents($tmp) : '';
assert_ok($content !== false && str_contains($content, '[2026-10-31 22:09:43] api.INFO: hello world'),
    '文件内容格式正确：' . trim((string) $content));
@unlink($tmp);

// ----------------------------------------------------------------------
section('LogLevel 常量与严重度');
assert_ok(LogLevel::severity(LogLevel::EMERGENCY) > LogLevel::severity(LogLevel::DEBUG), 'emergency 严重度 > debug');
assert_ok(LogLevel::isValid('info') && ! LogLevel::isValid('verbose'), 'isValid 判定正确');
$sevThrew = false;
try { LogLevel::severity('bad'); } catch (LogInvalidArgumentException $e) { $sevThrew = true; }
assert_ok($sevThrew, 'LogLevel::severity 对非法级别抛异常');

// ----------------------------------------------------------------------
echo "\n========================================\n";
echo "结果：PASS {$passed} / FAIL {$failed}\n";
echo "========================================\n";

exit($failed === 0 ? 0 : 1);
