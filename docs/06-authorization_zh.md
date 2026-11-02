← [English version](./06-authorization.md)

# 鉴权（JWT / 图形验证码 / MFA）

鉴权包 `Sharky\Authorization` 提供三类工具，可直接 `new` 使用，不依赖框架内核。

## JWT

基于 HMAC（HS256/HS384/HS512）的 JSON Web Token 生成与校验。

```php
use Sharky\Authorization\JWT;

$jwt = new JWT($secretKey, 'HS256');      // 算法默认 HS256

$token = (string) $jwt->issue(
    ['uid' => 1, 'role' => 'admin'],       // 载荷
    3600                                   // 过期秒数（自动写入 exp）
);

// 校验：成功返回 JWT 实例，失败抛异常
try {
    $verified = (new JWT($secretKey, 'HS256'))->verify($token);
    $payload  = $verified->getPayload();    // 解码后的载荷（含 exp）
} catch (\Throwable $e) {
    // token 非法 / 过期 / 未生效
}
```

- `issue($payload, $timeout = 3600)`：设置载荷与过期时间，返回 `$this`。
- `(string) $jwt`：序列化为 token 字符串（`__toString` / `jsonSerialize`）。
- `verify($token)`：校验签名与过期，返回已填充 `payload` / `header` 的实例。
- `getPayload()` / `getHeader()`：读取解码内容。

## 图形验证码（ImageCaptcha）

基于 GD + TrueType 生成验证码图片（需 `gd` 扩展与 FreeType 支持）。

```php
use Sharky\Authorization\ImageCaptcha;

$options = [
    'width'      => 150,
    'height'     => 60,
    'fontSize'   => 30,
    'linecount'  => random_int(3, 5),
    'pointCount' => random_int(100, 200),
    // 'characters' => 'ABC...',   // 可选自定义字符集
    // 'fontFile'   => '/path/arial.ttf',  // 可选自定义字体
];

$captcha = new ImageCaptcha($options);

$code   = $captcha->makeCaptcha(4);          // 生成 4 位随机字符串
$buffer = $captcha->makeImage($code);         // 生成 PNG 二进制（默认）
$buffer = $captcha->makeImage($code, true);   // 生成 data:image/png;base64,... 字符串

// 直接输出图片
return \Sharky\Facade\Http::response($buffer, 200)
    ->setHeader(['Content-Type' => 'image/png']);
```

## 多因素认证（MFA / TOTP）

基于 RFC‑6238 的 TOTP 一次性密码。

```php
use Sharky\Authorization\MFA;

// 生成共享密钥（Base32，长度建议 >= 16）
$secret = MFA::generateSharedSecret(16);

// 构造验证器：密钥、周期(秒)、位数、哈希算法
$mfa = new MFA($secret, 30, 6, 'sha1');

// 生成 otpauth URI（用于生成二维码，供 Authenticator App 扫描）
$uri = $mfa->getQRCodeUrl('user@example.com');     // 实例方法
$uri = MFA::makeQRCodeUrl('user@example.com', $secret);  // 静态方法

// 校验用户输入的动态码（faultTol 容错窗口，默认 1）
if ($mfa->verify($userInputCode, 1)) {
    // 通过
}

// 调试：获取当前窗口内全部 TOTP 与剩余重置秒数
['totps' => $totps, 'reset' => $reset] = $mfa->getTOTPToken(1);
```

- 构造函数校验：`period` 10–60，`digits` 6–8，`algo` ∈ {sha1, sha256, sha512}。
- `verify($inputCode, $faultTol = 1)`：容错窗口不超过 5。

---

← [返回文档索引](./README_zh.md)
