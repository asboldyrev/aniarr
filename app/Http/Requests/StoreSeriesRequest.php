<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'thetvdb_id' => ['required', 'integer', 'min:1'],
            'monitored' => ['sometimes', 'boolean'],
            'rss_feeds' => ['required', 'array', 'min:1'],
            'rss_feeds.*.rss_url' => ['required', 'url', 'max:500'],
            'rss_feeds.*.season_number' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function monitored(): bool
    {
        return $this->boolean('monitored', true);
    }
}
