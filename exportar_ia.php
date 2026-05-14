<?php
// ARCHIVO: exportar_ia.php
// Escanea la carpeta actual y genera un TXT con el código para dárselo a Gemini

$directorio_base = __DIR__; 
$archivo_salida = 'resumen_proyecto_ia.txt';
$contenido = "Estructura del Proyecto y Código Fuente:\n\n";

// Carpetas y extensiones que NO queremos incluir (para que no pese 1GB)
$carpetas_ignoradas = ['assets', 'img', 'libreria', 'vendor', '.git'];
$extensiones_permitidas = ['php', 'js', 'html', 'json', 'css'];

$iterador = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directorio_base, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterador as $archivo) {
    // Ignorar carpetas no deseadas
    $ignorar = false;
    foreach ($carpetas_ignoradas as $carpeta) {
        if (strpos($archivo->getPathname(), DIRECTORY_SEPARATOR . $carpeta . DIRECTORY_SEPARATOR) !== false) {
            $ignorar = true; break;
        }
    }
    if ($ignorar || $archivo->isDir()) continue;

    $ext = strtolower(pathinfo($archivo->getFilename(), PATHINFO_EXTENSION));
    
    // Si es un archivo de código permitido, lo leemos
    if (in_array($ext, $extensiones_permitidas)) {
        // Sacamos la ruta relativa para que sea más fácil de leer
        $ruta_relativa = str_replace($directorio_base . DIRECTORY_SEPARATOR, '', $archivo->getPathname());
        
        // Evitamos que este mismo script se lea a sí mismo
        if ($ruta_relativa == basename(__FILE__)) continue;

        $contenido .= "=================================================\n";
        $contenido .= "ARCHIVO: " . $ruta_relativa . "\n";
        $contenido .= "=================================================\n";
        $contenido .= file_get_contents($archivo->getPathname()) . "\n\n";
    }
}

// Forzamos la descarga del archivo de texto
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="'.$archivo_salida.'"');
echo $contenido;
exit;
?>