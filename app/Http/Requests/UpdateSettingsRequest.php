<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    public const KEYS = [
        'sonarr_url',
        'sonarr_api_key',
        'qbittorrent_url',
        'qbittorrent_username',
        'qbittorrent_password',
        'jellyfin_url',
        'jellyfin_api_key',
        'thetvdb_api_key',
        'thetvdb_pin',
        'download_save_path',
        'qbittorrent_category',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_fill_keys(
            self::KEYS,
            ['sometimes', 'nullable', 'string', 'max:2048'],
        );
    }
}
