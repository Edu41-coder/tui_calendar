<?php
$pageTitle = 'Mon Calendrier';
$includeCalendarAssets = true;

// Transmettre les données PHP au JavaScript
$calendarDataForJS = [];
foreach ($calendars as $cal) {
    $calendarDataForJS[] = [
        'id' => $cal['calendar_id'],
        'name' => $cal['name'],
        'color' => '#ffffff',
        'backgroundColor' => $cal['color'],
        'borderColor' => $cal['color'],
        'dragBackgroundColor' => $cal['color']
    ];
}

// Définir les valeurs par défaut
$defaultView = isset($settings['default_view']) ? $settings['default_view'] : 'week';
$startDayOfWeek = isset($settings['start_day_of_week']) ? $settings['start_day_of_week'] : '0';
$customTheme = isset($settings['custom_theme']) ? json_encode($settings['custom_theme']) : 'null';

// Encoder les données pour JavaScript
$calendarDataJSON = !empty($calendarDataForJS) ? json_encode($calendarDataForJS) : '[]';

// Préparer les paramètres pour JavaScript
$pageScripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, initialisation du calendrier');
    
    const container = document.getElementById('calendar');
    if (!container) {
        console.error("Élément 'calendar' introuvable!");
        return;
    }
    
    console.log('Données des calendriers:', {$calendarDataJSON});
    
    const calendar = new tui.Calendar(container, {
        defaultView: '{$defaultView}',
        taskView: true,
        scheduleView: true,
        useCreationPopup: true,
        useDetailPopup: true,
        calendars: {$calendarDataJSON},
        template: {
            milestone: function(model) {
                return '<span class="calendar-font-icon ic-milestone-b"></span> ' + model.title;
            },
            time: function(schedule) {
                return schedule.title;
            }
        }
    });
    
    console.log('Calendrier initialisé');    
    console.log('Méthodes disponibles dans calendar:', Object.getOwnPropertyNames(Object.getPrototypeOf(calendar)));
    console.log('Version de TUI Calendar:', tui.Calendar.version || 'Non disponible');
    console.log('Composants du calendrier:', calendar.getComponent());
    
    // Ajouter au début de votre fonction DOMContentLoaded, juste après l'initialisation du calendrier
    console.log('Ajout des écouteurs d\'événements de débogage');

    // Variable pour suivre la dernière plage horaire sélectionnée
    let lastSelectedTimeSlot = null;
    // Variable pour stocker le dernier événement vide détecté
    let lastEmptyEvent = null;
    // Variable pour stocker les données des logs des événements vides pour debugging
    let emptyEventLogs = [];
    // Variables pour améliorer la capture des sélections
    let lastRealSelection = null;
    let mouseDownPosition = null;
    let mouseUpPosition = null;
    let selectionInProgress = false;
    let selectionStartTime = null;

    // Fonction pour capturer manuellement les événements du calendrier
    function setupEventCapture() {
        // 1. Stocker les événements vides directement depuis la console
        const originalConsoleLog = console.log;
        console.log = function() {
            // Capturer uniquement les logs qui contiennent des événements vides
            if (arguments.length > 0 && 
                typeof arguments[0] === 'string' && 
                arguments[0].includes('Événement "" déclenché:') && 
                arguments[1] && 
                arguments[1].start && 
                arguments[1].end) {
                
                // Stocker l'événement et la taille du rectangle
                const event = arguments[1];
                lastEmptyEvent = {
                    start: new Date(event.start),
                    end: new Date(event.end),
                    isAllday: event.isAllday || false,
                    // Ajouter des métadonnées sur le rectangle
                    gridElements: event.gridSelectionElements ? Array.from(event.gridSelectionElements).length : 0,
                    timestamp: new Date()
                };
                
                if (selectionInProgress && event.gridSelectionElements && 
                    event.gridSelectionElements.length > 0) {
                    // C'est une vraie sélection utilisateur, pas un événement généré automatiquement
                    lastRealSelection = {
                        start: new Date(event.start),
                        end: new Date(event.end),
                        isAllday: event.isAllday || false,
                        timestamp: new Date(),
                        mouseDistance: mouseUpPosition ? 
                            Math.sqrt(
                                Math.pow(mouseUpPosition.x - mouseDownPosition.x, 2) + 
                                Math.pow(mouseUpPosition.y - mouseDownPosition.y, 2)
                            ) : 0
                    };
                    
                    console.debug('Sélection utilisateur capturée:', lastRealSelection);
                }
                
                // Stocker tous les événements pour analyse
                emptyEventLogs.push({
                    timestamp: new Date(),
                    event: {
                        start: new Date(event.start),
                        end: new Date(event.end),
                        isAllday: event.isAllday || false
                    }
                });
                
                // Si l'événement a une durée significative (plus de 10 minutes),
                // c'est probablement une vraie sélection
                const durationMs = new Date(event.end) - new Date(event.start);
                if (durationMs > 10 * 60 * 1000) {
                    // Enregistrer uniquement les événements significatifs pour éviter le bruit
                    lastRealSelection = {
                        start: new Date(event.start),
                        end: new Date(event.end),
                        isAllday: event.isAllday || false,
                        timestamp: new Date(),
                        duration: durationMs
                    };
                    console.debug('Grande sélection détectée:', lastRealSelection);
                }
                
                console.debug('Événement vide capturé:', lastEmptyEvent);
            }
            
            // Appeler le console.log original
            return originalConsoleLog.apply(console, arguments);
        };
        
        // 2. Surveiller les clics de souris pour détecter les sélections
        container.addEventListener('mousedown', function(e) {
            mouseDownPosition = { x: e.clientX, y: e.clientY };
            selectionInProgress = true;
            selectionStartTime = new Date();
            
            // Effacer les anciennes sélections pour éviter la confusion
            if (Date.now() - (lastRealSelection?.timestamp || 0) > 5000) {
                lastRealSelection = null;
            }
        });
        
        container.addEventListener('mouseup', function(e) {
            mouseUpPosition = { x: e.clientX, y: e.clientY };
            const mouseMoveDistance = mouseDownPosition ? 
                Math.sqrt(Math.pow(e.clientX - mouseDownPosition.x, 2) + Math.pow(e.clientY - mouseDownPosition.y, 2)) : 0;
                
            // Si le mouvement de souris est significatif, c'est probablement une sélection
            if (mouseMoveDistance > 20) {
                console.debug('Mouvement de souris significatif détecté:', mouseMoveDistance);
                
                // Petit délai pour permettre au calendrier de mettre à jour la sélection
                setTimeout(function() {
                    const selections = document.querySelectorAll('.toastui-calendar-grid-selection');
                    if (selections.length > 0) {
                        // Capturer les dimensions du rectangle
                        const selection = selections[selections.length - 1];
                        const rect = selection.getBoundingClientRect();
                        
                        // Si la taille du rectangle est significative, on considère que c'est une vraie sélection
                        if (rect.height > 30 || rect.width > 30) {
                            console.debug('Rectangle de sélection significatif détecté:', rect);
                            
                            // Si nous avons un événement vide récent, l'utiliser comme source de date
                            if (emptyEventLogs.length > 0) {
                                // Trouver le dernier événement avec une grande durée
                                const significantEvents = emptyEventLogs
                                    .slice(-5)  // Prendre les 5 derniers événements
                                    .filter(log => {
                                        const duration = log.event.end - log.event.start;
                                        return duration > 10 * 60 * 1000; // Plus de 10 minutes
                                    });
                                
                                if (significantEvents.length > 0) {
                                    const mostRecentSignificant = significantEvents[significantEvents.length - 1];
                                    lastRealSelection = {
                                        start: new Date(mostRecentSignificant.event.start),
                                        end: new Date(mostRecentSignificant.event.end),
                                        isAllday: mostRecentSignificant.event.isAllday,
                                        timestamp: new Date(),
                                        fromMouseSelection: true
                                    };
                                    console.debug('Sélection rectangulaire identifiée via événements:', lastRealSelection);
                                }
                            }
                        }
                    }
                }, 100);
            }
            
            // Petite temporisation pour permettre au calendrier de mettre à jour la sélection
            setTimeout(() => {
                selectionInProgress = false;
            }, 200);
        });
        
        // 3. Observer les éléments de sélection du DOM directement
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.classList && node.classList.contains('toastui-calendar-grid-selection')) {
                            // Nous avons un élément de sélection, attendre qu'il soit stable
                            setTimeout(() => {
                                const rect = node.getBoundingClientRect();
                                
                                // Si c'est un grand rectangle (plus que le minimal)
                                if (rect.height > 40) {
                                    console.debug('Grande sélection détectée dans le DOM:', rect);
                                    const selection = document.querySelectorAll('.toastui-calendar-grid-selection');
                                    if (selection.length > 0) {
                                        // Calculer les dates basées sur la position et la taille
                                        const extractedData = extractScheduleInfoFromDOM(selection[selection.length - 1]);
                                        if (extractedData && 
                                            (extractedData.end - extractedData.start) > 20 * 60 * 1000) { // > 20 minutes
                                            lastRealSelection = extractedData;
                                            lastRealSelection.timestamp = new Date();
                                            console.debug('Grande sélection détectée via DOM:', lastRealSelection);
                                        }
                                    }
                                }
                            }, 50);
                        }
                    });
                }
            });
        });
        
        observer.observe(container, { 
            childList: true, 
            subtree: true 
        });
    }
    
    // Fonction pour patcher les méthodes du calendrier pour intercepter les créations d'événements
    function monkeyPatchCalendar() {
        try {
            // Définir un gestionnaire de mutation qui va observer les changements des attributs de date
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes') {
                        const target = mutation.target;
                        // Vérifier si c'est un élément qui contient des informations de date
                        if (target && target.dataset && (target.dataset.startTime || target.dataset.time)) {
                            try {
                                let startTimeStr = target.dataset.startTime || target.dataset.time;
                                if (startTimeStr) {
                                    const startTime = new Date(startTimeStr);
                                    
                                    // Essayer de trouver la date de fin dans les attributs ou calculer
                                    let endTimeStr = target.dataset.endTime;
                                    let endTime;
                                    
                                    if (endTimeStr) {
                                        endTime = new Date(endTimeStr);
                                    } else {
                                        // Estimer la fin basée sur la taille de l'élément
                                        const height = target.offsetHeight;
                                        const heightPerHour = 40; // pixels approximatifs par heure
                                        const durationHours = height / heightPerHour;
                                        
                                        endTime = new Date(startTime.getTime() + durationHours * 60 * 60 * 1000);
                                    }
                                    
                                    lastEmptyEvent = {
                                        start: startTime,
                                        end: endTime,
                                        isAllday: target.classList.contains('toastui-calendar-allday')
                                    };
                                    
                                    console.debug('Dates extraites des attributs:', lastEmptyEvent);
                                }
                            } catch (error) {
                                console.error('Erreur lors de l\'extraction des dates:', error);
                            }
                        }
                    }
                });
            });
            
            // Observer les modifications d'attributs dans le conteneur du calendrier
            observer.observe(container, {
                attributes: true,
                subtree: true,
                attributeFilter: ['data-start-time', 'data-end-time', 'data-time']
            });
            
            console.log('Observateur des attributs initialisé');
            
            // Patch des méthodes internes du calendrier
            if (typeof calendar._createSchedule === 'function') {
                const originalCreate = calendar._createSchedule;
                calendar._createSchedule = function(schedule) {
                    console.debug('Interception de la création d\'un événement:', schedule);
                    if (schedule && schedule.start && schedule.end) {
                        lastEmptyEvent = {
                            start: new Date(schedule.start),
                            end: new Date(schedule.end),
                            isAllday: schedule.isAllDay || false
                        };
                    }
                    return originalCreate.apply(this, arguments);
                };
            }
            
            // Essai avec d'autres méthodes possibles
            const methodsToWrap = ['createSchedules', 'createEvents', '_onClickTimezonesCollapseBtn', '_onClick'];
            methodsToWrap.forEach(function(methodName) {
                if (typeof calendar[methodName] === 'function') {
                    const originalMethod = calendar[methodName];
                    calendar[methodName] = function() {
                        console.debug(`Méthode ${methodName} appelée:`, arguments);
                        return originalMethod.apply(this, arguments);
                    };
                }
            });
            
            console.log('Méthodes du calendrier patched');
        } catch (error) {
            console.error('Erreur lors du monkey patching:', error);
        }
    }

    // Appeler les fonctions de capture d'événements
    setupEventCapture();
    monkeyPatchCalendar();

    // Intercepter tous les événements possibles sauf les événements vides qui causent l'erreur
    const eventsToLog = [
      'clickSchedule', 
      'beforeCreateSchedule', 
      'beforeUpdateSchedule', 
      'beforeDeleteSchedule',
      'selectDateTime'
    ];

    eventsToLog.forEach(eventName => {
      try {
        calendar.on(eventName, (event) => {
          console.log(`Événement "${eventName}" déclenché:`, event);
        });
        console.log(`Écouteur ajouté pour l'événement: ${eventName}`);
      } catch (error) {
        console.log(`Erreur lors de l'ajout de l'écouteur pour ${eventName}:`, error.message);
      }
    });

    // Gestionnaire du bouton "Nouvel événement"
    document.getElementById('create-event-btn').addEventListener('click', () => {
        // Ouvrir le modal
        const modal = document.getElementById('event-modal');
        const modalTitle = document.getElementById('event-modal-title');
        const modalInstance = new bootstrap.Modal(modal);
        
        // Préremplir le formulaire avec la date actuelle
        const now = new Date();
        document.getElementById('event-id').value = '';
        document.getElementById('event-title').value = '';
        document.getElementById('event-start').value = formatDateForInput(now);
        document.getElementById('event-end').value = formatDateForInput(new Date(now.getTime() + 3600000)); // +1 heure
        document.getElementById('event-description').value = '';
        document.getElementById('event-location').value = '';
        document.getElementById('event-all-day').checked = false;
        
        // Masquer le bouton de suppression (c'est un nouvel événement)
        document.getElementById('delete-event').style.display = 'none';
        
        // Mettre à jour le titre du modal
        modalTitle.textContent = 'Nouvel événement';
        
        // Afficher le modal
        modalInstance.show();
    });

    // Ajouter après l'initialisation du calendrier
    if (typeof tui !== 'undefined' && tui.Calendar) {
        console.log('TUI Calendar version:', 
            tui.Calendar.version || 
            tui.version || 
            'Version non détectable');
        
        console.log('Méthodes de l\'instance calendar:', 
            Object.getOwnPropertyNames(calendar).filter(prop => typeof calendar[prop] === 'function'));
    }
    
    // Charger les événements
    function loadEvents() {
        const eventsUrl = container.getAttribute('data-events-url');
        console.log('URL pour charger les événements:', eventsUrl);
        
        fetch(eventsUrl)
            .then(response => {
                console.log('Réponse reçue:', response);
                return response.json();
            })
            .then(events => {
                console.log('Événements chargés:', events);
                
                // Dans TUI Calendar v2.x, on utilise directement calendar.createEvents()
                try {
                    if (typeof calendar.createEvents === 'function') {
                        console.log('Utilisation de calendar.createEvents()');
                        calendar.createEvents(events);
                    } else if (typeof calendar.create === 'function') {
                        console.log('Utilisation de calendar.create()');
                        calendar.create(events);
                    } else if (typeof calendar.addEvents === 'function') {
                        console.log('Utilisation de calendar.addEvents()');
                        calendar.addEvents(events);
                    } else {
                        // Tentative avec la méthode getInstance() qui existe dans certaines versions
                        const calendarInstance = calendar.getInstance ? calendar.getInstance() : calendar;
                        console.log('Instance du calendrier:', calendarInstance);
                        console.log('Méthodes disponibles:', Object.getOwnPropertyNames(calendarInstance));
                        
                        if (typeof calendarInstance.createEvents === 'function') {
                            calendarInstance.createEvents(events);
                        } else if (typeof calendarInstance.addEvents === 'function') {
                            calendarInstance.addEvents(events);
                        } else {
                            console.error('Aucune méthode trouvée pour ajouter des événements');
                        }
                    }
                } catch (error) {
                    console.error('Erreur lors de l\'ajout des événements:', error);
                    
                    // Solution de dernier recours: recréer le calendrier avec les événements
                    console.log('Tentative de recréation du calendrier avec les événements');
                    const calOptions = {
                        defaultView: '{$defaultView}',
                        taskView: true,
                        scheduleView: true,
                        useCreationPopup: true,
                        useDetailPopup: true,
                        calendars: {$calendarDataJSON},
                        events: events // Ajouter les événements directement dans les options
                    };
                    
                    // Tenter de recréer le calendrier
                    while (container.firstChild) {
                        container.removeChild(container.firstChild);
                    }
                    calendar = new tui.Calendar(container, calOptions);
                    console.log('Calendrier recréé avec les événements');
                    
                    // Réappliquer les hooks après recréation
                    setupEventCapture();
                    monkeyPatchCalendar();
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des événements:', error);
            });
    }
    
    loadEvents();

    // Navigation entre les vues - version corrigée pour TUI Calendar v2.x
    document.getElementById('today-btn').addEventListener('click', () => {
        try {
            calendar.today();
            console.log('Navigation vers aujourd\'hui');
        } catch (error) {
            console.error('Erreur lors de la navigation vers aujourd\'hui:', error);
        }
    });

    document.getElementById('prev-btn').addEventListener('click', () => {
        try {
            calendar.prev();
            console.log('Navigation vers la période précédente');
        } catch (error) {
            console.error('Erreur lors de la navigation vers la période précédente:', error);
            
            // Méthode alternative si la précédente échoue
            try {
                const date = calendar.getDate();
                if (date) {
                    const newDate = new Date(date);
                    newDate.setMonth(newDate.getMonth() - 1);
                    calendar.setDate(newDate);
                    console.log('Navigation alternative appliquée');
                }
            } catch (e) {
                console.error('Navigation alternative échouée:', e);
            }
        }
    });

    document.getElementById('next-btn').addEventListener('click', () => {
        try {
            calendar.next();
            console.log('Navigation vers la période suivante');
        } catch (error) {
            console.error('Erreur lors de la navigation vers la période suivante:', error);
            
            // Méthode alternative si la précédente échoue
            try {
                const date = calendar.getDate();
                if (date) {
                    const newDate = new Date(date);
                    newDate.setMonth(newDate.getMonth() + 1);
                    calendar.setDate(newDate);
                    console.log('Navigation alternative appliquée');
                }
            } catch (e) {
                console.error('Navigation alternative échouée:', e);
            }
        }
    });

    document.getElementById('day-view').addEventListener('click', () => {
        try {
            calendar.changeView('day');
            console.log('Vue jour activée');
        } catch (error) {
            console.error('Erreur lors du changement de vue:', error);
        }
    });

    document.getElementById('week-view').addEventListener('click', () => {
        try {
            calendar.changeView('week');
            console.log('Vue semaine activée');
        } catch (error) {
            console.error('Erreur lors du changement de vue:', error);
        }
    });

    document.getElementById('month-view').addEventListener('click', () => {
        try {
            calendar.changeView('month');
            console.log('Vue mois activée');
        } catch (error) {
            console.error('Erreur lors du changement de vue:', error);
        }
    });

    // Gestionnaire de double-clic amélioré pour ouvrir le modal avec la sélection correcte
    container.addEventListener('dblclick', function(event) {
        console.log('Double-clic détecté');
        
        // Petit délai pour s'assurer que les logs les plus récents sont pris en compte
        setTimeout(function() {
            // Ouvrir le modal
            const modal = document.getElementById('event-modal');
            const modalTitle = document.getElementById('event-modal-title');
            const modalInstance = new bootstrap.Modal(modal);
            
            let startTime, endTime, isAllDay = false;
            
            // PRIORITÉ 1: Utiliser la sélection rectangulaire avec distance de souris significative
            if (lastRealSelection && (
                lastRealSelection.mouseDistance > 10 || 
                lastRealSelection.fromMouseSelection ||
                (lastRealSelection.end - lastRealSelection.start) > 20 * 60 * 1000)) {
                
                startTime = new Date(lastRealSelection.start);
                endTime = new Date(lastRealSelection.end);
                isAllDay = lastRealSelection.isAllday || false;
                console.log('Utilisation de la sélection rectangulaire:', startTime, endTime, 
                            'Durée:', (endTime - startTime) / (1000 * 60), 'minutes');
            }
            // PRIORITÉ 2: Utiliser le dernier événement vide capturé avec une durée significative
            else if (lastEmptyEvent && 
                     (lastEmptyEvent.end - lastEmptyEvent.start) > 15 * 60 * 1000) {
                startTime = new Date(lastEmptyEvent.start);
                endTime = new Date(lastEmptyEvent.end);
                isAllDay = lastEmptyEvent.isAllday || false;
                console.log('Utilisation du dernier événement vide de longue durée:', 
                           startTime, endTime, 
                           'Durée:', (endTime - startTime) / (1000 * 60), 'minutes');
            }
            // PRIORITÉ 3: Examiner tous les logs récents pour trouver un événement significatif
            else if (emptyEventLogs.length > 0) {
                // Chercher le dernier événement avec une durée significative
                const significantEvents = emptyEventLogs
                    .filter(log => {
                        const duration = log.event.end - log.event.start;
                        return duration > 15 * 60 * 1000; // Plus de 15 minutes
                    });
                
                if (significantEvents.length > 0) {
                    const mostRecent = significantEvents[significantEvents.length - 1];
                    startTime = new Date(mostRecent.event.start);
                    endTime = new Date(mostRecent.event.end);
                    isAllDay = mostRecent.event.isAllday || false;
                    console.log('Utilisation d\'un événement significatif des logs:', 
                               startTime, endTime,
                               'Durée:', (endTime - startTime) / (1000 * 60), 'minutes');
                }
                // Si pas d'événement significatif, prendre le tout dernier
                else {
                    const lastLog = emptyEventLogs[emptyEventLogs.length - 1];
                    startTime = new Date(lastLog.event.start);
                    endTime = new Date(lastLog.event.end);
                    isAllDay = lastLog.event.isAllday || false;
                    console.log('Utilisation du dernier événement des logs:', startTime, endTime);
                }
            }
            
            // Solution par défaut: utiliser l'heure actuelle
            if (!startTime || !endTime) {
                const now = new Date();
                now.setMinutes(Math.floor(now.getMinutes() / 30) * 30);
                now.setSeconds(0);
                now.setMilliseconds(0);
                
                startTime = now;
                endTime = new Date(now.getTime() + 3600000); // +1 heure
                console.log('Utilisation des dates par défaut:', startTime, endTime);
            }
            
            // Préremplir le formulaire
            document.getElementById('event-id').value = '';
            document.getElementById('event-title').value = '';
            document.getElementById('event-start').value = formatDateForInput(startTime);
            document.getElementById('event-end').value = formatDateForInput(endTime);
            document.getElementById('event-description').value = '';
            document.getElementById('event-location').value = '';
            document.getElementById('event-all-day').checked = isAllDay;
            
            // Masquer le bouton de suppression
            document.getElementById('delete-event').style.display = 'none';
            
            // Mettre à jour le titre du modal
            modalTitle.textContent = 'Nouvel événement';
            
            // Afficher le modal
            modalInstance.show();
            
            // Stockage pour diagnostic
            window._lastEventData = {
                lastRealSelection,
                lastEmptyEvent,
                startTime,
                endTime,
                isAllDay,
                eventLogs: emptyEventLogs.slice(-5) // Garder les 5 derniers logs
            };
            
            // Réinitialiser les sélections pour la prochaine fois
            lastEmptyEvent = null;
            // Ne pas réinitialiser lastRealSelection pour permettre le diagnostic
        }, 50);
    });

    // Fonction pour extraire les infos d'un planning à partir du DOM
    function extractScheduleInfoFromDOM(element) {
        try {
            // 1. Essayer d'obtenir les attributs data directement
            if (element.dataset && (element.dataset.startTime || element.dataset.time)) {
                const start = element.dataset.startTime || element.dataset.time;
                const end = element.dataset.endTime;
                
                if (start) {
                    const startTime = new Date(start);
                    let endTime;
                    
                    if (end) {
                        endTime = new Date(end);
                    } else {
                        // Estimer en fonction de la hauteur
                        const height = element.offsetHeight;
                        const pixelsPerHour = 40; // Approximation
                        const durationHours = Math.max(1, height / pixelsPerHour);
                        endTime = new Date(startTime.getTime() + durationHours * 60 * 60 * 1000);
                    }
                    
                    return {
                        start: startTime,
                        end: endTime,
                        isAllDay: element.classList.contains('toastui-calendar-allday')
                    };
                }
            }
            
            // 2. Essayer d'extraire des log de console 
            const consoleItems = document.querySelectorAll('div[class*="console-item"]');
            for (let i = consoleItems.length - 1; i >= 0; i--) {
                const item = consoleItems[i];
                const text = item.textContent;
                
                if (text.includes('Événement "" déclenché')) {
                    // Extraire les dates avec regex
                    const startMatch = text.match(/start: ([^{}]+) {}/);
                    const endMatch = text.match(/end: ([^{}]+) {}/);
                    
                    if (startMatch && endMatch) {
                        return {
                            start: new Date(startMatch[1]),
                            end: new Date(endMatch[1]),
                            isAllDay: text.includes('isAllday: true')
                        };
                    }
                }
            }
            
            // 3. Essayer d'extraire à partir de la position
            const rect = element.getBoundingClientRect();
            const calendar = document.getElementById('calendar');
            const calendarRect = calendar.getBoundingClientRect();
            
            // Extraire jour et heure à partir des positions relatives
            const relativeY = rect.top - calendarRect.top;
            const relativeX = rect.left - calendarRect.left;
            
            // Estimer le jour (0-6 pour dim-sam)
            const dayWidthPercent = 100 / 7;
            const dayIndex = Math.floor((relativeX / calendarRect.width) * 7);
            
            // Estimer l'heure (0-23)
            const hourHeightPercent = 100 / 24;
            const hourIndex = Math.floor((relativeY / calendarRect.height) * 24);
            
            // Estimer la durée
            const durationHours = Math.max(0.5, rect.height / (calendarRect.height / 24));
            
            // Construire les dates
            const today = new Date();
            const startDay = new Date(today);
            startDay.setDate(today.getDate() - today.getDay() + dayIndex);
            startDay.setHours(hourIndex, 0, 0, 0);
            
            const endDay = new Date(startDay);
            endDay.setTime(startDay.getTime() + (durationHours * 60 * 60 * 1000));
            
            return {
                start: startDay,
                end: endDay,
                isAllDay: false
            };
        } catch (error) {
            console.error('Erreur lors de l\'extraction des infos du DOM:', error);
            return null;
        }
    }

    // Conserver l'événement clickSchedule car il ne cause pas d'erreur
    try {
        calendar.on('clickSchedule', function(event) {
            console.log('Édition d\'un événement', event);
            
            // Ouvrir le modal
            const modal = document.getElementById('event-modal');
            const modalTitle = document.getElementById('event-modal-title');
            const modalInstance = new bootstrap.Modal(modal);
            
            // Préremplir le formulaire avec les données de l'événement
            document.getElementById('event-id').value = event.schedule.id;
            document.getElementById('event-title').value = event.schedule.title;
            document.getElementById('event-start').value = formatDateForInput(event.schedule.start);
            document.getElementById('event-end').value = formatDateForInput(event.schedule.end);
            document.getElementById('event-description').value = event.schedule.body || '';
            document.getElementById('event-location').value = event.schedule.location || '';
            document.getElementById('event-all-day').checked = event.schedule.isAllDay;
            
            if (event.schedule.calendarId) {
                document.getElementById('event-calendar').value = event.schedule.calendarId;
            }
            
            if (event.schedule.category) {
                document.getElementById('event-category').value = event.schedule.category;
            }
            
            // Afficher le bouton de suppression
            document.getElementById('delete-event').style.display = 'block';
            
            // Mettre à jour le titre du modal
            modalTitle.textContent = 'Modifier l\'événement';
            
            // Afficher le modal
            modalInstance.show();
        });
    } catch (error) {
        console.error('Erreur lors de l\'ajout du gestionnaire clickSchedule:', error);
    }
    
    // 3. Gérer la soumission du formulaire
    document.getElementById('save-event').addEventListener('click', function() {
        const form = document.getElementById('event-form');
        const eventId = document.getElementById('event-id').value;
        
        const formData = {
            event_id: eventId || null,
            title: document.getElementById('event-title').value,
            calendar_id: document.getElementById('event-calendar').value,
            category_id: document.getElementById('event-category').value || null,
            start_date: document.getElementById('event-start').value,
            end_date: document.getElementById('event-end').value,
            is_all_day: document.getElementById('event-all-day').checked ? 1 : 0,
            location: document.getElementById('event-location').value,
            body: document.getElementById('event-description').value
        };
        
        console.log('Données à envoyer:', formData);
        
        // Envoyer les données à l'API
        fetch(form.dataset.saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Réponse du serveur:', data);
            if (data.success) {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('event-modal'));
                modal.hide();
                
                // Recharger les événements
                loadEvents();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la sauvegarde de l\'événement:', error);
        });
    });
    
    // 4. Gérer la suppression d'un événement
    document.getElementById('delete-event').addEventListener('click', function() {
        const form = document.getElementById('event-form');
        const eventId = document.getElementById('event-id').value;
        
        if (!eventId) {
            return; // On ne peut pas supprimer un événement qui n'existe pas encore
        }
        
        if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
            fetch(form.dataset.deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ event_id: eventId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('event-modal'));
                    modal.hide();
                    
                    // Recharger les événements
                    loadEvents();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur lors de la suppression de l\'événement:', error);
            });
        }
    });

    // Stocker les logs pour référence (pour débogage)
    if (!console._logs) {
        console._logs = [];
        const originalLog = console.log;
        console.log = function() {
            console._logs.push({
                timestamp: new Date(),
                message: Array.from(arguments).map(arg => String(arg)).join(' '),
                data: arguments[1]
            });
            if (console._logs.length > 100) console._logs.shift(); // Garder une taille raisonnable
            originalLog.apply(console, arguments);
        };
    }

    // Fonction pour formatter les dates pour les champs input
    function formatDateForInput(date) {
        const d = new Date(date);
        return d.toISOString().slice(0, 16); // Format YYYY-MM-DDTHH:MM
    }
});
</script>
HTML;

// Démarrer la capture du contenu
ob_start();
?>

<!-- Contenu HTML du calendrier -->
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><i class="fas fa-calendar-alt me-2"></i>Mon Calendrier</h1>
        </div>
        <div class="col-md-6 text-end">
            <button id="today-btn" class="btn btn-outline-primary me-2">
                <i class="fas fa-calendar-day me-1"></i> Aujourd'hui
            </button>
            <button id="create-event-btn" class="btn btn-success me-2">
                <i class="fas fa-plus me-1"></i> Nouvel événement
            </button>
            <div class="btn-group me-2">
                <button id="prev-btn" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="next-btn" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="btn-group">
                <button id="day-view" class="btn btn-outline-secondary">Jour</button>
                <button id="week-view" class="btn btn-outline-secondary active">Semaine</button>
                <button id="month-view" class="btn btn-outline-secondary">Mois</button>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-3 col-lg-2 mb-4">
            <!-- Calendriers et filtres -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mes calendriers</h5>
                    <a href="<?= Routes::url('calendar', 'calendars') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cog"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" id="calendar-list">
                        <?php foreach ($calendars as $calendar): ?>
                            <li class="list-group-item d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input calendar-checkbox" 
                                           type="checkbox" 
                                           value="<?= $calendar['calendar_id'] ?>" 
                                           id="cal-<?= $calendar['calendar_id'] ?>" 
                                           data-toggle-url="<?= Routes::url('calendar', 'toggleCalendarVisibility') ?>"
                                           <?= $calendar['is_visible'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="cal-<?= $calendar['calendar_id'] ?>">
                                        <span class="color-dot" style="background-color: <?= htmlspecialchars($calendar['color']) ?>;"></span>
                                        <?= htmlspecialchars($calendar['name']) ?>
                                    </label>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-footer text-center p-2">
                    <a href="<?= Routes::url('calendar', 'createCalendar') ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Nouveau calendrier
                    </a>
                </div>
            </div>
            
            <!-- Catégories -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Catégories</h5>
                    <a href="<?= Routes::url('calendar', 'categories') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cog"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categories as $category): ?>
                            <li class="list-group-item">
                                <span class="color-dot" style="background-color: <?= htmlspecialchars($category['bg_color']) ?>;
                                                              border-color: <?= htmlspecialchars($category['border_color']) ?>;"></span>
                                <?= htmlspecialchars($category['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <!-- Calendrier TUI -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div id="calendar" style="height: 800px;" data-events-url="<?= Routes::url('calendar', 'getEvents') ?>"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour l'ajout/édition d'événement -->
<div class="modal fade" id="event-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="event-modal-title">Nouvel événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="event-form" data-save-url="<?= Routes::url('event', 'save') ?>" data-delete-url="<?= Routes::url('event', 'deleteAjax') ?>">
                    <input type="hidden" id="event-id">
                    
                    <div class="mb-3">
                        <label for="event-title" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="event-title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event-calendar" class="form-label">Calendrier</label>
                        <select class="form-select" id="event-calendar" required>
                            <?php foreach ($calendars as $calendar): ?>
                                <option value="<?= $calendar['calendar_id'] ?>"><?= htmlspecialchars($calendar['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event-category" class="form-label">Catégorie</label>
                        <select class="form-select" id="event-category">
                            <option value="">Aucune catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="event-all-day">
                            <label class="form-check-label" for="event-all-day">
                                Toute la journée
                            </label>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <label for="event-start" class="form-label">Début</label>
                            <input type="datetime-local" class="form-control" id="event-start" required>
                        </div>
                        <div class="col">
                            <label for="event-end" class="form-label">Fin</label>
                            <input type="datetime-local" class="form-control" id="event-end" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event-location" class="form-label">Lieu</label>
                        <input type="text" class="form-control" id="event-location">
                    </div>
                    
                    <div class="mb-3">
                        <label for="event-description" class="form-label">Description</label>
                        <textarea class="form-control" id="event-description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="delete-event">Supprimer</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="save-event">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<?php
// Récupérer le contenu capturé
$content = ob_get_clean();

// Inclure le layout principal avec les variables définies
include_once __DIR__ . '/../layouts/main.php';
?>
