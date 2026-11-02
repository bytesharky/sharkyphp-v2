← [English version](./05-events.md)

# 事件系统

事件系统封装了 PSR‑14。`Sharky\Event\EventManager` 通过 `Event` 门面暴露，并支持「按事件名」派发（自动包装为 `Event` 对象）。

## 注册监听器

```php
use Sharky\Facade\Event;

// 闭包
Event::listen('user.registered', function (\Sharky\Event\Event $e) {
    $payload = $e->getPayload();   // 派发时传入的数组
});

// [类, 方法]
Event::listen('user.registered', [\App\Listeners\UserListener::class, 'onRegistered']);

// Class@method
Event::listen('user.registered', '\App\Listeners\UserListener@onRegistered');

// 仅类名（非静态时由容器解析，支持构造函数依赖注入）
Event::listen('user.registered', \App\Listeners\UserListener::class);
```

- `Event::listen()` 与 `Event::on()` 等价。
- 监听器可以是 `callable`、`[class, method]`、`class@method`，或以类名字符串给出的可调用类。
- 派发时若监听器中方法非静态，框架从容器取实例（支持依赖注入）。

## 派发事件

```php
// 按事件名 + 负载数组（自动包装成 Event 对象）
$event = Event::dispatch('user.registered', ['uid' => 1]);

// 派发已有的事件对象
Event::dispatch($myEventObject);
```

- 返回派发后的事件对象。
- 事件对象提供 `getName()` 与 `getPayload()`。

## 监听器匹配规则

监听器按「事件名（推荐）」或「事件类名」匹配：

- 事件对象有 `getName()` → 按事件名匹配；
- 否则回退为 `get_class($event)` 匹配（PSR‑14 默认行为）。

## 取消 / 清理

```php
Event::off('user.registered');   // 移除该事件的全部监听器
Event::clear();                  // 清空全部监听器
```

## 内置框架事件

| 事件常量                                      | 事件标识                | 说明                                     |
| --------------------------------------------- | ----------------------- | ---------------------------------------- |
| `APP_START`                                   | `app.start`             | 应用初始化启动，最早触发                 |
| **内核通用（HTTP / Console 双内核都会触发）** |                         |                                          |
| `KERNEL_BOOTING`                              | `kernel.booting`        | 内核启动前，引导加载阶段                 |
| `KERNEL_BOOTED`                               | `kernel.booted`         | 内核引导完成，服务就绪                   |
| `KERNEL_TERMINATING`                          | `kernel.terminating`    | 内核准备终止、开始收尾                   |
| `KERNEL_WORKER_START`                         | `kernel.worker_start`   | Worker 进程启动（Swoole 协程场景）       |
| `KERNEL_WORKER_STOP`                          | `kernel.worker_stop`    | Worker 进程停止、销毁                    |
| **HTTP 请求生命周期**                         |                         |                                          |
| `REQUEST_RECEIVE`                             | `request.receive`       | 收到 HTTP 请求                           |
| `REQUEST_ROUTE`                               | `request.route`         | 路由解析匹配阶段                         |
| `REQUEST_CONTROLLER`                          | `request.controller`    | 控制器方法执行前                         |
| `REQUEST_RESPONSE`                            | `request.response`      | 响应对象组装完成                         |
| `REQUEST_RESPONSE_SENT`                       | `request.response_sent` | 响应内容已经发送给客户端                 |
| `REQUEST_ERROR`                               | `request.error`         | 请求链路内捕获异常（未进入全局异常渲染） |
| `REQUEST_FINISH`                              | `request.finish`        | 请求全部流程结束，收尾清理               |
| **全局异常**                                  |                         |                                          |
| `EXCEPTION_THROW`                             | `exception.throw`       | 异常抛出时触发（原始异常）               |
| `EXCEPTION_RENDER`                            | `exception.render`      | 异常渲染输出阶段                         |
| **Console 命令行生命周期**                    |                         |                                          |
| `CONSOLE_RECEIVE`                             | `console.receive`       | 接收到命令行输入                         |
| `CONSOLE_MATCH`                               | `console.match`         | 命令匹配查找                             |
| `CONSOLE_START`                               | `console.start`         | 命令执行开始                             |
| `CONSOLE_FINISH`                              | `console.finish`        | 命令正常执行完毕                         |
| `CONSOLE_ERROR`                               | `console.error`         | 命令执行抛出错误                         |


你可以在业务代码中 `Event::listen()` 这些事件名来挂载钩子。

---

← [返回文档索引](./README_zh.md)
