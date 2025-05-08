/**
 * Script de communication avec le backend pour TUI Calendar
 * Ce fichier gère toutes les requêtes AJAX liées aux événements du calendrier
 */

const CalendarBackend = (function() {
    // Configuration et variables
    let baseUrl = '/tui_calendar/';
    let csrfToken = ''; // À remplir si nécessaire
    
    // Système d'alerte simple pour les messages
    const AlertSystem = {
        success: function(message) {
            // Uniquement log dans la console, pas d'alerte visuelle
            console.log('Succès:', message);
            // Aucun message visuel n'est affiché
        },
        error: function(message) {
            // On garde les erreurs visibles dans la console uniquement
            console.error('Erreur:', message);
            // Aucun message visuel n'est affiché
        }
    };
    
    /**
     * Initialiser le module
     * @param {Object} config - Configuration
     */
    function init(config) {
        if (config && config.baseUrl) {
            baseUrl = config.baseUrl;
        }
        if (config && config.csrfToken) {
            csrfToken = config.csrfToken;
        }
        
        console.log('CalendarBackend initialisé avec baseUrl:', baseUrl);
    }
    
    /**
     * Créer ou mettre à jour un événement
     * @param {Object} eventData - Données de l'événement
     * @param {Function} callback - Fonction de rappel (facultatif)
     */
    function saveEvent(eventData, callback) {
        // Convertir les dates en format ISO string si nécessaire
        const formattedData = formatEventData(eventData);
        
        console.log('Envoi des données d\'événement au serveur:', formattedData);
        
        $.ajax({
            url: baseUrl + 'ajax-handler.php?action=save-event',
            type: 'POST',
            data: JSON.stringify(formattedData),
            processData: false,
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                console.log('Réponse du serveur:', response);
                if (response.success) {
                    AlertSystem.success(response.message || 'Événement enregistré avec succès');
                    
                    // Mettre à jour l'ID si c'est une création
                    if (response.id && !eventData.id) {
                        console.log('Nouvel ID d\'événement attribué:', response.id);
                    }
                    
                    if (callback && typeof callback === 'function') {
                        callback(null, response);
                    }
                } else {
                    AlertSystem.error(response.message || 'Erreur lors de l\'enregistrement');
                    if (callback && typeof callback === 'function') {
                        callback(new Error(response.message), null);
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
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
                
                AlertSystem.error(errorMessage);
                
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
    function moveEvent(moveData, callback) {
        // IMPORTANT: Utiliser formatLocalISOString au lieu de toISOString()
        if (moveData.start instanceof Date) {
            moveData.start = formatLocalISOString(moveData.start);
        } else if (moveData.start && moveData.start._date) {
            // Gérer le cas TZDate
            moveData.start = formatLocalISOString(moveData.start._date);
        }
        
        if (moveData.end instanceof Date) {
            moveData.end = formatLocalISOString(moveData.end);
        } else if (moveData.end && moveData.end._date) {
            // Gérer le cas TZDate
            moveData.end = formatLocalISOString(moveData.end._date);
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
            url: baseUrl + 'ajax-handler.php?action=move-event',
            type: 'POST',
            data: JSON.stringify(moveData),
            processData: false,
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    AlertSystem.success(response.message || 'Événement déplacé avec succès');
                    if (callback && typeof callback === 'function') {
                        callback(null, response);
                    }
                } else {
                    AlertSystem.error(response.message || 'Erreur lors du déplacement');
                    if (callback && typeof callback === 'function') {
                        callback(new Error(response.message), null);
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
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
                
                AlertSystem.error(errorMessage);
                
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
    function deleteEvent(eventId, calendarId, callback) {
        console.log('Suppression de l\'événement:', eventId, 'du calendrier:', calendarId);
        
        $.ajax({
            url: baseUrl + 'ajax-handler.php?action=delete-event',
            type: 'POST',
            data: JSON.stringify({
                id: eventId,
                calendarId: calendarId
            }),
            processData: false,
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    AlertSystem.success(response.message || 'Événement supprimé avec succès');
                    if (callback && typeof callback === 'function') {
                        callback(null, response);
                    }
                } else {
                    AlertSystem.error(response.message || 'Erreur lors de la suppression');
                    if (callback && typeof callback === 'function') {
                        callback(new Error(response.message), null);
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
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
                
                AlertSystem.error(errorMessage);
                
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
    function loadEvents(start, end, callback, preserveHours = false) {
        if (start instanceof Date) {
            // Format YYYY-MM-DD (et heures si préservées)
            if (preserveHours) {
                start = formatLocalISOString(start);
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
                end = formatLocalISOString(end);
            } else {
                const year = end.getFullYear();
                const month = String(end.getMonth() + 1).padStart(2, '0');
                const day = String(end.getDate()).padStart(2, '0');
                end = `${year}-${month}-${day}`;
            }
        }
        
        console.log('Chargement des événements du', start, 'au', end);
        
        $.ajax({
            url: baseUrl + 'ajax-handler.php?action=get-events',
            type: 'GET',
            data: {
                start: start,
                end: end
            },
            dataType: 'json',
            success: function(response) {
                console.log('Événements chargés:', response.length || 0, 'événements');
                if (callback && typeof callback === 'function') {
                    callback(null, response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Erreur lors du chargement des événements:', textStatus);
                let errorMessage = 'Erreur lors du chargement des événements';
                
                try {
                    const errorData = JSON.parse(jqXHR.responseText);
                    if (errorData && errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {}
                
                AlertSystem.error(errorMessage);
                
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
    function getEvent(eventId, calendarId, callback) {
        const url = `${baseUrl}ajax-handler.php?action=get-event&id=${eventId}&calendarId=${calendarId}`;
        
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
 * Charger les événements pour une vue jour en préservant les heures
 */

function loadDayViewEvents(date, callback) {
    // S'assurer que date est un objet Date
    const dateObj = date instanceof Date ? date : new Date(date);
    
    // Formater pour l'API avec les heures (ne pas tronquer)
    const formattedDate = dateObj.toISOString();
    
    $.ajax({
        url: baseUrl + 'ajax-handler.php?action=get-day-events',
        type: 'GET',
        data: {
            date: formattedDate
        },
        dataType: 'json',
        success: function(response) {
            console.log('Événements vue jour chargés:', response.length || 0, 'événements');
            if (callback && typeof callback === 'function') {
                callback(null, response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Erreur lors du chargement des événements jour:', textStatus, errorThrown);
            if (callback && typeof callback === 'function') {
                callback(new Error('Erreur lors du chargement des événements'), null);
            }
        }
    });
}


    /**
     * Format d'événement pour l'envoi au backend
     * @param {Object} eventData - Données de l'événement
     * @returns {Object} - Données formatées
     */
    function formatEventData(eventData) {
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
                formattedData.start = formatLocalISOString(eventData.start);
            } else if (eventData.start && eventData.start._date) {
                // Objet TZDate
                formattedData.start = formatLocalISOString(eventData.start._date);
            } else {
                // Autres formats (chaîne, etc.)
                formattedData.start = eventData.start;
            }
            
            console.log('Date de début envoyée:', formattedData.start);
        }
        
        if (eventData.end) {
            if (eventData.end instanceof Date) {
                // Format ISO local
                formattedData.end = formatLocalISOString(eventData.end);
            } else if (eventData.end && eventData.end._date) {
                // Objet TZDate
                formattedData.end = formatLocalISOString(eventData.end._date);
            } else {
                // Autres formats (chaîne, etc.)
                formattedData.end = eventData.end;
            }
                
            console.log('Date de fin envoyée:', formattedData.end);
        }
        
        return formattedData;
    }
    
    // Fonction locale pour formater les dates sans le Z de UTC
    function formatLocalISOString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
    }
    
    // Exposer l'API publique
    return {
        init: init,
        saveEvent: saveEvent,
        moveEvent: moveEvent,
        deleteEvent: deleteEvent,
        loadEvents: loadEvents,
        loadDayViewEvents: loadDayViewEvents,
        getEvent: getEvent
    };
})();