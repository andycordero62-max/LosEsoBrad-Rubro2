╔══════════════════════════════════════════════════════════════════╗
║        FLORERÍA ALESLI — Sistema de Gestión Operativa          ║
║        Grupo: Los Eso Brad — UNIFRANZ 3er Semestre              ║
╚══════════════════════════════════════════════════════════════════╝

── INSTRUCCIONES PARA CORRER EL PROYECTO ──────────────────────────

  REQUISITOS PREVIOS
  ──────────────────
  ✔ XAMPP instalado (https://www.apachefriends.org/)
    → Apache + MySQL deben estar activos en el Panel de Control XAMPP
  ✔ Visual Studio Code (para editar el código)
  ✔ Navegador Chrome, Firefox o Edge

══════════════════════════════════════════════════════════════════════
  PASO 1 — COPIAR LA CARPETA A XAMPP
══════════════════════════════════════════════════════════════════════

  Copiá la carpeta ALESLI completa dentro de:

    Windows:  C:\xampp\htdocs\ALESLI\
    Mac/Linux: /Applications/XAMPP/htdocs/ALESLI/

  La estructura debe quedar así:
    htdocs/
    └── ALESLI/
        ├── index.php
        ├── dashboard.php
        ├── pedidos.php  ...etc
        ├── css/style.css
        ├── db/setup.sql
        └── includes/ (db.php, auth.php, layout.php)

══════════════════════════════════════════════════════════════════════
  PASO 2 — INICIAR XAMPP
══════════════════════════════════════════════════════════════════════

  1. Abrí el Panel de Control de XAMPP
  2. Hacé clic en "Start" junto a Apache
  3. Hacé clic en "Start" junto a MySQL
  4. Ambos deben aparecer en verde ✔

══════════════════════════════════════════════════════════════════════
  PASO 3 — INSTALAR LA BASE DE DATOS
══════════════════════════════════════════════════════════════════════

  OPCIÓN A — Instalador automático (más fácil):
    1. Abrí: http://localhost/ALESLI/setup.php
    2. Hacé clic en "Instalar Base de Datos"
    3. Esperá el mensaje de éxito ✅

  OPCIÓN B — phpMyAdmin (manual):
    1. Ingresá a: http://localhost/phpmyadmin
    2. Creá BD: floreria_alesli (utf8mb4_general_ci)
    3. Importá: ALESLI/db/setup.sql

══════════════════════════════════════════════════════════════════════
  PASO 4 — ABRIR EL SISTEMA
══════════════════════════════════════════════════════════════════════

  URL: http://localhost/ALESLI/

  CREDENCIALES:
  ┌──────────────────────────────────┬──────────────┬────────────┐
  │ Email                            │ Contraseña   │ Rol        │
  ├──────────────────────────────────┼──────────────┼────────────┤
  │ admin@alesli.com                 │ admin123     │ Admin      │
  │ florencia@alesli.com             │ empleado123  │ Empleado   │
  └──────────────────────────────────┴──────────────┴────────────┘

══════════════════════════════════════════════════════════════════════
  ABRIR CON VISUAL STUDIO CODE
══════════════════════════════════════════════════════════════════════

  1. VS Code → Archivo → Abrir Carpeta → htdocs/ALESLI/
  2. Extensión recomendada: "PHP Intelephense"
  NOTA: El sistema siempre corre desde XAMPP.
        Editás en VS Code, pero accedés por http://localhost/ALESLI/

══════════════════════════════════════════════════════════════════════
  MÓDULOS IMPLEMENTADOS
══════════════════════════════════════════════════════════════════════

  ✅ RF01 — Gestión de Pedidos (5 estados, historial, filtros)
  ✅ RF02 — Dashboard (KPIs en tiempo real, Chart.js)
  ✅ RF03 — Control de Inventario (stock, alertas, movimientos)
  ✅ RF04 — Contabilidad (ingresos/egresos, gráficos)
  ✅ RF05 — Clientes (CRUD, historial de pedidos)
  ✅ RF06 — Cursos (alumnos, inscripciones, cupo)
  ✅ RF07 — Catálogo de Arreglos (CRUD, precios)
  ✅ RNF04 — Autenticación por roles (admin/empleado)
  ✅ RNF03 — Diseño responsivo Bootstrap 5

══════════════════════════════════════════════════════════════════════
  PROBLEMAS COMUNES
══════════════════════════════════════════════════════════════════════

  ❌ "No se puede conectar a MySQL"
     → Verificá que MySQL esté verde en XAMPP

  ❌ "Access denied for user root"
     → Editá includes/db.php y poné tu contraseña en DB_PASS

  ❌ Página en blanco o error 500
     → Verificá en phpMyAdmin que la BD exista con todas las tablas

  ❌ CSS no carga / diseño roto
     → La carpeta debe llamarse exactamente "ALESLI" (mayúsculas)

══════════════════════════════════════════════════════════════════════
  CONFIGURACIÓN MYSQL
══════════════════════════════════════════════════════════════════════
  Host: localhost | Puerto: 3306
  Usuario: root | Password: (vacía por defecto en XAMPP)
  Base: floreria_alesli
  Cambiar en: includes/db.php

══════════════════════════════════════════════════════════════════════
  Proyecto UNIFRANZ — Grupo "Los Eso Brad" — 3er Semestre 2026
  Docente: Ing. Génesis Dannae Selaya Ticona
══════════════════════════════════════════════════════════════════════
