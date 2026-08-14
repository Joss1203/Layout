<?php
declare(strict_types=1);
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
require __DIR__ . "/../conexion.php";
$p = (int) ($_GET["pabellon"] ?? 0);
$sql =
    "SELECT id,clave,numero,categoria,estado,empresa,logo,contacto,email,telefono,x,y,ancho,alto,bloqueado FROM stands" .
    ($p ? " WHERE pabellon_id=?" : "") .
    " ORDER BY numero";
$s = $conexion->prepare($sql);
$s->execute($p ? [$p] : []);
echo json_encode(
    $s->fetchAll(),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
);
