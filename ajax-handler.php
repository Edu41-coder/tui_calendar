<?php
/**
 * Point d'entrée dédié aux requêtes AJAX
 */

// TEMPORAIRE: Afficher toutes les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le fuseau horaire par défaut
date_default_timezone_set('Europe/Paris'); // Ou votre fuseau horaire local

/**
 * Convertit une date JavaScript en format MySQL
 * @param string $dateString Chaîne de date au format JavaScript ISO8601
 * @return string Date formatée pour MySQL
 */
function formatJavaScriptDateForMySQL($dateString) {
    // Si la chaîne est au format ISO 8601 avec timezone
    if (preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $dateString)) {
        // Créer un objet DateTime à partir de la chaîne ISO
        $date = new DateTime($dateString);
        
        // Formater pour MySQL sans convertir le fuseau horaire
        return $date->format('Y-m-d H:i:s');
    } else {
        // Pour les formats non-ISO, utiliser strtotime
        $timestamp = strtotime($dateString);
        return date('Y-m-d H:i:s', $timestamp);
    }
}

/**
 * Convertit une date MySQL en format ISO pour JavaScript
 * @param string $mySqlDate Date au format MySQL
 * @return string Date au format ISO 8601
 */
function formatMySQLDateForJavaScript($mySqlDate) {
    // Créer un objet DateTime avec le fuseau horaire déjà configuré (Europe/Paris)
    $date = new DateTime($mySqlDate);
    
    // Renvoyer au format ISO8601 complet avec le timestamp local
    return $date->format('Y-m-d\TH:i:s');
}

/**
 * Envoie une réponse JSON et termine l'exécution
 * @param array $data Données à envoyer
 * @param int $statusCode Code HTTP de statut
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Formate un événement pour le frontend
 * @param array $event Données de l'événement
 * @param object $calendarModel Instance du modèle Calendar
 * @param object $categoryModel Instance du modèle Category
 * @return array Événement formaté
 */
function formatEventForFrontend($event, $calendarModel, $categoryModel) {
    // Récupérer les données du calendrier
    $calendar = $calendarModel->getById($event['calendar_id']);
    $calendarColor = $calendar ? $calendar['color'] : '#2c3e50';
    
    // Récupérer les données de la catégorie
    $category = $categoryModel->getById($event['category_id']);
    $categoryColor = $category ? $category['bg_color'] : '#34495e';
    $categoryTextColor = $category ? $category['color'] : '#ffffff';
    
    // Formater les dates avec la bonne gestion du fuseau horaire
    $startDate = formatMySQLDateForJavaScript($event['start_date']);
    $endDate = formatMySQLDateForJavaScript($event['end_date']);
    
    // Formater l'événement pour le frontend
    return [
        'id' => $event['event_id'],
        'calendarId' => $event['calendar_id'],
        'title' => $event['title'],
        'body' => $event['description'],
        'location' => $event['location'],
        'start' => $startDate,
        'end' => $endDate,
        'isAllDay' => (bool) $event['is_all_day'],
        'calendarColor' => $calendarColor,
        'categoryColor' => $categoryColor,
        'categoryTextColor' => $categoryTextColor,
        'categoryId' => $event['category_id'],
        'raw' => [
            'calendarColor' => $calendarColor,
            'categoryColor' => $categoryColor,
            'categoryTextColor' => $categoryTextColor,
            'categoryId' => $event['category_id']
        ]
    ];
}

// Logger les informations de la requête
error_log('AJAX Request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);
error_log('Action: ' . ($_GET['action'] ?? 'none'));

// Pour les requêtes POST, logger le contenu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST Data: ' . file_get_contents('php://input'));
}

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
require_once 'controllers/CalendarController.php';

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

    case 'get-event':
        $eventController = new EventController();
        $eventController->getEvent();
        break;  
                

    case 'toggle-calendar-visibility':
        $calendarController = new CalendarController();
        $calendarController->toggleCalendarVisibility();
        break;
        
    default:
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'Action non trouvée']);
        exit;
}
?>