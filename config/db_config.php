<?php
/**
 * Configuration de la base de données pour MariaDB dans XAMPP
 */

// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');     // Hôte de la base de données (par défaut: localhost)
define('DB_USER', 'root');          // Nom d'utilisateur MariaDB (par défaut: root dans XAMPP)
define('DB_PASS', '');              // Mot de passe MariaDB (par défaut: vide dans XAMPP)
define('DB_NAME', 'tui_calendar_db'); // Nom de la base de données
define('DB_PORT', 3306);            // Port MariaDB (par défaut: 3306)
define('DB_CHARSET', 'utf8mb4');    // Jeu de caractères pour prendre en charge tous les caractères Unicode

/**
 * Fonction pour établir une connexion à la base de données MariaDB
 * 
 * @return mysqli Connexion à la base de données
 */
function getDbConnection() {
    // Créer une nouvelle instance de connexion MySQLi (fonctionne avec MariaDB)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Vérifier la connexion
    if ($conn->connect_error) {
        die("Échec de connexion à la base de données MariaDB: " . $conn->connect_error);
    }
    
    // Définir le jeu de caractères pour la connexion
    $conn->set_charset(DB_CHARSET);
    
    return $conn;
}

/**
 * Fonction pour exécuter une requête et retourner le résultat
 * 
 * @param string $sql Requête SQL à exécuter
 * @return mysqli_result|bool Résultat de la requête
 */
function executeQuery($sql) {
    $conn = getDbConnection();
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}

/**
 * Fonction pour exécuter une requête préparée avec des paramètres
 * 
 * @param string $sql Requête SQL avec placeholders
 * @param string $types Types des paramètres (i: integer, s: string, d: double, b: blob)
 * @param array $params Tableau de paramètres à lier à la requête
 * @return array|bool Résultat de la requête ou false en cas d'erreur
 */
function executeQueryParams($sql, $types, $params) {
    $conn = getDbConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    // Utiliser bind_param() avec des références
    if (!empty($params)) {
        // bind_param() nécessite des références à tous les paramètres
        $bind_params = array();
        $bind_params[] = &$types;
        
        for ($i = 0; $i < count($params); $i++) {
            $bind_params[] = &$params[$i];
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    }
    
    // Exécuter la requête
    $stmt->execute();
    
    // Récupérer le résultat
    $result = $stmt->get_result();
    
    if ($result) {
        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        $conn->close();
        return $data;
    } else {
        // Pour les requêtes INSERT, UPDATE, DELETE
        $affected = $stmt->affected_rows;
        $insert_id = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        return ['affected' => $affected, 'insert_id' => $insert_id];
    }
}
?>