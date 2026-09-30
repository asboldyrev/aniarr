<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSeriesMonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monitored' => ['required', 'boolean'],
        ];
    }

    public function monitored(): bool
    {
        return $this->boolean('monitored');
    }
}
