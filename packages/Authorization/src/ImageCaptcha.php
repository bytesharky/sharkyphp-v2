<?php
/**
 * SharkyPHP Authorization Library
 *
 * 图像验证码实现
 *
 * @package SharkyPHP\\Authorization
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Authorization;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ImageCaptcha
{
    private string $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    private string $fontFile   = __DIR__ . '/../font/arial.ttf';

    public function __construct(
        private array $options = []
    ) {
        $this->characters = $options['characters'] ?? $this->characters;
        $this->fontFile   = $options['fontFile'] ?? $this->fontFile;

    }

    // 生成验证码图形
    public function makeImage(string $captcha, bool $base64 = false): string
    {
        if (empty($captcha)) {
            throw new InvalidArgumentException('Captcha content cannot be empty.');
        }

        $this->verifyGDLibrary();
        $fontPath = $this->getUsableFont($this->fontFile);

        $imgw       = intval($this->options['width'] ?? 100);
        $imgh       = intval($this->options['height'] ?? 40);
        $fontSize   = intval($this->options['fontSize'] ?? 20);
        $linecount  = intval($this->options['linecount'] ?? random_int(3, 5));
        $pointCount = intval($this->options['pointCount'] ?? random_int(100, 200));

        $image = imagecreatetruecolor($imgw, $imgh);
        $color = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 20, 20, $color);

        $characters = str_split($captcha);
        $length     = count($characters);
        $fontW      = intdiv($imgw, $length + 1);
        $xx         = intdiv($fontW, 2);

        foreach ($characters as $i => $chr) {
            $x     = $i * $fontW + $xx + max(0, intdiv($fontW - $fontSize, 2));
            $y     = intval(rand(5, $imgh - $fontSize - 5) + $fontSize);
            $color = imagecolorallocate($image, rand(0, 120), rand(0, 120), rand(0, 120));
            imagettftext($image, $fontSize, rand(-45, 45), $x, $y, $color, $fontPath, $chr);
        }

        for ($i = 0; $i < $pointCount; $i++) {
            $pointColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
            imagesetpixel($image, rand(0, $imgw), rand(0, $imgh), $pointColor);
        }
        for ($i = 0; $i < $linecount; $i++) {
            $linePoint = imagecolorallocate($image, rand(150, 255), rand(150, 255), rand(150, 255));
            imageline($image, rand(10, 50), rand(10, $imgh - 10), rand($imgw - 50, $imgw - 10), rand(10, $imgh - 10), $linePoint);
        }

        ob_start();
        imagepng($image);
        $buffer = ob_get_clean();
        if (PHP_VERSION_ID < 80000) {
            imagedestroy($image);
        } else {
            $image = null;
        }

        if ($base64) {
            $encode = base64_encode($buffer);
            return sprintf('data:image/png;base64,%s', $encode);
        }

        //response($buffer, 200)->setHeader(['Content-Type' => 'image/png']);
        return $buffer;
    }

    // 生成验证码
    public function makeCaptcha(int $length = 4): string
    {
        $captcha = '';
        for ($i = 0; $i < $length; $i++) {
            $string   = substr($this->characters, rand(0, strlen($this->characters) - 1), 1);
            $captcha .= $string;
        }
        return $captcha;
    }

    // 检查字体文件是否可用
    private function fontIsUsable(string $fontPath): bool
    {
        if (! file_exists($fontPath) || ! is_readable($fontPath)) {
            return false;
        }

        $lastHandler = set_error_handler(function ($errno, $errstr) {
            throw new RuntimeException($errstr);
        });

        $image = null;
        try {
            $image = imagecreatetruecolor(1, 1);
            $black = imagecolorallocate($image, 0, 0, 0);
            imagettftext($image, 10, 0, 0, 10, $black, $fontPath, "test");
            return true;
        } catch (Throwable) {
            return false;
        } finally {
            set_error_handler($lastHandler);
            if ($image !== null) {
                if (PHP_VERSION_ID < 80000) {
                    imagedestroy($image);
                } else {
                    $image = null;
                }
            }
        }
    }

    // 获取可用的字体
    private function getUsableFont(string $fontPath): string
    {
        if (! $this->fontIsUsable($fontPath)) {
            if (PHP_OS_FAMILY !== 'Windows') {
                throw new RuntimeException("Font file is unavailable.");
            }
            $converted = iconv("UTF-8", "GBK//IGNORE", $fontPath);
            if (! ($converted !== false &&
                $converted !== $fontPath &&
                $this->fontIsUsable($converted))) {
                throw new RuntimeException("Font file is unavailable.");
            }
            return $converted;
        }
        return $fontPath;
    }

    // 检查是否支持 GD
    private function verifyGDLibrary(): void
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException(sprintf('GD library is not installed or configured correctly.'));
        } else {
            $gdInfo = gd_info();
            if (! $gdInfo['FreeType Support']) {
                throw new RuntimeException(sprintf('GD library does not support TrueType fonts.'));
            }
        }
    }
}
