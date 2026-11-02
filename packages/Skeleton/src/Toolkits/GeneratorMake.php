<?php
/**
 * SharkyPHP Skeleton Library
 *
 * make 命令基类
 *
 * 用法：php sharky make:controller Demo/UserProfile [--force]
 *  1. 第一个位置参数既是类名也是相对路径（Demo/UserProfile => 默认命名空间\Demo\UserProfile）；
 *  2. 以 \ 开头的参数视为完整命名空间，不再拼接默认命名空间；
 *  3. 命名空间按 PSR-4 落盘：根命名空间小写（App\ => app/），其余段保持原样。
 *
 * @package SharkyPHP\\Skeleton
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Skeleton\Toolkits;

use PhpToken;
use Sharky\Console\Context\Input;
use Sharky\Console\Context\Output;
use Sharky\Support\Helper;

abstract class GeneratorMake
{
    // 应用根命名空间（PSR-4 约定：App\ => app/）
    protected const ROOT_NAMESPACE = 'App';

    // 命令入口（命令注解默认执行 execute 方法）
    public function execute(Input $argvInput, Output $output): void
    {
        $name = $argvInput->getArgument(0);

        if (! is_string($name) || trim($name) === '') {
            $output->errorLn(sprintf(
                'Missing class name. Usage: php sharky %s <name> [--force]',
                $argvInput->getCommand()
            ));
            exit(255);
        }

        $this->handle(trim($name), $output, (bool) $argvInput->getOption('force', false));
    }

    // 生成文件
    protected function handle(string $input, Output $output, bool $force = false): void
    {
        [$namespace, $className] = $this->parseName($input);

        // 不是 \ 开头使用相对路径（拼默认命名空间）
        if (! str_starts_with($namespace, '\\')) {
            $namespace = trim($this->getDefaultNamespace() . '\\' . $namespace, ' \\');
        } else {
            $namespace = trim($namespace, '\\');
        }

        $fqcn = $namespace . '\\' . $className;

        if (! $this->isValidFqcnSyntax($fqcn)) {
            $output->errorLn("Invalid class name: {$fqcn}");
            exit(255);
        }

        $directory = $this->resolveDirectory($namespace);
        $filePath  = $directory . DIRECTORY_SEPARATOR . $className . '.php';

        if (is_file($filePath) && ! $force) {
            $output->errorLn("File already exists: {$filePath} (use --force to overwrite)");
            exit(255);
        }

        $stub = @file_get_contents($this->getStub());

        if (! is_string($stub)) {
            $output->errorLn("Stub not found: {$this->getStub()}");
            exit(255);
        }

        $content = $this->replaceStub($stub, $this->stubVariables($namespace, $className));
        $content = $this->normalizeBlankLines($content);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $output->errorLn("Unable to create directory: {$directory}");
            exit(255);
        }

        if (@file_put_contents($filePath, $content) === false) {
            $output->errorLn("Unable to write file: {$filePath}");
            exit(255);
        }

        $output->successLn(sprintf('Created %s', $className));
        $output->writeln("NameSpace: {$namespace}");
        $output->writeln("ClassName: {$className}");
        $output->writeln("FilePath:  {$filePath}");

        // 子类可追加注册提示等
        $this->afterGenerate($fqcn, $filePath, $output);
    }

    // 命名空间 => 项目内目录（根命名空间小写，其余段保持原样以符合 PSR-4）
    protected function resolveDirectory(string $namespace): string
    {
        $segments = array_values(array_filter(
            explode('\\', trim($namespace, '\\')),
            static fn($segment) => $segment !== ''
        ));

        if ($segments === []) {return Helper::base_path();}

        $segments[0] = strtolower($segments[0]);

        return Helper::base_path(implode('/', $segments));
    }

    // 模板变量
    protected function stubVariables(string $namespace, string $className): array
    {
        return [
            'namespace' => $namespace,
            'class'     => $className,
            'command'   => $this->commandName($className),
            'table'     => $this->snakeCase($className),
        ];
    }

    // 解析输入路径
    protected function parseName(string $input): array
    {
        $input    = str_replace(['/', '\\'], '\\', $input);
        $segments = explode('\\', $input);
        $cls      = $this->snakeToPascal(trim((string) array_pop($segments)));
        $ns       = implode('\\', $segments);
        return [$ns, $cls];
    }

    // 下划线转 Pascal（app_middleware => AppMiddleware）
    protected function snakeToPascal(string $str): string
    {
        // 下划线后面第一个字母大写，删掉下划线
        $res = preg_replace_callback('/_([a-z])/', function ($m) {
            return strtoupper($m[1]);
        }, $str);
        // 首字母大写
        return ucfirst((string) $res);
    }

    // 驼峰转下划线（UserProfile => user_profile）
    protected function snakeCase(string $str): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $str));
    }

    // 类名转命令名（SendMail => send:mail，与 Resolver 的规范化规则一致）
    protected function commandName(string $className): string
    {
        return strtolower((string) preg_replace('/([A-Z])/', ':$1', lcfirst($className)));
    }

    // 替换模版变量（值里出现 $ 或 \ 也不能被当成反向引用）
    protected function replaceStub(string $stub, array $vars): string
    {
        foreach ($vars as $name => $value) {
            $stub = (string) preg_replace_callback(
                '/\{\{\s*' . preg_quote((string) $name, '/') . '\s*\}\}/',
                static fn() => (string) $value,
                $stub
            );
        }

        return $stub;
    }

    protected function isValidClassNameSyntax(string $className): bool
    {
        // 字母开头，只能字母数字，禁止下划线
        $pattern = '/^[a-z][a-z0-9]*$/i';
        if (! preg_match($pattern, $className)) {
            return false;
        }
        return ! $this->isReservedKeyword($className);
    }

    protected function isValidFqcnSyntax(string $fqcn): bool
    {
        $segments = explode('\\', $fqcn);
        foreach ($segments as $seg) {
            if ($seg === '' || ! $this->isValidClassNameSyntax($seg)) {
                return false;
            }
        }
        return true;
    }

    protected function isReservedKeyword(string $name): bool
    {
        $lower = strtolower($name);

        $specialIdentifiers = [
            'true', 'false', 'null',
            'bool', 'int', 'string', 'float', 'array', 'object', 'callable',
            'resource', 'mixed', 'never', 'void',
        ];
        if (in_array($lower, $specialIdentifiers, true)) {
            return true;
        }

        $code   = "<?php {$name}";
        $tokens = PhpToken::tokenize($code);
        foreach ($tokens as $t) {
            if ($t->isIgnorable()) {
                continue;
            }

            return ! $t->is(T_STRING);
        }
        return false;
    }

    // 生成成功后的钩子（默认什么都不做）
    protected function afterGenerate(string $fqcn, string $filePath, Output $output): void
    {}

    // 压掉可空占位符留下的多余空行
    protected function normalizeBlankLines(string $content): string
    {
        return (string) preg_replace("/(?:\r?\n){3,}/", "\n\n", $content);
    }

    abstract protected function getStub(): string;
    abstract protected function getDefaultNamespace(): string;
}
