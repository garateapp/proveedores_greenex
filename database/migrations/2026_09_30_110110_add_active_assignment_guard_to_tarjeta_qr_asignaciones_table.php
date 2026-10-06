<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MySQL no soporta índices únicos parciales, así que la garantía de "un
     * trabajador con una sola tarjeta vigente" se implementa con una columna
     * virtual que vale 1 solo cuando la asignación está vigente y NULL en
     * todas las demás. MySQL trata cada NULL como un valor distinto en un
     * índice único, por lo que solo las filas vigentes compiten entre sí y las
     * históricas nunca chocan.
     *
     * SQLite —el motor que usa la suite de tests— no soporta columnas virtuales
     * generadas con esta sintaxis, así que la restricción se omite allí. Es
     * defensa en profundidad: la ventana de 4 h por trabajador_id ya impide el
     * doble almuerzo aunque existan dos tarjetas activas.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE `tarjeta_qr_asignaciones`
             ADD COLUMN `asignacion_vigente` TINYINT UNSIGNED
             GENERATED ALWAYS AS (IF(`desasignada_en` IS NULL, 1, NULL)) VIRTUAL'
        );

        DB::statement(
            'ALTER TABLE `tarjeta_qr_asignaciones`
             ADD UNIQUE KEY `tarjeta_qr_asignaciones_trabajador_vigente_unique`
             (`trabajador_id`, `asignacion_vigente`)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE `tarjeta_qr_asignaciones`
             DROP INDEX `tarjeta_qr_asignaciones_trabajador_vigente_unique`'
        );

        DB::statement(
            'ALTER TABLE `tarjeta_qr_asignaciones`
             DROP COLUMN `asignacion_vigente`'
        );
    }
};
