|<?php
require __DIR__ . "/includes/conexion.php";

$stand = trim($_GET["stand"] ?? "");
$pabellones = $conexion
    ->query("SELECT id,nombre FROM pabellones WHERE activo=1 ORDER BY orden,nombre")
    ->fetchAll();
$stands = $conexion
    ->query(
        "SELECT s.id,s.numero,s.pabellon_id,p.nombre pabellon
         FROM stands s
         LEFT JOIN pabellones p ON p.id=s.pabellon_id
         WHERE s.estado='Disponible' AND s.bloqueado=0
         ORDER BY p.orden,p.nombre,s.numero",
    )
    ->fetchAll();
$paises = [
    "Afganistán",
    "Albania",
    "Alemania",
    "Andorra",
    "Angola",
    "Arabia Saudita",
    "Argelia",
    "Argentina",
    "Armenia",
    "Australia",
    "Austria",
    "Bélgica",
    "Bolivia",
    "Brasil",
    "Canadá",
    "Chile",
    "China",
    "Colombia",
    "Corea del Sur",
    "Costa Rica",
    "Cuba",
    "Dinamarca",
    "Ecuador",
    "Egipto",
    "El Salvador",
    "Emiratos Árabes Unidos",
    "España",
    "Estados Unidos",
    "Finlandia",
    "Francia",
    "Guatemala",
    "Honduras",
    "India",
    "Indonesia",
    "Irlanda",
    "Israel",
    "Italia",
    "Japón",
    "México",
    "Noruega",
    "Nueva Zelanda",
    "Países Bajos",
    "Panamá",
    "Paraguay",
    "Perú",
    "Polonia",
    "Portugal",
    "Puerto Rico",
    "República Dominicana",
    "Reino Unido",
    "Rusia",
    "Singapur",
    "Sudáfrica",
    "Suecia",
    "Suiza",
    "Turquía",
    "Ucrania",
    "Uruguay",
    "Venezuela",
    "Vietnam",
];
$ladas = [
    ["+1", "EE.UU. / Canadá"],
    ["+7", "Rusia, Kazajistán"],
    ["+20", "Egipto"],
    ["+27", "Sudáfrica"],
    ["+30", "Grecia"],
    ["+31", "Países Bajos"],
    ["+32", "Bélgica"],
    ["+33", "Francia"],
    ["+34", "España"],
    ["+36", "Hungría"],
    ["+39", "Italia"],
    ["+40", "Rumanía"],
    ["+41", "Suiza"],
    ["+44", "Reino Unido"],
    ["+45", "Dinamarca"],
    ["+46", "Suecia"],
    ["+47", "Noruega"],
    ["+48", "Polonia"],
    ["+49", "Alemania"],
    ["+52", "México"],
    ["+54", "Argentina"],
    ["+55", "Brasil"],
    ["+56", "Chile"],
    ["+57", "Colombia"],
    ["+58", "Venezuela"],
    ["+60", "Malasia"],
    ["+61", "Australia"],
    ["+62", "Indonesia"],
    ["+63", "Filipinas"],
    ["+64", "Nueva Zelanda"],
    ["+65", "Singapur"],
    ["+66", "Tailandia"],
    ["+81", "Japón"],
    ["+82", "Corea del Sur"],
    ["+84", "Vietnam"],
    ["+86", "China"],
    ["+90", "Turquía"],
    ["+91", "India"],
    ["+92", "Pakistán"],
    ["+93", "Afganistán"],
    ["+94", "Sri Lanka"],
    ["+95", "Myanmar"],
    ["+98", "Irán"],
    ["+211", "Sudán del Sur"],
    ["+212", "Marruecos"],
    ["+213", "Argelia"],
    ["+216", "Túnez"],
    ["+218", "Libia"],
    ["+220", "Gambia"],
    ["+221", "Senegal"],
    ["+222", "Mauritania"],
    ["+223", "Mali"],
    ["+224", "Guinea"],
    ["+225", "Costa de Marfil"],
    ["+226", "Burkina Faso"],
    ["+227", "Níger"],
    ["+228", "Togo"],
    ["+229", "Benín"],
    ["+230", "Mauricio"],
    ["+231", "Liberia"],
    ["+232", "Sierra Leona"],
    ["+233", "Ghana"],
    ["+234", "Nigeria"],
    ["+235", "Chad"],
    ["+236", "República Centroafricana"],
    ["+237", "Camerún"],
    ["+238", "Cabo Verde"],
    ["+239", "Santo Tomé y Príncipe"],
    ["+240", "Guinea Ecuatorial"],
    ["+241", "Gabón"],
    ["+242", "República del Congo"],
    ["+244", "Angola"],
    ["+245", "Guinea-Bisáu"],
    ["+246", "Territorio Británico del Océano Índico"],
    ["+248", "Seychelles"],
    ["+249", "Sudán"],
    ["+250", "Ruanda"],
    ["+251", "Etiopía"],
    ["+252", "Somalia"],
    ["+253", "Yibuti"],
    ["+254", "Kenia"],
    ["+255", "Tanzania"],
    ["+256", "Uganda"],
    ["+257", "Burundi"],
    ["+258", "Mozambique"],
    ["+260", "Zambia"],
    ["+261", "Madagascar"],
    ["+262", "Reunión"],
    ["+263", "Zimbabue"],
    ["+264", "Namibia"],
    ["+265", "Malaui"],
    ["+266", "Lesoto"],
    ["+267", "Botsuana"],
    ["+268", "Suazilandia"],
    ["+269", "Comoras"],
    ["+290", "Santa Elena"],
    ["+291", "Eritrea"],
    ["+297", "Aruba"],
    ["+298", "Islas Feroe"],
    ["+299", "Groenlandia"],
    ["+350", "Gibraltar"],
    ["+351", "Portugal"],
    ["+352", "Luxemburgo"],
    ["+353", "Irlanda"],
    ["+354", "Islandia"],
    ["+355", "Albania"],
    ["+356", "Malta"],
    ["+357", "Chipre"],
    ["+358", "Finlandia"],
    ["+359", "Bulgaria"],
    ["+370", "Lituania"],
    ["+371", "Letonia"],
    ["+372", "Estonia"],
    ["+373", "Moldavia"],
    ["+374", "Armenia"],
    ["+375", "Bielorrusia"],
    ["+376", "Andorra"],
    ["+377", "Mónaco"],
    ["+378", "San Marino"],
    ["+380", "Ucrania"],
    ["+381", "Serbia"],
    ["+382", "Montenegro"],
    ["+383", "Kosovo"],
    ["+385", "Croacia"],
    ["+386", "Eslovenia"],
    ["+387", "Bosnia y Herzegovina"],
    ["+389", "Macedonia del Norte"],
    ["+420", "Chequia"],
    ["+421", "Eslovaquia"],
    ["+423", "Liechtenstein"],
    ["+500", "Islas Malvinas"],
    ["+501", "Belice"],
    ["+502", "Guatemala"],
    ["+503", "El Salvador"],
    ["+504", "Honduras"],
    ["+505", "Nicaragua"],
    ["+506", "Costa Rica"],
    ["+507", "Panamá"],
    ["+508", "San Pedro y Miquelón"],
    ["+509", "Haití"],
    ["+590", "Guadalupe"],
    ["+591", "Bolivia"],
    ["+592", "Guyana"],
    ["+593", "Ecuador"],
    ["+594", "Guayana Francesa"],
    ["+595", "Paraguay"],
    ["+596", "Martinica"],
    ["+597", "Surinam"],
    ["+598", "Uruguay"],
    ["+599", "Antillas Neerlandesas"],
    ["+670", "Timor Oriental"],
    ["+672", "Islas Norfolk"],
    ["+673", "Brunéi"],
    ["+674", "Nauru"],
    ["+675", "Papúa Nueva Guinea"],
    ["+676", "Tonga"],
    ["+677", "Islas Salomón"],
    ["+678", "Vanuatu"],
    ["+679", "Fiyi"],
    ["+680", "Palaos"],
    ["+681", "Wallis y Futuna"],
    ["+682", "Islas Cook"],
    ["+683", "Niue"],
    ["+685", "Samoa"],
    ["+686", "Kiribati"],
    ["+687", "Nueva Caledonia"],
    ["+688", "Tuvalu"],
    ["+689", "Polinesia Francesa"],
    ["+690", "Tokelau"],
    ["+691", "Micronesia"],
    ["+692", "Islas Marshall"],

];
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rfc = strtoupper(trim($_POST["rfc"] ?? ""));
    $correo = trim($_POST["correo"] ?? "");
    $operacion = trim($_POST["operacion"] ?? "");
    $operacionesValidas = ["comprar", "solicitar_informe", "prepago"];

    if (!preg_match("/^[A-Z0-9]{13}$/", $rfc)) {
        $mensaje = "El RFC debe tener 13 caracteres con letras y números.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Escribe un correo electrónico válido.";
    } elseif (!in_array($operacion, $operacionesValidas, true)) {
        $mensaje = "Selecciona una operación válida.";
    } else {
        $extras = [];
        if (($_POST["stands_adicionales"] ?? "no") === "si") {
            $pabellonesExtra = $_POST["pabellon_adicional"] ?? [];
            $numerosExtra = $_POST["numero_adicional"] ?? [];

            foreach ($numerosExtra as $index => $numeroExtra) {
                $pabellonId = (int) ($pabellonesExtra[$index] ?? 0);
                $numeroExtra = trim((string) $numeroExtra);

                if ($pabellonId && $numeroExtra !== "") {
                    $nombrePabellon = "";
                    foreach ($pabellones as $pabellon) {
                        if ((int) $pabellon["id"] === $pabellonId) {
                            $nombrePabellon = $pabellon["nombre"];
                            break;
                        }
                    }
                    $extras[] = [
                        "pabellon_id" => $pabellonId,
                        "pabellon" => $nombrePabellon,
                        "numero" => $numeroExtra,
                    ];
                }
            }
        }

        $guardar = $conexion->prepare(
            "INSERT INTO solicitudes_reserva (stand,empresa,rfc,lada,telefono,direccion,ciudad,pais,codigo_postal,correo,web,operacion,stands_adicionales,representante,puesto,lada_representante,telefono_representante,comentarios) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        );
        $guardar->execute([
            trim($_POST["stand"] ?? ""),
            trim($_POST["empresa"] ?? ""),
            $rfc,
            trim($_POST["lada"] ?? ""),
            trim($_POST["telefono"] ?? ""),
            trim($_POST["direccion"] ?? ""),
            trim($_POST["ciudad"] ?? ""),
            trim($_POST["pais"] ?? ""),
            trim($_POST["codigo_postal"] ?? ""),
            $correo,
            trim($_POST["web"] ?? ""),
            $operacion,
            json_encode($extras, JSON_UNESCAPED_UNICODE),
            trim($_POST["representante"] ?? ""),
            trim($_POST["puesto"] ?? ""),
            trim($_POST["lada_representante"] ?? ""),
            trim($_POST["telefono_representante"] ?? ""),
            trim($_POST["comentarios"] ?? ""),
        ]);
        $mensaje = "Solicitud enviada correctamente. Un operador revisará la información.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Formulario de reserva | FAMEX</title>
    <link rel="stylesheet" href="CSS/reserva.css">
</head>
<body>
    <main class="reservation-page">
        <form class="reservation-form" method="post">
            <div class="form-header">
                <img src="IMG/Logo_FAMEX-2027.png" alt="FAMEX 2027" href="https://f-airmexico.com.mx/es">
                <div>
                    <span>Formulario</span>
                    <h1>Reserva de stand</h1>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <p class="<?= str_contains($mensaje, "correctamente") ? "form-success" : "form-error" ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </p>
            <?php endif; ?>

            <label class="stand-number-field">
                Número del Stand
                <input name="stand" value="<?= htmlspecialchars($stand) ?>" readonly>
            </label>

            <section class="form-grid">
                <label>Nombre de la empresa o institución:<input name="empresa" required></label>
                <label>RFC<input name="rfc" maxlength="13" minlength="13" pattern="[A-Za-z0-9]{13}" title="El RFC debe tener 13 caracteres: letras y números." required></label>
                <label class="phone-field">Número de teléfono
                    <span>
                        <select name="lada" required>
                            <?php foreach ($ladas as [$clave, $pais]): ?>
                                <option value="<?= htmlspecialchars($clave) ?>"><?= htmlspecialchars($pais . " " . $clave) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="telefono" inputmode="tel" autocomplete="tel-national" pattern="[0-9\s\-()]{7,20}" required>
                    </span>
                </label>
                <label>Dirección de la compañía o institución:<input name="direccion" required></label>
                <label>Ciudad<input name="ciudad" required></label>
                <label>País
                    <input name="pais" list="paises" required>
                    <datalist id="paises">
                        <?php foreach ($paises as $pais): ?>
                            <option value="<?= htmlspecialchars($pais) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label>Código postal<input name="codigo_postal" required></label>
                <label>Correo electrónico de la empresa o institución<input type="email" name="correo" pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" title="Incluye un correo válido con arroba (@)." required></label>
                <label>Página web<input type="url" name="web" placeholder="https://"></label>
                <label>Operación
                    <select name="operacion" required>
                        <option value="">Selecciona una operación</option>
                        <option value="comprar">Comprar</option>
                        <option value="solicitar_informe">Solicitar informe</option>
                        <option value="prepago">Prepago</option>
                    </select>
                </label>
            </section>

            <fieldset class="extra-stands">
                <legend>¿Deseas agregar stands adicionales?</legend>
                <label class="check-row"><input type="radio" name="stands_adicionales" value="no" checked> No</label>
                <label class="check-row"><input type="radio" name="stands_adicionales" value="si"> Sí</label>
                <div id="additionalStandBlock" class="additional-stand-block" hidden>
                    <div id="additionalStandRows" class="additional-stand-rows"></div>
                    <button type="button" id="addStandRow" class="secondary-action">Agregar otro stand</button>
                </div>
            </fieldset>

            <template id="additionalStandTemplate">
                <div class="additional-stand-row">
                    <label>Filtrar pabellón
                        <select name="pabellon_adicional[]">
                            <option value="">Selecciona un pabellón</option>
                            <?php foreach ($pabellones as $pabellon): ?>
                                <option value="<?= (int) $pabellon["id"] ?>"><?= htmlspecialchars($pabellon["nombre"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Número de stand
                        <select name="numero_adicional[]">
                            <option value="">Selecciona un número</option>
                            <?php foreach ($stands as $extraStand): ?>
                                <option value="<?= htmlspecialchars($extraStand["numero"]) ?>" data-pavilion="<?= (int) $extraStand["pabellon_id"] ?>">
                                    <?= htmlspecialchars(($extraStand["pabellon"] ? $extraStand["pabellon"] . " - " : "") . $extraStand["numero"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="button" class="remove-stand-row" aria-label="Quitar stand adicional">Quitar</button>
                </div>
            </template>

            <section class="form-grid">
                <label>Nombre del representante:<input name="representante" required></label>
                <label>Puesto:<input name="puesto" required></label>
                <label class="phone-field">Número telefónico:
                    <span>
                        <select name="lada_representante" required>
                            <?php foreach ($ladas as [$clave, $pais]): ?>
                                <option value="<?= htmlspecialchars($clave) ?>"><?= htmlspecialchars($pais . " " . $clave) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="telefono_representante" inputmode="tel" autocomplete="tel-national" pattern="[0-9\s\-()]{7,20}" required>
                    </span>
                </label>
                <label class="full-field">Comentarios o anotaciones<textarea name="comentarios" rows="5"></textarea></label>
            </section>

            <div class="form-actions">
                <button type="submit">Enviar solicitud</button>
            </div>
        </form>
    </main>
    <script src="JS/reserva.js"></script>
</body>
</html>
