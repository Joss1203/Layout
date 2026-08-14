<?php
require __DIR__ . "/../../includes/validar_sesion.php";
require __DIR__ . "/../../includes/conexion.php";
require __DIR__ . "/../../includes/seguridad.php";

$id = (int) ($_GET["id"] ?? 0);
$s = $conexion->prepare(
    "SELECT s.*,p.nombre pabellon,p.clave pabellon_clave FROM stands s LEFT JOIN pabellones p ON p.id=s.pabellon_id WHERE s.id=?",
);
$s->execute([$id]);
$stand = $s->fetch();

if (!$stand) {
    header("Location: stands.php");
    exit();
}

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validarCsrf();

    try {
        $estado = $_POST["estado"] ?? "Disponible";

        if (!in_array($estado, ["Disponible", "Ocupado"], true)) {
            throw new RuntimeException("Estado inválido.");
        }

        $categoria =
            $stand["pabellon_clave"] === "CH"
                ? "Sin categoria"
                : $_POST["categoria"] ?? "Premium";
        $logo = subirImagen("logo", "logos", $stand["clave"]) ?: $stand["logo"];

        if (isset($_POST["quitar_logo"])) {
            $logo = null;
        }

        $empresa = trim($_POST["empresa"] ?? "");

        $conexion
            ->prepare(
                "UPDATE stands SET categoria=?,estado=?,empresa=?,logo=?,bloqueado=? WHERE id=?",
            )
            ->execute([
                $categoria,
                $estado,
                $empresa ?: null,
                $logo,
                $estado === "Ocupado" ? 1 : 0,
                $id,
            ]);

        $ok = "Espacio actualizado correctamente.";
        $s->execute([$id]);
        $stand = $s->fetch();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editar espacio</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <header class="admin-topbar">
            <div>
                <span class="admin-kicker"><?= htmlspecialchars($stand["pabellon"] ?? "") ?></span>
                <h1><?= htmlspecialchars($stand["numero"]) ?></h1>
            </div>
            <a class="admin-btn secondary" href="stands.php?pabellon=<?= $stand["pabellon_id"] ?>">
                Volver
            </a>
        </header>

        <section class="admin-panel">
            <?php if ($error): ?>
                <div class="admin-alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($ok): ?>
                <div class="admin-success"><?= htmlspecialchars($ok) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

                <?php if ($stand["pabellon_clave"] === "CH"): ?>
                    <label>
                        Categoría
                        <input value="Sin categoría" disabled>
                    </label>
                    <input type="hidden" name="categoria" value="Sin categoria">
                <?php else: ?>
                    <label>
                        Categoría
                        <select name="categoria">
                            <?php
                            $categorias = [
                                "Premium" => "Premium",
                                "Estandar" => "Estándar",
                                "Economico" => "Económico",
                                "PyMES" => "PyMES",
                                "Sin categoria" => "Sin categoría",
                            ];
                            ?>
                            <?php foreach ($categorias as $valor => $texto): ?>
                                <option
                                    value="<?= $valor ?>"
                                    <?= $stand["categoria"] === $valor ? "selected" : "" ?>
                                >
                                    <?= $texto ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>

                <label>
                    Estado
                    <select name="estado">
                        <option <?= $stand["estado"] === "Disponible" ? "selected" : "" ?>>
                            Disponible
                        </option>
                        <option <?= $stand["estado"] === "Ocupado" ? "selected" : "" ?>>
                            Ocupado
                        </option>
                    </select>
                </label>

                <label>
                    Empresa
                    <input
                        name="empresa"
                        value="<?= htmlspecialchars($stand["empresa"] ?? "") ?>"
                        placeholder="Nombre del expositor"
                    >
                </label>

                <?php if ($stand["logo"]): ?>
                    <img
                        class="admin-logo-preview"
                        src="../../<?= htmlspecialchars($stand["logo"]) ?>"
                        alt="Logo actual"
                    >
                    <label class="check">
                        <input type="checkbox" name="quitar_logo">
                        Quitar logo actual
                    </label>
                <?php endif; ?>

                <label>
                    Imagen o logo sobre el recuadro
                    <input
                        type="file"
                        name="logo"
                        accept="image/png,image/jpeg,image/webp,image/gif"
                    >
                </label>

                <small>
                    Al marcarlo ocupado, el recuadro queda bloqueado para el público y muestra la imagen cargada.
                </small>
                <button class="admin-btn">Guardar cambios</button>
            </form>
        </section>
    </main>
</body>
</html>
