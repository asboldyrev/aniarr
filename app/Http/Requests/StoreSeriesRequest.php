<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeriesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Нет авторизации
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'thetvdb_id' => ['required', 'integer', 'min:1'],
            'thetvdb_slug' => ['nullable', 'string', 'max:255'],
            'rss_url' => ['required', 'url', 'max:500'],
            'poster_url' => ['nullable', 'url', 'max:500'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
        ];
    }
}
