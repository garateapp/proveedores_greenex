<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentoTrabajadorFirmaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdmin() || $user?->isSupervisor();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'signature_data_url' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! str_starts_with($value, 'data:image/png;base64,')) {
                        $fail('La firma digital debe enviarse en formato PNG.');

                        return;
                    }

                    $encodedImage = substr($value, strlen('data:image/png;base64,'));
                    $decodedImage = base64_decode($encodedImage, true);

                    if ($decodedImage === false || $decodedImage === '') {
                        $fail('La firma digital no es válida.');

                        return;
                    }

                    if (strlen($decodedImage) > (2 * 1024 * 1024)) {
                        $fail('La firma digital excede el tamaño permitido.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signature_data_url.required' => 'Debe capturar la firma antes de guardar el documento.',
        ];
    }
}
