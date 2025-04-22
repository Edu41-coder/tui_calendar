<?php
require_once __DIR__ . '/../config/db_config.php';

/**
 * Classe modèle pour gérer les catégories d'événements
 */
class Category {
    private $conn;
    
    // Propriétés
    public $category_id;
    public $name;
    public $color;
    public $bg_color;
    public $drag_bg_color;
    public $border_color;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->conn = getDbConnection();
    }
    
    /**
     * Obtenir toutes les catégories
     * 
     * @return array Liste des catégories
     */
    public function getAll() {
        $query = "SELECT * FROM categories";
        $result = $this->conn->query($query);
        
        $categories = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        return $categories;
    }
    
    /**
     * Obtenir une catégorie par son ID
     * 
     * @param int $category_id ID de la catégorie
     * @return array|null Données de la catégorie ou null si non trouvée
     */
    public function getById($category_id) {
        $query = "SELECT * FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Créer une nouvelle catégorie
     * 
     * @param array $data Données de la catégorie
     * @return int|bool ID de la catégorie créée ou false en cas d'erreur
     */
    public function create($data) {
        $query = "INSERT INTO categories (name, color, bg_color, drag_bg_color, border_color) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "sssss", 
            $data['name'], 
            $data['color'], 
            $data['bg_color'],
            $data['drag_bg_color'],
            $data['border_color']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour une catégorie
     * 
     * @param int $category_id ID de la catégorie
     * @param array $data Nouvelles données
     * @return bool Succès ou échec
     */
    public function update($category_id, $data) {
        $query = "UPDATE categories SET 
                 name = ?, 
                 color = ?, 
                 bg_color = ?, 
                 drag_bg_color = ?, 
                 border_color = ? 
                 WHERE category_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "sssssi", 
            $data['name'], 
            $data['color'], 
            $data['bg_color'],
            $data['drag_bg_color'],
            $data['border_color'],
            $category_id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer une catégorie
     * 
     * @param int $category_id ID de la catégorie
     * @return bool Succès ou échec
     */
    public function delete($category_id) {
        $query = "DELETE FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $category_id);
        
        return $stmt->execute();
    }
    
    /**
     * Initialiser les catégories par défaut
     * 
     * @return array IDs des catégories créées
     */
    public function initializeDefaultCategories() {
        $defaultCategories = [
            [
                'name' => 'Réunion',
                'color' => '#2C3E50',
                'bg_color' => '#34495E',
                'drag_bg_color' => '#34495E',
                'border_color' => '#2C3E50'
            ],
            [
                'name' => 'Rendez-vous',
                'color' => '#8E44AD',
                'bg_color' => '#9B59B6',
                'drag_bg_color' => '#9B59B6',
                'border_color' => '#8E44AD'
            ],
            [
                'name' => 'Évènement',
                'color' => '#D35400',
                'bg_color' => '#E67E22',
                'drag_bg_color' => '#E67E22',
                'border_color' => '#D35400'
            ],
            [
                'name' => 'Rappel',
                'color' => '#16A085',
                'bg_color' => '#1ABC9C',
                'drag_bg_color' => '#1ABC9C',
                'border_color' => '#16A085'
            ]
        ];
        
        $created = [];
        
        // Vérifier d'abord si des catégories existent déjà
        $existing = $this->getAll();
        if (count($existing) > 0) {
            return array_column($existing, 'category_id');
        }
        
        // Créer les catégories par défaut
        foreach ($defaultCategories as $cat) {
            $id = $this->create($cat);
            if ($id) {
                $created[] = $id;
            }
        }
        
        return $created;
    }
}
?>