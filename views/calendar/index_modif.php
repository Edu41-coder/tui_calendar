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
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentSelection = null;
    
    const calendar = new tui.Calendar('#calendar', {
        defaultView: 'week',
        taskView: true,
        scheduleView: true,
        useCreationPopup: false, // Désactiver le popup par défaut
        useDetailPopup: true,
        calendars: {$calendarDataJSON},
    });

    // Navigation entre les vues
    document.getElementById('today-btn').addEventListener('click', () => calendar.today());
    document.getElementById('prev-btn').addEventListener('click', () => calendar.prev());
    document.getElementById('next-btn').addEventListener('click', () => calendar.next());
    document.getElementById('day-view').addEventListener('click', () => calendar.changeView('day'));
    document.getElementById('week-view').addEventListener('click', () => calendar.changeView('week'));
    document.getElementById('month-view').addEventListener('click', () => calendar.changeView('month'));
    
    // Gestionnaire pour la création de la sélection
    calendar.on('beforeCreateSchedule', function(eventObj) {
        // Stocker la sélection courante
        currentSelection = eventObj;
        
        // Créer un événement temporaire pour visualiser la sélection
        const tempId = 'temp-selection-' + Date.now();
        calendar.createSchedules([{
            id: tempId,
            calendarId: eventObj.calendarId || '1',
            title: 'Nouvelle sélection',
            start: eventObj.start,
            end: eventObj.end,
            category: 'time',
            isVisible: true,
            backgroundColor: '#DBE9FA',
            borderColor: '#4285F4',
            dragBgColor: '#DBE9FA',
            dueDateClass: '',
            customStyle: {
                backgroundColor: '#DBE9FA',
                borderColor: '#4285F4',
                color: '#4285F4',
                opacity: 0.7
            }
        }]);
        
        // Identifier l'élément de sélection dans le DOM pour ajouter un gestionnaire de double-clic
        setTimeout(() => {
            // Attendre que l'élément soit rendu dans le DOM
            const scheduleElements = document.querySelectorAll('.tui-full-calendar-time-schedule');
            scheduleElements.forEach(element => {
                if (element.textContent.includes('Nouvelle sélection')) {
                    // Ajouter un gestionnaire de double-clic
                    element.addEventListener('dblclick', () => {
                        // Supprimer l'événement temporaire
                        calendar.deleteSchedule(tempId, eventObj.calendarId || '1');
                        
                        // Ouvrir le modal avec les informations de la sélection
                        openEventModal(currentSelection);
                    });
                }
            });
        }, 100);
    });
    
    // Gérer le clic simple sur le calendrier pour effacer la sélection précédente
    document.getElementById('calendar').addEventListener('click', function(e) {
        // Vérifier que le clic n'est pas sur un élément de sélection
        if (!e.target.closest('.tui-full-calendar-time-schedule')) {
            // Effacer les sélections temporaires
            const schedules = calendar.getSchedules();
            schedules.forEach(schedule => {
                if (schedule.id.toString().startsWith('temp-selection-')) {
                    calendar.deleteSchedule(schedule.id, schedule.calendarId);
                }
            });
            currentSelection = null;
        }
    });
    
    // Fonction pour ouvrir le modal avec les détails de l'événement
    function openEventModal(eventObj) {
        // Formatage de la date/heure de début
        const startDate = new Date(eventObj.start);
        const formattedStart = startDate.toISOString().slice(0, 16); // Format YYYY-MM-DDTHH:MM
        
        // Formatage de la date/heure de fin
        const endDate = new Date(eventObj.end || new Date(startDate.getTime() + 60 * 60 * 1000));
        const formattedEnd = endDate.toISOString().slice(0, 16);
        
        // Remplir les champs avec les valeurs initiales
        document.getElementById('eventTitle').value = '';
        document.getElementById('eventStart').value = formattedStart;
        document.getElementById('eventEnd').value = formattedEnd;
        document.getElementById('eventCalendar').value = eventObj.calendarId || '1';
        
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
            
            // Créer l'événement réel
            calendar.createSchedules([{
                id: String(new Date().getTime()),
                calendarId: calendarId,
                title: title,
                start: start,
                end: end,
                category: 'time', // Catégorie 'time' pour les événements avec heure spécifique
                isAllDay: false,
                location: eventObj.location || '',
                raw: {
                    class: eventObj.raw?.class || 'public'
                },
                state: 'busy'
            }]);
            
            // Fermer le modal
            modal.hide();
        };
    }
});
</script>
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
        <!-- Calendriers et filtres -->
        <div class="col-md-3 col-lg-2 mb-4">
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
        
        <!-- Calendrier TUI -->
        <div class="col-md-9 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div id="calendar" style="height: 800px;"></div>
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