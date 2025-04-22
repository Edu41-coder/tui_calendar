<?php
require_once __DIR__ . '/../config/db_config.php';

/**
 * Classe modèle pour gérer les calendriers
 */
class Calendar {
    private $conn;
    
    // Propriétés
    public $calendar_id;
    public $user_id;
    public $name;
    public $color;
    public $is_visible;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->conn = getDbConnection();
    }
    
    /**
     * Obtenir tous les calendriers
     * 
     * @return array Liste des calendriers
     */
    public function getAll() {
        $query = "SELECT * FROM calendars";
        $result = $this->conn->query($query);
        
        $calendars = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $calendars[] = $row;
            }
        }
        
        return $calendars;
    }
    
    /**
     * Obtenir les calendriers d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Liste des calendriers
     */
    public function getByUserId($user_id) {
        $query = "SELECT * FROM calendars WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $calendars = [];
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $calendars[] = $row;
            }
        }
        
        return $calendars;
    }
    
    /**
     * Obtenir un calendrier par son ID
     * 
     * @param int $calendar_id ID du calendrier
     * @return array|null Données du calendrier ou null si non trouvé
     */
    public function getById($calendar_id) {
        $query = "SELECT * FROM calendars WHERE calendar_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $calendar_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Créer un nouveau calendrier
     * 
     * @param array $data Données du calendrier
     * @return int|bool ID du calendrier créé ou false en cas d'erreur
     */
    public function create($data) {
        $query = "INSERT INTO calendars (user_id, name, color, is_visible) 
                 VALUES (?, ?, ?, ?)";
        
        $is_visible = isset($data['is_visible']) ? $data['is_visible'] : 1;
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "issi", 
            $data['user_id'], 
            $data['name'], 
            $data['color'],
            $is_visible
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour un calendrier
     * 
     * @param int $calendar_id ID du calendrier
     * @param array $data Nouvelles données
     * @return bool Succès ou échec
     */
    public function update($calendar_id, $data) {
        $query = "UPDATE calendars SET 
                 name = ?, 
                 color = ?, 
                 is_visible = ? 
                 WHERE calendar_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "ssii", 
            $data['name'], 
            $data['color'], 
            $data['is_visible'],
            $calendar_id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer un calendrier
     * 
     * @param int $calendar_id ID du calendrier
     * @return bool Succès ou échec
     */
    public function delete($calendar_id) {
        $query = "DELETE FROM calendars WHERE calendar_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $calendar_id);
        
        return $stmt->execute();
    }
    
    /**
     * Créer des calendriers par défaut pour un nouvel utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array IDs des calendriers créés
     */
    public function createDefaultCalendars($user_id) {
        $defaultCalendars = [
            ['name' => 'Personnel', 'color' => '#E91E63'],
            ['name' => 'Travail', 'color' => '#3F51B5'],
            ['name' => 'Formation', 'color' => '#4CAF50']
        ];
        
        $created = [];
        
        foreach ($defaultCalendars as $cal) {
            $data = [
                'user_id' => $user_id,
                'name' => $cal['name'],
                'color' => $cal['color'],
                'is_visible' => 1
            ];
            
            $id = $this->create($data);
            if ($id) {
                $created[] = $id;
            }
        }
        
        return $created;
    }
    
    /**
     * Changer la visibilité d'un calendrier
     * 
     * @param int $calendar_id ID du calendrier
     * @param bool $visible Visibilité souhaitée
     * @return bool Succès ou échec
     */
    public function setVisibility($calendar_id, $visible) {
        $query = "UPDATE calendars SET is_visible = ? WHERE calendar_id = ?";
        $stmt = $this->conn->prepare($query);
        
        $visibility = $visible ? 1 : 0;
        $stmt->bind_param("ii", $visibility, $calendar_id);
        
        return $stmt->execute();
    }
}
?>