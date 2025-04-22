<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/CalendarSettings.php';
require_once __DIR__ . '/../models/Calendar.php';

/**
 * Contrôleur pour gérer les utilisateurs et l'authentification
 */
class UserController {
    private $userModel;
    private $settingsModel;
    private $calendarModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->userModel = new User();
        $this->settingsModel = new CalendarSettings();
        $this->calendarModel = new Calendar();
    }
    
    /**
     * Page de connexion
     */
    public function login() {
        // Si déjà connecté, rediriger vers le profil
        if (isset($_SESSION['user_id'])) {
            Routes::redirect('user', 'profile');
            return;
        }
        
        $error = '';
        
        // Traitement du formulaire de connexion
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            
            // Vérification des identifiants
            $user = $this->userModel->authenticate($username, $password);
            
            if ($user) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Rediriger vers le calendrier
                Routes::redirect('calendar', 'index');
                return;
            } else {
                $error = 'Nom d\'utilisateur ou mot de passe incorrect';
            }
        }
        
        // Afficher le formulaire de connexion
        include __DIR__ . '/../views/user/login.php';
    }
    
    /**
     * Page d'inscription
     */
    public function register() {
        // Si déjà connecté, rediriger vers le profil
        if (isset($_SESSION['user_id'])) {
            Routes::redirect('user', 'profile');
            return;
        }
        
        $error = '';
        $success = '';
        
        // Traitement du formulaire d'inscription
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
            
            // Validation
            if (empty($username) || empty($password) || empty($email)) {
                $error = 'Veuillez remplir tous les champs obligatoires';
            } else if ($this->userModel->usernameExists($username)) {
                $error = 'Ce nom d\'utilisateur est déjà utilisé';
            } else if ($this->userModel->emailExists($email)) {
                $error = 'Cet email est déjà utilisé';
            } else if (strlen($password) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères';
            } else {
                // Créer l'utilisateur
                $userData = [
                    'username' => $username,
                    'password' => $password,
                    'email' => $email,
                    'full_name' => $full_name
                ];
                
                $user_id = $this->userModel->create($userData);
                
                if ($user_id) {
                    // Création réussie
                    $success = 'Votre compte a été créé avec succès';
                    
                    // Initialiser les paramètres par défaut et calendriers
                    $this->settingsModel->initializeForUser($user_id);
                    $this->calendarModel->createDefaultCalendars($user_id);
                    
                    // Connexion automatique
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    
                    // Rediriger après 2 secondes
                    header("Refresh:2; url=" . Routes::url('calendar', 'index'));
                } else {
                    $error = 'Erreur lors de la création du compte';
                }
            }
        }
        
        // Afficher le formulaire d'inscription
        include __DIR__ . '/../views/user/register.php';
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        // Détruire la session
        session_unset();
        session_destroy();
        
        // Rediriger vers la page de connexion
        Routes::redirect('user', 'login');
    }
    
    /**
     * Page de profil utilisateur
     */
    public function profile() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            Routes::redirect('user', 'login');
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $error = '';
        $success = '';
        
        // Récupérer les informations de l'utilisateur
        $user = $this->userModel->getById($user_id);
        
        // Traitement du formulaire de mise à jour du profil
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            // Vérifier si l'email existe déjà pour un autre utilisateur
            if ($this->userModel->emailExists($email, $user_id)) {
                $error = 'Cet email est déjà utilisé par un autre compte';
            } else {
                $userData = [
                    'full_name' => $full_name,
                    'email' => $email
                ];
                
                // Si l'utilisateur souhaite changer de mot de passe
                if (!empty($current_password) || !empty($new_password)) {
                    // Vérifier le mot de passe actuel
                    if (empty($current_password) || !$this->userModel->authenticate($user['username'], $current_password)) {
                        $error = 'Le mot de passe actuel est incorrect';
                    } else if (empty($new_password) || strlen($new_password) < 6) {
                        $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
                    } else if ($new_password !== $confirm_password) {
                        $error = 'Les mots de passe ne correspondent pas';
                    } else {
                        $userData['password'] = $new_password;
                    }
                }
                
                // Si pas d'erreur, mettre à jour le profil
                if (empty($error)) {
                    $result = $this->userModel->update($user_id, $userData);
                    
                    if ($result) {
                        $success = 'Vos informations ont été mises à jour';
                        // Mettre à jour le nom complet dans la session
                        $_SESSION['full_name'] = $full_name;
                        // Rafraîchir les infos utilisateur
                        $user = $this->userModel->getById($user_id);
                    } else {
                        $error = 'Erreur lors de la mise à jour du profil';
                    }
                }
            }
        }
        
        // Récupérer les paramètres de calendrier de l'utilisateur
        $settings = $this->settingsModel->getByUserId($user_id);
        
        // Afficher la page de profil
        include __DIR__ . '/../views/user/profile.php';
    }
    
    /**
     * Page des paramètres du calendrier
     */
    public function settings() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            Routes::redirect('user', 'login');
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
        
        // Traitement du formulaire de mise à jour des paramètres
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $default_view = isset($_POST['default_view']) ? $_POST['default_view'] : 'week';
            $start_day_of_week = isset($_POST['start_day_of_week']) ? (int)$_POST['start_day_of_week'] : 0;
            $week_numbers_visible = isset($_POST['week_numbers_visible']) ? 1 : 0;
            $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC';
            $use_strict_mode = isset($_POST['use_strict_mode']) ? 1 : 0;
            $show_past_events = isset($_POST['show_past_events']) ? 1 : 0;
            
            // Construire un thème personnalisé si nécessaire
            $custom_theme = null;
            if (isset($_POST['use_custom_theme']) && $_POST['use_custom_theme'] == 1) {
                $theme = [
                    'common.border' => isset($_POST['theme_border']) ? $_POST['theme_border'] : '#e5e5e5',
                    'common.backgroundColor' => isset($_POST['theme_bg']) ? $_POST['theme_bg'] : '#fff',
                    'common.holiday.color' => isset($_POST['theme_holiday']) ? $_POST['theme_holiday'] : '#ff4040',
                    'month.dayname.height' => '31px',
                    'month.dayname.borderLeft' => '1px solid #e5e5e5',
                    'month.dayname.paddingLeft' => '10px',
                    'month.dayname.paddingRight' => '10px',
                    'month.dayname.fontSize' => '12px',
                    'month.dayname.backgroundColor' => 'inherit',
                    'month.dayname.fontWeight' => 'normal',
                    'month.dayname.textAlign' => 'left'
                ];
                $custom_theme = json_encode($theme);
            }
            
            $settingsData = [
                'default_view' => $default_view,
                'start_day_of_week' => $start_day_of_week,
                'week_numbers_visible' => $week_numbers_visible,
                'timezone' => $timezone,
                'use_strict_mode' => $use_strict_mode,
                'show_past_events' => $show_past_events,
                'custom_theme' => $custom_theme
            ];
            
            $result = $this->settingsModel->saveForUser($user_id, $settingsData);
            
            if ($result) {
                $success = 'Vos paramètres de calendrier ont été mis à jour';
                $settings = $this->settingsModel->getByUserId($user_id);
            } else {
                $error = 'Erreur lors de la mise à jour des paramètres';
            }
        }
        
        // Décoder le thème JSON si présent
        if (isset($settings['custom_theme']) && !empty($settings['custom_theme'])) {
            $settings['custom_theme'] = json_decode($settings['custom_theme'], true);
        }
        
        // Afficher la page des paramètres
        include __DIR__ . '/../views/user/settings.php';
    }
    
    /**
     * Sauvegarder les paramètres du calendrier
     */
    public function saveSettings() {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            Routes::redirect('user', 'login');
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupérer les paramètres du formulaire
            $default_view = isset($_POST['default_view']) ? $_POST['default_view'] : 'week';
            $start_day_of_week = isset($_POST['start_day_of_week']) ? (int)$_POST['start_day_of_week'] : 0;
            $theme = isset($_POST['theme']) ? $_POST['theme'] : 'default';
            $show_weekends = isset($_POST['show_weekends']) ? 1 : 0;
            $show_week_numbers = isset($_POST['show_week_numbers']) ? 1 : 0;
            $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
            $notification_time = isset($_POST['notification_time']) ? (int)$_POST['notification_time'] : 0;
            
            // Traiter le thème personnalisé
            $custom_theme = null;
            if ($theme === 'custom' && isset($_POST['custom_theme'])) {
                $custom_theme = json_encode($_POST['custom_theme']);
            }
            
            // Préparer les données à sauvegarder
            $settingsData = [
                'default_view' => $default_view,
                'start_day_of_week' => $start_day_of_week,
                'theme' => $theme,
                'custom_theme' => $custom_theme,
                'show_weekends' => $show_weekends,
                'show_week_numbers' => $show_week_numbers,
                'enable_notifications' => $enable_notifications,
                'notification_time' => $notification_time
            ];
            
            // Sauvegarder les paramètres
            $result = $this->settingsModel->saveForUser($user_id, $settingsData);
            
            if ($result) {
                $_SESSION['success_message'] = 'Vos paramètres ont été enregistrés avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de l\'enregistrement des paramètres';
            }
        }
        
        // Rediriger vers la page des paramètres
        Routes::redirect('calendar', 'settings');
    }
    
    /**
     * Vérifier si l'utilisateur est connecté, sinon rediriger
     * 
     * @return bool True si connecté, sinon redirect
     */
    public static function checkLogin() {
        if (!isset($_SESSION['user_id'])) {
            Routes::redirect('user', 'login');
            return false;
        }
        return true;
    }
}
?>