<?php

declare(strict_types=1);

namespace TinyFramework\Opcache\Http\Middleware;

use Closure;
use TinyFramework\Http\Middleware\MiddlewareInterface;
use TinyFramework\Http\Request;
use TinyFramework\Http\RequestInterface;
use TinyFramework\Http\Response;

class OpcacheMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, Closure $next, mixed ...$parameters): Response
    {
        $key = $request->post('key');
        if ((bool)$key && hash_equals(hash('sha512', config('app.secret')), $key)) {
            return $next($request);
        }
        return Response::error(403);
    }
}
