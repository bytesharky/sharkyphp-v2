<?php
/**
 * 无标记版本：依靠【首行约定】识别语言切换链接
 * 用法：
 * php md_lang_switch.php --dry-run
 * php md_lang_switch.php
 */
declare (strict_types = 1);

// ========== 配置区 ==========
$scanDir = __DIR__."/docs"; // 扫描目录，可以改成 './docs'
$backup  = false;
// ============================

$dryRun = in_array('--dry-run', $argv, true);

function scanMdFiles(string $dir): array
{
    $result = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($fullPath)) {
            $result = array_merge($result, scanMdFiles($fullPath));
            continue;
        }
        if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'md') {
            $result[] = $fullPath;
        }
    }
    return $result;
}

function buildSwitchLine(string $filePath): string
{
    $filename = basename($filePath);
    if (preg_match('/^(.*)_zh\.md$/i', $filename, $m)) {
        $enName = $m[1] . '.md';
        return "← [English version](./{$enName})";
    } elseif (preg_match('/^(.*)\.md$/i', $filename, $m)) {
        $zhName = $m[1] . '_zh.md';
        if (str_ends_with($m[1], '_zh')) return '';
        return "← [中文版本](./{$zhName})";
    }
    return '';
}

$files = scanMdFiles($scanDir);
$changedCount = 0;

foreach ($files as $file) {
    $switchLine = buildSwitchLine($file);
    if (empty($switchLine)) continue;

    $content = file_get_contents($file);
    if ($content === false) {
        echo "[SKIP READ FAIL] {$file}\n";
        continue;
    }

    // 按行读取
    $lines = preg_split('/\r\n|\r|\n/', $content);
    // 过滤掉文件开头空行，找到第一行非空内容
    $nonEmptyIndex = null;
    foreach ($lines as $idx => $line) {
        if (trim($line) !== '') {
            $nonEmptyIndex = $idx;
            break;
        }
    }

    $newBlock = $switchLine . "\n\n";
    $newContent = '';

    if ($nonEmptyIndex === null) {
        // 文件是空的
        $newContent = $newBlock;
    } else {
        $firstContentLine = $lines[$nonEmptyIndex];
        // 判断第一行是否已经是语言切换链接（简单特征：包含 [ 和 ](./）
        $isOldSwitchLine = (strpos($firstContentLine, '[') !== false && strpos($firstContentLine, '](./') !== false);

        if ($isOldSwitchLine) {
            // 替换旧的首行导航
            $lines[$nonEmptyIndex] = $switchLine;
            $newContent = implode("\n", $lines);
        } else {
            // 在最前面插入新导航
            $newContent = $newBlock . $content;
        }
    }

    if ($newContent === $content) {
        echo "[NO CHANGE] {$file}\n";
        continue;
    }
    echo "[CHANGE] {$file}\n";
    $changedCount++;

    if ($dryRun) continue;
    if ($backup) file_put_contents($file . '.bak', $content);
    file_put_contents($file, $newContent);
}

echo "\n===== 完成 =====\n";
echo "文件总数: " . count($files) . "\n";
echo "待变更/已变更文件数: {$changedCount}\n";
if ($dryRun) {
    echo "⚠️ 当前是 dry-run 预览模式，没有修改磁盘文件\n";
} else {
    echo "✅ 已经写入文件\n";
}
