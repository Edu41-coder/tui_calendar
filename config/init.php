<?php
// Charger la configuration de la base de données
require_once __DIR__ . '/db_config.php';

// Charger le système de routes
require_once __DIR__ . '/Routes.php';

// Autoloader pour charger automatiquement les modèles
spl_autoload_register(function($class_name) {
    // Chercher dans le dossier models
    $model_path = __DIR__ . '/../models/' . $class_name . '.php';
    if (file_exists($model_path)) {
        require_once $model_path;
    }
});

// Configurer les routes
Routes::setDefault('Calendar', 'index');

// Routes Utilisateur
Routes::add('login', ['controller' => 'User', 'action' => 'login']);
Routes::add('logout', ['controller' => 'User', 'action' => 'logout']);
Routes::add('register', ['controller' => 'User', 'action' => 'register']);
Routes::add('profile', ['controller' => 'User', 'action' => 'profile']);
Routes::add('settings', ['controller' => 'User', 'action' => 'settings']);
Routes::add('save-settings', ['controller' => 'User', 'action' => 'saveSettings']);

// Routes Calendrier
Routes::add('calendar', ['controller' => 'Calendar', 'action' => 'index']);
Routes::add('calendars', ['controller' => 'Calendar', 'action' => 'calendars']);
Routes::add('categories', ['controller' => 'Calendar', 'action' => 'categories']);
Routes::add('create-calendar', ['controller' => 'Calendar', 'action' => 'createCalendar']);
Routes::add('edit-calendar', ['controller' => 'Calendar', 'action' => 'editCalendar']);
Routes::add('delete-calendar', ['controller' => 'Calendar', 'action' => 'deleteCalendar']);
Routes::add('create-category', ['controller' => 'Calendar', 'action' => 'createCategory']);
Routes::add('edit-category', ['controller' => 'Calendar', 'action' => 'editCategory']);
Routes::add('delete-category', ['controller' => 'Calendar', 'action' => 'deleteCategory']);
Routes::add('toggle-visibility', ['controller' => 'Calendar', 'action' => 'toggleCalendarVisibility']);
Routes::add('get-events', ['controller' => 'Event', 'action' => 'getEvents']);

// Routes Événement
Routes::add('event-create', ['controller' => 'Event', 'action' => 'create']);
Routes::add('event-edit', ['controller' => 'Event', 'action' => 'edit']);
Routes::add('event-delete', ['controller' => 'Event', 'action' => 'delete']);
Routes::add('event-view', ['controller' => 'Event', 'action' => 'view']);
Routes::add('event-save', ['controller' => 'Event', 'action' => 'save']);
Routes::add('event-move', ['controller' => 'Event', 'action' => 'move']);
Routes::add('event-delete-ajax', ['controller' => 'Event', 'action' => 'deleteAjax']);
Routes::add('export', ['controller' => 'Event', 'action' => 'export']);
Routes::add('import', ['controller' => 'Event', 'action' => 'import']);
?>