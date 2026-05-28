<?php

namespace App\Http\Requests\Admin;

use App\Services\PackingAttendanceReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PackingAttendanceReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'turno_id' => ['nullable', 'integer', 'exists:turnos,id'],
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    PackingAttendanceReportService::STATUS_APP_CONTROL,
                    PackingAttendanceReportService::STATUS_APP_SIN_CONTROL,
                    PackingAttendanceReportService::STATUS_CONTROL_SIN_APP,
                    'multiple',
                ]),
            ],
        ];
    }

    public function reportDate(): Carbon
    {
        $timezone = config('app.timezone', 'America/Santiago');
        $date = $this->validated('date');

        return $date
            ? Carbon::parse((string) $date, $timezone)->startOfDay()
            : Carbon::now($timezone)->subDay()->startOfDay();
    }
}
