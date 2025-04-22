/**
 * Script principal pour la gestion du calendrier TUI
 */
document.addEventListener('DOMContentLoaded', function() {
    // Récupération des données transmises depuis PHP
    const calendarSettings = window.calendarSettings || {};
    const calendarData = window.calendarData || [];
    
    // Configuration du calendrier TUI avec Bootstrap
    const container = document.getElementById('calendar');
    const options = {
        defaultView: calendarSettings.default_view || 'week',
        usageStatistics: false,
        week: {
            startDayOfWeek: parseInt(calendarSettings.start_day_of_week || 0),
            daynames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
            workweek: true,
            showTimezoneCollapseButton: true,
            timezonesCollapsed: false
        },
        month: {
            daynames: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
            startDayOfWeek: parseInt(calendarSettings.start_day_of_week || 0),
            narrowWeekend: false
        },
        calendars: calendarData,
        theme: calendarSettings.custom_theme || null
    };
    
    // Initialiser le calendrier
    const calendar = new tui.Calendar(container, options);
    
    // Charger les événements
    loadEvents();
    
    // Fonction pour charger les événements
    function loadEvents() {
        const apiUrl = document.getElementById('calendar').dataset.eventsUrl;
        
        fetch(apiUrl + '?start=' + 
              calendar.getDateRangeStart().toDate().toISOString().slice(0, 10) +
              '&end=' + calendar.getDateRangeEnd().toDate().toISOString().slice(0, 10))
            .then(response => response.json())
            .then(data => {
                calendar.clear();
                calendar.createEvents(data);
            })
            .catch(error => console.error('Erreur lors du chargement des événements:', error));
    }
    
    // Gérer les boutons de navigation et de vue
    document.getElementById('today-btn').addEventListener('click', () => {
        calendar.today();
        loadEvents();
    });
    
    document.getElementById('prev-btn').addEventListener('click', () => {
        calendar.prev();
        loadEvents();
    });
    
    document.getElementById('next-btn').addEventListener('click', () => {
        calendar.next();
        loadEvents();
    });
    
    document.getElementById('day-view').addEventListener('click', () => {
        calendar.changeView('day');
        updateActiveViewButton('day-view');
    });
    
    document.getElementById('week-view').addEventListener('click', () => {
        calendar.changeView('week');
        updateActiveViewButton('week-view');
    });
    
    document.getElementById('month-view').addEventListener('click', () => {
        calendar.changeView('month');
        updateActiveViewButton('month-view');
    });
    
    function updateActiveViewButton(activeId) {
        ['day-view', 'week-view', 'month-view'].forEach(id => {
            document.getElementById(id).classList.remove('active');
        });
        document.getElementById(activeId).classList.add('active');
    }
    
    // Implémentation de la visibilité des calendriers
    document.querySelectorAll('.calendar-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const calendarId = checkbox.value;
            const isVisible = checkbox.checked;
            const toggleUrl = checkbox.dataset.toggleUrl;
            
            // Mettre à jour la visibilité sur le serveur
            fetch(toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'calendar_id=' + calendarId + '&visible=' + (isVisible ? '1' : '0')
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage du calendrier
                    calendar.setCalendarVisibility(calendarId, isVisible);
                } else {
                    // Restaurer l'état précédent du checkbox en cas d'erreur
                    checkbox.checked = !isVisible;
                    alert('Erreur lors de la mise à jour de la visibilité');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                checkbox.checked = !isVisible;
            });
        });
    });
    
    // Gestion du modal pour les événements
    const eventModal = new bootstrap.Modal(document.getElementById('event-modal'));
    const eventForm = document.getElementById('event-form');
    
    // Clic sur un événement pour l'éditer
    calendar.on('clickEvent', function(event) {
        const eventData = calendar.getEvent(event.id, event.calendarId);
        
        document.getElementById('event-modal-title').textContent = 'Modifier l\'événement';
        document.getElementById('event-id').value = eventData.id;
        document.getElementById('event-title').value = eventData.title;
        document.getElementById('event-calendar').value = eventData.calendarId;
        document.getElementById('event-category').value = eventData.raw?.category_id || '';
        document.getElementById('event-all-day').checked = eventData.isAllDay;
        document.getElementById('event-start').value = formatDateTimeForInput(eventData.start);
        document.getElementById('event-end').value = formatDateTimeForInput(eventData.end);
        document.getElementById('event-location').value = eventData.location || '';
        document.getElementById('event-description').value = eventData.body || '';
        
        document.getElementById('delete-event').style.display = 'block';
        eventModal.show();
    });
    
    // Clic sur une cellule pour créer un événement
    calendar.on('clickTimezonesCollapseBtn', function(timezonesCollapsed) {
        calendar.setOptions({ week: { timezonesCollapsed } });
    });
    
    calendar.on('beforeCreateSchedule', function(event) {
        document.getElementById('event-modal-title').textContent = 'Nouvel événement';
        document.getElementById('event-id').value = '';
        document.getElementById('event-title').value = '';
        document.getElementById('event-calendar').value = event.calendarId || document.getElementById('event-calendar').options[0].value;
        document.getElementById('event-category').value = '';
        document.getElementById('event-all-day').checked = event.isAllDay;
        document.getElementById('event-start').value = formatDateTimeForInput(event.start);
        document.getElementById('event-end').value = formatDateTimeForInput(event.end);
        document.getElementById('event-location').value = '';
        document.getElementById('event-description').value = '';
        
        document.getElementById('delete-event').style.display = 'none';
        eventModal.show();
    });
    
    // Enregistrer un événement
    document.getElementById('save-event').addEventListener('click', function() {
        const eventId = document.getElementById('event-id').value;
        const title = document.getElementById('event-title').value;
        const calendarId = document.getElementById('event-calendar').value;
        const categoryId = document.getElementById('event-category').value;
        const isAllDay = document.getElementById('event-all-day').checked;
        const start = document.getElementById('event-start').value;
        const end = document.getElementById('event-end').value;
        const location = document.getElementById('event-location').value;
        const description = document.getElementById('event-description').value;
        
        if (!title || !start || !end) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        const saveUrl = document.getElementById('event-form').dataset.saveUrl;
        
        const eventData = {
            id: eventId || undefined,
            title: title,
            calendarId: calendarId,
            categoryId: categoryId || null,
            isAllDay: isAllDay,
            start: start,
            end: end,
            location: location,
            body: description
        };
        
        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(eventData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                eventModal.hide();
                loadEvents(); // Recharger les événements
            } else {
                alert('Erreur lors de l\'enregistrement: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de l\'enregistrement');
        });
    });
    
    // Supprimer un événement
    document.getElementById('delete-event').addEventListener('click', function() {
        const eventId = document.getElementById('event-id').value;
        
        if (!eventId) return;
        
        if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
            const deleteUrl = document.getElementById('event-form').dataset.deleteUrl;
            
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: eventId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    eventModal.hide();
                    loadEvents(); // Recharger les événements
                } else {
                    alert('Erreur lors de la suppression: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la suppression');
            });
        }
    });
    
    // Formater une date pour l'input datetime-local
    function formatDateTimeForInput(date) {
        if (!date) return '';
        
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
});