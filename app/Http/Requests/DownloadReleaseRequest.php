<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DownloadReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'episode_ids' => ['sometimes', 'array'],
            'episode_ids.*' => ['integer', 'distinct', 'exists:episodes,id'],
        ];
    }

    /** @return array<int>|null */
    public function episodeIds(): ?array
    {
        if (! $this->has('episode_ids')) {
            return null;
        }

        return array_map('intval', $this->validated('episode_ids', []));
    }
}
