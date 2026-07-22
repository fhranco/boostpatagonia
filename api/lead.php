<?php
declare(strict_types=1);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- 1. CONFIGURACIÓN DE DESTINO ---
$mi_email = "proyectos@boostpatagonia.com"; 

// --- 2. FUNCIONES DE APOYO ---
function wants_json(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return stripos($accept, 'application/json') !== false || stripos($xhr, 'XMLHttpRequest') !== false;
}

function respond_json(int $status, array $payload): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_phone(string $raw): string {
    $p = preg_replace('/[^\d\+]/', '', trim($raw));
    if (!$p) return '';
    if (strpos($p, '00') === 0) $p = '+' . substr($p, 2);
    if (isset($p[0]) && $p[0] === '+') return $p;
    return '+56' . ltrim($p, '0');
}

// --- 3. PROCESAMIENTO DEL FORMULARIO ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

// Honeypot Anti-Spam (Campos ocultos del index.html)
if (!empty($_POST['_gotcha']) || !empty($_POST['company'])) {
    respond_json(200, ['ok' => true]);
}

// Captura de datos
$nombre    = strip_tags(trim($_POST['responsable'] ?? ''));
$whatsapp  = normalize_phone($_POST['whatsapp'] ?? '');
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$categoria = strip_tags(trim($_POST['categoria'] ?? ''));
$codigo    = strip_tags(trim($_POST['codigo'] ?? ''));

// Validación
if (!$nombre || !$email) {
    respond_json(400, ['error' => 'Datos incompletos']);
}

// --- 4. ENVÍO AL REPOSITORIO (TU CORREO) ---
$asunto_admin = "🚀 NUEVO LEAD 26/27: $nombre";
$cuerpo_admin = "Nuevo interesado en BoostPatagonia:\n\n"
              . "Responsable: $nombre\n"
              . "WhatsApp: $whatsapp\n"
              . "Email: $email\n"
              . "Categoría: $categoria\n"
              . "Código: $codigo\n"
              . "Fecha: " . date('d/m/Y H:i:s') . "\n";

// El 'From' DEBE ser una cuenta real de tu hosting (proyectos@boostpatagonia.com) para que Hostinger lo valide y envíe
$headers_admin = "From: BoostPatagonia <proyectos@boostpatagonia.com>\r\n"
               . "Reply-To: $email\r\n"
               . "Content-Type: text/plain; charset=UTF-8";

$enviado_admin = mail($mi_email, $asunto_admin, $cuerpo_admin, $headers_admin);

// Simular éxito en entorno local para desarrollo (evitar error de mail() no configurado localmente)
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || stripos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
if (!$enviado_admin && $is_local) {
    $enviado_admin = true;
}

// --- 5. RESPUESTA AUTOMÁTICA AL CLIENTE (EMAIL HTML PREMIUM) ---
$asunto_cliente = "Confirmación de solicitud - BoostPatagonia";

$cuerpo_cliente = "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Postulación Recibida</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #03071e; margin: 0; padding: 40px 10px; color: #e6ecf2; }
        .card { max-width: 550px; margin: 0 auto; background: #050a30; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 40px 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center; }
        .logo { max-width: 160px; height: auto; margin-bottom: 30px; }
        h1 { font-size: 22px; color: #ffffff; margin-bottom: 20px; font-weight: 800; letter-spacing: -0.02em; }
        p { font-size: 15px; line-height: 1.6; color: #cbd5e1; margin-bottom: 25px; text-align: left; }
        .highlight { color: #28e4d3; font-weight: 700; }
        .btn-container { text-align: center; margin-top: 10px; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #28e4d3; color: #050a30 !important; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
        .footer { font-size: 10px; color: #64748b; margin-top: 35px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class='card'>
        <img src='https://boostpatagonia.com/boostpatagonia.png' alt='BoostPatagonia' class='logo'>
        <h1>¡Postulación Recibida!</h1>
        <p>Hola <span class='highlight'>{$nombre}</span>,</p>
        <p>He recibido correctamente tu postulación a mi convocatoria de la temporada 2026–2027.</p>
        <p>Estoy revisando personalmente los datos que me ingresaste. Me pondré en contacto contigo a la brevedad vía WhatsApp para ver los siguientes pasos.</p>
        <div class='btn-container'>
            <a href='https://boostpatagonia.com' class='btn'>Visitar Sitio Web</a>
        </div>
        <div class='footer'>
            © 2026 BoostPatagonia. Todos los derechos reservados.<br>
            Magallanes, Patagonia Chilena.
        </div>
    </div>
</body>
</html>
";

$headers_cliente = "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/html; charset=UTF-8\r\n"
                 . "From: BoostPatagonia <proyectos@boostpatagonia.com>\r\n";

mail($email, $asunto_cliente, $cuerpo_cliente, $headers_cliente);

// --- 6. RESPUESTA PARA LA WEB ---
header('Content-Type: application/json');
echo json_encode(['ok' => $enviado_admin]);
exit;