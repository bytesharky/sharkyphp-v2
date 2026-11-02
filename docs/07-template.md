← [中文版本](./07-template_zh.md)

# Template Engine

`Sharky\Template\TemplateEngine` is a lightweight compiled template engine: template files are compiled to PHP on first render (or when the source changes), and the results are cached to `cachePath`.

## Basic Usage

```php
use Sharky\Template\TemplateEngine;
use Sharky\Facade\Config;

$engine = new TemplateEngine(Config::get('template'));   // config shown below
// Register a custom template function (called by name inside templates)
$engine->addFunction('__', fn(string $key, array $vars = []) => __($key, $vars));

$template = $engine->render('SharkyDemo/index.html', [     // view path (relative to viewsPath)
    'title'   => 'Hello',
    'content' => 'World',
]);

// $template implements __toString, so it can be output directly or wrapped in a response
echo $template;
return \Sharky\Facade\Http::html((string) $template);
```

`Config::get('template')` structure (see `configs/template.php`):

```php
return [
    'viewsPath' => base_path('app/Http/Views'),   // template directory (required, must exist)
    'cachePath' => base_path('runtime/views'),    // compiled cache directory (created automatically if missing)
    'compress'  => false,                          // whether to compress HTML
    'debug'     => false,                          // debug mode (no caching, outputs readable PHP)
];
```

- `render($template, $variables)` returns `Sharky\Template\Template` (stringifying it yields the rendered result).
- View path resolution: `viewsPath` + prefix directories separated by `/` + file name. For example, passing `SharkyDemo/index.html` → `app/Http/Views/SharkyDemo/index.html`.

> **Variables and the `$` prefix convention**: Inside `{{ }}`, bare identifiers are automatically prefixed with `$` (e.g. `{{ score }}` → `$score`), and `{{ func(...) }}` is compiled into a template function call `call('func', ...)` (see the `addFunction` example above). However, in `{% %}` control-flow statements (if / foreach / for / while), conditions and variables are **not** automatically prefixed with `$`; you must write them explicitly as `{% if $score >= 60 %}` or `{% foreach $item in $list %}`, otherwise a bare name will be treated as a PHP constant and cause an error.

## Template Syntax

### Variable Output

```html
{{ title }}          <!-- escaped output (htmlspecialchars, ENT_QUOTES | ENT_SUBSTITUTE, UTF-8) -->
{!! content !!}       <!-- raw output, not escaped -->
{{ __('welcome', ['name' => 'Tom']) }}   <!-- call a registered template function -->
{{ item.name }} / {{ $item['name'] }}    <!-- values that already start with $ are kept as-is, never compiled into $$item -->
```

> Escaping is emitted at compile time (`htmlspecialchars((string)(...))`), so non-scalars such as arrays are simply cast to string. Use `{!! !!}` for structured output, or serialize the value in the controller first.

### Control Flow

```html
{% if $score >= 60 %}
    <p>Pass</p>
{% elif $score >= 30 %}
    <p>Makeup exam</p>
{% else %}
    <p>Fail</p>
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
    // raw PHP code block
    $x = 1 + 1;
{% endphp %}
```

### Template Inheritance / Blocks

Parent template `layout.html`:

```html
<html><body>{% block content %}{% endblock %}</body></html>
```

Child template:

```html
{% extends "layout.html" %}
{% block content %}
    <h1>Hello</h1>
{% endblock %}
```

### Including

```html
{% include "partial/header.html" %}
```

> **Path resolution (same for `extends` and `include`)**: the engine first resolves relative to the directory of the current template, then relative to the view root. So `{% extends "layout.html" %}` inside `demo/page.html` resolves to `demo/layout.html` instead of being prefixed twice into `demo/demo/layout.html`.

### Comments

```html
{# This is a template comment and will not appear in the output after compilation #}
```

### HTML Compression

When `compress = true`, the engine compresses whitespace, removes HTML comments (while preserving IE conditional comments), and strips comments only from `<script>`/`<style>`; the contents of `<pre>`/`<textarea>`/`<code>` are preserved as-is.

> Note: `extends` must appear at the very beginning of the template, and each block name may not be defined more than once within the inheritance chain.

---

← [Back to Documentation Index](./README.md)
