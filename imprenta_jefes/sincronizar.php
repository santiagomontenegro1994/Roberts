<?php
date_default_timezone_set('America/Argentina/Cordoba');
require_once 'auth_jefes.php';
verificarSesionApp();

$MiConexion = ConexionBD();

$enlaces_csv = [
    'productos_simples' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vS6WoliUD3dIGLQpyCgpPLMAha4Jsnovi-9zn9-KVjZQLEkGTLHIIwPp5sFjJE6I-BSDw_pyq-simm1/pub?gid=0&single=true&output=csv',
    'variables_imprenta' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vS6WoliUD3dIGLQpyCgpPLMAha4Jsnovi-9zn9-KVjZQLEkGTLHIIwPp5sFjJE6I-BSDw_pyq-simm1/pub?gid=2042612844&single=true&output=csv',
    'escalas_cantidades' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vS6WoliUD3dIGLQpyCgpPLMAha4Jsnovi-9zn9-KVjZQLEkGTLHIIwPp5sFjJE6I-BSDw_pyq-simm1/pub?gid=1687296352&single=true&output=csv',
    'promociones' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vS6WoliUD3dIGLQpyCgpPLMAha4Jsnovi-9zn9-KVjZQLEkGTLHIIwPp5sFjJE6I-BSDw_pyq-simm1/pub?gid=136780670&single=true&output=csv'
];

$carpeta_destino = __DIR__ . '/../datos/';
if (!is_dir($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

$resultados = [];

// Descargar los datos del Excel
foreach ($enlaces_csv as $nombre_archivo => $url) {
    $datos = [];
    if (($gestor = fopen($url, "r")) !== FALSE) {
        $titulos = fgetcsv($gestor, 1000, ",");
        $titulos = array_map('trim', $titulos);
        
        while (($fila = fgetcsv($gestor, 1000, ",")) !== FALSE) {
            if (count($titulos) == count($fila)) {
                $datos[] = array_combine($titulos, $fila);
            }
        }
        fclose($gestor);
        
        $ruta_json = $carpeta_destino . $nombre_archivo . '.json';
        file_put_contents($ruta_json, json_encode($datos, JSON_PRETTY_PRINT));
        $resultados[] = ['tipo' => 'success', 'texto' => "Archivo <b>$nombre_archivo</b> actualizado desde Drive."];
    } else {
        $resultados[] = ['tipo' => 'danger', 'texto' => "No se pudo leer <b>$nombre_archivo</b>."];
    }
}

// Actualizar base de datos MySQL (Adaptado a mysqli_stmt)
$productos_actualizados = 0;
$json_productos = file_get_contents($carpeta_destino . 'productos_simples.json');
$productos_excel = json_decode($json_productos, true);

if ($productos_excel) {
    // Preparamos la consulta para mayor seguridad
    $stmt = mysqli_prepare($MiConexion, "UPDATE productos SET precio = ?, titulo = ?, idActivo = ? WHERE id = ?");
    
    foreach ($productos_excel as $prod) {
        $id = (int)$prod['ID_Producto'];
        $precio = floatval($prod['Precio']);
        $titulo = $prod['Titulo'];
        $estado = (strtoupper(trim($prod['Estado'])) == 'ACTIVO') ? 1 : 0;

        mysqli_stmt_bind_param($stmt, "dsii", $precio, $titulo, $estado, $id);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $productos_actualizados++;
        }
    }
    mysqli_stmt_close($stmt);
    $resultados[] = ['tipo' => 'primary', 'texto' => "Base de Datos MySQL: Se actualizaron <b>$productos_actualizados</b> productos con los precios nuevos."];
}

include 'header_mobile.php';
?>

<div class="bg-white p-3 d-flex align-items-center shadow-sm sticky-top mb-4">
    <h5 class="m-0 fw-bold mx-auto">Sincronización</h5>
</div>

<div class="container px-4">
    <div class="text-center mb-4">
        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
            <i class="bi bi-check-lg" style="font-size: 3rem;"></i>
        </div>
        <h4 class="fw-bold">¡Precios Actualizados!</h4>
        <p class="text-muted small">Los datos se han descargado correctamente desde Google Drive.</p>
    </div>

    <div class="list-group shadow-sm border-0 rounded-4 mb-4">
        <?php foreach($resultados as $res): ?>
            <div class="list-group-item border-0 border-bottom p-3 d-flex align-items-start">
                <i class="bi bi-info-circle-fill text-<?= $res['tipo'] ?> me-3 mt-1 fs-5"></i>
                <span class="text-secondary" style="font-size: 0.9rem;"><?= $res['texto'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="index.php" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
        Volver al Menú
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>