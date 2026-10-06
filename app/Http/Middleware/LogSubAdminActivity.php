<?php

namespace App\Http\Middleware;

use App\Models\SubAdminActivityLog;
use App\Support\SubAdminActivityRecorder;
use App\Support\SubAdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogSubAdminActivity
{
    private const HIDDEN_INPUT = ['_token', '_method', 'password', 'password_confirmation', 'current_password', 'otp'];

    public function __construct(private SubAdminActivityRecorder $recorder)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! SubAdminPermissions::isSubAdminSession()) {
            return $next($request);
        }

        if (Str::startsWith((string) $request->route()?->getName(), 'speak_up.') || $request->is('speak-up', 'speak-up/*')) {
            return $next($request);
        }

        $this->recorder->start();

        try {
            $response = $next($request);
        } finally {
            $this->recorder->stop();
        }

        try {
            $this->write($request, $response);
        } catch (\Throwable $e) {
            Log::warning('Sub admin activity log failed: '.$e->getMessage());
        }

        return $response;
    }

    private function write(Request $request, Response $response): void
    {
        $changes = $this->recorder->changes();
        $isRead = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        if ($isRead && $changes === []) {
            return;
        }

        $user = $request->user();
        $routeName = (string) $request->route()?->getName();

        SubAdminActivityLog::create([
            'user_id' => $user->id,
            'user_name' => trim(($user->f_name ?? '').' '.($user->l_name ?? '')) ?: $user->email,
            'action' => $this->action($request, $routeName, $changes),
            'module' => $this->module($routeName, $request),
            'route_name' => $routeName ?: null,
            'method' => $request->method(),
            'url' => Str::limit($request->fullUrl(), 2000, ''),
            'description' => $this->describe($changes),
            'changes' => $changes ?: null,
            'request_data' => $isRead ? null : $this->input($request),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     */
    private function action(Request $request, string $routeName, array $changes): string
    {
        $events = array_column($changes, 'event');
        if (in_array('deleted', $events, true)) {
            return 'delete';
        }
        if (in_array('created', $events, true) && ! in_array('updated', $events, true)) {
            return 'create';
        }
        if ($events !== []) {
            return 'update';
        }

        $suffix = Str::afterLast($routeName, '.');
        return match (true) {
            in_array($suffix, ['store', 'import', 'bulk-import'], true) => 'create',
            in_array($suffix, ['destroy', 'delete'], true) || $request->isMethod('DELETE') => 'delete',
            $request->isMethod('PUT') || $request->isMethod('PATCH') || in_array($suffix, ['update', 'manage_process'], true) => 'update',
            default => 'other',
        };
    }

    private function module(string $routeName, Request $request): ?string
    {
        if ($routeName !== '') {
            $parts = explode('.', $routeName);
            $segment = $parts[0] === 'subadmin' ? ($parts[1] ?? $parts[0]) : $parts[0];

            return Str::of($segment)->replace(['_', '-'], ' ')->title()->toString();
        }

        return Str::of((string) $request->segment(2) ?: (string) $request->segment(1))->replace(['_', '-'], ' ')->title()->toString() ?: null;
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     */
    private function describe(array $changes): ?string
    {
        if ($changes === []) {
            return null;
        }

        $parts = [];
        foreach (array_slice($changes, 0, 5) as $c) {
            $parts[] = ucfirst((string) $c['event']).' '.$c['model'].' #'.$c['id'];
        }
        if (count($changes) > 5) {
            $parts[] = '+'.(count($changes) - 5).' more';
        }

        return Str::limit(implode(', ', $parts), 500, '');
    }

    /**
     * @return array<string, mixed>
     */
    private function input(Request $request): array
    {
        $data = $request->except(self::HIDDEN_INPUT);

        array_walk_recursive($data, function (&$value) {
            if ($value instanceof UploadedFile) {
                $value = '[file] '.$value->getClientOriginalName();
            } elseif (is_string($value) && mb_strlen($value) > 1000) {
                $value = mb_substr($value, 0, 1000).'…';
            }
        });

        return $data;
    }
}
