<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Contratista;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ContratistaRegistrationController extends Controller
{
    /**
     * Show the contratista registration form.
     */
    public function create(): Response
    {
        return Inertia::render('contratistas/register');
    }

    /**
     * Handle the contratista registration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Datos del contratista
            'rut' => ['required', 'string', 'max:20', 'unique:contratistas,rut', 'regex:/^[0-9]{7,8}-[0-9kK]$/'],
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_fantasia' => ['nullable', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'comuna' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],

            // Datos del usuario administrador
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Validar dígito verificador del RUT
        if (! $this->validateRut($validated['rut'])) {
            return back()->withErrors(['rut' => 'El RUT ingresado no es válido.']);
        }

        DB::transaction(function () use ($validated) {
            // Crear el contratista
            $contratista = Contratista::create([
                'rut' => $validated['rut'],
                'razon_social' => $validated['razon_social'],
                'nombre_fantasia' => $validated['nombre_fantasia'],
                'direccion' => $validated['direccion'],
                'comuna' => $validated['comuna'],
                'region' => $validated['region'],
                'telefono' => $validated['telefono'],
                'email' => $validated['email'],
                'estado' => 'activo',
            ]);

            // Crear el usuario administrador del contratista
            User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'role' => UserRole::Contratista,
                'contratista_id' => $contratista->id,
                'is_active' => true,
            ]);
        });

        return redirect()->route('login')->with('success', 'Registro exitoso. Por favor inicia sesión.');
    }

    /**
     * Validate Chilean RUT using Modulo 11 algorithm.
     */
    private function validateRut(string $rut): bool
    {
        $rut = str_replace(['.', '-'], '', $rut);
        $dv = strtoupper(substr($rut, -1));
        $numero = substr($rut, 0, -1);

        if (! is_numeric($numero)) {
            return false;
        }

        $suma = 0;
        $multiplo = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += intval($numero[$i]) * $multiplo;
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }

        $resto = $suma % 11;
        $dvCalculado = 11 - $resto;

        if ($dvCalculado == 11) {
            $dvCalculado = '0';
        } elseif ($dvCalculado == 10) {
            $dvCalculado = 'K';
        } else {
            $dvCalculado = (string) $dvCalculado;
        }

        return $dv === $dvCalculado;
    }
}
