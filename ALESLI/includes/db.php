<?php
function getDB(): PDO {
    $dir = __DIR__ . '/../db';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dsn = 'sqlite:' . $dir . '/alesli.sqlite';
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL');
    initSchema($pdo);
    return $pdo;
}

function initSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            rol TEXT NOT NULL DEFAULT 'cliente',
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS catalogo_arreglos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            descripcion TEXT,
            precio REAL NOT NULL DEFAULT 0,
            disponible INTEGER DEFAULT 1,
            imagen_url TEXT,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS pedidos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre_cliente TEXT NOT NULL,
            telefono TEXT NOT NULL,
            direccion_entrega TEXT NOT NULL,
            producto TEXT NOT NULL,
            fecha_entrega TEXT NOT NULL,
            hora_entrega TEXT,
            estado TEXT DEFAULT 'pendiente',
            mensaje_personal TEXT,
            medio_pago TEXT,
            foto_evidencia_url TEXT,
            notas TEXT,
            id_catalogo_arreglo INTEGER,
            fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_catalogo_arreglo) REFERENCES catalogo_arreglos(id)
        );
    ");
    seedData($pdo);
}

function seedData(PDO $pdo): void {
    $count = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    if ($count > 0) return;

    $usuarios = [
        ['Admin Alesli',  'admin@alesli.com',     'admin123',    'admin'],
        ['Florencia',     'florencia@alesli.com', 'empleado123', 'empleado'],
        ['María García',  'maria@gmail.com',      'cliente123',  'cliente'],
    ];
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)");
    foreach ($usuarios as $u) {
        $stmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3]]);
    }

    $catalogo = [
        ['Ramo Rojo Pasión',    'Rosas rojas premium con follaje verde, el clásico de siempre.',  8500,  'https://images.unsplash.com/photo-1548094990-c16ca90f1f0b?w=600&q=80'],
        ['Bouquet Primaveral',  'Mix de flores de temporada en tonos rosados y blancos.',          6800,  'https://images.unsplash.com/photo-1490750967868-88df5691cc5d?w=600&q=80'],
        ['Caja de Girasoles',   'Girasoles frescos en caja de madera, ideales para regalo.',       7200,  'https://images.unsplash.com/photo-1597848212624-a19eb35e2651?w=600&q=80'],
        ['Arreglo Romántico',   'Rosas blancas y rosas con detalles de lavanda y eucalipto.',     9500,  'https://images.unsplash.com/photo-1561181286-d3fee7d55364?w=600&q=80'],
        ['Centro de Mesa',      'Arreglo redondo con flores mixtas, perfecto para eventos.',       11000, 'https://images.unsplash.com/photo-1487530811015-780a41aa2be2?w=600&q=80'],
        ['Mini Ramo Especial',  'Pequeño bouquet de tulipanes, ideal para sorprender.',            4500,  'https://images.unsplash.com/photo-1508610048659-a06b669e3321?w=600&q=80'],
    ];
    $stmt = $pdo->prepare("INSERT INTO catalogo_arreglos (nombre, descripcion, precio, imagen_url) VALUES (?, ?, ?, ?)");
    foreach ($catalogo as $c) {
        $stmt->execute($c);
    }

    $hoy = date('Y-m-d');
    $manana = date('Y-m-d', strtotime('+1 day'));
    $pedidos = [
        ['María García',  '1155667788', 'Av. Corrientes 1234, CABA',   'Ramo Rojo Pasión',   $hoy,    '14:00', 'pendiente',  '¡Feliz cumpleaños!',  'Transferencia', 1],
        ['Carlos López',  '1144556677', 'Belgrano 567, CABA',           'Bouquet Primaveral', $hoy,    '16:00', 'pendiente',  'Con mucho cariño',    'Efectivo',      2],
        ['Ana Rodríguez', '1133445566', 'Palermo 890, CABA',            'Caja de Girasoles',  $hoy,    '11:00', 'entregado',  'Para mi mamá',        'MercadoPago',   3],
        ['Luis Martínez', '1122334455', 'Recoleta 321, CABA',           'Arreglo Romántico',  $manana, '10:00', 'pendiente',  'Aniversario 5 años',  'Tarjeta',       4],
        ['Sofia Herrera', '1111223344', 'San Telmo 654, CABA',          'Mini Ramo Especial', $manana, '15:30', 'pendiente',  null,                  'Efectivo',      6],
    ];
    $stmt = $pdo->prepare("INSERT INTO pedidos (nombre_cliente, telefono, direccion_entrega, producto, fecha_entrega, hora_entrega, estado, mensaje_personal, medio_pago, id_catalogo_arreglo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($pedidos as $p) {
        $stmt->execute($p);
    }
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
