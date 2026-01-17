<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notificationTitle }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .email-header {
            background: linear-gradient(135deg, #c8102e 0%, #8b0a1f 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        
        .email-header h1 {
            font-size: 24px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .email-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        .email-body {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .notification-card {
            background-color: #f8f9fa;
            border-left: 4px solid #c8102e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .notification-title {
            font-size: 16px;
            font-weight: 600;
            color: #c8102e;
            margin-bottom: 10px;
        }
        
        .notification-message {
            font-size: 14px;
            color: #555555;
            line-height: 1.8;
        }
        
        .action-button {
            display: inline-block;
            margin: 30px 0;
            padding: 14px 32px;
            background-color: #c8102e;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        
        .action-button:hover {
            background-color: #8b0a1f;
        }
        
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-text {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .footer-links {
            margin: 15px 0;
        }
        
        .footer-links a {
            color: #c8102e;
            text-decoration: none;
            margin: 0 10px;
            font-size: 13px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 20px 0;
        }
        
        .note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #856404;
        }
        
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }
            
            .email-header h1 {
                font-size: 20px;
            }
            
            .action-button {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>SAAC - UNA</h1>
            <p>Sistema de Acreditación y Autoevaluación de las Carreras</p>
        </div>
        
        <!-- Body -->
        <div class="email-body">
            <p class="greeting">Hola, <strong>{{ $userName }}</strong></p>
            
            <div class="notification-card">
                <div class="notification-title">
                    {{ $notificationTitle }}
                </div>
                <div class="notification-message">
                    {!! nl2br(e($notificationMessage)) !!}
                </div>
            </div>
            
            @if($actionUrl)
            <div style="text-align: center;">
                <a href="{{ $actionUrl }}" class="action-button">
                    {{ $actionText }}
                </a>
            </div>
            @endif
            
            <div class="note">
                <strong>Nota:</strong> Este es un mensaje automático del sistema. Si tiene alguna duda, comuníquese con el administrador de su carrera.
            </div>
            
            <div class="divider"></div>
            
            <p style="font-size: 13px; color: #6c757d;">
                Este correo fue enviado el {{ date('d/m/Y') }} a las {{ date('H:i') }} (Hora de Costa Rica).
            </p>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <p class="footer-text">
                <strong>Universidad Nacional de Costa Rica</strong><br>
                Campus Omar Dengo, Heredia
            </p>
            
            <div class="footer-links">
                <a href="https://www.una.ac.cr">www.una.ac.cr</a>
                <span style="color: #dee2e6;">|</span>
                <a href="mailto:saac@una.ac.cr">saac@una.ac.cr</a>
            </div>
            
            <p class="footer-text" style="font-size: 12px; margin-top: 15px;">
                © {{ date('Y') }} Universidad Nacional. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>
