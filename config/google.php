<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google OAuth Redirect URI
    |--------------------------------------------------------------------------
    |
    | The fixed callback URL registered in your Google Cloud Console.
    | For Laragon local development, this is always vfos.test.
    |
    | IMPORTANT: This URI must be added to the "Authorized redirect URIs"
    | in your Google Cloud Console OAuth 2.0 credentials.
    |
    */
    'redirect_uri' => env('GOOGLE_REDIRECT_URI', 'http://vfos.test/auth/google/callback'),

    /*
    |--------------------------------------------------------------------------
    | Supported Providers
    |--------------------------------------------------------------------------
    |
    | Each key maps to a set of OAuth scopes. Future modules (Gmail, Drive,
    | Calendar) plug in here by adding their provider slug.
    |
    */
    'providers' => [
        'gmail'    => 'Gmail',
        'drive'    => 'Google Drive',
        'calendar' => 'Google Calendar',
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes by Provider
    |--------------------------------------------------------------------------
    |
    | Base scopes are always requested (openid, email, profile).
    | Provider-specific scopes are merged on top at authorization time
    | via GoogleScopeManager.
    |
    | Phase 1 uses 'gmail' as the default provider for the Connect button,
    | but no Gmail API calls are made until Phase 2 (GmailService).
    |
    */
    'scopes' => [
        'base' => [
            'openid',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ],
        'gmail' => [
            'https://www.googleapis.com/auth/gmail.readonly',
        ],
        'drive' => [
            'https://www.googleapis.com/auth/drive.readonly',
        ],
        'calendar' => [
            'https://www.googleapis.com/auth/calendar.readonly',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Refresh Buffer
    |--------------------------------------------------------------------------
    |
    | Number of seconds before expiry to proactively refresh the access token.
    | Default: 300 (5 minutes before expiry).
    |
    */
    'refresh_buffer_seconds' => 300,

];
