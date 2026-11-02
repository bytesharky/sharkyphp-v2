← [中文版本](./06-authorization_zh.md)

# Authorization (JWT / Image Captcha / MFA)

The authorization package `Sharky\Authorization` provides three types of tools that can be used directly with `new`, without depending on the framework kernel.

## JWT

Generation and verification of JSON Web Tokens based on HMAC (HS256/HS384/HS512).

```php
use Sharky\Authorization\JWT;

$jwt = new JWT($secretKey, 'HS256');      // algorithm defaults to HS256

$token = (string) $jwt->issue(
    ['uid' => 1, 'role' => 'admin'],       // payload
    3600                                   // expiration in seconds (exp written automatically)
);

// Verify: returns a JWT instance on success, throws an exception on failure
try {
    $verified = (new JWT($secretKey, 'HS256'))->verify($token);
    $payload  = $verified->getPayload();    // decoded payload (includes exp)
} catch (\Throwable $e) {
    // token invalid / expired / not yet valid
}
```

- `issue($payload, $timeout = 3600)`: sets the payload and expiration, returns `$this`.
- `(string) $jwt`: serializes into a token string (`__toString` / `jsonSerialize`).
- `verify($token)`: verifies the signature and expiration, returns an instance populated with `payload` / `header`.
- `getPayload()` / `getHeader()`: read the decoded content.

## Image Captcha (ImageCaptcha)

Generates captcha images based on GD + TrueType (requires the `gd` extension with FreeType support).

```php
use Sharky\Authorization\ImageCaptcha;

$options = [
    'width'      => 150,
    'height'     => 60,
    'fontSize'   => 30,
    'linecount'  => random_int(3, 5),
    'pointCount' => random_int(100, 200),
    // 'characters' => 'ABC...',   // optional custom character set
    // 'fontFile'   => '/path/arial.ttf',  // optional custom font
];

$captcha = new ImageCaptcha($options);

$code   = $captcha->makeCaptcha(4);          // generate a 4-character random string
$buffer = $captcha->makeImage($code);         // generate PNG binary (default)
$buffer = $captcha->makeImage($code, true);   // generate data:image/png;base64,... string

// Output the image directly
return \Sharky\Facade\Http::response($buffer, 200)
    ->setHeader(['Content-Type' => 'image/png']);
```

## Multi-Factor Authentication (MFA / TOTP)

TOTP one-time passwords based on RFC-6238.

```php
use Sharky\Authorization\MFA;

// Generate a shared secret (Base32, length recommended >= 16)
$secret = MFA::generateSharedSecret(16);

// Build the verifier: secret, period (seconds), digits, hash algorithm
$mfa = new MFA($secret, 30, 6, 'sha1');

// Generate an otpauth URI (used to generate a QR code for the Authenticator App to scan)
$uri = $mfa->getQRCodeUrl('user@example.com');     // instance method
$uri = MFA::makeQRCodeUrl('user@example.com', $secret);  // static method

// Verify the dynamic code entered by the user (faultTol fault-tolerance window, default 1)
if ($mfa->verify($userInputCode, 1)) {
    // passed
}

// Debug: get all TOTPs within the current window and the remaining reset seconds
['totps' => $totps, 'reset' => $reset] = $mfa->getTOTPToken(1);
```

- Constructor validation: `period` is 10–60, `digits` is 6–8, `algo` ∈ {sha1, sha256, sha512}.
- `verify($inputCode, $faultTol = 1)`: the fault-tolerance window may not exceed 5.

---

← [Back to Documentation Index](./README.md)
