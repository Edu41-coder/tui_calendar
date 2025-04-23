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
        useDetailPopup: true,
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
        // Added month config to start on Monday with French day names
        month: {
            startDayOfWeek: 1,
            daynames: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']
        },
        template: {
            // Cette méthode traduit le CONTENU des événements toute la journée
            allday: function(schedule) {
                return schedule.title;
            },
            // Cette méthode traduit l'EN-TÊTE de la section toute la journée
            alldayTitle: function() {
                return '<div style="text-align: center; width: 100%;">Toute la journée</div>';
            },
            time(schedule) {
                const calColor = schedule.raw?.calendarColor || '#333';
                const catColor = schedule.raw?.categoryColor || '#999';
                return `
                  <div style="
                    width: 100%;
                    height: 100%;
                    box-sizing: border-box;
                    border: 8px solid \${calColor};
                    background-color: \${catColor};
                  ">
\                    \${schedule.title}
                  </div>
                `;
            }
        }
    });

    // Appelez la fonction juste après l’initialisation
    updateCalendarHeader();

    // Ajouter cette fonction après l'initialisation du calendrier
    function updateCalendarHeader() {
        const currentDate = calendar.getDate();
        const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                       'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        const month = months[currentDate.getMonth()];
        const year = currentDate.getFullYear();
        
        // CORRIGÉ : enlever les barres obliques inverses
        document.getElementById('calendar-date-header').textContent = `\${month} \${year}`.replace(/\\/g, '');
    }

    // Fonction pour mettre à jour l'état actif des boutons de vue
    function updateViewButtons(viewName) {
        // Supprimer la classe active de tous les boutons
        document.getElementById('day-view').classList.remove('active');
        document.getElementById('week-view').classList.remove('active');
        document.getElementById('month-view').classList.remove('active');
        
        // Ajouter la classe active au bouton correspondant à la vue actuelle
        if (viewName === 'day') {
            document.getElementById('day-view').classList.add('active');
        } else if (viewName === 'week') {
            document.getElementById('week-view').classList.add('active');
        } else if (viewName === 'month') {
            document.getElementById('month-view').classList.add('active');
        }
    }

    // Initialiser l'état des boutons
    updateViewButtons('week'); // Vue par défaut

    // Appeler la fonction immédiatement pour initialiser l'affichage
    updateCalendarHeader();

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
        
        // Fonction pour formater correctement une date pour un input datetime-local
        function formatDateForInput(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `\${year}-\${month}-\${day}T\${hours}:\${minutes}`;
        }
        
        // Afficher le modal Bootstrap
        const modal = new bootstrap.Modal(document.getElementById('createEventModal'));
        modal.show();
        
        // Gérer la soumission du formulaire
        document.getElementById('saveEventBtn').onclick = function() {
            const title = document.getElementById('eventTitle').value;
            const start = new Date(document.getElementById('eventStart').value);
            const end = new Date(document.getElementById('eventEnd').value);
            const calendarId = document.getElementById('eventCalendar').value;
            
            if (!title) {
                alert('Veuillez entrer un titre pour l\'événement');
                return;
            }
            
            // Récupérer la couleur de la catégorie
            const categorySelect = document.getElementById('eventCategory');
            const categoryColor = categorySelect.options[categorySelect.selectedIndex]
                                        .getAttribute('data-color');

            // Récupérer la couleur du calendrier
            const calendarSelect = document.getElementById('eventCalendar');
            const calId = calendarSelect.value;
            const calOption = calendarSelect.querySelector(`option[value="\${calId}"]`);
            // On suppose que le style de l'option contient un "background-color" (ex: style="background-color: #abcdef")
            const calendarColor = calOption.getAttribute('style')
              .replace('background-color:', '')
              .replace(';', '')
              .trim();

            // Utiliser ces couleurs pour l'événement
            calendar.createSchedules([{
                id: String(new Date().getTime()),
                calendarId: calendarId,
                title: title,
                start: start,
                end: end,
                isAllDay: false,                 // ou true, selon votre logique
                category: 'time', 
                raw: {
                    calendarColor: calendarColor,
                    categoryColor: categoryColor
                }
            }]);

            document.documentElement.style.setProperty('--calendarColor', calendarColor);
            document.documentElement.style.setProperty('--categoryColor', categoryColor);
            
            // Fermer le modal
            modal.hide();
        };
    });

    // Initialiser jQuery datepicker (au lieu d'utiliser $(document).ready)
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
            
            // Définir la date dans le calendrier TUI
            calendar.setDate(selectedDate);
            
            // Mettre à jour l'en-tête
            updateCalendarHeader();
        });
    });
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
                            <li class="list-group-item">
                                <span class="color-dot" style="background-color: <?= htmlspecialchars($category['bg_color']) ?>;"></span>
                                <?= htmlspecialchars($category['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de création d'événement -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createEventModalLabel">Créer un événement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <form>
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
            <select class="form-select" id="eventCalendar">
              <?php foreach ($calendars as $cal): ?>
                <option value="<?= $cal['calendar_id'] ?>" style="background-color: <?= $cal['color'] ?>">
                  <?= htmlspecialchars($cal['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="eventCategory" class="form-label">Catégorie</label>
            <select class="form-select" id="eventCategory">
              <?php foreach ($categories as $cat): ?>
                <option 
                  value="<?= $cat['id'] ?>"
                  data-color="<?= $cat['bg_color'] ?>"
                >
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-primary" id="saveEventBtn">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<?php
// Récupérer le contenu capturé
$content = ob_get_clean();

// Inclure le layout principal avec les variables définies
include_once __DIR__ . '/../layouts/main.php';