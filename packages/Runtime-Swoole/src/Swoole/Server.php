<?php
/**
 * SharkyPHP Runtime-Swoole Library
 *
 * Swoole Server
 *
 * @package SharkyPHP\\Runtime-Swoole
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Runtime\Swoole;

use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server as SwooleServer;
use OpenSwoole\Util;
use RuntimeException;
use Sharky\Event\Events;
use Sharky\Foundation\Application;
use Sharky\Foundation\Kernel\HttpKernel;
use Sharky\Runtime\Storage\SwooleStorage;
use Sharky\Facade\Config;
use Sharky\Facade\Context;
use Sharky\Facade\Event;
use Throwable;

class Server
{
    protected string $directory;
    protected Application $app;
    protected HttpKernel $kernel;

    public static function launch(string $directory): void
    {
        $server            = new self;
        $server->directory = $directory;
        $server->start();
    }

    public function start(): void
    {
        if (! class_exists(Application::class)) {
            throw new RuntimeException(
                "Cannot run independently. This component must rely on the main package sharkyphp/foundation."
            );
        }
        // 初始化框架App
        $this->app = require "{$this->directory}/bootstrap.php";
        $this->app->setContext(SwooleStorage::class)->bootstrap();

        $swooleCfg = Config::get('config.swoole');

        $server = new SwooleServer($swooleCfg['host'], $swooleCfg['port']);
        $server->set([
            'worker_num'            => $swooleCfg['worker_num'] ?? (Util::getCPUNum() * 2),
            'max_request'           => $swooleCfg['max_request'] ?? 8000,
            'enable_coroutine'      => $swooleCfg['enable_coroutine'] ?? true,
            'enable_coroutine'      => true,
            'on_coroutine_destruct' => function () {
                try {
                    Context::clear();
                } catch (Throwable) {}
            },
        ]);

        $server->on('WorkerStart', [$this, 'onWorkerStart']);
        $server->on('WorkerStop', [$this, 'onWorkerStop']);
        $server->on('Request', [$this, 'onRequest']);

        $server->start();
    }

    public function onWorkerStart(mixed $server, int $wid): void
    {
        echo "Server started at http://{$swooleHost}:{$swoolePort}\n";

        // 工作进程启动
        Event::dispatch(Events::KERNEL_WORKER_START, ['workerId' => $wid, 'server' => $server]);

        // 启动Http核心
        $this->kernel = HttpKernel::launch();
    }

    public function onWorkerStop(mixed $server, int $wid): void
    {
        // 工作进程退出
        Event::dispatch(Events::KERNEL_WORKER_STOP, ['workerId' => $wid, 'server' => $server]);

        // 核心关闭
        $this->kernel->terminate();
    }

    public function onRequest(mixed $server, mixed $swooleReq, mixed $swooleResp): void
    {
        $psrReq  = ServerRequest::from($swooleReq);
        $psrResp = $this->kernel->handleRequest($psrReq);
        Response::emit($psrResp, $swooleResp);
    }
}
