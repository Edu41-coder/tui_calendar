# Flux de communication entre frontend et backend dans l'application de calendrier

L'application suit une architecture en couches bien définie, voici le cycle complet d'une requête:

## Sens aller (Frontend → Backend)

1.  **Frontend (frontend-calendar.js)**
    
    -   L'interface utilisateur déclenche une action (clic sur un bouton, déplacement d'événement, etc.)
    -   Appelle une méthode du module  CalendarBackend
2.  **Couche d'abstraction (backend-calendar.js)**
    
    -   Formate les données (notamment les dates avec  formatLocalISOString())
    -   Effectue une requête AJAX vers  ajax-handler.php  avec une action spécifique
   ```javascript
$.ajax({
    url: baseUrl + 'ajax-handler.php?action=get-events',
    // ...paramètres...
});
```
**Point d'entrée (ajax-handler.php)**

-   Reçoit la requête HTTP
-   Identifie l'action demandée dans le paramètre d'URL
-   Inclut les fichiers nécessaires
```php
<?php
switch($action) {
    case 'get-events':
        $eventController = new EventController();
        $eventController->getEvents();
        break;
}
?>
```
1.  **Contrôleur (EventController.php)**
    
    -   Traite la logique métier
    -   Interagit avec les modèles pour accéder aux données
    -   Prépare la réponse

## Sens retour (Backend → Frontend)

1.  **Contrôleur → Point d'entrée**
    
    -   Le contrôleur envoie directement la réponse JSON (pas de retour à ajax-handler)

```php
<?php
$this->jsonResponse($formattedEvents);
```
2.  **Point d'entrée → Couche d'abstraction**
    
    -   La réponse HTTP est renvoyée à la requête AJAX
3.  **Couche d'abstraction → Frontend**
    
    -   backend-calendar.js  reçoit la réponse dans sa fonction  success
    -   Traite la réponse et appelle le callback
 ```php
success: function(response) {
    callback(null, response);
}
```   
1.  **Frontend**
    
    -   Reçoit les données via le callback
    -   Met à jour l'interface utilisateur

Cette architecture en couches permet une séparation claire des responsabilités, facilitant la maintenance et les tests du code.

    

 



