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

// Préparer les scripts pour la page
$pageScripts = <<<HTML
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.fr.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
            time(schedule) {
                const calColor = schedule.raw?.calendarColor || '#333';
                const catColor = schedule.raw?.categoryColor || '#999';
                const textColor = schedule.raw?.categoryTextColor || '#000000';
                return `
                  <div style="
                    width: 100%;
                    height: 100%;
                    box-sizing: border-box;
                    border: 8px solid \${calColor};
                    background-color: \${catColor};
                    color: \${textColor};
                  ">
                    \${schedule.title}
                  </div>
                `;
            }
        }
    });

    // Intercepter l'événement de clic sur un événement existant
    calendar.on('clickSchedule', function(e) {
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

    // Navigation entre les vues
    document.getElementById('today-btn').addEventListener('click', () => {
        calendar.today();
        updateCalendarHeader();
    });
    
    document.getElementById('prev-btn').addEventListener('click', () => {
        calendar.prev();
        updateCalendarHeader();
    });
    
    document.getElementById('next-btn').addEventListener('click', () => {
        calendar.next(); 
        updateCalendarHeader();
    });
    
    document.getElementById('day-view').addEventListener('click', () => {
        calendar.changeView('day');
        updateViewButtons('day');
        updateCalendarHeader();
    });
    
    document.getElementById('week-view').addEventListener('click', () => {
        calendar.changeView('week');
        updateViewButtons('week');
        updateCalendarHeader();
    });
    
    document.getElementById('month-view').addEventListener('click', () => {
        calendar.changeView('month');
        updateViewButtons('month');
        updateCalendarHeader();
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
