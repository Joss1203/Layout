<?php require __DIR__ . "/includes/conexion.php";
$id = $conexion
    ->query("SELECT id FROM pabellones WHERE clave='D'")
    ->fetchColumn();
header("Location: pabellon.php?id=" . $id);
exit();
