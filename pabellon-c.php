<?php require __DIR__ . "/includes/conexion.php";
$id = $conexion
    ->query("SELECT id FROM pabellones WHERE clave='C'")
    ->fetchColumn();
header("Location: pabellon.php?id=" . $id);
exit();
