<?php

include __DIR__ . "/conexion.php";

$fila = $conexion->query("SELECT COUNT(*) AS total FROM stands")->fetch();

echo "Conexión OK. Stands: " . $fila["total"];

?>
