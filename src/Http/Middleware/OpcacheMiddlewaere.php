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
        if (!hash_equals(hash('sha512', config('app.key')), $request->get('key'))) {
            return Response::error(404);
        }
        return $next($request);
    }

}
