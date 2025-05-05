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
                    
                    // Détecter si on est en vue mensuelle
                    const currentView = calendar.getViewName();
                    const padding = currentView === 'month' ? '0px 2px' : '2px 8px';
                    
                    return `
                        <div class="event-content" data-schedule-id="${schedule.id}" data-calendar-id="${schedule.calendarId}" style="
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
            const isCalendarEvent = targetElement.closest('.tui-full-calendar-time-schedule') || 
                                  targetElement.closest('.tui-full-calendar-time-schedule-content') ||
                                  targetElement.closest('.tui-full-calendar-weekday-schedule') ||
                                  targetElement.closest('.event-content.month-view') ||
                                  targetElement.closest('.tui-full-calendar-weekday-grid-schedule-container');
            
            if (isCalendarEvent) {
                e.preventDefault();
                e.stopPropagation();
                
                const eventElement = targetElement.closest('.tui-full-calendar-time-schedule') || 
                                    targetElement.closest('.tui-full-calendar-weekday-schedule') ||
                                    targetElement.closest('.event-content');
                
                let eventId = null;
                if (eventElement) {
                    eventId = eventElement.getAttribute('data-schedule-id');
                    if (!eventId && targetElement.closest('[data-schedule-id]')) {
                        eventId = targetElement.closest('[data-schedule-id]').getAttribute('data-schedule-id');
                    }
                }
                
                if (eventId) {
                    calendarIds.forEach(calId => {
                        const schedule = calendar.getSchedule(eventId, calId.toString());
                        if (schedule) {
                            const clonedEventData = {
                                title: schedule.title,
                                start: schedule.start,
                                end: schedule.end,
                                calendarId: schedule.calendarId,
                                categoryId: schedule.raw?.categoryId,
                                raw: schedule.raw || {}
                            };
                            
                            openCloneModal(clonedEventData);
                        }
                    });
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
        document.addEventListener('dblclick', function(e) {
            const eventElement = e.target.closest('.tui-full-calendar-time-schedule');
            if (!eventElement) return;
            
            const scheduleId = eventElement.getAttribute('data-schedule-id');
            if (!scheduleId) return;
            
            // Trouver l'événement correspondant
            let foundEvent = null;
            for (const calId of calendarIds) {
                const schedule = calendar.getSchedule(scheduleId, calId.toString());
                if (schedule) {
                    foundEvent = schedule;
                    break;
                }
            }
            
            if (foundEvent) {
                e.preventDefault();
                e.stopPropagation();
                
                openEditModal({
                    id: foundEvent.id,
                    title: foundEvent.title,
                    start: foundEvent.start,
                    end: foundEvent.end,
                    calendarId: foundEvent.calendarId,
                    raw: foundEvent.raw || {}
                });
            }
        }, false);
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
        requestArrowHandlersUpdate(300);
    }
    
    /**
     * Fonction pour enregistrer un événement (création ou modification)
     */
    function saveEvent(calendar) {
        const title = document.getElementById('eventTitle').value;
        const start = new Date(document.getElementById('eventStart').value);
        const end = new Date(document.getElementById('eventEnd').value);
        const calendarId = document.getElementById('eventCalendar').value;
        const categoryId = document.getElementById('eventCategory').value;

        console.log('--- DÉBUT LOGS DE DÉBOGAGE ---');
        console.log('calendarId:', calendarId);
        console.log('calendrier sélectionné:', document.getElementById('eventCalendar').selectedOptions[0].text);
        console.log('categoryId:', categoryId);
        console.log('catégorie sélectionnée:', document.getElementById('eventCategory').selectedOptions[0].text);
        
        if (!title) {
            alert('Veuillez entrer un titre pour l\'événement');
            return;
        }
        
        // Récupérer les couleurs de la catégorie sélectionnée
        const categorySelect = document.getElementById('eventCategory');
        let categoryColor = '#FFFFFF';
        let categoryTextColor = '#000000';
        for(let i = 0; i < categorySelect.options.length; i++) {
            if(categorySelect.options[i].value === categoryId) {
                categoryColor = categorySelect.options[i].style.backgroundColor || '#FFFFFF';
                categoryTextColor = categorySelect.options[i].style.color || '#000000';
                console.log('Catégorie trouvée:', categorySelect.options[i].text);
                console.log('Couleur de texte récupérée:', categoryTextColor);
                break;
            }
        }

        // Récupérer la couleur du calendrier
        const calendarSelect = document.getElementById('eventCalendar');
        let calendarColor = '#FFFFFF';
        for(let i = 0; i < calendarSelect.options.length; i++) {
            if(calendarSelect.options[i].value === calendarId) {
                calendarColor = calendarSelect.options[i].style.backgroundColor || '#FFFFFF';
                console.log('Calendrier trouvé:', calendarSelect.options[i].text);
                break;
            }
        }

        console.log('calendarColor:', calendarColor);
        console.log('categoryColor:', categoryColor);

        // Vérifier si on est en mode création ou édition
        const eventId = document.getElementById('editEventId').value;
        console.log('eventId:', eventId, eventId ? '(Mode édition)' : '(Mode création)');

        // Préparer les données pour le backend
        const eventData = {
            id: eventId || null,
            title: title,
            start: start,
            end: end,
            calendarId: calendarId,
            categoryId: categoryId,
            isAllDay: false,
            category: 'time',
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
                } else {
                    // Mise à jour simple
                    calendar.updateSchedule(eventId, calendarId, {
                        title: title,
                        start: start,
                        end: end,
                        raw: eventData.raw
                    });
                }
            }
            
            calendar.render();
            requestArrowHandlersUpdate(300);
            
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
        console.log(`Clic sur flèche ${direction} détecté pour l'événement:`, scheduleData.title);
        
        const dayOffset = direction === 'left' ? -1 : 1;
        
        const newStart = new Date(scheduleData.start);
        newStart.setDate(newStart.getDate() + dayOffset);
        
        const newEnd = new Date(scheduleData.end);
        newEnd.setDate(newEnd.getDate() + dayOffset);
        
        console.log("Création d'une copie avec dates:", formatDateForInput(newStart), "à", formatDateForInput(newEnd));
        
        // Préparer les données pour le backend
        const newEventData = {
            title: scheduleData.title,
            calendarId: scheduleData.calendarId,
            start: newStart,
            end: newEnd,
            isAllDay: scheduleData.isAllDay,
            category: scheduleData.category,
            categoryId: scheduleData.raw?.categoryId,
            raw: scheduleData.raw
        };
        
        // Envoyer au backend et mettre à jour l'UI
        CalendarBackend.saveEvent(newEventData, function(error, response) {
            if (error) {
                console.error("Erreur lors de la création de la copie:", error);
                return;
            }
            
            try {
                // Créer l'événement dans le calendrier UI avec l'ID renvoyé par le serveur
                calendar.createSchedules([{
                    id: response.id || String(new Date().getTime()),
                    calendarId: scheduleData.calendarId,
                    title: scheduleData.title,
                    start: newStart,
                    end: newEnd,
                    isAllDay: scheduleData.isAllDay,
                    category: scheduleData.category,
                    raw: scheduleData.raw
                }]);
                console.log("Copie créée avec succès");
                
                // Mettre à jour les flèches
                requestArrowHandlersUpdate(300);
            } catch (error) {
                console.error("Erreur lors de la création de la copie dans l'UI:", error);
            }
        });
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
            const events = document.querySelectorAll('.tui-full-calendar-time-schedule');
            console.log(`Ajout de flèches pour ${events.length} événements`);
            
            events.forEach(event => {
                const scheduleId = event.getAttribute('data-schedule-id');
                if (!scheduleId) return;
                
                // Trouver l'élément de contenu où nous insérerons les flèches
                const contentElement = event.querySelector('.event-content') || event;
                
                // Vérifier si les flèches existent déjà pour éviter les doublons
                if (contentElement.querySelector('.event-extension-arrow')) return;
                
                // Chercher les données de l'événement
                let scheduleData = null;
                let foundCalendarId = null;
                
                for (const calId of calendarIds) {
                    const schedule = calendar.getSchedule(scheduleId, calId.toString());
                    if (schedule) {
                        foundCalendarId = calId.toString();
                        
                        scheduleData = {
                            id: schedule.id,
                            calendarId: schedule.calendarId,
                            title: schedule.title,
                            start: schedule.start._date.toISOString(),
                            end: schedule.end._date.toISOString(),
                            isAllDay: schedule.isAllDay,
                            category: schedule.category,
                            raw: schedule.raw || {}
                        };
                        break;
                    }
                }
                
                if (!foundCalendarId || !scheduleData) return;
                
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
            });
        }, 100);
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
        modalTitle.textContent = 'Modifier un événement';
        deleteEventBtn.classList.remove('d-none');
        editEventId.value = eventData.id;
        document.getElementById('eventTitle').value = eventData.title;
        document.getElementById('eventStart').value = formatDateForInput(eventData.start);
        document.getElementById('eventEnd').value = formatDateForInput(eventData.end);
        document.getElementById('originalCalendarId').value = eventData.calendarId;
        document.getElementById('eventCalendar').value = eventData.calendarId;
        
        if (eventData.raw && eventData.raw.categoryId) {
            document.getElementById('eventCategory').value = eventData.raw.categoryId;
        }
        
        modal.show();
    }
    
    window.openCloneModal = function(eventData) {
        modalTitle.textContent = 'Dupliquer un événement';
        deleteEventBtn.classList.add('d-none');
        editEventId.value = '';
        document.getElementById('originalCalendarId').value = eventData.calendarId;
        document.getElementById('eventTitle').value = eventData.title;
        document.getElementById('eventStart').value = formatDateForInput(eventData.start);
        document.getElementById('eventEnd').value = formatDateForInput(eventData.end);
        document.getElementById('eventCalendar').value = eventData.calendarId;
        
        if (eventData.raw && eventData.raw.categoryId) {
            document.getElementById('eventCategory').value = eventData.raw.categoryId;
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
    
    /**
     * Fonction utilitaire de formatage
     */
    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
});