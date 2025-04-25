<!-- filepath: c:\xampp\htdocs\tui_calendar\views\calendar\index.php -->
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
        'borderColor' => $cal['color']
    ];
}

// Encoder les données pour JavaScript
$calendarDataJSON = json_encode($calendarDataForJS);
// Préparer les IDs de calendrier pour JavaScript
$calendarIdsJSON = json_encode(array_column($calendars, 'calendar_id'));

// Préparer les scripts pour la page
$pageScripts = <<<HTML
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.fr.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ajoutez ce CSS dans votre section <style>
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        .event-extension-arrow {
            position: absolute;
            top: calc(50% - 15px);
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
        
        .tui-full-calendar-time-schedule:hover .event-extension-arrow {
            opacity: 1;
        }
        
        .event-extension-arrow:hover {
            transform: scale(1.2);
            opacity: 1;
        }
    `;
    document.head.appendChild(styleElement);

    // Fonction pour supprimer toutes les flèches d'extension
    function removeAllArrows() {
        // Supprimer tous les conteneurs de flèches
        const arrowContainers = document.querySelectorAll('.event-arrows-container');
        arrowContainers.forEach(container => {
            container.remove();
        });
        console.log('Toutes les flèches ont été supprimées');
    }

    // Factorisation du template time avec les flèches d'extension
    const calendar = new tui.Calendar('#calendar', {
        defaultView: 'week',
        taskView: false,
        scheduleView: ['time', 'allday'],
        useCreationPopup: false,
        useDetailPopup: false,  // Désactiver le popup d'édition par défaut
        calendars: {$calendarDataJSON},
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
                
                return `
                    <div class="event-content" data-schedule-id="\${schedule.id}" data-calendar-id="\${schedule.calendarId}" style="
                        position: relative;
                        width: 100%;
                        height: 100%;
                        box-sizing: border-box;
                        border: 8px solid \${calColor};
                        background-color: \${catColor};
                        color: \${textColor};
                    ">
                        <div style="padding: 2px 8px;">
                            \${schedule.title}
                        </div>
                    </div>
                `;
            }
        }
    });

    // Ajouter une variable globale pour suivre les clics sur les flèches
    let arrowClickInProgress = false;

    // Ajouter une variable pour contrôler la fréquence des attachements
    let attachHandlersTimeout = null;
    
 // Fonction centralisée pour gérer les appels à attachArrowHandlers   
function requestArrowHandlersUpdate(delay = 300) {
    // Annuler tout appel précédent en attente
    if (attachHandlersTimeout) {
        clearTimeout(attachHandlersTimeout);
    }
    
    // Planifier un nouvel appel
    attachHandlersTimeout = setTimeout(function() {
        attachArrowHandlers();
        attachHandlersTimeout = null;
    }, delay);
}
    // Fonction pour ajouter les flèches en surimpression avec stockage des données
    function addExtensionArrows() {
    // Vérifier si on est en vue semaine
    const currentView = calendar.getViewName();
    if (currentView !== 'week') {
        return; // Ne pas ajouter de flèches si on n'est pas en vue semaine
    }
    
    // Obtenir tous les événements actuellement affichés
    const events = document.querySelectorAll('.tui-full-calendar-time-schedule');
    console.log(`Ajout de flèches pour \${events.length} événements`);
    
    events.forEach(event => {
        // Vérifier si les flèches existent déjà pour éviter les doublons
        if (event.parentNode.querySelector(`.event-extension-arrow[data-for-schedule-id="\${event.getAttribute('data-schedule-id')}"]`)) return;
        
        // Obtenir l'ID de l'événement
        const scheduleId = event.getAttribute('data-schedule-id');
        if (!scheduleId) {
            console.error("Événement sans ID détecté");
            return;
        }
        
        // Récupérer les données de l'événement
        const calendarIds =  $calendarIdsJSON ;
        let scheduleData = null;
        let foundCalendarId = null;
        
        // Trouver le calendrier contenant l'événement
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
                    raw: {
                        categoryId: schedule.raw?.categoryId || '1',
                        calendarColor: schedule.raw?.calendarColor || '#FFFFFF',
                        categoryColor: schedule.raw?.categoryColor || '#FFFFFF',
                        categoryTextColor: schedule.raw?.categoryTextColor || '#000000'
                    }
                };
                
                console.log(`Événement trouvé: \${schedule.title}, calendarId: \${foundCalendarId}`);
                break;
            }
        }
        
        if (!foundCalendarId || !scheduleData) {
            console.error("Impossible de trouver les données de l'événement:", scheduleId);
            return;
        }
        
        // Convertir les données en JSON pour les stocker
        const scheduleDataJson = JSON.stringify(scheduleData);
        
        // IMPORTANT: Création d'un conteneur séparé pour les flèches
        const arrowContainer = document.createElement('div');
        arrowContainer.className = 'event-arrows-container';
        arrowContainer.style.position = 'absolute';
        arrowContainer.style.pointerEvents = 'none';
        
        // Créer la flèche gauche avec les données stockées
        const leftArrow = document.createElement('div');
        leftArrow.className = 'event-extension-arrow left';
        leftArrow.innerHTML = '<i class="fas fa-chevron-left"></i>';
        leftArrow.setAttribute('data-schedule-id', scheduleId);
        leftArrow.setAttribute('data-for-schedule-id', scheduleId);
        leftArrow.setAttribute('data-calendar-id', foundCalendarId);
        leftArrow.setAttribute('data-schedule-data', scheduleDataJson);
        leftArrow.style.pointerEvents = 'auto';
        
        // Créer la flèche droite avec les données stockées
        const rightArrow = document.createElement('div');
        rightArrow.className = 'event-extension-arrow right';
        rightArrow.innerHTML = '<i class="fas fa-chevron-right"></i>';
        rightArrow.setAttribute('data-schedule-id', scheduleId);
        rightArrow.setAttribute('data-for-schedule-id', scheduleId);
        rightArrow.setAttribute('data-calendar-id', foundCalendarId);
        rightArrow.setAttribute('data-schedule-data', scheduleDataJson);
        rightArrow.style.pointerEvents = 'auto';
        
        // Ajouter les flèches au conteneur
        arrowContainer.appendChild(leftArrow);
        arrowContainer.appendChild(rightArrow);
        
        // Ajouter le conteneur au body pour un positionnement absolu
        document.body.appendChild(arrowContainer);
        
        // Positionner le conteneur par-dessus l'événement
        const eventRect = event.getBoundingClientRect();
        arrowContainer.style.top = `\${eventRect.top + window.scrollY}px`;
        arrowContainer.style.left = `\${eventRect.left + window.scrollX}px`;
        arrowContainer.style.width = `\${eventRect.width}px`;
        arrowContainer.style.height = `\${eventRect.height}px`;
        arrowContainer.style.zIndex = '1000';
    });
}

    // Fonction factorisée pour gérer les clics sur les flèches d'extension avec données en cache
    function handleArrowClick(direction, scheduleData) {
        console.log(`Clic sur flèche \${direction} détecté pour l'événement:`, scheduleData.title);
        
        // Calculer le décalage de jours pour la copie
        const dayOffset = direction === 'left' ? -1 : 1;
        
        // Créer de nouvelles dates pour la copie (décalées d'un jour)
        const newStart = new Date(scheduleData.start);
        newStart.setDate(newStart.getDate() + dayOffset);
        
        const newEnd = new Date(scheduleData.end);
        newEnd.setDate(newEnd.getDate() + dayOffset);
        
        console.log("Création d'une copie avec dates:", formatDateForInput(newStart), "à", formatDateForInput(newEnd));
        
        // Créer un nouvel événement comme copie
        try {
            calendar.createSchedules([{
                id: String(new Date().getTime()), // Nouvel ID unique
                calendarId: scheduleData.calendarId,
                title: scheduleData.title,
                start: newStart,
                end: newEnd,
                isAllDay: scheduleData.isAllDay,
                category: scheduleData.category,
                raw: scheduleData.raw
            }]);
            console.log("Copie créée avec succès");
        } catch (error) {
            console.error("Erreur lors de la création de la copie:", error);
        }
        
        // Forcer le rendu sans réattacher les gestionnaires immédiatement
        calendar.render();
    }

    // Fonction pour attacher les gestionnaires de flèches
    function attachArrowHandlers() {
        // Vérifier d'abord si on est en vue semaine
        const currentView = calendar.getViewName();
        if (currentView !== 'week') {
            // Si ce n'est pas la vue semaine, supprimer toutes les flèches existantes
            removeAllArrows();
            return;
        }
        
        // Ajouter les flèches aux événements
        addExtensionArrows();
        console.log('Flèches d\'extension ajoutées');
    }

    // Mettre à jour le gestionnaire d'événements click pour les flèches
    document.addEventListener('click', function(e) {
    // Vérifier si l'élément cliqué est une flèche
    const arrow = e.target.closest('.event-extension-arrow');
    if (arrow) {
        // Marquer que nous sommes en train de traiter un clic de flèche
        arrowClickInProgress = true;
        setTimeout(() => { arrowClickInProgress = false; }, 100);
        
        e.stopPropagation();
        e.stopImmediatePropagation();
        e.preventDefault();
        
        const isLeft = arrow.classList.contains('left');
        const cachedData = arrow.getAttribute('data-schedule-data');
        
        if (cachedData) {
            try {
                const scheduleData = JSON.parse(cachedData);
                handleArrowClick(isLeft ? 'left' : 'right', scheduleData);
            } catch (error) {
                console.error("Erreur de parsing des données:", error);
            }
        }
        
        return false;
    }
}, true);

    // Modifier l'écouteur afterRenderSchedule
    calendar.on('afterRenderSchedule', function() {
        // Annuler le précédent timeout s'il existe
        if (attachHandlersTimeout) {
            clearTimeout(attachHandlersTimeout);
        }
        
        // Définir un nouveau timeout
        attachHandlersTimeout = setTimeout(function() {
            attachArrowHandlers();
            attachHandlersTimeout = null;
        }, 300);
    });

    // Ajouter les gestionnaires d'événements pour les flèches après le chargement initial
    setTimeout(attachArrowHandlers, 500);
    
    // Réattacher les gestionnaires après chaque changement de vue
    ['day', 'week', 'month'].forEach(view => {
        document.getElementById(`\${view}-view`).addEventListener('click', function() {
            setTimeout(attachArrowHandlers, 500);
        });
    });
    
    // Réattacher après navigation (prev/next/today)
    document.getElementById('prev-btn').addEventListener('click', () => {
        // Supprimer d'abord toutes les flèches
        removeAllArrows();
        
        calendar.prev();
        updateCalendarHeader();
        
        // Réattacher les flèches seulement si on est en vue semaine
        if (calendar.getViewName() === 'week') {
            requestArrowHandlersUpdate(500);
        }
    });

    // Faire de même pour les boutons 'next' et 'today'
    document.getElementById('next-btn').addEventListener('click', () => {
        removeAllArrows();
        calendar.next(); 
        updateCalendarHeader();
        
        if (calendar.getViewName() === 'week') {
            requestArrowHandlersUpdate(500);
        }
    });

    document.getElementById('today-btn').addEventListener('click', () => {
        removeAllArrows();
        calendar.today();
        updateCalendarHeader();
        
        if (calendar.getViewName() === 'week') {
            requestArrowHandlersUpdate(500);
        }
    });

    // Intercepter l'événement de clic sur un événement existant
    calendar.on('clickSchedule', function(e) {
    // Si un clic de flèche est en cours, ignorer l'ouverture du modal
    if (arrowClickInProgress) {
        e.preventDefault && e.preventDefault();
        console.log("Clic sur événement ignoré car clic sur flèche détecté");
        return;
    }
    
    e.preventDefault && e.preventDefault();
    const schedule = e.schedule;
    openEditModal({
        id: schedule.id,
        title: schedule.title,
        start: schedule.start,
        end: schedule.end,
        calendarId: schedule.calendarId,
        raw: schedule.raw || {}
    });
});

    // Fonction pour mettre à jour l'en-tête du calendrier
    function updateCalendarHeader() {
        const currentDate = calendar.getDate();
        const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                       'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        const month = months[currentDate.getMonth()];
        const year = currentDate.getFullYear();
        
        document.getElementById('calendar-date-header').textContent = `\${month} \${year}`;
    }

    // Initialiser l'en-tête du calendrier
    updateCalendarHeader();

    // Fonction pour mettre à jour l'état actif des boutons de vue
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

    // Initialiser l'état des boutons
    updateViewButtons('week');

    // Navigation entre les vues - avec nettoyage des flèches
    document.getElementById('day-view').addEventListener('click', () => {
        // Supprimer toutes les flèches avant de changer de vue
        removeAllArrows();
        
        calendar.changeView('day');
        updateViewButtons('day');
        updateCalendarHeader();
        
        // Pas besoin de réattacher les flèches en vue jour
    });

    document.getElementById('week-view').addEventListener('click', () => {
        calendar.changeView('week');
        updateViewButtons('week');
        updateCalendarHeader();
        
        // Réattacher les flèches seulement en vue semaine
        requestArrowHandlersUpdate(500);
    });

    document.getElementById('month-view').addEventListener('click', () => {
        // Supprimer toutes les flèches avant de changer de vue
        removeAllArrows();
        
        calendar.changeView('month');
        updateViewButtons('month');
        updateCalendarHeader();
        
        // Pas besoin de réattacher les flèches en vue mois
    });

    // Intercepter l'événement de création pour utiliser notre modal
    calendar.on('beforeCreateSchedule', function(eventObj) {
        // Formatage de la date/heure de début avec prise en compte du fuseau horaire
        const startDate = new Date(eventObj.start);
        const formattedStart = formatDateForInput(startDate);
        
        // Formatage de la date/heure de fin avec prise en compte du fuseau horaire
        const endDate = new Date(eventObj.end || new Date(startDate.getTime() + 60 * 60 * 1000));
        const formattedEnd = formatDateForInput(endDate);
        
        // Remplir les champs avec les valeurs initiales
        document.getElementById('eventTitle').value = '';
        document.getElementById('eventStart').value = formattedStart;
        document.getElementById('eventEnd').value = formattedEnd;
        document.getElementById('eventCalendar').value = eventObj.calendarId || '1';
        
        // Afficher le modal Bootstrap
        openCreateModal();
    });

    // SUPPRIMER ce gestionnaire natif de TUI Calendar
// calendar.on('beforeRightClickSchedule', function(e) {
//     console.log('Clic droit sur un événement détecté!', e);
//     e.preventDefault && e.preventDefault();
//     const schedule = e.schedule;
//     
//     // Cloner les données de l'événement
//     const clonedEventData = {
//         title: schedule.title,
//         start: schedule.start,
//         end: schedule.end,
//         calendarId: schedule.calendarId,
//         categoryId: schedule.raw?.categoryId,
//         raw: schedule.raw || {}
//     };
//     
//     // Ouvrir le modal en mode "clonage"
//     openCloneModal(clonedEventData);
// });

    // Gérer le redimensionnement des événements (glissement de bordure)
    calendar.on('beforeUpdateSchedule', function(e) {
        const schedule = e.schedule;
        const changes = e.changes;
        
        console.log('Événement redimensionné:', schedule);
        console.log('Modifications:', changes);
        
        // Si c'est un changement de durée (redimensionnement)
        if (changes && (changes.start || changes.end)) {
            // Appliquer les modifications au calendrier
            calendar.updateSchedule(schedule.id, schedule.calendarId, changes);
            
            // Logs pour le débogage
            console.log('Nouvel horaire:', 
                changes.start ? formatDateForInput(changes.start) : formatDateForInput(schedule.start), 
                'à', 
                changes.end ? formatDateForInput(changes.end) : formatDateForInput(schedule.end)
            );
        }
    });

    // CONSERVER ce gestionnaire d'événements contextmenu du DOM (plus fiable)
    document.addEventListener('contextmenu', function(e) {
        const targetElement = e.target;
        const isCalendarEvent = targetElement.closest('.tui-full-calendar-time-schedule') || 
                              targetElement.closest('.tui-full-calendar-time-schedule-content');
        
        if (isCalendarEvent) {
            // Bloquer le menu contextuel du navigateur
            e.preventDefault();
            e.stopPropagation();
            
            // Récupérer l'ID de l'événement à partir du DOM
            const eventElement = targetElement.closest('.tui-full-calendar-time-schedule');
            const eventId = eventElement ? eventElement.getAttribute('data-schedule-id') : null;
            
            // Si on a un ID d'événement, rechercher l'événement dans le calendrier
            if (eventId) {
                // Parcourir tous les calendriers possibles pour trouver l'événement
                const calendarIds = $calendarIdsJSON ;
                calendarIds.forEach(calId => {
                    const schedule = calendar.getSchedule(eventId, calId.toString());
                    if (schedule) {
                        // Cloner les données de l'événement
                        const clonedEventData = {
                            title: schedule.title,
                            start: schedule.start,
                            end: schedule.end,
                            calendarId: schedule.calendarId,
                            categoryId: schedule.raw?.categoryId,
                            raw: schedule.raw || {}
                        };
                        
                        // Ouvrir le modal en mode "clonage"
                        openCloneModal(clonedEventData);
                    }
                });
            }
            
            return false;
        }
    }, true); // Le "true" est important pour la phase de capture

    // Gestionnaire de clic sur le bouton Enregistrer (défini une seule fois)
    document.getElementById('saveEventBtn').onclick = function() {
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
        let categoryColor = '#FFFFFF';  // bg_color
        let categoryTextColor = '#000000';  // color (pour le texte)
        for(let i = 0; i < categorySelect.options.length; i++) {
            if(categorySelect.options[i].value === categoryId) {
                categoryColor = categorySelect.options[i].style.backgroundColor || '#FFFFFF';
                // Ajouter cette ligne pour récupérer la couleur du texte
                categoryTextColor = categorySelect.options[i].style.color || '#000000';
                console.log('Catégorie trouvée:', categorySelect.options[i].text);
                console.log('Couleur de texte récupérée:', categoryTextColor);
                break;
            }
        }

        // Récupérer la couleur du calendrier (méthode alternative)
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

        if (!eventId) {
            // MODE CRÉATION
            calendar.createSchedules([{
                id: String(new Date().getTime()),
                calendarId: calendarId,
                title: title,
                start: start,
                end: end,
                isAllDay: false,
                category: 'time',
                raw: {
                    calendarColor: calendarColor,
                    categoryColor: categoryColor,
                    categoryTextColor: categoryTextColor,
                    categoryId: categoryId 
                }
            }]);
        } else {
            // MODE ÉDITION
            // Récupérer le calendrier d'origine de l'événement
            const originalCalendarId = document.getElementById('originalCalendarId').value;
            const originalEvent = calendar.getSchedule(eventId, originalCalendarId);
                        
            console.log('Calendrier original:', originalCalendarId);
            console.log('Nouveau calendrier:', calendarId);
            
            // Vérifier si le calendrier a changé
            if (originalCalendarId !== calendarId) {
                console.log('Changement de calendrier détecté - Suppression puis recréation');
                
                // 1. Supprimer l'événement existant
                calendar.deleteSchedule(eventId, originalCalendarId);
                
                // 2. Créer un nouvel événement avec les nouvelles valeurs
                calendar.createSchedules([{
                    id: eventId, // Conserver le même ID
                    calendarId: calendarId,
                    title: title,
                    start: start,
                    end: end,
                    isAllDay: false,
                    category: 'time',
                    raw: {
                        calendarColor: calendarColor,
                        categoryColor: categoryColor,
                        categoryId: categoryId
                    }
                }]);
            } else {
                // Pas de changement de calendrier, utiliser updateSchedule normalement
                calendar.updateSchedule(eventId, calendarId, {
                    title: title,
                    start: start,
                    end: end,
                    raw: {
                        calendarColor: calendarColor,
                        categoryColor: categoryColor,
                        categoryId: categoryId
                    }
                });
            }
            
            // Force le rafraîchissement visuel
            calendar.render();
        }
        console.log('--- FIN LOGS DE DÉBOGAGE ---');

        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('createEventModal'));
        modal.hide();
    };

    // Gérer le clic sur "Supprimer" en mode édition
    const deleteEventBtn = document.getElementById('deleteEventBtn');
    deleteEventBtn.onclick = function() {
        // Ouvrir simplement le modal de confirmation
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        confirmModal.show();
    };

    // Au clic sur "Confirmer la suppression"
    const confirmDeleteEventBtn = document.getElementById('confirmDeleteEventBtn');
    confirmDeleteEventBtn.onclick = function() {
        const eventId = document.getElementById('editEventId').value;
        const calendarId = document.getElementById('eventCalendar').value;

        if (eventId) {
            calendar.deleteSchedule(eventId, calendarId);
        }

        // Fermer les deux modals
        const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
        const mainEventModal = bootstrap.Modal.getInstance(document.getElementById('createEventModal'));
        confirmModal.hide();
        mainEventModal.hide();
    };

    // Initialiser jQuery datepicker
    jQuery(function($) {
        $('#datepicker').datepicker({
            format: 'dd/mm/yyyy',
            language: 'fr',
            autoclose: true,
            todayHighlight: true
        });
        
        // Ouvrir le datepicker quand on clique sur le bouton
        $('#date-picker-btn').click(function(){
            $('#datepicker').datepicker('show');
        });
        
        // Quand une date est sélectionnée, naviguer vers cette date dans le calendrier TUI
        $('#datepicker').on('changeDate', function(e){
            const selectedDate = e.date;
            calendar.setDate(selectedDate);
            updateCalendarHeader();
        });
    });

    const modal = new bootstrap.Modal(document.getElementById('createEventModal'));
    const modalTitle = document.getElementById('createEventModalLabel');
    const editEventId = document.getElementById('editEventId');

    // Pour créer un nouvel événement
    function openCreateModal() {
        modalTitle.textContent = 'Créer un événement';
        deleteEventBtn.classList.add('d-none');
        editEventId.value = ''; 
        
        const calendarSelect = document.getElementById('eventCalendar');   
        
        // FORCER la sélection du premier élément si aucun n'est sélectionné
        if (calendarSelect.selectedIndex < 0 && calendarSelect.options.length > 0) {
            calendarSelect.selectedIndex = 0;
        }
        
        modal.show();
    }

    // Pour éditer un événement existant
    function openEditModal(eventData) {
        modalTitle.textContent = 'Modifier un événement';
        deleteEventBtn.classList.remove('d-none');
        editEventId.value = eventData.id;
        document.getElementById('eventTitle').value = eventData.title;
        document.getElementById('eventStart').value = formatDateForInput(eventData.start);
        document.getElementById('eventEnd').value = formatDateForInput(eventData.end);
        document.getElementById('originalCalendarId').value = eventData.calendarId;
        document.getElementById('eventCalendar').value = eventData.calendarId;
        
        // AJOUT: Définir la catégorie si disponible
        if (eventData.raw && eventData.raw.categoryId) {
            document.getElementById('eventCategory').value = eventData.raw.categoryId;
        }
        
        modal.show();
    }
    // Fonction pour ouvrir le modal en mode clonage
    function openCloneModal(eventData) {
        // Définir le titre du modal
        modalTitle.textContent = 'Dupliquer un événement';
        
        // Cacher le bouton de suppression
        deleteEventBtn.classList.add('d-none');
        
        // Définir ID vide pour forcer la création d'un nouveau événement
        editEventId.value = '';
        document.getElementById('originalCalendarId').value = eventData.calendarId;
        
        // Préremplir les champs avec les données de l'événement source
        document.getElementById('eventTitle').value = eventData.title;
        document.getElementById('eventStart').value = formatDateForInput(eventData.start);
        document.getElementById('eventEnd').value = formatDateForInput(eventData.end);
        document.getElementById('eventCalendar').value = eventData.calendarId;
        
        // Définir la catégorie si disponible
        if (eventData.raw && eventData.raw.categoryId) {
            document.getElementById('eventCategory').value = eventData.raw.categoryId;
        }
        
        // Afficher le modal
        modal.show();
    }


    // Fonction utilitaire de formatage (définie une seule fois)
    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `\${year}-\${month}-\${day}T\${hours}:\${minutes}`;
    }
});
</script>
<style>
.date-picker-container {
    position: relative;
}
.datepicker-dropdown {
    z-index: 1060 !important; /* S'assurer que le datepicker s'affiche au-dessus des autres éléments */
}
/* Exemple : 25 % à gauche en couleur de calendrier, 75 % au centre en couleur de catégorie */
.tui-full-calendar-time-schedule .tui-full-calendar-schedule {
  background: linear-gradient(to right, var(--calendarColor, #333) 25%, var(--categoryColor, #999) 25%) !important;
  /* Si besoin, retirez aussi la couleur inline déjà appliquée : */
  background-color: transparent !important;
}

/* Remplacez le CSS pour la vue jour par celui-ci */
.tui-full-calendar-day-name {
  text-align: center !important;
}

/* Pour la vue jour spécifiquement - */
.tui-full-calendar-dayname-container .tui-full-calendar-dayname-date,
.tui-full-calendar-dayname-container .tui-full-calendar-dayname-name {
  text-align: center !important;
}

/* Centrer le conteneur entier plutôt que ses parties individuellement */
.tui-full-calendar-dayname-date-area {
  text-align: center !important;
  justify-content: center !important;
  display: flex !important;
}

/* Masquer les points de couleur dans la vue mensuelle */
.tui-full-calendar-month-view .tui-full-calendar-weekday-schedule-bullet,
.tui-full-calendar-month-view .tui-full-calendar-weekday-schedule-dot {
  display: none !important;
}

/* S'assurer que l'événement occupe tout l'espace sans le point */
.tui-full-calendar-month-view .tui-full-calendar-weekday-schedule-title {
  padding-left: 0 !important;
}
</style>
HTML;

// Démarrer la capture du contenu
ob_start();
?>

<!-- Contenu HTML -->
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><i class="fas fa-calendar-alt me-2"></i>Mon Calendrier</h1>
        </div>
        <div class="col-md-6 text-end">
            <button id="today-btn" class="btn btn-outline-primary me-2">
                <i class="fas fa-calendar-day me-1"></i> Aujourd'hui
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
        <!-- Calendrier TUI -->
        <div class="col-md-9 col-lg-10 order-1 order-md-2">
            <div class="card shadow-sm mb-3">
                <div class="card-body py-2 d-flex justify-content-center align-items-center">
                    <h2 id="calendar-date-header" class="h4 mb-0 text-center me-2">
                        <!-- Le mois et l'année seront affichés ici -->
                    </h2>
                    <div class="date-picker-container">
                        <input type="text" id="datepicker" class="d-none">
                        <button class="btn btn-sm btn-outline-secondary" id="date-picker-btn">
                            <i class="fas fa-calendar-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div id="calendar" style="height: 800px;"></div>
                </div>
            </div>
        </div>

        <!-- Calendriers et filtres -->
        <div class="col-md-3 col-lg-2 mb-4 order-2 order-md-1">
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
                            <!-- <li class="list-group-item"> 
                                <span class="color-dot" style="background-color: <?= htmlspecialchars($category['bg_color']) ?>;"></span>
                                <?= htmlspecialchars($category['name']) ?>
                            </li> -->
                            <li class="list-group-item" style="background-color: <?= htmlspecialchars($category['bg_color']) ?>;">
                                <?= htmlspecialchars($category['name']) ?>
                            </li>
                            <!-- Commented out category option 
                            <option 
                              value="<?= $category['category_id'] ?>" 
                              style="background-color: <?= htmlspecialchars($category['bg_color']) ?>"
                            >
                              <?= htmlspecialchars($category['name']) ?>
                            </option>
                            -->
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de création/édition d'événement -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <!-- Le titre évolue selon création ou édition -->
                <h5 class="modal-title" id="createEventModalLabel">Créer/Modifier un événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form>
                    <!-- Champ caché pour l'ID de l'événement (vide si on crée) -->
                    <input type="hidden" id="editEventId">
                    <input type="hidden" id="originalCalendarId">

                    <div class="mb-3">
                        <label for="eventTitle" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="eventTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="eventStart" class="form-label">Début</label>
                        <input type="datetime-local" class="form-control" id="eventStart">
                    </div>
                    <div class="mb-3">
                        <label for="eventEnd" class="form-label">Fin</label>
                        <input type="datetime-local" class="form-control" id="eventEnd">
                    </div>
                    <div class="mb-3">
                        <label for="eventCalendar" class="form-label">Calendrier</label>
                        <select class="form-select" id="eventCalendar" required>
                            <?php if (!empty($calendars)): ?>
                                <?php foreach ($calendars as $index => $cal):
                                    $isFirst = ($index === 0);
                                ?>
                                    <option
                                        value="<?= $cal['calendar_id'] ?>"
                                        <?= $isFirst ? 'selected="selected"' : '' ?>
                                        style="background-color: <?= htmlspecialchars($cal['color']) ?>">
                                        <?= htmlspecialchars($cal['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="eventCategory" class="form-label">Catégorie</label>
                        <select class="form-select" id="eventCategory">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>" style="background-color: <?= htmlspecialchars($category['bg_color']) ?>; color: <?= htmlspecialchars($category['color']) ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger d-none" id="deleteEventBtn">Supprimer</button>
                <button type="button" class="btn btn-primary" id="saveEventBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer cet événement ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteEventBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<?php
// Récupérer le contenu capturé
$content = ob_get_clean();

// Inclure le layout principal avec les variables définies
include_once __DIR__ . '/../layouts/main.php';
