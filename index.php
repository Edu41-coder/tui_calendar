<?php
/**
 * Point d'entrée principal de l'application tui_calendar
 */

// Charger la configuration de la base de données
require_once 'config/db_config.php';

// Charger l'initialisation
require_once 'config/init.php';

// Démarrer la session
session_start();

// Dispatcher la route vers le contrôleur approprié
Routes::dispatch();
?>