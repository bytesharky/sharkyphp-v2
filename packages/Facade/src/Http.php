<?php
/**
 * SharkyPHP Foundation Library
 *
 * HTTP 消息工厂门面（代理 Sharky\Http\Factory\HttpFactory，实现 PSR-17）。
 *
 * @method static \Psr\Http\Message\ResponseInterface      createResponse(int $code = 200, string $reasonPhrase = '')
 * @method static \Psr\Http\Message\RequestInterface       createRequest(string $method, $uri)
 * @method static \Psr\Http\Message\ServerRequestInterface createServerRequest(string $method, $uri, array $serverParams = [])
 * @method static \Psr\Http\Message\StreamInterface        createStream(string $content = '')
 * @method static \Psr\Http\Message\StreamInterface        createStreamFromFile(string $filename, string $mode = 'r')
 * @method static \Psr\Http\Message\StreamInterface        createStreamFromResource($resource)
 * @method static \Psr\Http\Message\UploadedFileInterface  createUploadedFile(\Psr\Http\Message\StreamInterface $stream, ?int $size = null, int $error = \UPLOAD_ERR_OK, ?string $clientFilename = null, ?string $clientMediaType = null)
 * @method static \Psr\Http\Message\UriInterface           createUri(string $uri = '')
 * @method static \Sharky\Http\Factory\HttpFactory         setFactory(string $targetTag = '')
 *
 * @package SharkyPHP\\Facade
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Facade;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Sharky\Http\Factory\HttpFactory;

final class Http extends AbstractFacade
{
    protected static function getFacadeAccessor()
    {
        return HttpFactory::class;
    }

    /**
     * 构造响应（工厂的响应模式）
     *
     * 带参直接调用工厂的 response()；不带参返回工厂对象本身，便于链式设置。
     *
     * @return HttpFactory|ResponseInterface
     */
    public static function response(...$args)
    {
        $factory = static::getInstance()->setFactory('response');

        if (! empty($args)) {
            return $factory->response(...$args);
        }

        return $factory;
    }

    // 构造一个重定向响应
    public static function redirect(string $url, int $status = 302): ResponseInterface
    {
        if ($status < 300 || $status > 399) {
            throw new InvalidArgumentException(
                sprintf('Redirect status code must be in range [3xx], given: %d', $status)
            );
        }

        return static::getInstance()->createResponse($status)->addedHeader('Location', $url);
    }
}
