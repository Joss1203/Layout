<?php
declare(strict_types=1);
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}
require __DIR__ . "/includes/conexion.php";
$conexion->beginTransaction();
try {
    $conexion->exec(
        "INSERT INTO pabellones (clave,nombre,orden,activo) VALUES ('__TEST__','Prueba temporal',9999,0)",
    );
    $insertado = (int) $conexion
        ->query("SELECT COUNT(*) FROM pabellones WHERE clave='__TEST__'")
        ->fetchColumn();
    $conexion->rollBack();
    if ($insertado !== 1) {
        throw new RuntimeException("La lectura de comprobacion fallo.");
    }
    echo "OK: conexión, migración, escritura, lectura y rollback funcionan.\n";
    echo "Pabellones: " .
        $conexion->query("SELECT COUNT(*) FROM pabellones")->fetchColumn() .
        "\n";
    echo "Usuarios: " .
        $conexion->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() .
        "\n";
    echo "Stands: " .
        $conexion->query("SELECT COUNT(*) FROM stands")->fetchColumn() .
        "\n";
} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
