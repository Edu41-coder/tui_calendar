/**
 * Classe de communication avec le backend pour TUI Calendar
 * Cette classe gère toutes les requêtes AJAX liées aux événements du calendrier
 */

class CalendarBackend {
    /**
     * Crée une nouvelle instance du backend du calendrier
     * @param {Object} config - Configuration initiale (optionnel)
     */
    constructor(config = {}) {
      // Configuration et variables d'instance
      this.baseUrl = config.baseUrl || '/tui_calendar/';
      this.csrfToken = config.csrfToken || '';
      
      // Système d'alerte simple pour les messages
      this.alertSystem = {
        success: function(message) {
          console.log('Succès:', message);
          // Ajouter une alerte visuelle
          if (typeof bootstrap !== 'undefined') {
            const alertPlaceholder = document.getElementById('alert-container') || document.createElement('div');
            alertPlaceholder.id = 'alert-container';
            alertPlaceholder.className = 'position-fixed top-0 end-0 p-3';
            document.body.appendChild(alertPlaceholder);
            
            const alertEl = document.createElement('div');
            alertEl.className = 'alert alert-success alert-dismissible fade show';
            alertEl.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            alertPlaceholder.appendChild(alertEl);
            
            setTimeout(() => {
              const bsAlert = new bootstrap.Alert(alertEl);
              bsAlert.close();
            }, 3000);
          }
        },
        error: function(message) {
          console.error('Erreur:', message);
          // Erreur visuelle
          // Code similaire pour l'alerte d'erreur
        }
      };
      
      console.log('CalendarBackend initialisé avec baseUrl:', this.baseUrl);
    }
    
    /**
     * Initialiser ou reconfigurer l'instance
     * @param {Object} config - Configuration
     */
    init(config) {
      if (config && config.baseUrl) {
        this.baseUrl = config.baseUrl;
      }
      if (config && config.csrfToken) {
        this.csrfToken = config.csrfToken;
      }
      
      console.log('CalendarBackend initialisé avec baseUrl:', this.baseUrl);
      return this;
    }
    
    /**
     * Créer ou mettre à jour un événement
     * @param {Object} eventData - Données de l'événement
     * @param {Function} callback - Fonction de rappel (facultatif)
     */
    saveEvent(eventData, callback) {
      // Convertir les dates en format ISO string si nécessaire
      const formattedData = this.formatEventData(eventData);
      
      console.log('Envoi des données d\'événement au serveur:', formattedData);
      
      $.ajax({
        url: this.baseUrl + 'ajax-handler.php?action=save-event',
        type: 'POST',
        data: JSON.stringify(formattedData),
        processData: false,
        contentType: 'application/json',
        dataType: 'json',
        success: (response) => {
          console.log('Réponse du serveur:', response);
          if (response.success) {
            this.alertSystem.success(response.message || 'Événement enregistré avec succès');
            
            // Mettre à jour l'ID si c'est une création
            if (response.id && !eventData.id) {
              console.log('Nouvel ID d\'événement attribué:', response.id);
            }
            
            if (callback && typeof callback === 'function') {
              callback(null, response);
            }
          } else {
            this.alertSystem.error(response.message || 'Erreur lors de l\'enregistrement');
            if (callback && typeof callback === 'function') {
              callback(new Error(response.message), null);
            }
          }
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error('Erreur AJAX:', textStatus, errorThrown);
          let errorMessage = 'Erreur lors de la communication avec le serveur';
          
          try {
            const errorData = JSON.parse(jqXHR.responseText);
            if (errorData && errorData.message) {
              errorMessage = errorData.message;
            }
          } catch (e) {
            console.error('Impossible de parser la réponse d\'erreur:', e);
          }
          
          this.alertSystem.error(errorMessage);
          
          if (callback && typeof callback === 'function') {
            callback(new Error(errorMessage), null);
          }
        }
      });
    }
    
    /**
     * Déplacer un événement (drag & drop)
     * @param {Object} moveData - Données du déplacement
     * @param {Function} callback - Fonction de rappel (facultatif)
     */
    moveEvent(moveData, callback) {
      // IMPORTANT: Utiliser formatLocalISOString au lieu de toISOString()
      if (moveData.start instanceof Date) {
        moveData.start = this.formatLocalISOString(moveData.start);
      } else if (moveData.start && moveData.start._date) {
        // Gérer le cas TZDate
        moveData.start = this.formatLocalISOString(moveData.start._date);
      }
      
      if (moveData.end instanceof Date) {
        moveData.end = this.formatLocalISOString(moveData.end);
      } else if (moveData.end && moveData.end._date) {
        // Gérer le cas TZDate
        moveData.end = this.formatLocalISOString(moveData.end._date);
      }
      
      // Détection automatique des événements sur toute la journée
      let startObj = null, endObj = null;
      
      if (moveData.start instanceof Date) {
        startObj = moveData.start;
      } else if (moveData.start && moveData.start._date) {
        startObj = moveData.start._date;
      } else if (typeof moveData.start === 'string') {
        startObj = new Date(moveData.start);
      }
      
      if (moveData.end instanceof Date) {
        endObj = moveData.end;
      } else if (moveData.end && moveData.end._date) {
        endObj = moveData.end._date;
      } else if (typeof moveData.end === 'string') {
        endObj = new Date(moveData.end);
      }
      
      // Si les deux objets Date sont disponibles, vérifier si c'est un événement sur toute la journée
      if (startObj && endObj) {
        const startIsMidnight = startObj.getHours() === 0 && startObj.getMinutes() === 0;
        const endIs2359 = endObj.getHours() === 23 && endObj.getMinutes() === 59;
        
        if (startIsMidnight && endIs2359) {
          moveData.isAllDay = true;
          console.log('Événement déplacé détecté comme "toute la journée" basé sur les heures');
        }
      }
      
      console.log('Déplacement d\'événement:', moveData);
      console.log('Date de début formatée:', moveData.start);
      console.log('Date de fin formatée:', moveData.end);
      
      $.ajax({
        url: this.baseUrl + 'ajax-handler.php?action=move-event',
        type: 'POST',
        data: JSON.stringify(moveData),
        processData: false,
        contentType: 'application/json',
        dataType: 'json',
        success: (response) => {
          if (response.success) {
            this.alertSystem.success(response.message || 'Événement déplacé avec succès');
            if (callback && typeof callback === 'function') {
              callback(null, response);
            }
          } else {
            this.alertSystem.error(response.message || 'Erreur lors du déplacement');
            if (callback && typeof callback === 'function') {
              callback(new Error(response.message), null);
            }
          }
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error('Erreur AJAX:', textStatus, errorThrown);
          let errorMessage = 'Erreur lors de la communication avec le serveur';
          
          try {
            const errorData = JSON.parse(jqXHR.responseText);
            if (errorData && errorData.message) {
              errorMessage = errorData.message;
            }
          } catch (e) {
            console.error('Impossible de parser la réponse d\'erreur:', e);
          }
          
          this.alertSystem.error(errorMessage);
          
          if (callback && typeof callback === 'function') {
            callback(new Error(errorMessage), null);
          }
        }
      });
    }
    
    /**
     * Supprimer un événement
     * @param {String|Number} eventId - ID de l'événement
     * @param {String|Number} calendarId - ID du calendrier
     * @param {Function} callback - Fonction de rappel (facultatif)
     */
    deleteEvent(eventId, calendarId, callback) {
      console.log('Suppression de l\'événement:', eventId, 'du calendrier:', calendarId);
      
      $.ajax({
        url: this.baseUrl + 'ajax-handler.php?action=delete-event',
        type: 'POST',
        data: JSON.stringify({
          id: eventId,
          calendarId: calendarId
        }),
        processData: false,
        contentType: 'application/json',
        dataType: 'json',
        success: (response) => {
          if (response.success) {
            this.alertSystem.success(response.message || 'Événement supprimé avec succès');
            if (callback && typeof callback === 'function') {
              callback(null, response);
            }
          } else {
            this.alertSystem.error(response.message || 'Erreur lors de la suppression');
            if (callback && typeof callback === 'function') {
              callback(new Error(response.message), null);
            }
          }
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error('Erreur AJAX:', textStatus, errorThrown);
          let errorMessage = 'Erreur lors de la communication avec le serveur';
          
          try {
            const errorData = JSON.parse(jqXHR.responseText);
            if (errorData && errorData.message) {
              errorMessage = errorData.message;
            }
          } catch (e) {
            console.error('Impossible de parser la réponse d\'erreur:', e);
          }
          
          this.alertSystem.error(errorMessage);
          
          if (callback && typeof callback === 'function') {
            callback(new Error(errorMessage), null);
          }
        }
      });
    }
    
    /**
     * Charger les événements pour une période donnée
     * @param {Date|String} start - Date de début
     * @param {Date|String} end - Date de fin
     * @param {Function} callback - Fonction de rappel
     * @param {Boolean} preserveHours - Conserver les heures dans les dates
     */
    loadEvents(start, end, callback, preserveHours = false) {
      if (start instanceof Date) {
        // Format YYYY-MM-DD (et heures si préservées)
        if (preserveHours) {
          start = this.formatLocalISOString(start);
        } else {
          const year = start.getFullYear();
          const month = String(start.getMonth() + 1).padStart(2, '0');
          const day = String(start.getDate()).padStart(2, '0');
          start = `${year}-${month}-${day}`;
        }
      }
    
      if (end instanceof Date) {
        // Format YYYY-MM-DD (et heures si préservées)
        if (preserveHours) {
          end = this.formatLocalISOString(end);
        } else {
          const year = end.getFullYear();
          const month = String(end.getMonth() + 1).padStart(2, '0');
          const day = String(end.getDate()).padStart(2, '0');
          end = `${year}-${month}-${day}`;
        }
      }
      
      console.log('Chargement des événements du', start, 'au', end);
      
      $.ajax({
        url: this.baseUrl + 'ajax-handler.php?action=get-events',
        type: 'GET',
        data: {
          start: start,
          end: end
        },
        dataType: 'json',
        success: (response) => {
          console.log('Événements chargés:', response.length || 0, 'événements');
          if (callback && typeof callback === 'function') {
            callback(null, response);
          }
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error('Erreur lors du chargement des événements:', textStatus);
          let errorMessage = 'Erreur lors du chargement des événements';
          
          try {
            const errorData = JSON.parse(jqXHR.responseText);
            if (errorData && errorData.message) {
              errorMessage = errorData.message;
            }
          } catch (e) {}
          
          this.alertSystem.error(errorMessage);
          
          if (callback && typeof callback === 'function') {
            callback(new Error(errorMessage), null);
          }
        }
      });
    }
    
    
    /**
     * Récupérer un événement spécifique par son ID
     * @param {String|Number} eventId - ID de l'événement
     * @param {String|Number} calendarId - ID du calendrier
     * @param {Function} callback - Fonction de rappel (facultatif)
     */
    getEvent(eventId, calendarId, callback) {
      const url = `${this.baseUrl}ajax-handler.php?action=get-event&id=${eventId}&calendarId=${calendarId}`;
      
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            callback && callback(null, data.event);
          } else {
            callback && callback(data.message || 'Erreur lors de la récupération de l\'événement', null);
          }
        })
        .catch(error => {
          console.error('Erreur lors de la récupération de l\'événement:', error);
          callback && callback('Erreur réseau lors de la récupération de l\'événement', null);
        });
    }
    
    /**
     * Met à jour la visibilité d'un calendrier
     * @param {Number} calendarId - ID du calendrier
     * @param {Boolean} visible - Nouvel état de visibilité
     * @param {Function} callback - Fonction de rappel
     */
    toggleCalendarVisibility(calendarId, visible, callback) {
      $.ajax({
        url: this.baseUrl + 'ajax-handler.php?action=toggle-calendar-visibility',
        type: 'POST',
        data: {
          calendar_id: calendarId,
          visible: visible ? 1 : 0
        },
        dataType: 'json',
        success: (response) => {
          if (response.success) {
            this.alertSystem.success('Visibilité du calendrier mise à jour');
            callback && callback(null, response);
          } else {
            this.alertSystem.error(response.message || 'Erreur de mise à jour');
            callback && callback(new Error(response.message), null);
          }
        },
        error: (xhr, status, error) => {
          this.alertSystem.error('Erreur de communication');
          callback && callback(new Error(error), null);
        }
      });
    }
    
    /**
     * Format d'événement pour l'envoi au backend
     * @param {Object} eventData - Données de l'événement
     * @returns {Object} - Données formatées
     */
    formatEventData(eventData) {
      const formattedData = {
        id: eventData.id || null,
        title: eventData.title,
        calendarId: eventData.calendarId,
        categoryId: eventData.raw?.categoryId || eventData.categoryId,
        body: eventData.body || '',
        location: eventData.location || '',
        isAllDay: eventData.isAllDay || false            
      };
      
      // IMPORTANT: Envoyer les dates au format ISO local (sans Z)
      if (eventData.start) {
        if (eventData.start instanceof Date) {
          // Format ISO local
          formattedData.start = this.formatLocalISOString(eventData.start);
        } else if (eventData.start && eventData.start._date) {
          // Objet TZDate
          formattedData.start = this.formatLocalISOString(eventData.start._date);
        } else {
          // Autres formats (chaîne, etc.)
          formattedData.start = eventData.start;
        }
        
        console.log('Date de début envoyée:', formattedData.start);
      }
      
      if (eventData.end) {
        if (eventData.end instanceof Date) {
          // Format ISO local
          formattedData.end = this.formatLocalISOString(eventData.end);
        } else if (eventData.end && eventData.end._date) {
          // Objet TZDate
          formattedData.end = this.formatLocalISOString(eventData.end._date);
        } else {
          // Autres formats (chaîne, etc.)
          formattedData.end = eventData.end;
        }
            
        console.log('Date de fin envoyée:', formattedData.end);
      }
      
      return formattedData;
    }
    
    /**
     * Fonction locale pour formater les dates sans le Z de UTC
     * @param {Date} date - Date à formater
     * @returns {String} - Date formatée en ISO local
     */
    formatLocalISOString(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      const seconds = String(date.getSeconds()).padStart(2, '0');
      
      return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
    }

  }
  window.CalendarBackendClass = CalendarBackend; // Exposer la classe elle-même  
  // Créer une instance unique globale
window.calendarBackend = new CalendarBackend();