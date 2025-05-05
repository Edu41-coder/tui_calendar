<?php
require_once __DIR__ . '/../config/db_config.php';

/**
 * Classe modèle pour gérer les événements du calendrier
 */
class Event {
    private $conn;
    
    // Propriétés
    public $event_id;
    public $calendar_id;
    public $category_id;
    public $title;
    public $body;
    public $start_date;
    public $end_date;
    public $is_all_day;
    public $location;
    public $is_recurring;
    public $recurrence_rule;
    public $created_at;
    public $updated_at;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->conn = getDbConnection();
    }
    
    /**
     * Obtenir tous les événements
     * 
     * @return array Liste des événements
     */
    public function getAll() {
        $query = "SELECT * FROM events ORDER BY start_date";
        $result = $this->conn->query($query);
        
        $events = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        }
        
        return $events;
    }
    
    /**
     * Obtenir un événement par son ID
     * 
     * @param int $event_id ID de l'événement
     * @return array|null Données de l'événement ou null si non trouvé
     */
    public function getById($event_id) {
        $query = "SELECT * FROM events WHERE event_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Obtenir les événements d'un calendrier
     * 
     * @param int $calendar_id ID du calendrier
     * @return array Liste des événements
     */
    public function getByCalendarId($calendar_id) {
        $query = "SELECT * FROM events WHERE calendar_id = ? ORDER BY start_date";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $calendar_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $events = [];
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        }
        
        return $events;
    }
    
    /**
     * Obtenir les événements pour une période spécifique
     * 
     * @param string $start_date Date de début au format 'YYYY-MM-DD'
     * @param string $end_date Date de fin au format 'YYYY-MM-DD'
     * @param int|null $calendar_id ID du calendrier (optionnel)
     * @return array Liste des événements
     */
    public function getByDateRange($start_date, $end_date, $calendar_id = null) {
        if ($calendar_id) {
            $query = "SELECT * FROM events 
                     WHERE ((start_date BETWEEN ? AND ?) 
                     OR (end_date BETWEEN ? AND ?) 
                     OR (start_date <= ? AND end_date >= ?)) 
                     AND calendar_id = ? 
                     ORDER BY start_date";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssssssi", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $calendar_id);
        } else {
            $query = "SELECT * FROM events 
                     WHERE (start_date BETWEEN ? AND ?) 
                     OR (end_date BETWEEN ? AND ?) 
                     OR (start_date <= ? AND end_date >= ?) 
                     ORDER BY start_date";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        $events = [];
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        }
        
        return $events;
    }
    
    /**
     * Obtenir les événements pour une période spécifique et plusieurs calendriers
     * 
     * @param array $calendar_ids Tableau d'IDs de calendriers
     * @param string $start_date Date de début au format 'YYYY-MM-DD'
     * @param string $end_date Date de fin au format 'YYYY-MM-DD'
     * @return array Liste des événements avec détails
     */
    public function getEventsByDateRange($calendar_ids, $start_date, $end_date) {
        if (empty($calendar_ids)) {
            return [];
        }
        
        // Construire la condition IN pour les calendriers
        $placeholders = implode(',', array_fill(0, count($calendar_ids), '?'));
        
        // Construire la requête complète avec jointures pour récupérer les détails
        $query = "SELECT e.*, c.name as calendar_name, c.color as calendar_color, 
         cat.name as category_name, cat.color as category_color, 
         cat.color as category_text_color
         FROM events e 
         LEFT JOIN calendars c ON e.calendar_id = c.calendar_id 
         LEFT JOIN categories cat ON e.category_id = cat.category_id
         WHERE e.calendar_id IN ($placeholders)
         AND ((e.start_date BETWEEN ? AND ?) 
             OR (e.end_date BETWEEN ? AND ?) 
             OR (e.start_date <= ? AND e.end_date >= ?)) 
         ORDER BY e.start_date";
        
        // Préparer les paramètres pour la requête
        $params = array_merge($calendar_ids, [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
        
        // Déterminer les types de paramètres
        $types = str_repeat('i', count($calendar_ids)) . 'ssssss';
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $events = [];
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Transformer les colonnes de date en format ISO pour le frontend
                $row['start_date'] = date('c', strtotime($row['start_date']));
                $row['end_date'] = date('c', strtotime($row['end_date']));
                $events[] = $row;
            }
        }
        
        return $events;
    }
    
    /**
     * Créer un nouvel événement
     * 
     * @param array $data Données de l'événement
     * @return int|bool ID de l'événement créé ou false en cas d'erreur
     */
    public function create($data) {
        // Valeurs par défaut pour les champs optionnels
        $category_id = isset($data['category_id']) ? $data['category_id'] : null;
        $body = isset($data['body']) ? $data['body'] : null;
        $is_all_day = isset($data['is_all_day']) ? $data['is_all_day'] : 0;
        $location = isset($data['location']) ? $data['location'] : null;
        $is_recurring = isset($data['is_recurring']) ? $data['is_recurring'] : 0;
        $recurrence_rule = isset($data['recurrence_rule']) ? $data['recurrence_rule'] : null;
        
        $query = "INSERT INTO events (
                    calendar_id, 
                    category_id, 
                    title, 
                    body, 
                    start_date, 
                    end_date, 
                    is_all_day, 
                    location, 
                    is_recurring, 
                    recurrence_rule
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "iissssisis", 
            $data['calendar_id'],
            $category_id,
            $data['title'],
            $body,
            $data['start_date'],
            $data['end_date'],
            $is_all_day,
            $location,
            $is_recurring,
            $recurrence_rule
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour un événement existant
     * 
     * @param int $event_id ID de l'événement
     * @param array $data Nouvelles données
     * @return bool Succès ou échec
     */
    public function update($event_id, $data) {
        // Récupérer les données existantes
        $current = $this->getById($event_id);
        if (!$current) {
            return false;
        }
        
        // Préparer les données avec les valeurs existantes si non spécifiées
        $calendar_id = isset($data['calendar_id']) ? $data['calendar_id'] : $current['calendar_id'];
        $category_id = isset($data['category_id']) ? $data['category_id'] : $current['category_id'];
        $title = isset($data['title']) ? $data['title'] : $current['title'];
        $body = isset($data['body']) ? $data['body'] : $current['body'];
        $start_date = isset($data['start_date']) ? $data['start_date'] : $current['start_date'];
        $end_date = isset($data['end_date']) ? $data['end_date'] : $current['end_date'];
        $is_all_day = isset($data['is_all_day']) ? $data['is_all_day'] : $current['is_all_day'];
        $location = isset($data['location']) ? $data['location'] : $current['location'];
        $is_recurring = isset($data['is_recurring']) ? $data['is_recurring'] : $current['is_recurring'];
        $recurrence_rule = isset($data['recurrence_rule']) ? $data['recurrence_rule'] : $current['recurrence_rule'];
        
        $query = "UPDATE events SET 
                 calendar_id = ?, 
                 category_id = ?, 
                 title = ?, 
                 body = ?, 
                 start_date = ?, 
                 end_date = ?, 
                 is_all_day = ?, 
                 location = ?, 
                 is_recurring = ?, 
                 recurrence_rule = ? 
                 WHERE event_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "iissssisisi", 
            $calendar_id,
            $category_id,
            $title,
            $body,
            $start_date,
            $end_date,
            $is_all_day,
            $location,
            $is_recurring,
            $recurrence_rule,
            $event_id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer un événement
     * 
     * @param int $event_id ID de l'événement
     * @return bool Succès ou échec
     */
    public function delete($event_id) {
        $query = "DELETE FROM events WHERE event_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $event_id);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer tous les événements d'un calendrier
     * 
     * @param int $calendar_id ID du calendrier
     * @return bool Succès ou échec
     */
    public function deleteByCalendarId($calendar_id) {
        $query = "DELETE FROM events WHERE calendar_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $calendar_id);
        
        return $stmt->execute();
    }    
    
    /**
     * Obtenir les événements avec des informations sur le calendrier et la catégorie
     * 
     * @param string|null $start_date Date de début (optionnel)
     * @param string|null $end_date Date de fin (optionnel)
     * @return array Liste des événements avec informations complémentaires
     */
    public function getEventsWithDetails($start_date = null, $end_date = null) {
        $query = "SELECT e.*, c.name as calendar_name, c.color as calendar_color, 
                 cat.name as category_name, cat.color as category_color, 
                 cat.bg_color, cat.border_color 
                 FROM events e 
                 LEFT JOIN calendars c ON e.calendar_id = c.calendar_id 
                 LEFT JOIN categories cat ON e.category_id = cat.category_id";
        
        $params = [];
        $types = "";
        
        if ($start_date && $end_date) {
            $query .= " WHERE (e.start_date BETWEEN ? AND ?) 
                      OR (e.end_date BETWEEN ? AND ?) 
                      OR (e.start_date <= ? AND e.end_date >= ?)";
            $params = [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date];
            $types = "ssssss";
        }
        
        $query .= " ORDER BY e.start_date";
        
        if (!empty($params)) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($query);
        }
        
        $events = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        }
        
        return $events;
    }
    
    /**
     * Convertir les données d'événements au format attendu par tui.calendar
     * 
     * @param array $events Liste d'événements
     * @return array Événements formatés pour tui.calendar
     */
    public function formatForTuiCalendar($events) {
        $formatted = [];
        
        foreach ($events as $event) {
            $formatted[] = [
                'id' => $event['event_id'],
                'calendarId' => $event['calendar_id'],
                'title' => $event['title'],
                'body' => $event['body'],
                'start' => $event['start_date'],
                'end' => $event['end_date'],
                'category' => $event['is_all_day'] == 1 ? 'allday' : 'time',
                'isAllDay' => $event['is_all_day'] == 1,
                'location' => $event['location'],
                'isReadOnly' => false,
                'color' => isset($event['category_color']) ? $event['category_color'] : null,
                'backgroundColor' => isset($event['bg_color']) ? $event['bg_color'] : null,
                'borderColor' => isset($event['border_color']) ? $event['border_color'] : null,
                'customStyle' => '',
                'raw' => [
                    'recurring' => $event['is_recurring'] == 1,
                    'recurrenceRule' => $event['recurrence_rule'],
                    'created_at' => $event['created_at'],
                    'updated_at' => $event['updated_at'],
                    'category_id' => $event['category_id']
                ]
            ];
        }
        
        return $formatted;
    }
}
?>