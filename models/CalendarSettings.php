<?php
require_once __DIR__ . '/../config/db_config.php';

/**
 * Classe modèle pour gérer les paramètres du calendrier
 */
class CalendarSettings {
    private $conn;
    
    // Propriétés
    public $setting_id;
    public $user_id;
    public $default_view;
    public $start_day_of_week;
    public $week_numbers_visible;
    public $timezone;
    public $use_strict_mode;
    public $show_past_events;
    public $custom_theme;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->conn = getDbConnection();
    }
    
    /**
     * Obtenir les paramètres d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array|null Paramètres du calendrier ou null si non trouvés
     */
    public function getByUserId($user_id) {
        $query = "SELECT * FROM calendar_settings WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtenir les paramètres par ID
     * 
     * @param int $setting_id ID des paramètres
     * @return array|null Paramètres du calendrier ou null si non trouvés
     */
    public function getById($setting_id) {
        $query = "SELECT * FROM calendar_settings WHERE setting_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $setting_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Créer ou mettre à jour les paramètres d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param array $data Données des paramètres
     * @return int|bool ID des paramètres ou false en cas d'erreur
     */
    public function saveForUser($user_id, $data) {
        // Vérifier si des paramètres existent déjà pour cet utilisateur
        $existing = $this->getByUserId($user_id);
        
        if ($existing) {
            // Mettre à jour les paramètres existants
            return $this->update($existing['setting_id'], $data);
        } else {
            // Créer de nouveaux paramètres
            return $this->create($user_id, $data);
        }
    }
    
    /**
     * Créer de nouveaux paramètres
     * 
     * @param int $user_id ID de l'utilisateur
     * @param array $data Données des paramètres
     * @return int|bool ID des paramètres créés ou false en cas d'erreur
     */
    public function create($user_id, $data) {
        // Préparer les données à insérer avec des valeurs par défaut si non spécifiées
        $default_view = isset($data['default_view']) ? $data['default_view'] : 'week';
        $start_day_of_week = isset($data['start_day_of_week']) ? $data['start_day_of_week'] : 0;
        $week_numbers_visible = isset($data['week_numbers_visible']) ? $data['week_numbers_visible'] : 1;
        $timezone = isset($data['timezone']) ? $data['timezone'] : 'UTC';
        $use_strict_mode = isset($data['use_strict_mode']) ? $data['use_strict_mode'] : 1;
        $show_past_events = isset($data['show_past_events']) ? $data['show_past_events'] : 1;
        $custom_theme = isset($data['custom_theme']) ? $data['custom_theme'] : null;
        
        $query = "INSERT INTO calendar_settings (
                    user_id, 
                    default_view, 
                    start_day_of_week, 
                    week_numbers_visible, 
                    timezone, 
                    use_strict_mode, 
                    show_past_events, 
                    custom_theme
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "isiissss", 
            $user_id,
            $default_view,
            $start_day_of_week,
            $week_numbers_visible,
            $timezone,
            $use_strict_mode,
            $show_past_events,
            $custom_theme
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour des paramètres existants
     * 
     * @param int $setting_id ID des paramètres
     * @param array $data Nouvelles données
     * @return bool Succès ou échec
     */
    public function update($setting_id, $data) {
        // Récupérer les paramètres existants
        $current = $this->getById($setting_id);
        if (!$current) {
            return false;
        }
        
        // Préparer les données avec les valeurs existantes si non spécifiées
        $default_view = isset($data['default_view']) ? $data['default_view'] : $current['default_view'];
        $start_day_of_week = isset($data['start_day_of_week']) ? $data['start_day_of_week'] : $current['start_day_of_week'];
        $week_numbers_visible = isset($data['week_numbers_visible']) ? $data['week_numbers_visible'] : $current['week_numbers_visible'];
        $timezone = isset($data['timezone']) ? $data['timezone'] : $current['timezone'];
        $use_strict_mode = isset($data['use_strict_mode']) ? $data['use_strict_mode'] : $current['use_strict_mode'];
        $show_past_events = isset($data['show_past_events']) ? $data['show_past_events'] : $current['show_past_events'];
        $custom_theme = isset($data['custom_theme']) ? $data['custom_theme'] : $current['custom_theme'];
        
        $query = "UPDATE calendar_settings SET 
                default_view = ?, 
                start_day_of_week = ?, 
                week_numbers_visible = ?, 
                timezone = ?, 
                use_strict_mode = ?, 
                show_past_events = ?, 
                custom_theme = ? 
                WHERE setting_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "siissisi", 
            $default_view,
            $start_day_of_week,
            $week_numbers_visible,
            $timezone,
            $use_strict_mode,
            $show_past_events,
            $custom_theme,
            $setting_id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer les paramètres
     * 
     * @param int $setting_id ID des paramètres à supprimer
     * @return bool Succès ou échec
     */
    public function delete($setting_id) {
        $query = "DELETE FROM calendar_settings WHERE setting_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $setting_id);
        
        return $stmt->execute();
    }
    
    /**
     * Obtenir les paramètres par défaut pour un nouvel utilisateur
     * 
     * @return array Paramètres par défaut
     */
    public function getDefaultSettings() {
        return [
            'default_view' => 'week',
            'start_day_of_week' => 0,  // 0 = Dimanche, 1 = Lundi, etc.
            'week_numbers_visible' => 1,
            'timezone' => 'UTC',
            'use_strict_mode' => 1,
            'show_past_events' => 1,
            'custom_theme' => null
        ];
    }
    
    /**
     * Initialise les paramètres par défaut pour un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return int|bool ID des paramètres créés ou false en cas d'erreur
     */
    public function initializeForUser($user_id) {
        return $this->create($user_id, $this->getDefaultSettings());
    }
    
    /**
     * Convertit la valeur custom_theme JSON en tableau associatif
     * 
     * @param array $settings Paramètres du calendrier
     * @return array Paramètres avec theme décodé
     */
    public function decodeTheme($settings) {
        if (isset($settings['custom_theme']) && !empty($settings['custom_theme'])) {
            $settings['custom_theme'] = json_decode($settings['custom_theme'], true);
        }
        return $settings;
    }
}
?>