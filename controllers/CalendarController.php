<?php
require_once __DIR__ . '/../models/Calendar.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/CalendarSettings.php';
require_once __DIR__ . '/../controllers/UserController.php';

/**
 * Contrôleur pour gérer les calendriers et catégories
 */
class CalendarController {
    private $calendarModel;
    private $categoryModel;
    private $eventModel;
    private $settingsModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->calendarModel = new Calendar();
        $this->categoryModel = new Category();
        $this->eventModel = new Event();
        $this->settingsModel = new CalendarSettings();
    }
    
    /**
     * Afficher le calendrier principal
     */
    public function index() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Récupérer les calendriers de l'utilisateur
        $calendars = $this->calendarModel->getByUserId($user_id);
        
        // Récupérer les catégories disponibles
        $categories = $this->categoryModel->getAll();
        
        // Récupérer les paramètres du calendrier
        $settings = $this->settingsModel->getByUserId($user_id);
        
        // Si aucun paramètre n'est trouvé, utiliser les valeurs par défaut
        if (!$settings) {
            $settings = $this->settingsModel->getDefaultSettings();
        } else {
            // Décoder le thème personnalisé s'il existe
            $settings = $this->settingsModel->decodeTheme($settings);
        }
        
        // Définir la période à afficher (mois actuel par défaut)
        $current_date = new DateTime();
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $current_date->format('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $current_date->format('Y-m-t');
        
        // Afficher la vue du calendrier
        include __DIR__ . '/../views/calendar/index.php';
    }
    
    /**
     * Obtenir les événements au format JSON pour TUI Calendar
     */
    public function getEvents() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Récupérer les paramètres de date
        $start_date = isset($_GET['start']) ? $_GET['start'] : null;
        $end_date = isset($_GET['end']) ? $_GET['end'] : null;
        
        // Récupérer les calendriers de l'utilisateur
        $calendars = $this->calendarModel->getByUserId($user_id);
        $calendar_ids = array_column($calendars, 'calendar_id');
        
        // Récupérer les événements avec leurs détails
        $events = [];
        if ($start_date && $end_date) {
            foreach ($calendar_ids as $cal_id) {
                $cal_events = $this->eventModel->getByDateRange($start_date, $end_date, $cal_id);
                $events = array_merge($events, $cal_events);
            }
        } else {
            foreach ($calendar_ids as $cal_id) {
                $cal_events = $this->eventModel->getByCalendarId($cal_id);
                $events = array_merge($events, $cal_events);
            }
        }
        
        // Formater pour TUI Calendar
        $formatted_events = $this->eventModel->formatForTuiCalendar($events);
        
        // Renvoyer les événements au format JSON
        header('Content-Type: application/json');
        echo json_encode($formatted_events);
        exit;
    }
    
    /**
     * Gestion des calendriers
     */
    public function calendars() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $error = '';
        $success = '';
        
        // Récupérer les calendriers de l'utilisateur
        $calendars = $this->calendarModel->getByUserId($user_id);
        
        // Afficher la page de gestion des calendriers
        include __DIR__ . '/../views/calendar/manage.php';
    }
    
    /**
     * Créer un nouveau calendrier
     */
    public function createCalendar() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $color = isset($_POST['color']) ? trim($_POST['color']) : '#000000';
            $is_visible = isset($_POST['is_visible']) ? 1 : 0;
            
            if (empty($name)) {
                $error = 'Veuillez entrer un nom pour le calendrier';
            } else {
                $data = [
                    'user_id' => $user_id,
                    'name' => $name,
                    'color' => $color,
                    'is_visible' => $is_visible
                ];
                
                $result = $this->calendarModel->create($data);
                
                if ($result) {
                    $success = 'Calendrier créé avec succès';
                    // Redirection après 2 secondes
                    header("Refresh:2; url=" . Routes::url('calendar', 'calendars'));
                } else {
                    $error = 'Erreur lors de la création du calendrier';
                }
            }
        }
        
        // Afficher le formulaire de création
        include __DIR__ . '/../views/calendar/create.php';
    }
    
    /**
     * Modifier un calendrier existant
     * 
     * @param int $id ID du calendrier
     */
    public function editCalendar($id = null) {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        // Si l'ID n'est pas spécifié dans l'URL, essayer de le récupérer depuis POST
        if ($id === null && isset($_POST['calendar_id'])) {
            $id = (int)$_POST['calendar_id'];
        }
        
        if (!$id) {
            Routes::redirect('calendar', 'calendars');
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $error = '';
        $success = '';
        
        // Récupérer les informations du calendrier
        $calendar = $this->calendarModel->getById($id);
        
        // Vérifier que le calendrier appartient bien à l'utilisateur
        if (!$calendar || $calendar['user_id'] != $user_id) {
            Routes::redirect('calendar', 'calendars');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $color = isset($_POST['color']) ? trim($_POST['color']) : '#000000';
            $is_visible = isset($_POST['is_visible']) ? 1 : 0;
            
            if (empty($name)) {
                $error = 'Veuillez entrer un nom pour le calendrier';
            } else {
                $data = [
                    'name' => $name,
                    'color' => $color,
                    'is_visible' => $is_visible
                ];
                
                $result = $this->calendarModel->update($id, $data);
                
                if ($result) {
                    $success = 'Calendrier mis à jour avec succès';
                    // Rafraîchir les données du calendrier
                    $calendar = $this->calendarModel->getById($id);
                } else {
                    $error = 'Erreur lors de la mise à jour du calendrier';
                }
            }
        }
        
        // Afficher le formulaire d'édition
        include __DIR__ . '/../views/calendar/edit.php';
    }
    
    /**
     * Supprimer un calendrier
     * 
     * @param int $id ID du calendrier
     */
    public function deleteCalendar($id = null) {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Si l'ID n'est pas spécifié dans l'URL, essayer de le récupérer depuis POST
        if ($id === null && isset($_POST['calendar_id'])) {
            $id = (int)$_POST['calendar_id'];
        }
        
        if (!$id) {
            $_SESSION['error_message'] = 'ID de calendrier non spécifié';
            Routes::redirect('calendar', 'calendars');
            return;
        }
        
        // Récupérer le calendrier
        $calendar = $this->calendarModel->getById($id);
        
        // Vérifier que le calendrier existe et appartient à l'utilisateur
        if (!$calendar || $calendar['user_id'] != $user_id) {
            $_SESSION['error_message'] = 'Calendrier non trouvé ou accès non autorisé';
            Routes::redirect('calendar', 'calendars');
            return;
        }
        
        // Supprimer directement le calendrier sans page de confirmation supplémentaire
        // La confirmation a déjà été faite via la modal JavaScript
        $result = $this->calendarModel->delete($id);
        
        if ($result) {
            $_SESSION['success_message'] = 'Calendrier "' . htmlspecialchars($calendar['name']) . '" supprimé avec succès';
        } else {
            $_SESSION['error_message'] = 'Erreur lors de la suppression du calendrier';
        }
        
        Routes::redirect('calendar', 'calendars');
        return;
    }
    
    /**
     * Gérer les catégories d'événements
     */
    public function categories() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $error = '';
        $success = '';
        
        // Récupérer toutes les catégories
        $categories = $this->categoryModel->getAll();
        
        // Afficher la page de gestion des catégories
        include __DIR__ . '/../views/calendar/categories.php';
    }
    
    /**
     * Créer une nouvelle catégorie
     */
    public function createCategory() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $color = isset($_POST['color']) ? trim($_POST['color']) : '#000000';
            $bg_color = isset($_POST['bg_color']) ? trim($_POST['bg_color']) : '#FFFFFF';
            $drag_bg_color = isset($_POST['drag_bg_color']) ? trim($_POST['drag_bg_color']) : '#FFFFFF';
            $border_color = isset($_POST['border_color']) ? trim($_POST['border_color']) : '#000000';
            
            if (empty($name)) {
                $error = 'Veuillez entrer un nom pour la catégorie';
            } else {
                $data = [
                    'name' => $name,
                    'color' => $color,
                    'bg_color' => $bg_color,
                    'drag_bg_color' => $drag_bg_color,
                    'border_color' => $border_color
                ];
                
                $result = $this->categoryModel->create($data);
                
                if ($result) {
                    $success = 'Catégorie créée avec succès';
                    // Redirection après 2 secondes
                    header("Refresh:2; url=" . Routes::url('calendar', 'categories'));
                } else {
                    $error = 'Erreur lors de la création de la catégorie';
                }
            }
        }
        
        // Afficher le formulaire de création
        include __DIR__ . '/../views/calendar/create_category.php';
    }
    
    /**
     * Modifier une catégorie existante
     * 
     * @param int $id ID de la catégorie
     */
    public function editCategory($id = null) {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        // Si l'ID n'est pas spécifié dans l'URL, essayer de le récupérer depuis POST
        if ($id === null && isset($_POST['category_id'])) {
            $id = (int)$_POST['category_id'];
        }
        
        if (!$id) {
            Routes::redirect('calendar', 'categories');
            return;
        }
        
        $error = '';
        $success = '';
        
        // Récupérer les informations de la catégorie
        $category = $this->categoryModel->getById($id);
        
        // Vérifier que la catégorie existe
        if (!$category) {
            Routes::redirect('calendar', 'categories');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $color = isset($_POST['color']) ? trim($_POST['color']) : '#000000';
            $bg_color = isset($_POST['bg_color']) ? trim($_POST['bg_color']) : '#FFFFFF';
            $drag_bg_color = isset($_POST['drag_bg_color']) ? trim($_POST['drag_bg_color']) : '#FFFFFF';
            $border_color = isset($_POST['border_color']) ? trim($_POST['border_color']) : '#000000';
            
            if (empty($name)) {
                $error = 'Veuillez entrer un nom pour la catégorie';
            } else {
                $data = [
                    'name' => $name,
                    'color' => $color,
                    'bg_color' => $bg_color,
                    'drag_bg_color' => $drag_bg_color,
                    'border_color' => $border_color
                ];
                
                $result = $this->categoryModel->update($id, $data);
                
                if ($result) {
                    $success = 'Catégorie mise à jour avec succès';
                    // Rafraîchir les données de la catégorie
                    $category = $this->categoryModel->getById($id);
                } else {
                    $error = 'Erreur lors de la mise à jour de la catégorie';
                }
            }
        }
        
        // Afficher le formulaire d'édition
        include __DIR__ . '/../views/calendar/edit_category.php';
    }
    
    /**
     * Supprimer une catégorie
     * 
     * @param int $id ID de la catégorie
     */
    public function deleteCategory($id = null) {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        // Si l'ID n'est pas spécifié dans l'URL, essayer de le récupérer depuis POST
        if ($id === null && isset($_POST['category_id'])) {
            $id = (int)$_POST['category_id'];
        }
        
        if (!$id) {
            Routes::redirect('calendar', 'categories');
            return;
        }
        
        // Récupérer les informations de la catégorie
        $category = $this->categoryModel->getById($id);
        
        // Vérifier que la catégorie existe
        if (!$category) {
            $_SESSION['error_message'] = 'Catégorie non trouvée';
            Routes::redirect('calendar', 'categories');
            return;
        }
        
        // Supprimer directement la catégorie sans confirmation
        $result = $this->categoryModel->delete($id);
        
        if ($result) {
            $_SESSION['success_message'] = 'Catégorie "' . htmlspecialchars($category['name']) . '" supprimée avec succès';
        } else {
            $_SESSION['error_message'] = 'Erreur lors de la suppression de la catégorie';
        }
        
        Routes::redirect('calendar', 'categories');
        return;
    }
    
    /**
     * Initialiser les catégories par défaut
     */
    public function initializeCategories() {
        // Vérifier que l'utilisateur est connecté et est admin
        if (!UserController::checkLogin() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            Routes::redirect('calendar', 'categories');
            return;
        }
        
        $result = $this->categoryModel->initializeDefaultCategories();
        
        if (!empty($result)) {
            $_SESSION['success_message'] = count($result) . ' catégories ont été initialisées avec succès';
        } else {
            $_SESSION['error_message'] = 'Aucune catégorie n\'a été créée. Elles existent peut-être déjà.';
        }
        
        Routes::redirect('calendar', 'categories');
    }
    
    /**
     * Changer la visibilité d'un calendrier (via AJAX)
     */
    public function toggleCalendarVisibility() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        
        // Vérifier que les données nécessaires sont présentes
        if (!isset($_POST['calendar_id']) || !isset($_POST['visible'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }
        
        $calendar_id = (int)$_POST['calendar_id'];
        $visible = $_POST['visible'] === 'true' || $_POST['visible'] === '1';
        
        // Récupérer le calendrier
        $calendar = $this->calendarModel->getById($calendar_id);
        
        // Vérifier que le calendrier appartient à l'utilisateur
        if (!$calendar || $calendar['user_id'] != $user_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Calendrier non trouvé ou non autorisé']);
            exit;
        }
        
        // Mettre à jour la visibilité
        $result = $this->calendarModel->setVisibility($calendar_id, $visible);
        
        // Renvoyer le résultat en JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Visibilité mise à jour' : 'Erreur lors de la mise à jour',
            'visible' => $visible
        ]);
        exit;
    }
    
    /**
     * Afficher et gérer les paramètres du calendrier
     */
    public function settings() {
        // Vérifier que l'utilisateur est connecté
        if (!UserController::checkLogin()) {
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $error = '';
        $success = '';
        
        // Récupérer les paramètres actuels
        $settings = $this->settingsModel->getByUserId($user_id);
        
        // Si les paramètres n'existent pas encore, créer les valeurs par défaut
        if (!$settings) {
            $this->settingsModel->initializeForUser($user_id);
            $settings = $this->settingsModel->getByUserId($user_id);
        }
        
        // Récupérer les calendriers de l'utilisateur
        $calendars = $this->calendarModel->getByUserId($user_id);
        
        // Afficher la page des paramètres (correction du chemin)
        include __DIR__ . '/../views/user/settings.php';
    }
}
?>