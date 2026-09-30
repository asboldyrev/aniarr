<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateRssFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rss_url' => ['required', 'url', 'max:500'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
