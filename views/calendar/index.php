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

<!-- Ajouter ces variables pour les rendre disponibles au JavaScript -->
<script>
    // Données injectées par PHP
    window.calendarData = {$calendarDataJSON};
    window.calendarIds = {$calendarIdsJSON};
</script>

<!-- Inclure les scripts factorisés -->
<script src="/tui_calendar/assets/js/frontend-calendar.js"></script>
<script src="/tui_calendar/assets/js/backend-calendar.js"></script>

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
