<?php

namespace Database\Seeders;

use App\Models\Ubicacion;
use Illuminate\Database\Seeder;

class UbicacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear ubicaciones principales
        $unitec1 = Ubicacion::create([
            'nombre' => 'UNITEC 1',
            'codigo' => 'UNITEC1',
            'descripcion' => 'Unidad Técnica 1',
            'tipo' => 'principal',
            'orden' => 1,
            'activa' => true,
        ]);

        $unitec2 = Ubicacion::create([
            'nombre' => 'UNITEC 2',
            'codigo' => 'UNITEC2',
            'descripcion' => 'Unidad Técnica 2',
            'tipo' => 'principal',
            'orden' => 2,
            'activa' => true,
        ]);

        $bodega = Ubicacion::create([
            'nombre' => 'BODEGA CENTRAL',
            'codigo' => 'BODEGA',
            'descripcion' => 'Bodega Central',
            'tipo' => 'principal',
            'orden' => 3,
            'activa' => true,
        ]);

        // Crear sub-ubicaciones para UNITEC 1
        Ubicacion::create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Filtro',
            'codigo' => 'UNITEC1-FILTRO',
            'descripcion' => 'Área de filtrado',
            'tipo' => 'secundaria',
            'orden' => 1,
            'activa' => true,
        ]);

        Ubicacion::create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Altillo',
            'codigo' => 'UNITEC1-ALTILLO',
            'descripcion' => 'Altillo de UNITEC 1',
            'tipo' => 'secundaria',
            'orden' => 2,
            'activa' => true,
        ]);

        Ubicacion::create([
            'padre_id' => $unitec1->id,
            'nombre' => 'Paletizado',
            'codigo' => 'UNITEC1-PALETIZADO',
            'descripcion' => 'Área de paletizado',
            'tipo' => 'secundaria',
            'orden' => 3,
            'activa' => true,
        ]);

        // Crear sub-ubicaciones para UNITEC 2
        Ubicacion::create([
            'padre_id' => $unitec2->id,
            'nombre' => 'Recepción',
            'codigo' => 'UNITEC2-RECEPCION',
            'descripcion' => 'Área de recepción',
            'tipo' => 'secundaria',
            'orden' => 1,
            'activa' => true,
        ]);

        Ubicacion::create([
            'padre_id' => $unitec2->id,
            'nombre' => 'Despacho',
            'codigo' => 'UNITEC2-DESPACHO',
            'descripcion' => 'Área de despacho',
            'tipo' => 'secundaria',
            'orden' => 2,
            'activa' => true,
        ]);

        // Crear sub-ubicaciones para BODEGA CENTRAL
        Ubicacion::create([
            'padre_id' => $bodega->id,
            'nombre' => 'Zona A',
            'codigo' => 'BODEGA-ZONA-A',
            'descripcion' => 'Zona A de bodega',
            'tipo' => 'secundaria',
            'orden' => 1,
            'activa' => true,
        ]);

        Ubicacion::create([
            'padre_id' => $bodega->id,
            'nombre' => 'Zona B',
            'codigo' => 'BODEGA-ZONA-B',
            'descripcion' => 'Zona B de bodega',
            'tipo' => 'secundaria',
            'orden' => 2,
            'activa' => true,
        ]);
    }
}
