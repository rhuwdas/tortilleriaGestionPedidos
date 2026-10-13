<?php
/**
 * index.php — Punto de entrada principal del sistema.
 *
 * Inicia la sesión, carga el núcleo del sistema (Database, Router)
 * y despacha la solicitud entrante al módulo correspondiente.
 *
 * Ruta: /proyecto-tienda/index.php
 */

// Iniciar sesión de forma segura antes de cualquier salida
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar las clases del núcleo del sistema
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Router.php';

// Crear el enrutador y despachar la solicitud actual
$enrutador = new Router();
$enrutador->despachar();
