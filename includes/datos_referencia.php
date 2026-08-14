<?php
declare(strict_types=1);

function importarSelectorReferencia(PDO $db): void
{
    $planos = [
        "A" => [
            "IMG/selector-pabellon-a.png",
            1386,
            1052,
            ["IMG/pabellon-a.png"],
        ],
        "B" => [
            "IMG/selector-pabellon-b.png",
            818,
            1058,
            ["IMG/pabellon-b.jpeg", "IMG/pabellon-b.png"],
        ],
        "D" => [
            "IMG/selector-pabellon-d.png",
            908,
            1056,
            ["IMG/pabellon-d.svg"],
        ],
        "CH" => ["IMG/selector-chalets.png", 1600, 443, [null]],
    ];
    foreach ($planos as $clave => $plano) {
        $q = $db->prepare("SELECT id,imagen FROM pabellones WHERE clave=?");
        $q->execute([$clave]);
        $p = $q->fetch();
        if (!$p) {
            continue;
        }
        if (in_array($p["imagen"], $plano[3], true)) {
            $db->prepare(
                "UPDATE pabellones SET imagen=?,ancho=?,alto=? WHERE id=?",
            )->execute([$plano[0], $plano[1], $plano[2], $p["id"]]);
        }
    }

    $maps = [
        "A" => [
            1386,
            1052,
            [
                ["A-31", 42.6, 15.3, 7, 4.8],
                ["A-32", 49.6, 15.3, 7, 4.8],
                ["A-33", 59.8, 15.3, 7, 4.8],
                ["A-34", 66.8, 15.3, 7, 4.8],
                ["A-35", 76.9, 15.3, 7, 4.8],
                ["A-36", 83.9, 15.3, 7, 4.8],
                ["A-119", 42.8, 29.1, 8.1, 9.2],
                ["A-120", 50.9, 29.1, 7.9, 9.2],
                ["A-121", 58.8, 29.1, 8.1, 9.2],
                ["A-122", 70.2, 29.1, 7, 9.2],
                ["A-123", 77.2, 29.1, 7, 9.2],
                ["A-124", 84.2, 29.1, 7, 9.2],
                ["A-115", 42.7, 42.7, 10.3, 18.5],
                ["A-116", 56.4, 42.7, 8.2, 12.4],
                ["A-117", 68, 42.7, 10.3, 9.6],
                ["A-118", 81.7, 42.7, 9.1, 12.4],
                ["A-112", 56.4, 59.8, 8.2, 12.3],
                ["A-113", 68, 55.6, 10.3, 16.3],
                ["A-114", 81.7, 59.8, 9.1, 12.3],
                ["A-108", 8.5, 65.7, 8.1, 10.8],
                ["A-109", 20, 65.7, 9, 10.8],
                ["A-104", 32.5, 65.7, 20.5, 26],
                ["A-103", 56.4, 76.3, 9.2, 15.6],
                ["A-102", 68.9, 76.3, 9.3, 15.6],
                ["A-101", 81.7, 76.3, 9.1, 15.6],
            ],
        ],
        "B" => [
            818,
            1058,
            [
                ["B-46", 10.4, 13.4, 5.3, 4.2],
                ["B-47", 15.7, 13.4, 5.2, 4.2],
                ["B-48", 20.9, 13.4, 5.3, 4.2],
                ["B-49", 26.2, 13.4, 5.3, 4.2],
                ["B-50", 31.5, 13.4, 5.3, 4.2],
                ["B-56", 63.2, 13.4, 5.2, 4.2],
                ["B-57", 68.4, 13.4, 5.3, 4.2],
                ["B-58", 73.7, 13.4, 5.3, 4.2],
                ["B-59", 79, 13.4, 5.3, 4.2],
                ["B-60", 84.3, 13.4, 5.3, 4.2],
                ["B-124", 42.1, 13.5, 15.7, 8.1],
                ["B-123", 26.3, 25.8, 10.5, 9.4],
                ["B-122", 42.1, 25.8, 14, 9.4],
                ["B-121", 61.5, 25.8, 12.3, 9.4],
                ["B-120", 79.1, 25.8, 10.5, 9.4],
                ["B-119", 10.4, 39.3, 10.5, 9.6],
                ["B-118", 26.3, 39.3, 10.5, 9.6],
                ["B-117", 42.1, 39.3, 14, 9.6],
                ["B-116", 61.5, 39.3, 12.3, 9.6],
                ["B-115", 79.1, 39.3, 10.5, 9.6],
                ["B-114", 10.4, 53.1, 10.5, 9.6],
                ["B-113", 26.3, 53.1, 10.5, 9.6],
                ["B-112", 42.1, 53.1, 14, 9.6],
                ["B-111", 61.5, 53.1, 12.3, 9.6],
                ["B-110", 79.1, 53.1, 10.5, 9.6],
                ["B-104", 10.4, 80.7, 17.6, 13.3],
                ["B-103", 31.5, 80.7, 14, 13.3],
                ["B-102", 50.8, 80.7, 16.1, 13.3],
                ["B-101", 72.1, 80.7, 18.5, 13.3],
            ],
        ],
        "D" => [
            908,
            1056,
            [
                ["D-32", 13.8, 16.8, 30.8, 10.6],
                ["D-109", 13.8, 48.7, 12.4, 10.8],
                ["D-110", 32.3, 48.7, 16.5, 10.8],
                ["D-111", 54.8, 48.7, 14.4, 10.8],
                ["D-112", 75.3, 48.7, 12.3, 10.8],
                ["D-105", 13.8, 64.6, 14.5, 12.4],
                ["D-106", 34.3, 64.6, 14.4, 12.4],
                ["D-107", 54.9, 64.6, 14.4, 12.4],
                ["D-108", 75.3, 64.6, 12.3, 12.4],
                ["D-101", 13.8, 82.2, 14.5, 12.6],
                ["D-102", 34.3, 82.2, 14.4, 12.6],
                ["D-103", 54.9, 82.2, 14.4, 12.6],
                ["D-104", 75.3, 82.2, 12.3, 12.6],
            ],
        ],
        "CH" => [
            1600,
            443,
            array_map(
                fn($i) => [
                    "CH-" . str_pad((string) $i, 2, "0", STR_PAD_LEFT),
                    56.7 +
                    ($i - 1 < 14
                        ? ($i - 1) * 2.05
                        : ($i - 1 - 14) * 2.08 + 35.4),
                    38.4,
                    1.8,
                    5.4,
                ],
                range(1, 18),
            ),
        ],
    ];
    $findP = $db->prepare("SELECT id FROM pabellones WHERE clave=?");
    $findS = $db->prepare("SELECT id,x FROM stands WHERE clave=?");
    $update = $db->prepare(
        "UPDATE stands SET pabellon_id=?,numero=?,x=?,y=?,ancho=?,alto=? WHERE id=? AND x IS NULL",
    );
    $insert = $db->prepare(
        "INSERT INTO stands (pabellon_id,clave,numero,categoria,estado,x,y,ancho,alto,bloqueado) VALUES (?,?,?,'Premium','Disponible',?,?,?,?,0)",
    );
    foreach ($maps as $clave => $map) {
        $findP->execute([$clave]);
        $pid = (int) $findP->fetchColumn();
        if (!$pid) {
            continue;
        }
        [$w, $h, $spaces] = $map;
        foreach ($spaces as $space) {
            [$numero, $x, $y, $sw, $sh] = $space;
            $dbClave = strtoupper(preg_replace("/[^A-Z0-9]/", "", $numero));
            $px = round(($x * $w) / 100, 3);
            $py = round(($y * $h) / 100, 3);
            $pw = round(($sw * $w) / 100, 3);
            $ph = round(($sh * $h) / 100, 3);
            $findS->execute([$dbClave]);
            $existing = $findS->fetch();
            if ($existing) {
                $update->execute([
                    $pid,
                    $numero,
                    $px,
                    $py,
                    $pw,
                    $ph,
                    $existing["id"],
                ]);
            } else {
                $insert->execute([$pid, $dbClave, $numero, $px, $py, $pw, $ph]);
            }
        }
    }
}
