<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_public_responses_include_browser_security_headers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }

    public function test_legacy_csp_reports_are_parsed_and_logged_safely(): void
    {
        Log::spy();
        $payload = json_encode([
            'csp-report' => [
                'document-uri' => 'https://mariwaze.test/carte',
                'violated-directive' => 'script-src',
                'blocked-uri' => 'https://evil.example/script.js',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/security/csp-reports',
            server: ['CONTENT_TYPE' => 'application/csp-report'],
            content: $payload,
        )->assertNoContent();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'CSP violation'
                && $context['violated_directive'] === 'script-src');
    }
}
