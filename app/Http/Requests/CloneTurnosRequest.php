<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloneTurnosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_date' => ['required', 'date', 'different:target_date'],
            'target_date' => ['required', 'date'],
        ];
    }
}
