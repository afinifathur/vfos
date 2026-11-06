<?php

namespace Tests\Feature\Legal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_accessible_without_authentication(): void
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertViewIs('legal.privacy-policy');
    }

    public function test_privacy_policy_contains_required_sections_and_google_limited_use_disclosure(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('vFOS');
        $response->assertSee('https://vfos.ngopikopi.my.id');
        $response->assertSee('Information We Collect');
        $response->assertSee('Google API Services Usage &amp; Limited Use Disclosure', false);
        $response->assertSeeText('Google API Services User Data Policy, including the Limited Use requirements');
        $response->assertSee('https://www.googleapis.com/auth/gmail.readonly');
        $response->assertSee('https://www.googleapis.com/auth/userinfo.email');
        $response->assertSee('https://www.googleapis.com/auth/userinfo.profile');
        $response->assertSee('openid');
        $response->assertSee('AES-256');
        $response->assertSee('https://myaccount.google.com/permissions');
        $response->assertSee('privacy@ngopikopi.my.id');
    }

    public function test_terms_of_service_is_accessible_without_authentication(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertViewIs('legal.terms');
    }

    public function test_terms_of_service_contains_required_sections_and_financial_disclaimer(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertStatus(200);
        $response->assertSee('Terms of Service');
        $response->assertSee('vFOS');
        $response->assertSee('https://vfos.ngopikopi.my.id');
        $response->assertSee('Acceptance of Terms');
        $response->assertSee('Description of Service');
        $response->assertSee('Financial Information and No Advice Disclaimer');
        $response->assertSee('NOT FINANCIAL, INVESTMENT, TAX, OR LEGAL ADVICE');
        $response->assertSee('AS IS');
        $response->assertSee('Limitation of Liability &amp; Warranty Disclaimer', false);
        $response->assertSee('Republic of Indonesia');
        $response->assertSee('legal@ngopikopi.my.id');
    }

    public function test_authenticated_user_can_access_legal_pages(): void
    {
        $user = User::factory()->create();

        $privacyResponse = $this->actingAs($user)->get(route('legal.privacy'));
        $privacyResponse->assertStatus(200);
        $privacyResponse->assertSee('Dashboard');

        $termsResponse = $this->actingAs($user)->get(route('legal.terms'));
        $termsResponse->assertStatus(200);
        $termsResponse->assertSee('Dashboard');
    }

    public function test_login_page_renders_links_to_privacy_policy_and_terms(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee(route('legal.privacy'));
        $response->assertSee(route('legal.terms'));
    }

    public function test_protected_routes_remain_protected(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/transactions')->assertRedirect(route('login'));
        $this->get('/wealth-statement')->assertRedirect(route('login'));
        $this->get('/settings')->assertRedirect(route('login'));
        $this->get('/gmail-inbox')->assertRedirect(route('login'));
    }
}

