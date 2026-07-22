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

// --- 5. RESPUESTA AUTOMÁTICA AL CLIENTE ---
$asunto_cliente = "Confirmación de solicitud - BoostPatagonia";
$cuerpo_cliente = "Hola $nombre,\n\n"
                . "Hemos recibido tu solicitud para el diagnóstico IDS de la temporada 2026–2027.\n"
                . "Un consultor senior se pondrá en contacto contigo a la brevedad vía WhatsApp.\n\n"
                . "Gracias por confiar en BoostPatagonia.\n";

$headers_cliente = "From: BoostPatagonia <proyectos@boostpatagonia.com>\r\n"
                 . "Content-Type: text/plain; charset=UTF-8";

mail($email, $asunto_cliente, $cuerpo_cliente, $headers_cliente);

// --- 6. RESPUESTA PARA LA WEB ---
header('Content-Type: application/json');
echo json_encode(['ok' => $enviado_admin]);
exit;