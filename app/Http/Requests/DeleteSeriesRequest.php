<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delete_from_sonarr' => ['sometimes', 'boolean'],
        ];
    }

    public function deleteFromSonarr(): bool
    {
        return $this->boolean('delete_from_sonarr');
    }
}
