<?php

namespace App\Http\Requests\Google;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the "Save Google Configuration" form from the Settings page.
 *
 * Fields:
 *  - google_client_id:     Required. The OAuth 2.0 Client ID from Google Cloud Console.
 *  - google_client_secret: Optional. If left empty, the existing stored secret is kept.
 *                          This is intentional — the secret is masked in the UI after first save.
 */
class SaveGoogleConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by route middleware
    }

    public function rules(): array
    {
        return [
            'google_client_id' => [
                'required',
                'string',
                'min:10',
                'max:255',
            ],
            // Secret is optional on update (user may leave it blank to keep existing)
            'google_client_secret' => [
                'nullable',
                'string',
                'min:10',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'google_client_id.required' => 'Google Client ID is required.',
            'google_client_id.min'      => 'Google Client ID appears too short. Please paste the full ID from Google Cloud Console.',
        ];
    }
}
