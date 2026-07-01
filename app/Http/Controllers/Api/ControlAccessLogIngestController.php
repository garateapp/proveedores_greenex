<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreControlAccessLogIngestRequest;
use App\Models\Contratista;
use App\Models\ControlAccessLog;
use App\Models\ControlAccessPresence;
use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ControlAccessLogIngestController extends Controller
{
    /**
     * @var array<string, list<string>>
     */
    private array $contractorGroups = [
        'Valsán Ltda' => ['valsan', 'valsán', 'valsan ltda', 'valsan noche'],
        'Las Orquídeas SpA' => ['Las Orquideas SpA', 'las orquideas', 'las orquídeas', 'Orquídeas Noche'],
        'Isaias Ballesteros' => ['isaias ballesteros', 'isaias ballesteros noche'],
        'Agrícola Lancair' => ['agrícola lancair', 'lancair noche'],
        'Fernando Urbina' => ['fernando urbina'],
        'Claudia Viera' => ['claudia viera'],
    ];

    public function store(StoreControlAccessLogIngestRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $stored = [];

        foreach ($payload['records'] as $record) {
            $departmentName = $record['departamento'] ?? null;
            $contractorGroup = $this->resolveContractorGroup($departmentName);

            if ($contractorGroup !== null) {
                $departmentName = $contractorGroup;
            }

            $contratista = $departmentName ? $this->findContratista($departmentName) : null;

            if ($contratista instanceof Contratista) {
                $departmentName = $contratista->razon_social;
            }

            $personalId = $this->normalizeRutId($record['personal_id']) ?? $record['personal_id'];
            $primera = $this->parseDate($record['primera_entrada'] ?? null);
            $ultima = $this->parseDate($record['ultima_salida'] ?? null);
            $fechaOperativa = $this->parseDate($record['fecha_operativa'] ?? $record['fecha'] ?? null);

            if (! ($fechaOperativa instanceof Carbon) && $primera instanceof Carbon) {
                [$fechaOperativa] = $this->computeOperationalFromEntrada($primera);
            }

            if ($fechaOperativa instanceof Carbon) {
                $fechaOperativa = $fechaOperativa->copy()->startOfDay();
            }

            $logQuery = ControlAccessLog::query()
                ->where('personal_id', $personalId);

            if ($fechaOperativa instanceof Carbon) {
                $logQuery->whereDate('fecha', $fechaOperativa->toDateString());
            }

            $log = $logQuery->first();

            if (! ($log instanceof ControlAccessLog)) {
                $log = new ControlAccessLog;
                $log->fecha = $fechaOperativa;
                $log->personal_id = $personalId;
                $log->primera_entrada = $primera;
                $log->ultima_salida = $ultima;
            } else {
                if ($primera instanceof Carbon && (! $log->primera_entrada || $primera->lt($log->primera_entrada))) {
                    $log->primera_entrada = $primera;
                }

                if ($ultima instanceof Carbon && (! $log->ultima_salida || $ultima->gt($log->ultima_salida))) {
                    $log->ultima_salida = $ultima;
                }
            }

            if (! empty($record['nombre'])) {
                $log->nombre = $record['nombre'];
            }

            $log->departamento = $departmentName;

            if (! empty($record['pin'])) {
                $log->pin = $record['pin'];
            }

            $log->save();
            $stored[] = $log->id;

            $this->updatePresence($record, $personalId, $departmentName, $primera, $ultima);
            $this->syncTrabajador($record, $personalId, $contractorGroup, $contratista);
        }

        Log::info('Control access logs ingested.', [
            'status' => 'ok',
            'stored' => count($stored),
            'ids' => $stored,
        ]);

        return response()->json([
            'status' => 'ok',
            'stored' => count($stored),
            'ids' => $stored,
        ]);
    }

    private function resolveContractorGroup(?string $departamento): ?string
    {
        if ($departamento === null || trim($departamento) === '') {
            return null;
        }

        $department = mb_strtolower(trim($departamento));

        foreach ($this->contractorGroups as $groupName => $aliases) {
            foreach ($aliases as $alias) {
                if ($department === mb_strtolower($alias)) {
                    return $groupName;
                }
            }
        }

        return null;
    }

    private function findContratista(string $departamento): ?Contratista
    {
        return Contratista::query()
            ->where(function (Builder $query) use ($departamento): void {
                $query->where('razon_social', 'like', $departamento.'%')
                    ->orWhere('nombre_fantasia', 'like', $departamento.'%');
            })
            ->first();
    }

    /**
     * @return array{0: Carbon, 1: string}
     */
    private function computeOperationalFromEntrada(Carbon $entrada): array
    {
        $dayStart = Carbon::createFromTimeString('07:00:00');
        $nightStartGrace = Carbon::createFromTimeString('17:30:00');
        $time = Carbon::createFromTime($entrada->hour, $entrada->minute, $entrada->second);

        if ($time->lt($dayStart)) {
            return [$entrada->copy()->subDay()->startOfDay(), 'NOCHE'];
        }

        if ($time->gte($nightStartGrace)) {
            return [$entrada->copy()->startOfDay(), 'NOCHE'];
        }

        return [$entrada->copy()->startOfDay(), 'DIA'];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function updatePresence(
        array $record,
        string $personalId,
        ?string $departmentName,
        ?Carbon $primera,
        ?Carbon $ultima,
    ): void {
        $presence = ControlAccessPresence::query()->firstOrNew([
            'personal_id' => $personalId,
        ]);

        if (! empty($record['nombre'])) {
            $presence->nombre = $record['nombre'];
        }

        if ($departmentName !== null) {
            $presence->departamento = $departmentName;
        }

        if (! empty($record['pin'])) {
            $presence->pin = $record['pin'];
        }

        $pairMaxTime = $this->parseDate($record['pair_max_time'] ?? null);
        $incomingLatest = $this->maxDate([$primera, $ultima, $pairMaxTime]);
        $currentLatest = $this->maxDate([$presence->last_entry_at, $presence->last_exit_at]);

        if ($incomingLatest instanceof Carbon && (! ($currentLatest instanceof Carbon) || $incomingLatest->gt($currentLatest))) {
            $isEntry = $primera instanceof Carbon && (! ($ultima instanceof Carbon) || $primera->gt($ultima));
            $isExit = $ultima instanceof Carbon && (! ($primera instanceof Carbon) || $ultima->gte($primera));

            if ($isEntry) {
                $presence->last_entry_at = $primera;
                $presence->last_exit_at = null;
            } elseif ($isExit) {
                if ($primera instanceof Carbon && (! $presence->last_entry_at || $primera->gt($presence->last_entry_at))) {
                    $presence->last_entry_at = $primera;
                }

                $presence->last_exit_at = $ultima;
            }

            if (! empty($record['max_event_id_pair'])) {
                $presence->last_event_id_pair = $record['max_event_id_pair'];
            }
        }

        if ($presence->isDirty()) {
            $presence->save();
        }
    }

    /**
     * @param  list<?Carbon>  $dates
     */
    private function maxDate(array $dates): ?Carbon
    {
        $max = null;

        foreach ($dates as $date) {
            if ($date instanceof Carbon && (! ($max instanceof Carbon) || $date->gt($max))) {
                $max = $date;
            }
        }

        return $max;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function syncTrabajador(
        array $record,
        string $personalId,
        ?string $contractorGroup,
        ?Contratista $contratista,
    ): void {
        if (! preg_match('/^[0-9]{7,8}$/', $personalId)) {
            return;
        }

        $trabajador = Trabajador::query()->find($personalId);

        if ($trabajador instanceof Trabajador) {
            if ($contratista instanceof Contratista && $trabajador->contratista_id !== $contratista->id) {
                $oldContratistaId = $trabajador->contratista_id;
                $trabajador->contratista_id = $contratista->id;
                [$trabajador->nombre, $trabajador->apellido] = $this->splitFullName($record['nombre'] ?? null);
                $trabajador->save();

                Log::info('Trabajador reasignado a nuevo contratista.', [
                    'trabajador_id' => $personalId,
                    'contratista_origen_id' => $oldContratistaId,
                    'contratista_destino_id' => $contratista->id,
                ]);
            }

            return;
        }

        if (! ($contratista instanceof Contratista)) {
            return;
        }

        $documento = $this->formatRutWithDv($personalId);

        if ($documento === null) {
            return;
        }

        [$nombre, $apellido] = $this->splitFullName($record['nombre'] ?? null);

        Trabajador::query()->create([
            'id' => $personalId,
            'documento' => $documento,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'contratista_id' => $contratista->id,
            'estado' => 'activo',
            'fecha_ingreso' => now()->toDateString(),
        ]);

        Log::info('Trabajador creado desde ingreso de control de acceso.', [
            'trabajador_id' => $personalId,
            'documento' => $documento,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'contratista_id' => $contratista->id,
        ]);
    }

    private function normalizeRutId(string $rut): ?string
    {
        $clean = strtoupper((string) preg_replace('/[^0-9K]/', '', $rut));

        if ($clean === '') {
            return null;
        }

        if (str_contains($rut, '-') && strlen($clean) > 1) {
            return substr($clean, 0, -1);
        }

        if (strlen($clean) > 8) {
            return substr($clean, 0, -1);
        }

        return $clean;
    }

    private function formatRutWithDv(string $rut): ?string
    {
        $rut = preg_replace('/[^0-9]/', '', $rut);

        if ($rut === null || $rut === '' || ! ctype_digit($rut)) {
            return null;
        }

        $sum = 0;
        $multiplier = 2;

        for ($i = strlen($rut) - 1; $i >= 0; $i--) {
            $sum += intval($rut[$i]) * $multiplier;
            $multiplier++;

            if ($multiplier > 7) {
                $multiplier = 2;
            }
        }

        $remainder = $sum % 11;
        $digit = 11 - $remainder;

        if ($digit === 11) {
            $verificationDigit = '0';
        } elseif ($digit === 10) {
            $verificationDigit = 'K';
        } else {
            $verificationDigit = (string) $digit;
        }

        return $rut.'-'.$verificationDigit;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitFullName(?string $fullName): array
    {
        $fullName = trim((string) $fullName);

        if ($fullName === '') {
            return ['Sin nombre', 'Sin apellido'];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $nombre = array_shift($parts) ?: $fullName;
        $apellido = trim(implode(' ', $parts));

        return [$nombre, $apellido !== '' ? $apellido : 'Sin apellido'];
    }
}
