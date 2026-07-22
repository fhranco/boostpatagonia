<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO BOOSTPATAGONIA ===\n\n";

// 1. PHP version
echo "PHP Version: " . phpversion() . "\n";

// 2. Check if mail() exists
echo "mail() disponible: " . (function_exists('mail') ? 'SÍ' : 'NO') . "\n";

// 3. Check if lead.php has syntax errors
echo "\n--- Verificando sintaxis de lead.php ---\n";
try {
    // Token-check the file
    $code = file_get_contents(__DIR__ . '/lead.php');
    $tokens = @token_get_all($code);
    echo "Tokens parseados: " . count($tokens) . " (archivo OK)\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 4. Simulate a POST to see what lead.php outputs
echo "\n--- Simulando envío POST ---\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['responsable'] = 'Test Diagnóstico';
$_POST['whatsapp'] = '+56912345678';
$_POST['email'] = 'test@test.com';
$_POST['categoria'] = 'Test';
$_POST['codigo'] = 'TEST';
$_POST['_gotcha'] = '';
$_POST['company'] = '';

ob_start();
try {
    include __DIR__ . '/lead.php';
} catch (Throwable $e) {
    echo "\nERROR CAPTURADO: " . get_class($e) . ": " . $e->getMessage();
    echo "\nArchivo: " . $e->getFile() . " línea " . $e->getLine();
}
$output = ob_get_clean();

echo "Respuesta de lead.php:\n";
echo $output ?: "(vacío)";
echo "\n\n=== FIN DIAGNÓSTICO ===\n";
