<?php
declare(strict_types=1);
$config = [
    "driver" => getenv("DB_DRIVER") ?: "sqlite",
    "host" => getenv("DB_HOST") ?: "localhost",
    "port" => getenv("DB_PORT") ?: "3306",
    "database" => getenv("DB_NAME") ?: __DIR__ . "/../database/famex.sqlite",
    "username" => getenv("DB_USER") ?: "",
    "password" => getenv("DB_PASS") ?: "",
];
$localConfig = __DIR__ . "/../config.php";
if (is_file($localConfig) && is_array($custom = require $localConfig)) {
    $config = array_merge($config, $custom);
}
try {
    if ($config["driver"] === "mysql") {
        $conexion = new PDO(
            sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                $config["host"],
                $config["port"],
                $config["database"],
            ),
            $config["username"],
            $config["password"],
        );
    } else {
        $dir = dirname($config["database"]);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $conexion = new PDO("sqlite:" . $config["database"]);
    }
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    inicializarBaseDatos($conexion, $config["driver"]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage());
    exit("No fue posible conectar con la base de datos. Revise config.php.");
}
function columnaExiste(PDO $db, string $driver, string $tabla, string $columna): bool
{
    if ($driver === "sqlite") {
        $cols = array_column(
            $db->query("PRAGMA table_info($tabla)")->fetchAll(),
            "name",
        );
    } else {
        $cols = array_column(
            $db->query("SHOW COLUMNS FROM $tabla")->fetchAll(),
            "Field",
        );
    }

    return in_array($columna, $cols, true);
}

function asegurarNumeroFontSize(PDO $db, string $driver): bool
{
    try {
        if (columnaExiste($db, $driver, "stands", "numero_font_size")) {
            return true;
        }

        $def = $driver === "sqlite"
            ? "numero_font_size REAL"
            : "numero_font_size DECIMAL(10,3) NULL";
        $db->exec("ALTER TABLE stands ADD COLUMN $def");
        return true;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return false;
    }
}

function inicializarBaseDatos(PDO $db, string $driver): void
{
    $auto =
        $driver === "mysql"
            ? "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY"
            : "INTEGER PRIMARY KEY AUTOINCREMENT";
    $db->exec(
        "CREATE TABLE IF NOT EXISTS usuarios (id $auto,usuario VARCHAR(80) NOT NULL UNIQUE,password_hash VARCHAR(255) NOT NULL,rol VARCHAR(20) NOT NULL DEFAULT 'operador',activo INTEGER NOT NULL DEFAULT 1)",
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS pabellones (id $auto,clave VARCHAR(20) NOT NULL UNIQUE,nombre VARCHAR(120) NOT NULL,imagen VARCHAR(255) NULL,ancho INTEGER NOT NULL DEFAULT 1200,alto INTEGER NOT NULL DEFAULT 800,orden INTEGER NOT NULL DEFAULT 0,activo INTEGER NOT NULL DEFAULT 1,area_x DECIMAL(10,3) NULL,area_y DECIMAL(10,3) NULL,area_ancho DECIMAL(10,3) NULL,area_alto DECIMAL(10,3) NULL)",
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS configuracion (clave VARCHAR(80) PRIMARY KEY,valor TEXT NULL)",
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS stands (id $auto,pabellon_id INTEGER NULL,clave VARCHAR(40) NOT NULL UNIQUE,numero VARCHAR(40) NOT NULL,categoria VARCHAR(40) NOT NULL DEFAULT 'Premium',estado VARCHAR(20) NOT NULL DEFAULT 'Disponible',empresa VARCHAR(160) NULL,logo VARCHAR(255) NULL,contacto VARCHAR(160) NULL,email VARCHAR(160) NULL,telefono VARCHAR(60) NULL,x DECIMAL(10,3) NULL,y DECIMAL(10,3) NULL,ancho DECIMAL(10,3) NULL,alto DECIMAL(10,3) NULL,numero_font_size DECIMAL(10,3) NULL,bloqueado INTEGER NOT NULL DEFAULT 0)",
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS solicitudes_reserva (id $auto,stand VARCHAR(80) NOT NULL,empresa VARCHAR(180) NOT NULL,rfc VARCHAR(13) NOT NULL,lada VARCHAR(10) NOT NULL,telefono VARCHAR(60) NOT NULL,direccion TEXT NOT NULL,ciudad VARCHAR(120) NOT NULL,pais VARCHAR(120) NOT NULL,codigo_postal VARCHAR(30) NOT NULL,correo VARCHAR(180) NOT NULL,web VARCHAR(180) NULL,operacion VARCHAR(40) NOT NULL,stands_adicionales TEXT NULL,representante VARCHAR(160) NOT NULL,puesto VARCHAR(120) NOT NULL,lada_representante VARCHAR(10) NOT NULL,telefono_representante VARCHAR(60) NOT NULL,comentarios TEXT NULL,estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,actualizado_en DATETIME NULL)",
    );
    if ($driver === "sqlite") {
        $cols = array_column(
            $db->query("PRAGMA table_info(stands)")->fetchAll(),
            "name",
        );
        foreach (
            [
                "pabellon_id INTEGER",
                "contacto TEXT",
                "email TEXT",
                "telefono TEXT",
                "x REAL",
                "y REAL",
                "ancho REAL",
                "alto REAL",
                "bloqueado INTEGER NOT NULL DEFAULT 0",
            ]
            as $def
        ) {
            if (!in_array(strtok($def, " "), $cols, true)) {
                $db->exec("ALTER TABLE stands ADD COLUMN $def");
            }
        }
        asegurarNumeroFontSize($db, $driver);
        $pcols = array_column(
            $db->query("PRAGMA table_info(pabellones)")->fetchAll(),
            "name",
        );
        foreach (
            ["area_x REAL", "area_y REAL", "area_ancho REAL", "area_alto REAL"]
            as $def
        ) {
            if (!in_array(strtok($def, " "), $pcols, true)) {
                $db->exec("ALTER TABLE pabellones ADD COLUMN $def");
            }
        }
    } else {
        $scols = array_column(
            $db->query("SHOW COLUMNS FROM stands")->fetchAll(),
            "Field",
        );
        foreach (
            [
                "contacto VARCHAR(160)",
                "email VARCHAR(160)",
                "telefono VARCHAR(60)",
            ]
            as $def
        ) {
            if (!in_array(strtok($def, " "), $scols, true)) {
                $db->exec("ALTER TABLE stands ADD COLUMN $def");
            }
        }
        asegurarNumeroFontSize($db, $driver);
        $pcols = array_column(
            $db->query("SHOW COLUMNS FROM pabellones")->fetchAll(),
            "Field",
        );
        foreach (
            [
                "area_x DECIMAL(10,3)",
                "area_y DECIMAL(10,3)",
                "area_ancho DECIMAL(10,3)",
                "area_alto DECIMAL(10,3)",
            ]
            as $def
        ) {
            if (!in_array(strtok($def, " "), $pcols, true)) {
                $db->exec("ALTER TABLE pabellones ADD COLUMN $def");
            }
        }
    }
    if (
        (int) $db->query("SELECT COUNT(*) FROM pabellones")->fetchColumn() === 0
    ) {
        $s = $db->prepare(
            "INSERT INTO pabellones (clave,nombre,imagen,ancho,alto,orden) VALUES (?,?,?,?,?,?)",
        );
        foreach (
            [
                ["CH", "Chalets", null, 1200, 800, 0],
                ["A", "Pabellón A", "IMG/pabellon-a.png", 696, 530, 1],
                ["B", "Pabellón B", "IMG/pabellon-b.jpeg", 696, 530, 2],
                ["C", "Pabellón C", "IMG/pabellon-c.svg", 1200, 800, 3],
                ["D", "Pabellón D", "IMG/pabellon-d.svg", 1200, 800, 4],
            ]
            as $p
        ) {
            $s->execute($p);
        }
    }
    $db->exec("UPDATE pabellones SET nombre='Pabellón A' WHERE clave='A'");
    $db->exec("UPDATE pabellones SET nombre='Pabellón B' WHERE clave='B'");
    $db->exec("UPDATE pabellones SET nombre='Pabellón C' WHERE clave='C'");
    $db->exec("UPDATE pabellones SET nombre='Pabellón D' WHERE clave='D'");
    if ($driver === "mysql") {
        $db->exec(
            "INSERT IGNORE INTO configuracion (clave,valor) VALUES ('mapa_areas','IMG/Areas.png')",
        );
    } else {
        $db->exec(
            "INSERT OR IGNORE INTO configuracion (clave,valor) VALUES ('mapa_areas','IMG/Areas.png')",
        );
    }
    $zonas = [
        "CH" => [550, 315, 120, 60],
        "A" => [510, 400, 65, 70],
        "B" => [610, 405, 65, 70],
        "C" => [690, 410, 65, 70],
        "D" => [810, 445, 65, 70],
    ];
    $zu = $db->prepare(
        "UPDATE pabellones SET area_x=?,area_y=?,area_ancho=?,area_alto=? WHERE clave=? AND area_x IS NULL",
    );
    foreach ($zonas as $k => $z) {
        $zu->execute([$z[0], $z[1], $z[2], $z[3], $k]);
    }
    // Areas.png es el selector general del índice, no el plano de Chalets.
    $db->exec(
        "UPDATE pabellones SET imagen=NULL WHERE clave='CH' AND imagen='IMG/Areas.png'",
    );
    require_once __DIR__ . "/datos_referencia.php";
    $referenciaImportada = $db
        ->query(
            "SELECT valor FROM configuracion WHERE clave='selector_referencia_importado'",
        )
        ->fetchColumn();
    if ($referenciaImportada === false) {
        $cantidadStands = (int) $db
            ->query("SELECT COUNT(*) FROM stands")
            ->fetchColumn();
        if ($cantidadStands === 0) {
            importarSelectorReferencia($db);
        }
        $marcaSql =
            $driver === "mysql"
                ? "INSERT INTO configuracion (clave,valor) VALUES ('selector_referencia_importado','1') ON DUPLICATE KEY UPDATE valor='1'"
                : "INSERT OR REPLACE INTO configuracion (clave,valor) VALUES ('selector_referencia_importado','1')";
        $db->exec($marcaSql);
    }
    $db->exec(
        "UPDATE stands SET categoria='Sin categoria' WHERE pabellon_id=(SELECT id FROM pabellones WHERE clave='CH')",
    );
    $pabA = (int) $db
        ->query("SELECT id FROM pabellones WHERE clave='A'")
        ->fetchColumn();
    $db->prepare(
        "UPDATE stands SET pabellon_id=? WHERE pabellon_id IS NULL AND clave LIKE 'A%'",
    )->execute([$pabA]);
    if (
        (int) $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() === 0
    ) {
        $s = $db->prepare(
            "INSERT INTO usuarios (usuario,password_hash,rol) VALUES (?,?,?)",
        );
        $s->execute([
            "admin",
            password_hash("CambiarAdmin2027!", PASSWORD_DEFAULT),
            "admin",
        ]);
        $s->execute([
            "operador",
            password_hash("CambiarOperador2027!", PASSWORD_DEFAULT),
            "operador",
        ]);
    }
}
