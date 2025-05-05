/**
 * Script principal du calendrier TUI
 * Ce fichier contient toute la logique JavaScript pour le calendrier
 */

document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let attachHandlersTimeout = null;
    let arrowClickInProgress = false; 
    
    // Initialiser le backend
    CalendarBackend.init({
        baseUrl: '/tui_calendar/'
    });
    
    // Récupérer les données du calendrier et des catégories injectées par PHP
    const calendarData = window.calendarData || [];
    const calendarIds = window.calendarIds || [];
    
    // Ajouter les styles pour les flèches d'extension
    addExtensionArrowStyles();
    
    // Initialiser le calendrier
    const calendar = initializeCalendar(calendarData);
    
    // Attacher tous les gestionnaires d'événements
    attachEventHandlers(calendar, calendarIds);
    
    // Initialiser l'interface utilisateur
    updateCalendarHeader(calendar);
    updateViewButtons('week');
    
    // Initialiser jQuery datepicker
    initializeDatepicker(calendar);
    
    // Initialiser les modaux
    initializeModals(calendar);
    
    // Charger les événements initiaux depuis le backend
    loadInitialEvents(calendar);
    
    // Attacher les flèches après le chargement initial
    setTimeout(() => attachArrowHandlers(calendar, calendarIds), 500);
    
    /**
     * Charge les événements initiaux depuis le backend
     */
    function loadInitialEvents(calendar) {
        // Calculer les dates de début et de fin pour le mois en cours
        const currentDate = calendar.getDate();
        const start = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
        const end = new Date(currentDate.getFullYear(), currentDate.getMonth() + 2, 0);
        
        CalendarBackend.loadEvents(start, end, function(error, events) {
            if (error) {
                console.error("Erreur lors du chargement des événements:", error);
                return;
            }
            
            if (events && events.length) {
                // Convertir les événements au format TUI Calendar et les ajouter
                const schedules = events.map(event => {
                    return {
                        id: event.id,
                        calendarId: event.calendarId,
                        title: event.title,
                        start: new Date(event.start),
                        end: new Date(event.end),
                        isAllDay: event.isAllDay,
                        category: event.isAllDay ? 'allday' : 'time',
                        raw: {
                            calendarColor: event.calendarColor,
                            categoryColor: event.categoryColor,
                            categoryTextColor: event.categoryTextColor,
                            categoryId: event.categoryId,
                            location: event.location,
                            body: event.body
                        }
                    };
                });
                
                calendar.createSchedules(schedules);
                calendar.render();
                
                // Attacher les flèches après le chargement des événements
                setTimeout(() => attachArrowHandlers(calendar, calendarIds), 300);
            }
        });
    }
    
    /**
     * Ajoute les styles CSS pour les flèches d'extension
     */
    function addExtensionArrowStyles() {
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            .event-extension-arrow {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 30px;
                height: 30px;
                background-color: rgba(255, 255, 255, 0.9);
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                cursor: pointer;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.2s, transform 0.2s;
            }
            
            .event-extension-arrow.left {
                left: -15px;
            }
            
            .event-extension-arrow.right {
                right: -15px;
            }
            
            .tui-full-calendar-time-schedule:hover .event-extension-arrow,
            .event-content:hover .event-extension-arrow {
                opacity: 1;
            }
            
            .event-extension-arrow:hover {
                transform: translateY(-50%) scale(1.2);
                opacity: 1;
            }
        `;
        document.head.appendChild(styleElement);
    }
    
    /**
     * Initialise l'instance TUI Calendar
     */
    function initializeCalendar(calendarData) {
        return new tui.Calendar('#calendar', {
            defaultView: 'week',
            taskView: false,
            scheduleView: ['time', 'allday'],
            useCreationPopup: false,
            useDetailPopup: false,
            calendars: calendarData,
            week: {
                hourStart: 0,
                hourEnd: 24,
                startDayOfWeek: 1,
                daynames: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                narrowWeekend: false,
                showTimezoneCollapseButton: false,
                timezonesCollapsed: false,
                currentTimeIndicator: false
            },
            month: {
                startDayOfWeek: 1,
                daynames: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']
            },
            template: {
                allday: function(schedule) {
                    return schedule.title;
                },
                alldayTitle: function() {
                    return '<div style="text-align: center; width: 100%;">Toute la journée</div>';
                },
                time: function(schedule) {
                    const calColor = schedule.raw?.calendarColor || '#333';
                    const catColor = schedule.raw?.categoryColor || '#999';
                    const textColor = schedule.raw?.categoryTextColor || '#000000';
                    const scheduleId = schedule.id;
                    const calendarId = schedule.calendarId;
                    const scheduleDataJson = encodeURIComponent(JSON.stringify(schedule));
                    const currentView = calendar.getViewName();
                    const padding = currentView === 'month' ? '0px 2px' : '2px 8px';

                    return `
                        <div class="event-content" data-schedule-id="${scheduleId}" data-calendar-id="${calendarId}" style="
                            position: relative;
                            width: 100%;
                            height: 100%;
                            box-sizing: border-box;
                            border: ${currentView === 'month' ? '4px solid ' : '8px solid '} ${calColor};
                            background-color: ${catColor};
                            color: ${textColor};
                        ">
                            <div style="padding: ${padding};">
                                ${schedule.title}
                            </div>
                            <div class="event-extension-arrow left" 
                                 data-schedule-id="${scheduleId}" 
                                 data-calendar-id="${calendarId}" 
                                 data-schedule-data="${scheduleDataJson}">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                            <div class="event-extension-arrow right" 
                                 data-schedule-id="${scheduleId}" 
                                 data-calendar-id="${calendarId}" 
                                 data-schedule-data="${scheduleDataJson}">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    `;
                },
                monthGridSchedule: function(schedule) {
                    const calColor = schedule.raw?.calendarColor || '#333';
                    const catColor = schedule.raw?.categoryColor || '#999';
                    const textColor = schedule.raw?.categoryTextColor || '#000000';
                    
                    return `
                        <div class="event-content month-view" data-schedule-id="${schedule.id}" data-calendar-id="${schedule.calendarId}" style="
                            position: relative;
                            width: 100%;
                            height: 100%;
                            box-sizing: border-box;
                            border-left: 4px solid ${calColor};
                            background-color: ${catColor};
                            color: ${textColor};
                        ">
                            <div style="padding: 0px 2px;">
                                ${schedule.title}
                            </div>
                        </div>
                    `;
                }
            }
        });
    }
    
    /**
     * Attache tous les gestionnaires d'événements
     */
    function attachEventHandlers(calendar, calendarIds) {
        // Gestionnaire centralisé pour les demandes de mise à jour des flèches
        window.requestArrowHandlersUpdate = function(delay = 300) {
            if (attachHandlersTimeout) {
                clearTimeout(attachHandlersTimeout);
            }
            
            // Toujours nettoyer immédiatement les flèches existantes
            removeAllArrows();
            
            attachHandlersTimeout = setTimeout(function() {
                attachArrowHandlers(calendar, calendarIds);
                attachHandlersTimeout = null;                
            }, delay);
        };
        
        // Clic sur les flèches d'extension
        document.addEventListener('click', function(e) {
            const arrow = e.target.closest('.event-extension-arrow');
            if (arrow) {
                // Définir un flag global avec une plus longue durée
                arrowClickInProgress = true;
                setTimeout(() => { arrowClickInProgress = false; }, 300);
                
                // S'assurer que l'événement ne se propage pas
                e.stopPropagation();
                e.stopImmediatePropagation();
                e.preventDefault();
                
                const isLeft = arrow.classList.contains('left');
                const cachedData = arrow.getAttribute('data-schedule-data');
                
                if (cachedData) {
                    try {
                        const scheduleData = JSON.parse(cachedData);
                        handleArrowClick(calendar, isLeft ? 'left' : 'right', scheduleData);
                    } catch (error) {
                        console.error("Erreur de parsing des données:", error);
                    }
                }
                
                return false;
            }
        }, true);
        
        // Clic droit sur les événements (menu contextuel)
        document.addEventListener('contextmenu', function(e) {
            const targetElement = e.target;
            const eventElement = targetElement.closest('.tui-full-calendar-time-schedule') || 
                                 targetElement.closest('.event-content') ||
                                 targetElement.closest('.tui-full-calendar-weekday-schedule');
            
            if (eventElement) {
                e.preventDefault();
                e.stopPropagation();
                
                const scheduleId = eventElement.getAttribute('data-schedule-id');
                if (!scheduleId) {
                    console.warn('Clic droit sur événement sans ID');
                    return false;
                }
                
                console.log('Clic droit sur événement:', scheduleId);
                
                // SOLUTION : Recherche améliorée de l'événement, similaire au double-clic
                let foundEvent = null;
                
                // 1. D'abord, essayer de trouver dans les calendriers spécifiques
                for (const calId of calendarIds) {
                    try {
                        const schedule = calendar.getSchedule(scheduleId, calId.toString());
                        if (schedule) {
                            foundEvent = schedule;
                            break;
                        }
                    } catch (err) {
                        // Ignorer les erreurs et continuer la recherche
                    }
                }
                
                // 2. Si rien n'est trouvé, chercher dans tous les événements affichés
                if (!foundEvent) {
                    try {
                        // Pour les nouveaux événements, leur ID peut être dans l'élément DOM
                        const calendarId = eventElement.getAttribute('data-calendar-id');
                        if (calendarId) {
                            const schedule = calendar.getSchedule(scheduleId, calendarId);
                            if (schedule) {
                                foundEvent = schedule;
                            }
                        }
                    } catch (err) {
                        // Ignorer les erreurs
                    }
                }
                
                // 3. En dernier recours, extraire les données des attributs HTML
                if (!foundEvent && eventElement) {
                    // Récupérer les informations de style pour les couleurs
                    const style = window.getComputedStyle(eventElement);
                    const titleElement = eventElement.querySelector('div');
                    
                    // Créer un événement synthétique à partir des données DOM
                    foundEvent = {
                        id: scheduleId,
                        calendarId: eventElement.getAttribute('data-calendar-id') || 
                                    document.getElementById('eventCalendar').value,
                        title: titleElement ? titleElement.innerText : 'Sans titre',
                        // Récupérer les dates depuis le serveur ou utiliser une approximation
                        start: new Date(),
                        end: new Date(new Date().getTime() + 3600000), // +1 heure par défaut
                        raw: {
                            // Utiliser les couleurs calculées
                            calendarColor: style.borderColor || '#333',
                            categoryColor: style.backgroundColor || '#fff',
                            categoryTextColor: style.color || '#000',
                            categoryId: document.getElementById('eventCategory').value || '1'
                        }
                    };
                    
                    // Essayer de récupérer les données complètes via une requête AJAX
                    CalendarBackend.getEvent(scheduleId, foundEvent.calendarId, function(error, eventData) {
                        if (!error && eventData) {
                            // Si on a réussi à récupérer les données, ouvrir le modal avec ces données
                            openCloneModal({
                                title: eventData.title,
                                start: new Date(eventData.start),
                                end: new Date(eventData.end),
                                calendarId: eventData.calendarId,
                                categoryId: eventData.raw?.categoryId,
                                raw: eventData.raw || {}
                            });
                        }
                    });
                }
                
                if (foundEvent) {
                    openCloneModal({
                        title: foundEvent.title,
                        start: foundEvent.start instanceof Date ? foundEvent.start : 
                              (foundEvent.start && foundEvent.start._date ? foundEvent.start._date : new Date(foundEvent.start)),
                        end: foundEvent.end instanceof Date ? foundEvent.end : 
                            (foundEvent.end && foundEvent.end._date ? foundEvent.end._date : new Date(foundEvent.end)),
                        calendarId: foundEvent.calendarId,
                        categoryId: foundEvent.raw?.categoryId,
                        raw: foundEvent.raw || {}
                    });
                } else {
                    console.warn('Événement non trouvé pour le clic droit:', scheduleId);
                }
                
                return false;
            }
        }, true);
        
        // Navigation entre les vues
        document.getElementById('day-view').addEventListener('click', () => {
            removeAllArrows();
            calendar.changeView('day');
            updateViewButtons('day');
            updateCalendarHeader(calendar);
        });
        
        document.getElementById('week-view').addEventListener('click', () => {
            calendar.changeView('week');
            updateViewButtons('week');
            updateCalendarHeader(calendar);
            requestArrowHandlersUpdate(500);
        });
        
        document.getElementById('month-view').addEventListener('click', () => {
            removeAllArrows();
            calendar.changeView('month');
            updateViewButtons('month');
            updateCalendarHeader(calendar);
        });
        
        // Boutons de navigation
        document.getElementById('prev-btn').addEventListener('click', () => {
            removeAllArrows();
            calendar.prev();
            updateCalendarHeader(calendar);
            
            // Vérifier si nous devons charger plus d'événements
            const currentDate = calendar.getDate();
            const startOfView = new Date(currentDate);
            startOfView.setDate(1); // Premier jour du mois
            
            // Charger les événements du mois précédent
            CalendarBackend.loadEvents(
                new Date(startOfView.getFullYear(), startOfView.getMonth() - 1, 1),
                startOfView,
                function(error, events) {
                    if (error || !events || !events.length) return;
                    
                    // Ajouter les événements au calendrier
                    addEventsToCalendar(calendar, events);
                }
            );
            
            if (calendar.getViewName() === 'week') {
                requestArrowHandlersUpdate(500);
            }
        });
        
        document.getElementById('next-btn').addEventListener('click', () => {
            removeAllArrows();
            calendar.next(); 
            updateCalendarHeader(calendar);
            
            // Vérifier si nous devons charger plus d'événements
            const currentDate = calendar.getDate();
            const endOfView = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0); // Dernier jour du mois
            
            // Charger les événements du mois suivant
            CalendarBackend.loadEvents(
                new Date(endOfView.getFullYear(), endOfView.getMonth() + 1, 1),
                new Date(endOfView.getFullYear(), endOfView.getMonth() + 2, 0),
                function(error, events) {
                    if (error || !events || !events.length) return;
                    
                    // Ajouter les événements au calendrier
                    addEventsToCalendar(calendar, events);
                }
            );
            
            if (calendar.getViewName() === 'week') {
                requestArrowHandlersUpdate(500);
            }
        });
        
        document.getElementById('today-btn').addEventListener('click', () => {
            removeAllArrows();
            calendar.today();
            updateCalendarHeader(calendar);
            
            if (calendar.getViewName() === 'week') {
                requestArrowHandlersUpdate(500);
            }
        });
        
        // Gestionnaires TUI Calendar
        calendar.on('afterRenderSchedule', function() {
            requestArrowHandlersUpdate(300);
        });

        calendar.on('clickSchedule', function(e) {
            // Désactiver complètement le comportement par défaut du clic simple
            e.preventDefault && e.preventDefault();
            // Ne rien faire d'autre - l'édition se fera uniquement par double-clic
        });
        
        calendar.on('beforeCreateSchedule', function(eventObj) {
            const startDate = new Date(eventObj.start);
            const formattedStart = formatDateForInput(startDate);
            
            const endDate = new Date(eventObj.end || new Date(startDate.getTime() + 60 * 60 * 1000));
            const formattedEnd = formatDateForInput(endDate);
            
            document.getElementById('eventTitle').value = '';
            document.getElementById('eventStart').value = formattedStart;
            document.getElementById('eventEnd').value = formattedEnd;
            document.getElementById('eventCalendar').value = eventObj.calendarId || '1';
            
            openCreateModal();
        });
        
        calendar.on('beforeUpdateSchedule', function(e) {
            const schedule = e.schedule;
            const changes = e.changes;
            
            console.log('Événement redimensionné ou déplacé:', schedule);
            console.log('Modifications:', changes);
            
            if (changes && (changes.start || changes.end)) {
                // Mettre à jour localement
                calendar.updateSchedule(schedule.id, schedule.calendarId, changes);
                
                // Envoyer au backend
                const moveData = {
                    id: schedule.id,
                    calendarId: schedule.calendarId,
                    start: changes.start || schedule.start,
                    end: changes.end || schedule.end
                };
                
                CalendarBackend.moveEvent(moveData, function(error, response) {
                    if (error) {
                        console.error('Erreur lors du déplacement:', error);
                        // Vous pourriez revenir à l'état précédent si nécessaire
                    }
                });
                
                console.log('Nouvel horaire:', 
                    changes.start ? formatDateForInput(changes.start) : formatDateForInput(schedule.start), 
                    'à', 
                    changes.end ? formatDateForInput(changes.end) : formatDateForInput(schedule.end)
                );
            }
        });

        // Gestionnaires pour déplacements et redimensionnements
        calendar.on('moveSchedule', function(event) {
            console.log('Événement déplacé');
            if (calendar.getViewName() !== 'week') {
                removeAllArrows();
                return;
            }
            
            // Au lieu de mettre à jour les positions, recréer toutes les flèches
            requestArrowHandlersUpdate(300);
        });

        calendar.on('resizeSchedule', function(event) {
            console.log('Événement redimensionné');
            if (calendar.getViewName() !== 'week') {
                removeAllArrows();
                return;
            }
            
            // Au lieu de mettre à jour les positions, recréer toutes les flèches
            requestArrowHandlersUpdate(300);
        });        
        
        // Gestionnaire du bouton Enregistrer
        document.getElementById('saveEventBtn').onclick = function() {
            saveEvent(calendar);
        };
        
        // Gestionnaire du bouton Supprimer
        document.getElementById('deleteEventBtn').onclick = function() {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            confirmModal.show();
        };
        
        // Gestionnaire de confirmation de suppression
        document.getElementById('confirmDeleteEventBtn').onclick = function() {
            const eventId = document.getElementById('editEventId').value;
            const calendarId = document.getElementById('eventCalendar').value;

            if (eventId) {
                // D'abord appeler le backend
                CalendarBackend.deleteEvent(eventId, calendarId, function(error, response) {
                    if (!error) {
                        // Supprimer localement si le backend a réussi
                        calendar.deleteSchedule(eventId, calendarId);
                    } else {
                        console.error('Erreur lors de la suppression de l\'événement:', error);
                    }
                    
                    // Fermer les modals quelle que soit la réponse
                    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                    const mainEventModal = bootstrap.Modal.getInstance(document.getElementById('createEventModal'));
                    confirmModal.hide();
                    mainEventModal.hide();
                });
            }
        };

        // Gestionnaire pour le double-clic (édition d'événement)
        document.querySelector('#calendar').addEventListener('dblclick', function(e) {
            // Si l'utilisateur clique sur un événement
            const eventElement = e.target.closest('.tui-full-calendar-time-schedule') || 
                                e.target.closest('.event-content') ||
                                e.target.closest('.tui-full-calendar-weekday-schedule');
            
            if (!eventElement) return;
            
            const scheduleId = eventElement.getAttribute('data-schedule-id');
            const calendarId = eventElement.getAttribute('data-calendar-id');
            
            if (!scheduleId) {
                console.warn('Double-clic sur événement sans ID');
                return;
            }
            
            console.log('Double-clic sur événement:', scheduleId);
            e.preventDefault();
            e.stopPropagation();
            
            // Toujours récupérer directement depuis le backend
            CalendarBackend.getEvent(scheduleId, calendarId, function(error, eventData) {
                if (error || !eventData) {
                    console.error("Erreur lors de la récupération des données de l'événement pour édition:", error);
                    
                    // Fallback - essayer de récupérer depuis le frontend
                    let foundEvent = null;
                    for (const calId of calendarIds) {
                        try {
                            const schedule = calendar.getSchedule(scheduleId, calId.toString());
                            if (schedule) {
                                foundEvent = schedule;
                                break;
                            }
                        } catch (err) {
                            // Ignorer les erreurs et continuer
                        }
                    }
                    
                    if (foundEvent) {
                        openEditModal({
                            id: foundEvent.id,
                            title: foundEvent.title,
                            start: foundEvent.start instanceof Date ? foundEvent.start : 
                                (foundEvent.start && foundEvent.start._date ? foundEvent.start._date : new Date(foundEvent.start)),
                            end: foundEvent.end instanceof Date ? foundEvent.end : 
                                (foundEvent.end && foundEvent.end._date ? foundEvent.end._date : new Date(foundEvent.end)),
                            calendarId: foundEvent.calendarId,
                            raw: foundEvent.raw || {}
                        });
                    } else {
                        console.warn('Événement non trouvé pour édition:', scheduleId);
                    }
                    
                    return;
                }
                
                // Si on a réussi à récupérer les données depuis le backend, ouvrir le modal directement
                openEditModal(eventData);
            });
        }, true);
    }
    
    /**
     * Ajoute des événements au calendrier depuis les données backend
     */
    function addEventsToCalendar(calendar, events) {
        if (!events || !events.length) return;
        
        const schedules = events.map(event => {
            return {
                id: event.id,
                calendarId: event.calendarId,
                title: event.title,
                start: new Date(event.start),
                end: new Date(event.end),
                isAllDay: event.isAllDay,
                category: event.isAllDay ? 'allday' : 'time',
                raw: {
                    calendarColor: event.calendarColor,
                    categoryColor: event.categoryColor,
                    categoryTextColor: event.categoryTextColor,
                    categoryId: event.categoryId,
                    location: event.location,
                    body: event.body
                }
            };
        });
        
        calendar.createSchedules(schedules);
        calendar.render();
        setTimeout(() => requestArrowHandlersUpdate(0), 200);
    }
    
    /**
     * Fonction pour enregistrer un événement (création, duplication ou modification)
     * @param {Object} calendar - Instance TUI Calendar
     */
    function saveEvent(calendar) {
        // Récupérer les valeurs du formulaire
        const title = document.getElementById('eventTitle').value;
        const startInput = document.getElementById('eventStart').value;
        const endInput = document.getElementById('eventEnd').value;
        const calendarId = document.getElementById('eventCalendar').value;
        const categoryId = document.getElementById('eventCategory').value;
        const eventId = document.getElementById('editEventId').value;
        
        // Vérifier si c'est un événement dupliqué
        const isDuplicated = document.getElementById('isDuplicatedEvent')?.value === 'true';
        
        console.log('--- DÉBUT LOGS DE DÉBOGAGE ---');
        console.log('Mode:', eventId ? 'Édition' : 'Création');
        console.log('Est duplication:', isDuplicated ? 'Oui' : 'Non');
        console.log('Date début (input):', startInput);
        console.log('Date fin (input):', endInput);
    
        // Validation de base
        if (!title) {
            alert('Veuillez entrer un titre pour l\'événement');
            return;
        }
        
        // Créer des objets Date à partir des chaînes
        const start = new Date(startInput);
        const end = new Date(endInput);
        
        console.log('Date début (objet):', start.toISOString());
        console.log('Date fin (objet):', end.toISOString());
        
        // Récupérer les couleurs de la catégorie sélectionnée
        let categoryColor = '#FFFFFF';
        let categoryTextColor = '#000000';
        const categorySelect = document.getElementById('eventCategory');
        
        for(let i = 0; i < categorySelect.options.length; i++) {
            if(categorySelect.options[i].value === categoryId) {
                categoryColor = categorySelect.options[i].style.backgroundColor || '#FFFFFF';
                categoryTextColor = categorySelect.options[i].style.color || '#000000';
                console.log('Catégorie trouvée:', categorySelect.options[i].text);
                break;
            }
        }
        
        // Récupérer la couleur du calendrier
        let calendarColor = '#FFFFFF';
        const calendarSelect = document.getElementById('eventCalendar');
        
        for(let i = 0; i < calendarSelect.options.length; i++) {
            if(calendarSelect.options[i].value === calendarId) {
                calendarColor = calendarSelect.options[i].style.backgroundColor || '#FFFFFF';
                console.log('Calendrier trouvé:', calendarSelect.options[i].text);
                break;
            }
        }
        
        // Préparer les données pour le backend (SANS forçage de category: 'allday')
        const eventData = {
            id: eventId || null,
            title: title,
            start: start,
            end: end,
            calendarId: calendarId,
            categoryId: categoryId,
            isDuplicated: isDuplicated,
            raw: {
                calendarColor: calendarColor,
                categoryColor: categoryColor,
                categoryTextColor: categoryTextColor,
                categoryId: categoryId
            }
        };
        
        // Envoyer au backend
        CalendarBackend.saveEvent(eventData, function(error, response) {
            if (error) {
                console.error('Erreur lors de l\'enregistrement:', error);
                return;
            }
            
            // Si c'est une création, utiliser l'ID renvoyé par le serveur
            const newId = response.id || String(new Date().getTime());
            
            // Mise à jour de l'UI du calendrier
            if (!eventId) {
                // MODE CRÉATION
                calendar.createSchedules([{
                    id: newId,
                    calendarId: calendarId,
                    title: title,
                    start: start,
                    end: end,
                    isAllDay: false,
                    category: 'time',
                    raw: eventData.raw
                }]);
                
                console.log('Événement créé avec succès:', newId);
            } else {
                // MODE ÉDITION
                const originalCalendarId = document.getElementById('originalCalendarId').value;
                
                if (originalCalendarId !== calendarId) {
                    // Changement de calendrier - supprimer l'ancien et créer le nouveau
                    calendar.deleteSchedule(eventId, originalCalendarId);
                    
                    calendar.createSchedules([{
                        id: eventId,
                        calendarId: calendarId,
                        title: title,
                        start: start,
                        end: end,
                        isAllDay: false,
                        category: 'time',
                        raw: eventData.raw
                    }]);
                    
                    console.log('Événement déplacé vers un autre calendrier:', originalCalendarId, '->', calendarId);
                } else {
                    // Mise à jour simple
                    calendar.updateSchedule(eventId, calendarId, {
                        title: title,
                        start: start,
                        end: end,
                        raw: eventData.raw
                    });
                    
                    console.log('Événement mis à jour avec succès:', eventId);
                }
            }
            
            // Rafraîchir l'affichage
            calendar.render();
            requestArrowHandlersUpdate(300);
            
            // Réinitialiser le flag de duplication
            if (document.getElementById('isDuplicatedEvent')) {
                document.getElementById('isDuplicatedEvent').value = 'false';
            }
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createEventModal'));
            modal.hide();
        });
        
        console.log('--- FIN LOGS DE DÉBOGAGE ---');
    }
    
    /**
     * Gère les clics sur les flèches d'extension
     */
    function handleArrowClick(calendar, direction, scheduleData) {
        console.log(`Clic sur flèche ${direction} détecté pour l'événement:`, scheduleData);
        
        // Vérification des données minimales requises
        if (!scheduleData || !scheduleData.id || !scheduleData.calendarId) {
            console.error("Données insuffisantes pour la duplication par flèche:", scheduleData);
            return;
        }
        
        const dayOffset = direction === 'left' ? -1 : 1;
        
        // Récupérer l'événement depuis le backend
        CalendarBackend.getEvent(scheduleData.id, scheduleData.calendarId, function(error, eventData) {
            if (error) {
                console.error("Erreur lors de la récupération des données:", error);
                
                // Fallback: utiliser les données disponibles
                if (scheduleData.start && scheduleData.end) {
                    duplicateWithLocalData(scheduleData);
                }
                return;
            }
            
            // Utiliser les données du backend
            duplicateWithBackendData(eventData);
        });
        
        // Fonction pour dupliquer avec les données du backend
        function duplicateWithBackendData(eventData) {
            // Extraire les dates en tant qu'objets Date
            const startDate = new Date(eventData.start);
            const endDate = new Date(eventData.end);
            
            // Ajouter le décalage de jour
            startDate.setDate(startDate.getDate() + dayOffset);
            endDate.setDate(endDate.getDate() + dayOffset);
            
            console.log("Création d'une copie avec dates:", startDate, "à", endDate);
            
            // Créer le nouvel événement
            const newEventData = {
                title: eventData.title,
                calendarId: eventData.calendarId,
                start: startDate,
                end: endDate,
                isAllDay: eventData.isAllDay,
                category: eventData.category,
                categoryId: eventData.raw?.categoryId,
                raw: eventData.raw
            };
            
            createDuplicateEvent(newEventData);
        }
        
        // Fonction pour dupliquer avec les données locales (fallback)
        function duplicateWithLocalData(data) {
            // Extraire les dates, en gérant le cas TZDate
            const getDateFromScheduleDate = function(dateValue) {
                if (dateValue && dateValue._date) return new Date(dateValue._date);
                if (dateValue instanceof Date) return new Date(dateValue);
                return new Date(dateValue);
            };
            
            const startDate = getDateFromScheduleDate(data.start);
            const endDate = getDateFromScheduleDate(data.end);
            
            // Ajouter le décalage de jour
            startDate.setDate(startDate.getDate() + dayOffset);
            endDate.setDate(endDate.getDate() + dayOffset);
            
            console.log("Création d'une copie (fallback) avec dates:", startDate, "à", endDate);
            
            // Créer le nouvel événement
            const newEventData = {
                title: data.title,
                calendarId: data.calendarId,
                start: startDate,
                end: endDate,
                isAllDay: data.isAllDay,
                category: data.category,
                categoryId: data.raw?.categoryId,
                raw: data.raw
            };
            
            createDuplicateEvent(newEventData);
        }
        
        // Fonction commune pour créer l'événement
        function createDuplicateEvent(eventData) {
            
            // Envoyer au backend
            CalendarBackend.saveEvent(eventData, function(error, response) {
                if (error) {
                    console.error("Erreur lors de la création de la copie:", error);
                    return;
                }
                
                // Ajouter au calendrier frontend
                calendar.createSchedules([{
                    id: response.id || String(new Date().getTime()),
                    calendarId: eventData.calendarId,
                    title: eventData.title,
                    start: eventData.start,
                    end: eventData.end,
                    isAllDay: eventData.isAllDay,
                    category: eventData.category,
                    raw: eventData.raw
                }]);
                
                requestArrowHandlersUpdate(300);
            });
        }
    }
    
    /**
     * Fonctions pour gérer les flèches d'extension
     */
    function removeAllArrows() {
        // Supprimer uniquement les flèches, pas les conteneurs
        document.querySelectorAll('.event-extension-arrow').forEach(arrow => arrow.remove());    
        console.log('Toutes les flèches ont été supprimées');
    }
    
    function attachArrowHandlers(calendar, calendarIds) {
        // TOUJOURS supprimer toutes les flèches existantes d'abord
        removeAllArrows();
        
        const currentView = calendar.getViewName();
        if (currentView !== 'week') {
            return;
        }
        
        addExtensionArrows(calendar, calendarIds);
        console.log('Flèches d\'extension ajoutées');
    }
    
    // Fonction qui crée les flèches pour les événements
    function addExtensionArrows(calendar, calendarIds) {
        const currentView = calendar.getViewName();
        if (currentView !== 'week') return;   
        
        // Attendre que le DOM soit complètement rendu
        setTimeout(() => {
            const events = document.querySelectorAll('.tui-full-calendar-time-schedule,.tui-full-calendar-weekday-schedule');
            console.log(`Ajout de flèches pour ${events.length} événements`);
            
            events.forEach(event => {
                console.log('Traitement de l\'événement DOM:', event);
                const scheduleId = event.getAttribute('data-schedule-id');
                if (!scheduleId) return;
                
                // Trouver l'élément de contenu où nous insérerons les flèches
                const contentElement = event.querySelector('.event-content') || event;
                console.log('Conteneur ciblé pour les flèches:', contentElement);
                // Vérifier si les flèches existent déjà pour éviter les doublons
                if (contentElement.querySelector('.event-extension-arrow')) return;
                
                // Chercher les données de l'événement
                let scheduleData = null;
                let foundCalendarId = null;
                
                for (const calId of calendarIds) {
                    const schedule = calendar.getSchedule(scheduleId, calId.toString());
                    if (schedule) {
                        foundCalendarId = calId.toString();
                        
                        // CORRECTION ICI: Utiliser une fonction sécurisée pour extraire les dates
                        scheduleData = {
                            id: schedule.id,
                            calendarId: schedule.calendarId,
                            title: schedule.title,
                            start: getISOStringFromScheduleDate(schedule.start),
                            end: getISOStringFromScheduleDate(schedule.end),
                            isAllDay: schedule.isAllDay,
                            category: schedule.category,
                            raw: schedule.raw || {}
                        };
                        break;
                    }
                }
                
                if (!foundCalendarId || !scheduleData) return;
                console.log('Ajout des flèches à:', contentElement, 'pour scheduleId:', scheduleId);
                
                const scheduleDataJson = JSON.stringify(scheduleData);
                
                // Style à ajouter au conteneur d'événement
                contentElement.style.position = 'relative';
                
                // Créer la flèche gauche
                const leftArrow = document.createElement('div');
                leftArrow.className = 'event-extension-arrow left';
                leftArrow.innerHTML = '<i class="fas fa-chevron-left"></i>';
                leftArrow.setAttribute('data-schedule-id', scheduleId);
                leftArrow.setAttribute('data-calendar-id', foundCalendarId);
                leftArrow.setAttribute('data-schedule-data', scheduleDataJson);
                
                // Créer la flèche droite
                const rightArrow = document.createElement('div');
                rightArrow.className = 'event-extension-arrow right';
                rightArrow.innerHTML = '<i class="fas fa-chevron-right"></i>';
                rightArrow.setAttribute('data-schedule-id', scheduleId);
                rightArrow.setAttribute('data-calendar-id', foundCalendarId);
                rightArrow.setAttribute('data-schedule-data', scheduleDataJson);
                
                // Les ajouter au conteneur de l'événement
                contentElement.appendChild(leftArrow);
                contentElement.appendChild(rightArrow);
                console.log('Flèches ajoutées pour:', scheduleId);
            });
        }, 100);
    }

    /**
     * Fonction utilitaire pour extraire la date ISO d'un objet schedule de TUI Calendar
     * qui peut avoir différentes structures selon la provenance de l'événement
     */
    function getISOStringFromScheduleDate(scheduleDate) {
        if (!scheduleDate) {
            return formatLocalISOString(new Date());
        }
        
        // Gestion des objets TZDate
        if (scheduleDate && typeof scheduleDate === 'object' && scheduleDate._date) {
            return formatLocalISOString(scheduleDate._date);
        }
        
        if (scheduleDate instanceof Date) {
            return formatLocalISOString(scheduleDate);
        }
        
        if (typeof scheduleDate === 'string') {
            return scheduleDate;
        }
        
        console.warn('Format de date non reconnu', scheduleDate);
        return formatLocalISOString(new Date());
    }
/**
 * Formate une Date en chaîne ISO locale (sans Z)
 */
function formatLocalISOString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
}
    /**
 * Fonction utilitaire pour formater une date pour les inputs HTML
 * Supporte Date standard, TZDate et chaînes ISO
 */
function formatDateForInput(dateValue) {
    if (!dateValue) return '';
    
    // Gestion des objets TZDate de TUI Calendar
    if (dateValue && typeof dateValue === 'object' && dateValue._date) {
        // TZDate contient une propriété _date qui est un Date standard
        return formatDateForInput(dateValue._date);
    }
    
    // Si c'est une chaîne ISO, extraire la partie pertinente
    if (typeof dateValue === 'string') {
        // Pour les chaînes ISO, extraire YYYY-MM-DDThh:mm
        if (dateValue.includes('T')) {
            // Tronquer à YYYY-MM-DDThh:mm
            return dateValue.substring(0, 16);
        }
        
        // Pour les autres formats de chaîne, créer un objet Date
        dateValue = new Date(dateValue);
    }
    
    // Si c'est un objet Date, formater pour l'input datetime-local
    if (dateValue instanceof Date) {
        const year = dateValue.getFullYear();
        const month = String(dateValue.getMonth() + 1).padStart(2, '0');
        const day = String(dateValue.getDate()).padStart(2, '0');
        const hours = String(dateValue.getHours()).padStart(2, '0');
        const minutes = String(dateValue.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    console.error('Format de date invalide:', dateValue);
    return '';
}
    /**
     * Fonctions pour les modaux
     */
    function initializeModals() {
        window.modal = new bootstrap.Modal(document.getElementById('createEventModal'));
        window.modalTitle = document.getElementById('createEventModalLabel');
        window.editEventId = document.getElementById('editEventId');
        window.deleteEventBtn = document.getElementById('deleteEventBtn');
    }
    
    function openCreateModal() {
        modalTitle.textContent = 'Créer un événement';
        deleteEventBtn.classList.add('d-none');
        editEventId.value = ''; 
        
        const calendarSelect = document.getElementById('eventCalendar');   
        
        if (calendarSelect.selectedIndex < 0 && calendarSelect.options.length > 0) {
            calendarSelect.selectedIndex = 0;
        }
        
        modal.show();
    }
    
    function openEditModal(eventData) {
        console.log('Ouverture du modal d\'édition pour:', eventData);
        
        // Toujours récupérer l'événement complet depuis le backend pour avoir les bonnes heures
        CalendarBackend.getEvent(eventData.id, eventData.calendarId, function(error, completeEvent) {
            if (error) {
                console.error("Erreur lors de la récupération des données complètes pour édition:", error);
                // En cas d'erreur, utiliser les données disponibles en fallback
                setupEditModal(eventData);
                return;
            }
            
            // Utiliser les données récupérées du backend (heures exactes)
            console.log('Données récupérées du backend pour édition:', completeEvent);
            setupEditModal(completeEvent, true);
        });
    }
    
    function setupEditModal(event, fromBackend = false) {
        modalTitle.textContent = 'Modifier un événement';
        deleteEventBtn.classList.remove('d-none');
        editEventId.value = event.id;
        document.getElementById('eventTitle').value = event.title;
        document.getElementById('originalCalendarId').value = event.calendarId;
        document.getElementById('eventCalendar').value = event.calendarId;
        
        // Utiliser directement les chaînes ISO pour les inputs
        if (fromBackend && typeof event.start === 'string' && typeof event.end === 'string') {
            // Extraire juste la partie YYYY-MM-DDThh:mm de la chaîne ISO
            document.getElementById('eventStart').value = formatDateForInput(event.start);
            document.getElementById('eventEnd').value = formatDateForInput(event.end);
            
            console.log('Heures ISO utilisées directement:', 
                        document.getElementById('eventStart').value, 
                        document.getElementById('eventEnd').value);
        } else {
            // Fallback au cas où les dates ne sont pas en format chaîne
            document.getElementById('eventStart').value = formatDateForInput(event.start);
            document.getElementById('eventEnd').value = formatDateForInput(event.end);
        }
        
        // Remplir la catégorie
        if (event.raw && event.raw.categoryId) {
            document.getElementById('eventCategory').value = event.raw.categoryId;
        } else if (event.categoryId) {
            document.getElementById('eventCategory').value = event.categoryId;
        }
        
        // Réinitialiser le flag de duplication si présent
        let duplicateFlag = document.getElementById('isDuplicatedEvent');
        if (duplicateFlag) {
            duplicateFlag.value = 'false';
        }
        
        modal.show();
    }
    
    window.openCloneModal = function(eventData) {
        console.log('Ouverture du modal de duplication pour:', eventData);
        
        // Si l'événement n'a pas d'ID ou de calendarId, utiliser directement les données disponibles
        if (!eventData.id || !eventData.calendarId) {
            console.log("Utilisation directe des données disponibles pour la duplication");
            setupCloneModal(eventData);
            return;
        }
        
        // Récupérer l'événement complet depuis le backend
        CalendarBackend.getEvent(eventData.id, eventData.calendarId, function(error, completeEvent) {
            if (error) {
                console.log("Utilisation des données locales pour la duplication");
                // Même en cas d'erreur, continuer avec les données disponibles
                setupCloneModal(eventData);
                return;
            }
            
            // Utiliser les données récupérées du backend (heures exactes)
            console.log('Données récupérées du backend pour duplication:', completeEvent);
            setupCloneModal(completeEvent, true);
        });
    }
    
    function setupCloneModal(event, fromBackend = false) {
        // Configuration du modal
        modalTitle.textContent = 'Dupliquer un événement';
        deleteEventBtn.classList.add('d-none');
        editEventId.value = '';
        
        // Ajouter un indicateur que c'est une duplication
        const form = document.getElementById('createEventModal').querySelector('form');
        let duplicateFlag = document.getElementById('isDuplicatedEvent');
        
        if (!duplicateFlag) {
            duplicateFlag = document.createElement('input');
            duplicateFlag.type = 'hidden';
            duplicateFlag.id = 'isDuplicatedEvent';
            form.appendChild(duplicateFlag);
        }
        duplicateFlag.value = 'true';
        
        // Remplir les champs avec les données de l'événement
        document.getElementById('eventTitle').value = event.title;
        document.getElementById('eventCalendar').value = event.calendarId;
        
        // Utiliser directement les chaînes ISO pour les inputs
        if (fromBackend && typeof event.start === 'string' && typeof event.end === 'string') {
            // Extraire juste la partie YYYY-MM-DDThh:mm de la chaîne ISO
            document.getElementById('eventStart').value = formatDateForInput(event.start);
            document.getElementById('eventEnd').value = formatDateForInput(event.end);
            
            console.log('Heures ISO utilisées directement:', 
                        document.getElementById('eventStart').value, 
                        document.getElementById('eventEnd').value);
        } else {
            // Fallback au cas où les dates ne sont pas en format chaîne
            document.getElementById('eventStart').value = formatDateForInput(event.start);
            document.getElementById('eventEnd').value = formatDateForInput(event.end);
        }
        
        // Remplir la catégorie
        if (event.raw && event.raw.categoryId) {
            document.getElementById('eventCategory').value = event.raw.categoryId;
        } else if (event.categoryId) {
            document.getElementById('eventCategory').value = event.categoryId;
        }
        
        modal.show();
    }

    /**
     * Fonctions d'interface utilisateur
     */
    function updateCalendarHeader(calendar) {
        const currentDate = calendar.getDate();
        const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                       'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        const month = months[currentDate.getMonth()];
        const year = currentDate.getFullYear();
        
        document.getElementById('calendar-date-header').textContent = `${month} ${year}`;
    }
    
    function updateViewButtons(viewName) {
        document.getElementById('day-view').classList.remove('active');
        document.getElementById('week-view').classList.remove('active');
        document.getElementById('month-view').classList.remove('active');
        
        if (viewName === 'day') {
            document.getElementById('day-view').classList.add('active');
        } else if (viewName === 'week') {
            document.getElementById('week-view').classList.add('active');
        } else if (viewName === 'month') {
            document.getElementById('month-view').classList.add('active');
        }
    }
    
    function initializeDatepicker(calendar) {
        jQuery(function($) {
            $('#datepicker').datepicker({
                format: 'dd/mm/yyyy',
                language: 'fr',
                autoclose: true,
                todayHighlight: true
            });
            
            $('#date-picker-btn').click(function(){
                $('#datepicker').datepicker('show');
            });
            
            $('#datepicker').on('changeDate', function(e){
                const selectedDate = e.date;
                calendar.setDate(selectedDate);
                updateCalendarHeader(calendar);
            });
        });
    }
});