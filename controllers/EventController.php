<?php
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Calendar.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../controllers/UserController.php';

/**
 * Contrôleur pour gérer les événements du calendrier
 */
class EventController {
    private $eventModel;
    private $calendarModel;
    private $categoryModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->eventModel = new Event();
        $this->calendarModel = new Calendar();
        $this->categoryModel = new Category();
    }
    
    /**
     * Retourne une réponse JSON avec le code d'état HTTP approprié
     */
    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Afficher le formulaire de création d'un événement
     */
    public function create() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $error = '';
        $success = '';
        
        // Récupérer les calendriers de l'utilisateur
        $calendars = $this->calendarModel->getByUserId($user_id);
        
        // Récupérer les catégories disponibles
        $categories = $this->categoryModel->getAll();
        
        // Valeurs par défaut pour le formulaire
        $event = [
            'calendar_id' => isset($_GET['calendar_id']) ? $_GET['calendar_id'] : 
                (!empty($calendars) ? $calendars[0]['calendar_id'] : ''),
            'category_id' => '',
            'title' => '',
            'body' => '',
            'start_date' => date('Y-m-d H:i'),
            'end_date' => date('Y-m-d H:i', strtotime('+1 hour')),
            'is_all_day' => 0,
            'location' => '',
            'is_recurring' => 0,
            'recurrence_rule' => ''
        ];
        
        // Traitement du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupérer les données du formulaire
            $calendar_id = isset($_POST['calendar_id']) ? $_POST['calendar_id'] : '';
            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $body = isset($_POST['body']) ? trim($_POST['body']) : '';
            $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
            $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
            $is_all_day = isset($_POST['is_all_day']) ? 1 : 0;
            $location = isset($_POST['location']) ? trim($_POST['location']) : '';
            $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
            $recurrence_rule = isset($_POST['recurrence_rule']) ? trim($_POST['recurrence_rule']) : '';
            
            // Valider le calendrier
            $calendar = $this->calendarModel->getById($calendar_id);
            if (!$calendar || $calendar['user_id'] != $user_id) {
                $error = 'Calendrier invalide';
            }
            // Valider le titre
            else if (empty($title)) {
                $error = 'Le titre de l\'événement est requis';
            }
            // Valider les dates
            else if (empty($start_date) || empty($end_date)) {
                $error = 'Les dates de début et de fin sont requises';
            }
            else {
                // Si c'est un événement sur toute la journée, ajuster les heures
                if ($is_all_day) {
                    // Extraire la date sans l'heure
                    $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
                    $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
                }
                
                // Créer l'événement
                $eventData = [
                    'calendar_id' => $calendar_id,
                    'category_id' => $category_id,
                    'title' => $title,
                    'body' => $body,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'is_all_day' => $is_all_day,
                    'location' => $location,
                    'is_recurring' => $is_recurring,
                    'recurrence_rule' => $recurrence_rule
                ];
                
                $result = $this->eventModel->create($eventData);
                
                if ($result) {
                    $success = 'Événement créé avec succès';
                    
                    // Redirection après 1 seconde
                    header("Refresh:1; url=" . Routes::url('calendar', 'index'));
                } else {
                    $error = 'Erreur lors de la création de l\'événement';
                }
            }
            
            // En cas d'erreur, conserver les valeurs du formulaire
            if (!empty($error)) {
                $event = [
                    'calendar_id' => $calendar_id,
                    'category_id' => $category_id,
                    'title' => $title,
                    'body' => $body,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'is_all_day' => $is_all_day,
                    'location' => $location,
                    'is_recurring' => $is_recurring,
                    'recurrence_rule' => $recurrence_rule
                ];
            }
        }
        
        // Afficher le formulaire
        include __DIR__ . '/../views/event/create.php';
    }
    
    /**
     * Afficher le formulaire de modification d'un événement
     * 
     * @param int $id ID de l'événement
     */
    public function edit($id = null) {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        // Si l'ID n'est pas spécifié dans l'URL, essayer de le récupérer depuis POST
        if ($id === null && isset($_POST['event_id'])) {
            $id = (int)$_POST['event_id'];
        }
        
        if (!$id) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $error = '';
        $success = '';
        
        // Récupérer l'événement
        $event = $this->eventModel->getById($id);
        
        if (!$event) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        // Récupérer le calendrier pour vérifier les droits
        $calendar = $this->calendarModel->getById($event['calendar_id']);
        
        // Vérifier que l'utilisateur a le droit de modifier cet événement
        if (!$calendar || $calendar['user_id'] != $user_id) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        // Récupérer les calendriers de l'utilisateur
        $calendars = $this->calendarModel->getByUserId($user_id);
        
        // Récupérer les catégories disponibles
        $categories = $this->categoryModel->getAll();
        
        // Traitement du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupérer les données du formulaire
            $calendar_id = isset($_POST['calendar_id']) ? $_POST['calendar_id'] : '';
            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $body = isset($_POST['body']) ? trim($_POST['body']) : '';
            $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
            $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
            $is_all_day = isset($_POST['is_all_day']) ? 1 : 0;
            $location = isset($_POST['location']) ? trim($_POST['location']) : '';
            $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
            $recurrence_rule = isset($_POST['recurrence_rule']) ? trim($_POST['recurrence_rule']) : '';
            
            // Valider le calendrier
            $calendar = $this->calendarModel->getById($calendar_id);
            if (!$calendar || $calendar['user_id'] != $user_id) {
                $error = 'Calendrier invalide';
            }
            // Valider le titre
            else if (empty($title)) {
                $error = 'Le titre de l\'événement est requis';
            }
            // Valider les dates
            else if (empty($start_date) || empty($end_date)) {
                $error = 'Les dates de début et de fin sont requises';
            }
            else {
                // Si c'est un événement sur toute la journée, ajuster les heures
                if ($is_all_day) {
                    // Extraire la date sans l'heure
                    $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
                    $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
                }
                
                // Mettre à jour l'événement
                $eventData = [
                    'calendar_id' => $calendar_id,
                    'category_id' => $category_id,
                    'title' => $title,
                    'body' => $body,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'is_all_day' => $is_all_day,
                    'location' => $location,
                    'is_recurring' => $is_recurring,
                    'recurrence_rule' => $recurrence_rule
                ];
                
                $result = $this->eventModel->update($id, $eventData);
                
                if ($result) {
                    $success = 'Événement mis à jour avec succès';
                    // Récupérer les données mises à jour
                    $event = $this->eventModel->getById($id);
                } else {
                    $error = 'Erreur lors de la mise à jour de l\'événement';
                }
            }
        }
        
        // Afficher le formulaire
        include __DIR__ . '/../views/event/edit.php';
    }
    
    /**
     * Supprimer un événement
     * 
     * @param int $id ID de l'événement
     */
    public function delete($id = null) {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        // Si l'ID n'est pas spécifié dans l'URL, essayer de le récupérer depuis POST
        if ($id === null && isset($_POST['event_id'])) {
            $id = (int)$_POST['event_id'];
        }
        
        if (!$id) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Récupérer l'événement
        $event = $this->eventModel->getById($id);
        
        if (!$event) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        // Récupérer le calendrier pour vérifier les droits
        $calendar = $this->calendarModel->getById($event['calendar_id']);
        
        // Vérifier que l'utilisateur a le droit de supprimer cet événement
        if (!$calendar || $calendar['user_id'] != $user_id) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            $result = $this->eventModel->delete($id);
            
            if ($result) {
                $_SESSION['success_message'] = 'Événement supprimé avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la suppression de l\'événement';
            }
            
            // Rediriger vers le calendrier
            Routes::redirect('calendar', 'index');
            return;
        }
        
        // Afficher la confirmation de suppression
        include __DIR__ . '/../views/event/delete.php';
    }
    
    /**
     * Afficher les détails d'un événement
     * 
     * @param int $id ID de l'événement
     */
    public function view($id = null) {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        if (!$id) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Récupérer l'événement avec les détails
        $events = $this->eventModel->getEventsWithDetails();
        $event = null;
        
        foreach ($events as $e) {
            if ($e['event_id'] == $id) {
                $event = $e;
                break;
            }
        }
        
        if (!$event) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        // Récupérer le calendrier pour vérifier les droits
        $calendar = $this->calendarModel->getById($event['calendar_id']);
        
        // Vérifier que l'utilisateur a le droit de voir cet événement
        if (!$calendar || $calendar['user_id'] != $user_id) {
            Routes::redirect('calendar', 'index');
            return;
        }
        
        // Afficher les détails de l'événement
        include __DIR__ . '/../views/event/view.php';
    }
    
    /**
     * API : Créer ou mettre à jour un événement via AJAX
     */
    public function save() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Vérifier si c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        // Récupérer les données JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Vérifier les données requises
        if (!isset($data['title']) || !isset($data['calendarId']) || !isset($data['start']) || !isset($data['end'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
            exit;
        }
        
        // Vérifier le calendrier
        $calendar = $this->calendarModel->getById($data['calendarId']);
        if (!$calendar || $calendar['user_id'] != $user_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Calendrier invalide']);
            exit;
        }
        
        // Préparer les données de l'événement
        $eventData = [
            'calendar_id' => $data['calendarId'],
            'category_id' => isset($data['categoryId']) ? $data['categoryId'] : null,
            'title' => $data['title'],
            'body' => isset($data['body']) ? $data['body'] : '',
            'start_date' => $data['start'],
            'end_date' => $data['end'],
            'is_all_day' => isset($data['isAllDay']) ? ($data['isAllDay'] ? 1 : 0) : 0,
            'location' => isset($data['location']) ? $data['location'] : '',
            'is_recurring' => isset($data['isRecurring']) ? ($data['isRecurring'] ? 1 : 0) : 0,
            'recurrence_rule' => isset($data['recurrenceRule']) ? $data['recurrenceRule'] : ''
        ];
        
        // Créer ou mettre à jour l'événement
        if (isset($data['id']) && !empty($data['id'])) {
            // Mettre à jour un événement existant
            $event = $this->eventModel->getById($data['id']);
            
            // Vérifier que l'événement existe et appartient à un calendrier de l'utilisateur
            if (!$event) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
                exit;
            }
            
            $eventCalendar = $this->calendarModel->getById($event['calendar_id']);
            if (!$eventCalendar || $eventCalendar['user_id'] != $user_id) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cet événement']);
                exit;
            }
            
            $result = $this->eventModel->update($data['id'], $eventData);
            $eventId = $data['id'];
        } else {
            // Créer un nouvel événement
            $result = $this->eventModel->create($eventData);
            $eventId = $result;
        }
        
        // Envoyer la réponse
        if ($result) {
            $updatedEvent = $this->eventModel->getById($eventId);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Événement enregistré avec succès',
                'event' => $updatedEvent,
                'id' => $eventId
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de l\'événement']);
        }
        exit;
    }
    
    /**
     * API : Déplacer un événement via AJAX (drag & drop)
     */
    public function move() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Vérifier si c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        // Récupérer les données JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Vérifier les données requises
        if (!isset($data['id']) || !isset($data['start']) || !isset($data['end'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
            exit;
        }
        
        // Récupérer l'événement
        $event = $this->eventModel->getById($data['id']);
        
        if (!$event) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
            exit;
        }
        
        // Vérifier le calendrier
        $calendar = $this->calendarModel->getById($event['calendar_id']);
        if (!$calendar || $calendar['user_id'] != $user_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cet événement']);
            exit;
        }
        
        // Optionnellement, changer de calendrier si spécifié
        $calendar_id = isset($data['calendarId']) ? $data['calendarId'] : $event['calendar_id'];
        
        if ($calendar_id != $event['calendar_id']) {
            // Vérifier que le nouveau calendrier appartient à l'utilisateur
            $newCalendar = $this->calendarModel->getById($calendar_id);
            if (!$newCalendar || $newCalendar['user_id'] != $user_id) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Calendrier invalide']);
                exit;
            }
        }
        
        // Mettre à jour l'événement
        $eventData = [
            'calendar_id' => $calendar_id,
            'start_date' => $data['start'],
            'end_date' => $data['end']
        ];
        
        $result = $this->eventModel->update($data['id'], $eventData);
        
        // Envoyer la réponse
        if ($result) {
            $updatedEvent = $this->eventModel->getById($data['id']);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Événement déplacé avec succès',
                'event' => $updatedEvent
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement de l\'événement']);
        }
        exit;
    }
    
    /**
     * API : Supprimer un événement via AJAX
     */
    public function deleteAjax() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Vérifier si c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }
        
        // Récupérer les données JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Vérifier les données requises
        if (!isset($data['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID d\'événement manquant']);
            exit;
        }
        
        // Récupérer l'événement
        $event = $this->eventModel->getById($data['id']);
        
        if (!$event) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
            exit;
        }
        
        // Vérifier le calendrier
        $calendar = $this->calendarModel->getById($event['calendar_id']);
        if (!$calendar || $calendar['user_id'] != $user_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cet événement']);
            exit;
        }
        
        // Supprimer l'événement
        $result = $this->eventModel->delete($data['id']);
        
        // Envoyer la réponse
        header('Content-Type: application/json');
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Événement supprimé avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'événement']);
        }
        exit;
    }
    
    /**
     * Exporter les événements au format iCalendar (ICS)
     * 
     * @param int|null $calendar_id ID du calendrier à exporter (optionnel)
     */
    public function export($calendar_id = null) {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Si un calendrier spécifique est demandé
        if ($calendar_id) {
            $calendar = $this->calendarModel->getById($calendar_id);
            
            // Vérifier que le calendrier appartient à l'utilisateur
            if (!$calendar || $calendar['user_id'] != $user_id) {
                Routes::redirect('calendar', 'index');
                return;
            }
            
            $events = $this->eventModel->getByCalendarId($calendar_id);
            $filename = 'calendar_' . $calendar_id . '_export.ics';
        } else {
            // Exporter tous les calendriers de l'utilisateur
            $calendars = $this->calendarModel->getByUserId($user_id);
            $events = [];
            
            foreach ($calendars as $cal) {
                $cal_events = $this->eventModel->getByCalendarId($cal['calendar_id']);
                $events = array_merge($events, $cal_events);
            }
            
            $filename = 'all_calendars_export.ics';
        }
        
        // Générer le contenu ICS
        $ics = $this->generateICS($events);
        
        // Envoyer l'en-tête pour le téléchargement
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo $ics;
        exit;
    }
    
    /**
     * Générer un fichier iCalendar (ICS) à partir des événements
     * 
     * @param array $events Liste d'événements
     * @return string Contenu ICS
     */
    private function generateICS($events) {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//TUI Calendar//NONSGML v1.0//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        
        foreach ($events as $event) {
            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:" . $event['event_id'] . "@tui-calendar\r\n";
            $ics .= "DTSTAMP:" . $this->formatDateForICS(date('Y-m-d H:i:s')) . "\r\n";
            $ics .= "DTSTART:" . $this->formatDateForICS($event['start_date']) . "\r\n";
            $ics .= "DTEND:" . $this->formatDateForICS($event['end_date']) . "\r\n";
            $ics .= "SUMMARY:" . $this->escapeICSString($event['title']) . "\r\n";
            
            if (!empty($event['body'])) {
                $ics .= "DESCRIPTION:" . $this->escapeICSString($event['body']) . "\r\n";
            }
            
            if (!empty($event['location'])) {
                $ics .= "LOCATION:" . $this->escapeICSString($event['location']) . "\r\n";
            }
            
            if ($event['is_recurring'] && !empty($event['recurrence_rule'])) {
                $ics .= "RRULE:" . $event['recurrence_rule'] . "\r\n";
            }
            
            $ics .= "END:VEVENT\r\n";
        }
        
        $ics .= "END:VCALENDAR\r\n";
        
        return $ics;
    }
    
    /**
     * Formater une date au format ICS
     * 
     * @param string $date Date au format MySQL (Y-m-d H:i:s)
     * @return string Date au format ICS
     */
    private function formatDateForICS($date) {
        return date('Ymd\THis\Z', strtotime($date));
    }
    
    /**
     * Échapper les caractères spéciaux pour ICS
     * 
     * @param string $string Chaîne à échapper
     * @return string Chaîne échappée
     */
    private function escapeICSString($string) {
        $string = str_replace(["\r\n", "\n"], "\\n", $string);
        $string = str_replace(",", "\\,", $string);
        $string = str_replace(";", "\\;", $string);
        return $string;
    }

    /**
     * Importer un fichier iCalendar (ICS) dans un calendrier
     */
    public function import() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Vérifier si c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Routes::redirect('calendar', 'settings');
            return;
        }
        
        // Vérifier que le fichier a été uploadé
        if (!isset($_FILES['ics_file']) || $_FILES['ics_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = 'Erreur lors de l\'upload du fichier';
            Routes::redirect('calendar', 'settings');
            return;
        }
        
        // Vérifier que c'est bien un fichier ICS
        $fileType = $_FILES['ics_file']['type'];
        $fileExt = pathinfo($_FILES['ics_file']['name'], PATHINFO_EXTENSION);
        
        if ($fileExt !== 'ics' && $fileType !== 'text/calendar') {
            $_SESSION['error_message'] = 'Le fichier doit être au format iCalendar (.ics)';
            Routes::redirect('calendar', 'settings');
            return;
        }
        
        // Vérifier le calendrier cible
        $calendar_id = isset($_POST['calendar_id']) ? $_POST['calendar_id'] : null;
        $calendar = $this->calendarModel->getById($calendar_id);
        
        if (!$calendar || $calendar['user_id'] != $user_id) {
            $_SESSION['error_message'] = 'Calendrier invalide';
            Routes::redirect('calendar', 'settings');
            return;
        }
        
        // Lire le contenu du fichier
        $icsContent = file_get_contents($_FILES['ics_file']['tmp_name']);
        
        if (!$icsContent) {
            $_SESSION['error_message'] = 'Impossible de lire le fichier';
            Routes::redirect('calendar', 'settings');
            return;
        }
        
        // Traiter le fichier ICS
        $events = $this->parseICSFile($icsContent);
        
        if (empty($events)) {
            $_SESSION['error_message'] = 'Aucun événement trouvé dans le fichier';
            Routes::redirect('calendar', 'settings');
            return;
        }
        
        // Importer les événements
        $importedCount = 0;
        foreach ($events as $event) {
            // Ajouter le calendar_id
            $event['calendar_id'] = $calendar_id;
            
            $result = $this->eventModel->create($event);
            if ($result) {
                $importedCount++;
            }
        }
        
        $_SESSION['success_message'] = $importedCount . ' événement(s) importé(s) avec succès';
        Routes::redirect('calendar', 'settings');
    }

    /**
     * Analyser un fichier ICS et extraire les événements
     * 
     * @param string $icsContent Contenu du fichier ICS
     * @return array Liste des événements au format adapté pour l'insertion
     */
    private function parseICSFile($icsContent) {
        $events = [];
        $inEvent = false;
        $currentEvent = [];
        
        // Normaliser les fins de ligne
        $icsContent = str_replace("\r\n", "\n", $icsContent);
        $lines = explode("\n", $icsContent);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $currentEvent = [];
            } elseif ($line === 'END:VEVENT') {
                $inEvent = false;
                // Vérifier que l'événement a au moins un titre et des dates
                if (isset($currentEvent['title']) && isset($currentEvent['start_date']) && isset($currentEvent['end_date'])) {
                    $events[] = $currentEvent;
                }
            } elseif ($inEvent) {
                // Analyser les propriétés de l'événement
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    
                    // Traiter les clés avec des paramètres
                    if (strpos($key, ';') !== false) {
                        list($key, $params) = explode(';', $key, 2);
                    }
                    
                    switch ($key) {
                        case 'SUMMARY':
                            $currentEvent['title'] = $this->unescapeICSString($value);
                            break;
                        case 'DESCRIPTION':
                            $currentEvent['body'] = $this->unescapeICSString($value);
                            break;
                        case 'LOCATION':
                            $currentEvent['location'] = $this->unescapeICSString($value);
                            break;
                        case 'DTSTART':
                            $currentEvent['start_date'] = $this->parseICSDate($value);
                            break;
                        case 'DTEND':
                            $currentEvent['end_date'] = $this->parseICSDate($value);
                            break;
                        case 'RRULE':
                            $currentEvent['is_recurring'] = 1;
                            $currentEvent['recurrence_rule'] = $value;
                            break;
                    }
                }
            }
        }
        
        return $events;
    }

    /**
     * Convertir une date ICS en format MySQL
     * 
     * @param string $icsDate Date au format ICS
     * @return string Date au format MySQL (Y-m-d H:i:s)
     */
    private function parseICSDate($icsDate) {
        // Format classique: 20230615T130000Z
        if (preg_match('/^(\d{8})T(\d{6})Z?$/', $icsDate, $matches)) {
            $year = substr($matches[1], 0, 4);
            $month = substr($matches[1], 4, 2);
            $day = substr($matches[1], 6, 2);
            
            $hour = substr($matches[2], 0, 2);
            $minute = substr($matches[2], 2, 2);
            $second = substr($matches[2], 4, 2);
            
            return sprintf('%s-%s-%s %s:%s:%s', $year, $month, $day, $hour, $minute, $second);
        }
        
        // Pour les dates simples sans heure
        if (preg_match('/^(\d{8})$/', $icsDate, $matches)) {
            $year = substr($matches[1], 0, 4);
            $month = substr($matches[1], 4, 2);
            $day = substr($matches[1], 6, 2);
            
            return sprintf('%s-%s-%s 00:00:00', $year, $month, $day);
        }
        
        // Si le format n'est pas reconnu, retourner la date actuelle
        return date('Y-m-d H:i:s');
    }

    /**
     * Annuler l'échappement des caractères spéciaux ICS
     * 
     * @param string $string Chaîne échappée
     * @return string Chaîne désechappée
     */
    private function unescapeICSString($string) {
        $string = str_replace("\\n", "\n", $string);
        $string = str_replace("\\,", ",", $string);
        $string = str_replace("\\;", ";", $string);
        return $string;
    }

    /**
     * API : Récupérer les événements pour une période donnée avec debug amélioré
     */
    public function getEvents() {
        try {
            
            
            // Fonction helper pour logger les messages
            $logger = function($message)  {
                $formattedMessage = '[' . date('Y-m-d H:i:s') . '] [getEvents] ' . $message;
                error_log($formattedMessage);
            };
            
            // ----- DÉBOGAGE : Vérification d'authentification -----
            if (!isset($_SESSION['user_id'])) {
                $logger("Erreur: Utilisateur non authentifié");
                $this->jsonResponse(['success' => false, 'error' => 'auth', 'message' => 'Utilisateur non authentifié'], 401);
                return;
            }
            
            $user_id = $_SESSION['user_id'];
            $logger("Utilisateur authentifié: " . $user_id);
            
            // ----- DÉBOGAGE : Paramètres de la requête -----
            $start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-1 month'));
            $end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));
            $logger("Période demandée: " . $start . " à " . $end);
            
            // ----- DÉBOGAGE : Calendriers de l'utilisateur -----
            $calendars = $this->calendarModel->getByUserId($user_id);
            if (empty($calendars)) {
                $logger("Aucun calendrier trouvé pour l'utilisateur " . $user_id);
                $this->jsonResponse(['success' => true, 'data' => [], 'message' => 'Aucun calendrier trouvé']);
                return;
            }
            
            $calendarIds = array_column($calendars, 'calendar_id');
            $logger("Calendriers trouvés: " . implode(', ', $calendarIds));
            
            // ----- DÉBOGAGE : Vérification de la méthode getEventsByDateRange -----
            if (!method_exists($this->eventModel, 'getEventsByDateRange')) {
                $logger("ERREUR CRITIQUE: Méthode getEventsByDateRange non trouvée dans Event model");
                $this->jsonResponse([
                    'success' => false, 
                    'error' => 'method',
                    'message' => 'Méthode getEventsByDateRange non implémentée'
                ], 500);
                return;
            }
            
            // ----- DÉBOGAGE : Exécution de la requête avec try/catch -----
            try {
                $logger("Récupération des événements...");
                $events = $this->eventModel->getEventsByDateRange($calendarIds, $start, $end);
                $logger(count($events) . " événements trouvés");
            } catch (Exception $e) {
                $logger("Exception lors de la récupération des événements: " . $e->getMessage());
                $this->jsonResponse([
                    'success' => false, 
                    'error' => 'db',
                    'message' => 'Erreur lors de la récupération des événements: ' . $e->getMessage()
                ], 500);
                return;
            }
            
            // ----- DÉBOGAGE : Formatage des événements -----
            $formattedEvents = [];
            foreach ($events as $event) {
                // Récupérer les couleurs du calendrier et de la catégorie
                $calendarColor = '#3788d8'; // Couleur par défaut
                $categoryColor = '#9e9e9e'; // Couleur par défaut
                $categoryTextColor = '#ffffff'; // Couleur par défaut
                
                foreach ($calendars as $cal) {
                    if ($cal['calendar_id'] == $event['calendar_id']) {
                        $calendarColor = $cal['color'];
                        break;
                    }
                }
                
                if (!empty($event['category_id'])) {
                    try {
                        $category = $this->categoryModel->getById($event['category_id']);
                        if ($category) {
                            $categoryColor = $category['color'];
                            $categoryTextColor = $category['text_color'];
                        }
                    } catch (Exception $e) {
                        $logger("Erreur avec la catégorie ID " . $event['category_id'] . ": " . $e->getMessage());
                        // Continuer avec les valeurs par défaut
                    }
                }
                
                $formattedEvents[] = [
                    'id' => $event['event_id'],
                    'calendarId' => $event['calendar_id'],
                    'categoryId' => $event['category_id'],
                    'title' => $event['title'],
                    'body' => $event['body'],
                    'start' => $event['start_date'],
                    'end' => $event['end_date'],
                    'isAllDay' => (bool)$event['is_all_day'],
                    'location' => $event['location'],
                    'isRecurring' => (bool)$event['is_recurring'],
                    'calendarColor' => $calendarColor,
                    'categoryColor' => $categoryColor,
                    'categoryTextColor' => $categoryTextColor
                ];
            }
            
            $logger("Réponse envoyée avec " . count($formattedEvents) . " événements formatés");
            
            // Réponse finale
            $this->jsonResponse([
                'success' => true,
                'count' => count($formattedEvents),
                'start' => $start,
                'end' => $end,
                'data' => $formattedEvents
            ]);
            
        } catch (Exception $e) {
            // En cas d'exception non gérée, s'assurer que le log est écrit
            $errorMessage = "Exception non gérée: " . $e->getMessage();
            $traceMessage = "Trace: " . $e->getTraceAsString();
            
            error_log($errorMessage);
            error_log($traceMessage);
            
            $this->jsonResponse([
                'success' => false,
                'error' => 'exception',
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
}
?>