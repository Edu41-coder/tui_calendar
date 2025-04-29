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
        // S'assurer que les dates sont au format ISO
        if (moveData.start instanceof Date) {
            moveData.start = moveData.start.toISOString();
        }
        if (moveData.end instanceof Date) {
            moveData.end = moveData.end.toISOString();
        }
        
        console.log('Déplacement d\'événement:', moveData);
        
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
     */
    function loadEvents(start, end, callback) {
        // Formater les dates si nécessaire
        if (start instanceof Date) {
            start = start.toISOString().split('T')[0]; // Format YYYY-MM-DD
        }
        if (end instanceof Date) {
            end = end.toISOString().split('T')[0]; // Format YYYY-MM-DD
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
        
        // Gestion des dates
        if (eventData.start) {
            formattedData.start = eventData.start instanceof Date ? eventData.start.toISOString() : eventData.start;
        }
        
        if (eventData.end) {
            formattedData.end = eventData.end instanceof Date ? eventData.end.toISOString() : eventData.end;
        }
        
        // Ajouter d'autres propriétés si nécessaire
        if (eventData.isRecurring) {
            formattedData.isRecurring = eventData.isRecurring;
            formattedData.recurrenceRule = eventData.recurrenceRule || '';
        }
        
        return formattedData;
    }
    
    // Exposer l'API publique
    return {
        init: init,
        saveEvent: saveEvent,
        moveEvent: moveEvent,
        deleteEvent: deleteEvent,
        loadEvents: loadEvents
    };
})();