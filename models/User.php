<?php
require_once __DIR__ . '/../config/db_config.php';

/**
 * Classe modèle User pour gérer les utilisateurs
 */
class User {
    private $conn;
    
    // Propriétés
    public $user_id;
    public $username;
    public $password;
    public $email;
    public $full_name;
    public $created_at;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->conn = getDbConnection();
    }
    
    /**
     * Obtenir tous les utilisateurs
     * 
     * @return array Liste des utilisateurs
     */
    public function getAll() {
        $query = "SELECT user_id, username, email, full_name, created_at FROM users";
        $result = $this->conn->query($query);
        
        $users = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    /**
     * Obtenir un utilisateur par son ID
     * 
     * @param int $id ID de l'utilisateur
     * @return array|null Données de l'utilisateur ou null si non trouvé
     */
    public function getById($id) {
        $query = "SELECT user_id, username, email, full_name, created_at FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtenir un utilisateur par son nom d'utilisateur
     * 
     * @param string $username Nom d'utilisateur
     * @return array|null Données de l'utilisateur ou null si non trouvé
     */
    public function getByUsername($username) {
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Créer un nouvel utilisateur
     * 
     * @param array $data Données de l'utilisateur
     * @return int|bool ID de l'utilisateur créé ou false en cas d'erreur
     */
    public function create($data) {
        // Hachage du mot de passe
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $query = "INSERT INTO users (username, password, email, full_name) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "ssss", 
            $data['username'], 
            $hashed_password, 
            $data['email'], 
            $data['full_name']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @param array $data Données de l'utilisateur
     * @return bool Succès ou échec
     */
    public function update($id, $data) {
        // On commence par vérifier si on a un nouveau mot de passe
        if (isset($data['password']) && !empty($data['password'])) {
            // Mise à jour avec mot de passe
            $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
            
            $query = "UPDATE users SET username = ?, password = ?, email = ?, full_name = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param(
                "ssssi", 
                $data['username'], 
                $hashed_password, 
                $data['email'], 
                $data['full_name'],
                $id
            );
        } else {
            // Mise à jour sans mot de passe
            $query = "UPDATE users SET username = ?, email = ?, full_name = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param(
                "sssi", 
                $data['username'], 
                $data['email'], 
                $data['full_name'],
                $id
            );
        }
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @return bool Succès ou échec
     */
    public function delete($id) {
        $query = "DELETE FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Vérifier les identifiants d'un utilisateur
     * 
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     * @return array|bool Données de l'utilisateur ou false si échec
     */
    public function authenticate($username, $password) {
        $user = $this->getByUsername($username);
        
        if ($user && password_verify($password, $user['password'])) {
            // Ne pas renvoyer le mot de passe
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Vérifier si un nom d'utilisateur existe déjà
     * 
     * @param string $username Nom d'utilisateur
     * @param int $exclude_id ID à exclure de la vérification (pour les mises à jour)
     * @return bool True si existe, false sinon
     */
    public function usernameExists($username, $exclude_id = 0) {
        $query = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $username, $exclude_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    /**
     * Vérifier si un email existe déjà
     * 
     * @param string $email Email
     * @param int $exclude_id ID à exclure de la vérification (pour les mises à jour)
     * @return bool True si existe, false sinon
     */
    public function emailExists($email, $exclude_id = 0) {
        $query = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $email, $exclude_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}
?>