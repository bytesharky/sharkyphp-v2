← [English version](./07-template.md)

# 模板引擎

`Sharky\Template\TemplateEngine` 是一个轻量编译型模板引擎：模板文件在首次渲染（或源文件变更）时编译为 PHP，结果缓存到 `cachePath`。

## 基本用法

```php
use Sharky\Template\TemplateEngine;
use Sharky\Facade\Config;

$engine = new TemplateEngine(Config::get('template'));   // 配置见下
// 注册自定义模板函数（在模板中通过函数名调用）
$engine->addFunction('__', fn(string $key, array $vars = []) => __($key, $vars));

$template = $engine->render('SharkyDemo/index.html', [     // 视图路径（相对于 viewsPath）
    'title'   => 'Hello',
    'content' => 'World',
]);

// $template 实现了 __toString，可直接输出或包进响应
echo $template;
return \Sharky\Facade\Http::html((string) $template);
```

`Config::get('template')` 结构（见 `configs/template.php`）：

```php
return [
    'viewsPath' => base_path('app/Http/Views'),   // 模板目录（必填，须存在）
    'cachePath' => base_path('runtime/views'),    // 编译缓存目录（不存在会自动创建）
    'compress'  => false,                          // 是否压缩 HTML
    'debug'     => false,                          // 调试模式（不缓存，输出可读 PHP）
];
```

- `render($template, $variables)` 返回 `Sharky\Template\Template`（字符串化即渲染结果）。
- 视图路径解析：`viewsPath` + 以 `/` 分隔的前缀目录 + 文件名。例如传入 `SharkyDemo/index.html` → `app/Http/Views/SharkyDemo/index.html`。

> **变量与 `$` 前缀约定**：在 `{{ }}` 中，裸标识符会自动加上 `$`（如 `{{ score }}` → `$score`），且 `{{ func(...) }}` 会被编译为模板函数调用 `call('func', ...)`（见上方 `addFunction` 示例）。但在 `{% %}` 控制流语句中（if / foreach / for / while），条件与变量**不会**自动加 `$`，必须显式写成 `{% if $score >= 60 %}`、`{% foreach $item in $list %}`，否则裸名会被当作 PHP 常量而报错。

## 模板语法

### 变量输出

```html
{{ title }}          <!-- 转义输出（htmlspecialchars，ENT_QUOTES | ENT_SUBSTITUTE，UTF-8） -->
{!! content !!}       <!-- 原始输出，不转义 -->
{{ __('welcome', ['name' => 'Tom']) }}   <!-- 调用注册的模板函数 -->
{{ item.name }} / {{ $item['name'] }}    <!-- 已带 $ 的写法原样保留，不会被编译成 $$item -->
```

> 转义在编译期写入产物（`htmlspecialchars((string)(...))`），对数组等非标量只做字符串强转；需要结构化输出请用 `{!! !!}`，或在控制器里先序列化。

### 控制流

```html
{% if $score >= 60 %}
    <p>及格</p>
{% elif $score >= 30 %}
    <p>补考</p>
{% else %}
    <p>不及格</p>
{% endif %}

{% foreach item in list %}
    <li>{{ item.name }}</li>
{% endforeach %}

{% for i in 0..9 %}
    <span>{{ i }}</span>
{% endfor %}

{% while cond %}
    ...
{% endwhile %}

{% php %}
    // 原生 PHP 代码块
    $x = 1 + 1;
{% endphp %}
```

### 模板继承 / 区块

父模板 `layout.html`：

```html
<html><body>{% block content %}{% endblock %}</body></html>
```

子模板：

```html
{% extends "layout.html" %}
{% block content %}
    <h1>Hello</h1>
{% endblock %}
```

### 引入

```html
{% include "partial/header.html" %}
```

> **路径解析规则（`extends` / `include` 同）**：先按「当前模板所在目录」解析，找不到再按「视图根目录」解析。因此在 `demo/page.html` 里写 `{% extends "layout.html" %}` 会命中 `demo/layout.html`，不会因为模板前缀再拼一次 `demo/` 而变成 `demo/demo/layout.html`。

### 注释

```html
{# 这是模板注释，编译后不会出现在输出中 #}
```

### HTML 压缩

当 `compress = true` 时，引擎会压缩空白、清除 HTML 注释（保留 IE 条件注释），并对 `<script>`/`<style>` 仅清注释；`<pre>`/`<textarea>`/`<code>` 内容原样保留。

> 注意：`extends` 必须位于模板最前面，且每个 block 名称在继承链中不可重复定义。

---

← [返回文档索引](./README_zh.md)
