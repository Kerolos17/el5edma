<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_baseline_security_headers_are_present(): void
    {
        $response = $this->get('/register');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('script-src', $csp);
        $this->assertStringContainsString("'unsafe-eval'", $csp);
        $this->assertStringContainsString('https://fcmregistrations.googleapis.com', $csp);
    }

    public function test_admin_login_inline_scripts_receive_the_csp_nonce(): void
    {
        $response = $this->get('/admin/login');
        $csp      = (string) $response->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/'nonce-([^']+)'/", $csp);
        preg_match("/'nonce-([^']+)'/", $csp, $matches);

        $response->assertSee('nonce="' . $matches[1] . '"', escape: false);
    }
}
