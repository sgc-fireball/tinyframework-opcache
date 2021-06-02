<?php declare(strict_types=1);

namespace TinyFramework\Opcache\Http\Middleware;

use Closure;
use TinyFramework\Http\Middleware\MiddlewareInterface;
use TinyFramework\Http\Request;
use TinyFramework\Http\Response;

class OpcacheMiddlewaere implements MiddlewareInterface
{

    public function handle(Request $request, Closure $next, ...$parameters): Response
    {
        $key = $request->post('key');
        if (!$key || !hash_equals(hash('sha512', config('app.secret')), $key)) {
            return Response::error(403);
        }
        return $next($request);
    }

}
