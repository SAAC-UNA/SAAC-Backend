<?php

namespace App\Http\Middleware;

use App\Services\GlobalFilterContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyGlobalFilterContext
{
    public function __construct(private readonly GlobalFilterContextService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $context = $this->service->injectableFilters($user);
        if (empty($context)) {
            return $next($request);
        }

        $merge = [];
        foreach ($context as $key => $value) {
            if (!$this->hasExplicitValue($request, $key)) {
                $merge[$key] = $value;
            }
        }

        if (!empty($merge)) {
            $request->merge($merge);
        }

        return $next($request);
    }

    private function hasExplicitValue(Request $request, string $key): bool
    {
        if ($request->query->has($key)) {
            $value = $request->query($key);
            return $value !== null && $value !== '';
        }

        if ($request->request->has($key)) {
            $value = $request->request->get($key);
            return $value !== null && $value !== '';
        }

        return false;
    }
}
