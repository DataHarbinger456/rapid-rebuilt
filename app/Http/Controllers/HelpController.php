<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    /**
     * Display the user manual.
     */
    public function manual()
    {
        return view('help.manual');
    }

    /**
     * Display quick help for a specific topic.
     */
    public function topic(string $topic)
    {
        $topics = [
            'cases' => [
                'title' => 'Ayuda: Casos',
                'content' => 'Los casos representan solicitudes de titulo rebuilt. Cada caso incluye informacion del vehiculo, documentos y el estado del proceso.',
            ],
            'documents' => [
                'title' => 'Ayuda: Documentos',
                'content' => 'Sube documentos en formato PDF, JPG o PNG. Tamano maximo 10MB por archivo.',
            ],
            'appointments' => [
                'title' => 'Ayuda: Citas',
                'content' => 'Programa tu cita de inspeccion seleccionando una fecha y hora disponible.',
            ],
            'payments' => [
                'title' => 'Ayuda: Pagos',
                'content' => 'Aceptamos Visa, Mastercard, American Express y Discover. Pagos seguros via Stripe.',
            ],
        ];

        if (!isset($topics[$topic])) {
            return response()->json(['error' => 'Topic not found'], 404);
        }

        return response()->json($topics[$topic]);
    }
}
