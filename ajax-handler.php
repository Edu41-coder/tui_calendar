<?php
/**
 * Point d'entrée dédié aux requêtes AJAX
 */

// Charger la configuration de la base de données
require_once 'config/db_config.php';

// Charger l'initialisation
require_once 'config/init.php';

// Inclusions des modèles (à ajouter ou vérifier)
require_once 'models/Event.php';
require_once 'models/Calendar.php';
require_once 'models/Category.php';
require_once 'controllers/EventController.php';
require_once 'controllers/UserController.php';

// Vérifier si les classes sont correctement chargées
if (!class_exists('EventController')) {
    error_log('ERREUR CRITIQUE: La classe EventController n\'existe pas');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'config', 'message' => 'Configuration incorrecte']);
    exit;
}

// Démarrer la session
session_start();

// Récupérer l'action demandée
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Traiter les différentes actions
switch($action) {
    case 'get-events':
        $eventController = new EventController();
        $eventController->getEvents();
        break;
        
    case 'save-event':
        $eventController = new EventController();
        $eventController->save();
        break;
        
    case 'move-event':
        $eventController = new EventController();
        $eventController->move();
        break;
        
    case 'delete-event':
        $eventController = new EventController();
        $eventController->deleteAjax();
        break;
        
    default:
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Action non trouvée']);
        exit;
}
?>