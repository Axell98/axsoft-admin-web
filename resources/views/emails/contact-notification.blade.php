<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo contacto desde la web</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #18181b;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding: 40px 20px;">
    <tr>
      <td align="center">
        <table width="100%" max-width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
          <!-- Encabezado con marca Voladizo -->
          <tr>
            <td style="background-color: #0d0d0d; padding: 28px 32px; border-bottom: 3px solid #c5a880;">
              <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: 2px; text-transform: uppercase;">
                VOLADIZO <span style="color: #c5a880;">|</span> CONTACTO WEB
              </h1>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding: 32px;">
              <h2 style="margin: 0 0 16px 0; font-size: 18px; color: #18181b; font-weight: 600;">
                Has recibido un nuevo mensaje desde el sitio web
              </h2>
              <p style="margin: 0 0 24px 0; color: #71717a; font-size: 13px;">
                Fecha: {{ date('d/m/Y h:i A') }}
              </p>

              <!-- Tabla de datos -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px; border: 1px solid #e4e4e7; border-radius: 6px;">
                <tr>
                  <td style="padding: 12px 16px; background-color: #fafafa; font-size: 13px; font-weight: 600; color: #52525b; width: 30%; border-bottom: 1px solid #e4e4e7;">
                    Correo
                  </td>
                  <td style="padding: 12px 16px; font-size: 14px; color: #18181b; border-bottom: 1px solid #e4e4e7;">
                    <a href="mailto:{{ $data['correo'] }}" style="color: #c5a880; font-weight: 600; text-decoration: none;">
                      {{ $data['correo'] }}
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="padding: 12px 16px; background-color: #fafafa; font-size: 13px; font-weight: 600; color: #52525b; width: 30%; border-bottom: 1px solid #e4e4e7;">
                    Teléfono
                  </td>
                  <td style="padding: 12px 16px; font-size: 14px; color: #18181b; border-bottom: 1px solid #e4e4e7;">
                    <a href="tel:{{ $data['telefono'] }}" style="color: #18181b; text-decoration: none;">
                      {{ $data['telefono'] }}
                    </a>
                    @php
                      $cleanPhone = preg_replace('/\D/', '', $data['telefono'] ?? '');
                    @endphp
                    @if(!empty($cleanPhone))
                      &nbsp;•&nbsp;
                      <a href="https://wa.me/{{ $cleanPhone }}" style="color: #25D366; font-size: 12px; font-weight: 600; text-decoration: none;" target="_blank">
                        Abrir WhatsApp
                      </a>
                    @endif
                  </td>
                </tr>
              </table>

              <!-- Mensaje del cliente -->
              <div style="background-color: #fcfcfc; border-left: 4px solid #c5a880; padding: 16px 20px; border-radius: 4px; margin-bottom: 28px;">
                <span style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1.5px; color: #a1a1aa; margin-bottom: 8px;">
                  Mensaje del cliente:
                </span>
                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #27272a; white-space: pre-wrap;">{{ $data['mensaje'] }}</p>
              </div>

              <!-- Botón responder -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="mailto:{{ $data['correo'] }}?subject=Respuesta a tu consulta - Voladizo" style="display: inline-block; background-color: #0d0d0d; color: #c5a880; font-weight: 700; font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; padding: 14px 28px; text-decoration: none; border-radius: 4px;">
                      Responder por correo
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Pie de página -->
          <tr>
            <td style="background-color: #fafafa; padding: 20px 32px; border-top: 1px solid #e4e4e7; font-size: 12px; color: #a1a1aa; text-align: center;">
              Este correo fue enviado automáticamente desde el formulario web de <strong style="color: #71717a;">Voladizo</strong>.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
