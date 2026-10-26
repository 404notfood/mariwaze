<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $report = $this->report($request);

        if ($report !== []) {
            Log::warning('CSP violation', [
                'document_uri' => $this->value($report, 'document-uri'),
                'violated_directive' => $this->value($report, 'violated-directive'),
                'blocked_uri' => $this->value($report, 'blocked-uri'),
                'source_file' => $this->value($report, 'source-file'),
                'line_number' => $report['line-number'] ?? null,
            ]);
        }

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function report(Request $request): array
    {
        $payload = $request->all();

        if ($payload === []) {
            $content = $request->getContent();

            if (strlen($content) > 32_768) {
                return [];
            }

            $decoded = json_decode($content, true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        if (array_is_list($payload)) {
            $payload = is_array($payload[0]['body'] ?? null) ? $payload[0]['body'] : [];
        }

        $report = $payload['csp-report'] ?? $payload;

        return is_array($report) ? $report : [];
    }

    /** @param array<string, mixed> $report */
    private function value(array $report, string $key): ?string
    {
        $value = $report[$key] ?? null;

        return is_string($value) ? mb_substr($value, 0, 1000) : null;
    }
}
