<?php

namespace App\Enums;

enum GaratePassErrorCode: string
{
    case Unauthorized = 'UNAUTHORIZED';
    case Forbidden = 'FORBIDDEN';
    case TicketNotRegistered = 'TICKET_NOT_REGISTERED';
    case TicketNotFound = 'TICKET_NOT_FOUND';
    case InvalidPayload = 'INVALID_PAYLOAD';
    case QrInvalidFormat = 'QR_INVALID_FORMAT';
    case PrintWindowActive = 'PRINT_WINDOW_ACTIVE';
    case TicketAlreadyUsed = 'TICKET_ALREADY_USED';
    case IdempotencyKeyMissing = 'IDEMPOTENCY_KEY_MISSING';
    case IdempotencyKeyReused = 'IDEMPOTENCY_KEY_REUSED';
    case AdminNotAssigned = 'ADMIN_NOT_ASSIGNED';

    /**
     * HTTP status con el que viaja cada código.
     *
     * PRINT_WINDOW_ACTIVE responde 409 y no 429 a propósito: es una regla de
     * negocio con hora de reintento conocida, no un límite de tasa. Con 429 la
     * app trataría el caso como "demasiadas consultas" y le diría al comensal
     * que espere, sin decirle cuánto.
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::Unauthorized => 401,
            self::Forbidden => 403,
            self::TicketNotRegistered, self::TicketNotFound => 404,
            self::PrintWindowActive, self::TicketAlreadyUsed => 409,
            default => 422,
        };
    }

    public function defaultMessage(): string
    {
        return match ($this) {
            self::Unauthorized => 'Token de API inválido o ausente.',
            self::Forbidden => 'El código QR no pertenece a un administrador.',
            self::TicketNotRegistered => 'El código QR no tiene una asignación vigente.',
            self::TicketNotFound => 'El vale no existe.',
            self::QrInvalidFormat => 'El código no viene en el formato esperado.',
            self::InvalidPayload => 'La carga de la solicitud es inválida.',
            self::PrintWindowActive => 'La persona ya emitió un vale dentro de la ventana activa.',
            self::TicketAlreadyUsed => 'El vale ya fue canjeado.',
            self::IdempotencyKeyMissing => 'Falta el encabezado Idempotency-Key.',
            self::IdempotencyKeyReused => 'La clave de idempotencia ya fue usada con otra carga.',
            self::AdminNotAssigned => 'La tarjeta de administrador no tiene un usuario asignado.',
        };
    }
}
