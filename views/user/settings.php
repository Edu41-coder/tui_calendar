<?php
$pageTitle = 'Paramètres du calendrier';
$breadcrumbs = [
    'Calendriers' => Routes::url('calendar', 'index'),
    'Paramètres' => '#'
];
include_once __DIR__ . '/../layouts/main.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Menu latéral -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Paramètres</h4>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item active">
                            <i class="fas fa-sliders-h"></i> Préférences générales
                        </li>
                        <li class="list-group-item">
                            <a href="<?= Routes::url('calendar', 'calendars') ?>">
                                <i class="fas fa-calendar-alt"></i> Gérer les calendriers
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?= Routes::url('calendar', 'categories') ?>">
                                <i class="fas fa-tags"></i> Gérer les catégories
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?= Routes::url('calendar', 'index') ?>">
                                <i class="fas fa-arrow-left"></i> Retour au calendrier
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Paramètres généraux -->
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Préférences générales</h4>
                    <?php if (!empty($success)): ?>
                        <div class="badge bg-success">Enregistré</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?= Routes::url('user', 'saveSettings') ?>">
                        <!-- Vue par défaut -->
                        <div class="mb-4">
                            <h5>Vue du calendrier</h5>
                            <div class="mb-3">
                                <label for="default_view" class="form-label">Vue par défaut</label>
                                <select class="form-select" id="default_view" name="default_view">
                                    <option value="day" <?= isset($settings['default_view']) && $settings['default_view'] == 'day' ? 'selected' : '' ?>>Jour</option>
                                    <option value="week" <?= isset($settings['default_view']) && $settings['default_view'] == 'week' ? 'selected' : '' ?>>Semaine</option>
                                    <option value="month" <?= isset($settings['default_view']) && $settings['default_view'] == 'month' ? 'selected' : '' ?>>Mois</option>
                                </select>
                                <div class="form-text">Vue affichée à l'ouverture du calendrier.</div>
                            </div>

                            <div class="mb-3">
                                <label for="start_day_of_week" class="form-label">Premier jour de la semaine</label>
                                <select class="form-select" id="start_day_of_week" name="start_day_of_week">
                                    <option value="0" <?= isset($settings['start_day_of_week']) && $settings['start_day_of_week'] == '0' ? 'selected' : '' ?>>Dimanche</option>
                                    <option value="1" <?= isset($settings['start_day_of_week']) && $settings['start_day_of_week'] == '1' ? 'selected' : '' ?>>Lundi</option>
                                    <option value="2" <?= isset($settings['start_day_of_week']) && $settings['start_day_of_week'] == '2' ? 'selected' : '' ?>>Mardi</option>
                                    <option value="3" <?= isset($settings['start_day_of_week']) && $settings['start_day_of_week'] == '3' ? 'selected' : '' ?>>Mercredi</option>
                                    <option value="4" <?= isset($settings['start_day_of_week']) && $settings['start_day_of_week'] == '4' ? 'selected' : '' ?>>Jeudi</option>
                                    <option value="5" <?= isset($settings['start_day_of_week']) && $settings['start_day_of_week'] == '5' ? 'selected' : '' ?>>Vendredi</option>
                                    <option value="6" <?= isset($settings['start_day_of_week']) && $settings['start_day_of_week'] == '6' ? 'selected' : '' ?>>Samedi</option>
                                </select>
                            </div>
                        </div>

                        <!-- Paramètres d'affichage -->
                        <div class="mb-4">
                            <h5>Personnalisation</h5>

                            <div class="mb-3">
                                <label for="theme_select" class="form-label">Thème du calendrier</label>
                                <select class="form-select" id="theme_select" name="theme">
                                    <option value="default" <?= isset($settings['theme']) && $settings['theme'] == 'default' ? 'selected' : '' ?>>Défaut</option>
                                    <option value="light" <?= isset($settings['theme']) && $settings['theme'] == 'light' ? 'selected' : '' ?>>Clair</option>
                                    <option value="dark" <?= isset($settings['theme']) && $settings['theme'] == 'dark' ? 'selected' : '' ?>>Sombre</option>
                                    <option value="custom" <?= isset($settings['theme']) && $settings['theme'] == 'custom' ? 'selected' : '' ?>>Personnalisé</option>
                                </select>
                            </div>

                            <!-- Options de personnalisation -->
                            <div id="custom-theme-options" class="border p-3 rounded mb-3 <?= isset($settings['theme']) && $settings['theme'] == 'custom' ? '' : 'd-none' ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="calendar_bg_color" class="form-label">Couleur de fond</label>
                                            <input type="color" class="form-control form-control-color" id="calendar_bg_color" name="custom_theme[backgroundColor]" value="<?= $settings['custom_theme']['backgroundColor'] ?? '#ffffff' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="calendar_grid_color" class="form-label">Couleur de la grille</label>
                                            <input type="color" class="form-control form-control-color" id="calendar_grid_color" name="custom_theme[gridColor]" value="<?= $settings['custom_theme']['gridColor'] ?? '#e1e1e1' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="calendar_today_bg" class="form-label">Surbrillance aujourd'hui</label>
                                            <input type="color" class="form-control form-control-color" id="calendar_today_bg" name="custom_theme[todayColor]" value="<?= $settings['custom_theme']['todayColor'] ?? '#f5f5f5' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="calendar_weekend_bg" class="form-label">Fond week-end</label>
                                            <input type="color" class="form-control form-control-color" id="calendar_weekend_bg" name="custom_theme[weekendColor]" value="<?= $settings['custom_theme']['weekendColor'] ?? '#fafafa' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_weekends" name="show_weekends" <?= isset($settings['show_weekends']) && $settings['show_weekends'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="show_weekends">Afficher les week-ends</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_week_numbers" name="show_week_numbers" <?= isset($settings['show_week_numbers']) && $settings['show_week_numbers'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="show_week_numbers">Afficher les numéros de semaine</label>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="mb-4">
                            <h5>Notifications</h5>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_notifications" name="enable_notifications" <?= isset($settings['enable_notifications']) && $settings['enable_notifications'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enable_notifications">Activer les notifications</label>
                                </div>
                                <div class="form-text">Recevoir des notifications pour les événements à venir.</div>
                            </div>

                            <div class="mb-3">
                                <label for="notification_time" class="form-label">Rappel par défaut</label>
                                <select class="form-select" id="notification_time" name="notification_time" <?= isset($settings['enable_notifications']) && $settings['enable_notifications'] ? '' : 'disabled' ?>>
                                    <option value="0" <?= isset($settings['notification_time']) && $settings['notification_time'] == '0' ? 'selected' : '' ?>>À l'heure de l'événement</option>
                                    <option value="5" <?= isset($settings['notification_time']) && $settings['notification_time'] == '5' ? 'selected' : '' ?>>5 minutes avant</option>
                                    <option value="15" <?= isset($settings['notification_time']) && $settings['notification_time'] == '15' ? 'selected' : '' ?>>15 minutes avant</option>
                                    <option value="30" <?= isset($settings['notification_time']) && $settings['notification_time'] == '30' ? 'selected' : '' ?>>30 minutes avant</option>
                                    <option value="60" <?= isset($settings['notification_time']) && $settings['notification_time'] == '60' ? 'selected' : '' ?>>1 heure avant</option>
                                    <option value="120" <?= isset($settings['notification_time']) && $settings['notification_time'] == '120' ? 'selected' : '' ?>>2 heures avant</option>
                                    <option value="1440" <?= isset($settings['notification_time']) && $settings['notification_time'] == '1440' ? 'selected' : '' ?>>1 jour avant</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer les paramètres
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Importation/Exportation -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Importation / Exportation</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Exporter</h5>
                            <p>Téléchargez votre calendrier au format iCalendar (ICS)</p>
                            <a href="<?= Routes::url('event', 'export') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-file-export me-2"></i>Exporter tous les calendriers
                            </a>
                            
                            <div class="mt-3">
                                <label for="export-calendar" class="form-label">Ou sélectionnez un calendrier spécifique :</label>
                                <div class="input-group">
                                    <select class="form-select" id="export-calendar">
                                        <?php foreach ($calendars as $calendar): ?>
                                        <option value="<?= $calendar['calendar_id'] ?>"><?= htmlspecialchars($calendar['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" id="export-selected" class="btn btn-outline-primary">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Importer</h5>
                            <p>Importez un fichier iCalendar (ICS) dans votre calendrier</p>
                            <form action="<?= Routes::url('event', 'import') ?>" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="import-calendar" class="form-label">Importer dans :</label>
                                    <select class="form-select" id="import-calendar" name="calendar_id">
                                        <?php foreach ($calendars as $calendar): ?>
                                        <option value="<?= $calendar['calendar_id'] ?>"><?= htmlspecialchars($calendar['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="ics-file" class="form-label">Fichier ICS :</label>
                                    <input type="file" class="form-control" id="ics-file" name="ics_file" accept=".ics" required>
                                </div>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-file-import me-2"></i>Importer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du thème personnalisé
    const themeSelect = document.getElementById('theme_select');
    const customThemeOptions = document.getElementById('custom-theme-options');
    
    themeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customThemeOptions.classList.remove('d-none');
        } else {
            customThemeOptions.classList.add('d-none');
        }
    });
    
    // Gestion des notifications
    const enableNotifications = document.getElementById('enable_notifications');
    const notificationTime = document.getElementById('notification_time');
    
    enableNotifications.addEventListener('change', function() {
        notificationTime.disabled = !this.checked;
    });
    
    // Exportation d'un calendrier spécifique
    const exportSelected = document.getElementById('export-selected');
    const exportCalendar = document.getElementById('export-calendar');
    
    exportSelected.addEventListener('click', function() {
        const calendarId = exportCalendar.value;
        window.location.href = '<?= Routes::url('event', 'export') ?>/' + calendarId;
    });
});
</script>