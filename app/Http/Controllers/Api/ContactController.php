<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactNotificationMail;
use App\Models\CompanyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    /**
     * Procesa y envía el correo de contacto desde la web (sin almacenar en la base de datos).
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Trampa anti-spam (Honeypot)
        if (!empty($request->input('website'))) {
            return response()->json([
                'success' => true,
                'message' => '¡Mensaje recibido correctamente!',
            ]);
        }

        // 2. Validación de campos
        try {
            $validated = $request->validate([
                'correo' => ['required', 'string', 'email', 'max:254'],
                'telefono' => ['required', 'string', 'min:7', 'max:30'],
                'mensaje' => ['required', 'string', 'min:10', 'max:1000'],
            ], [
                'correo.required' => 'Ingresa tu correo electrónico.',
                'correo.email' => 'Ingresa un correo electrónico válido.',
                'correo.max' => 'El correo electrónico es demasiado largo.',
                'telefono.required' => 'Ingresa tu número de teléfono.',
                'telefono.min' => 'El teléfono debe tener al menos 7 dígitos.',
                'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
                'mensaje.required' => 'Escribe tu mensaje o consulta.',
                'mensaje.min' => 'El mensaje debe tener al menos 10 caracteres.',
                'mensaje.max' => 'El mensaje no puede superar los 1000 caracteres.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Revisa los campos del formulario.',
                'errors' => $e->errors(),
            ], 422);
        }

        $data = [
            'correo' => trim($validated['correo']),
            'telefono' => trim($validated['telefono']),
            'mensaje' => trim($validated['mensaje']),
        ];

        // 3. Determinar destinatarios del correo:
        // Prioridad: 1. CONTACT_RECEIVER_EMAIL en .env -> 2. Email del Admin (/admin/datos-cliente) -> 3. MAIL_FROM_ADDRESS
        $rawRecipient = env('CONTACT_RECEIVER_EMAIL');

        if (empty($rawRecipient)) {
            try {
                $company = CompanyProfile::first();
                if (!empty($company?->email)) {
                    $rawRecipient = $company->email;
                }
            } catch (\Throwable $e) {
                // Si la base de datos no está disponible, continúa al fallback
            }
        }

        if (empty($rawRecipient)) {
            $rawRecipient = config('mail.from.address', 'proyectos@voladizo.pe');
        }

        // Soporta uno o múltiples correos separados por comas
        $recipients = array_filter(array_map('trim', explode(',', $rawRecipient)));

        // 4. Envío directo del correo mediante Laravel Mail
        try {
            Mail::to($recipients)->send(new ContactNotificationMail($data));

            return response()->json([
                'success' => true,
                'message' => '¡Mensaje enviado con éxito! Nos comunicaremos contigo pronto.',
            ]);
        } catch (\Throwable $e) {
            // Se registra el error detallado en el log (storage/logs/laravel.log)
            Log::error('Fallo al enviar correo de contacto desde la web', [
                'remitente_cliente' => $data['correo'],
                'telefono' => $data['telefono'],
                'destinatarios' => $recipients,
                'error_mensaje' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No pudimos enviar tu mensaje en este momento. Por favor contáctanos directamente por WhatsApp o correo.',
            ], 500);
        }
    }
}
