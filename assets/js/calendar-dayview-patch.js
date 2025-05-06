/**
 * Correctif pour les problèmes d'affichage des événements horaires dans la vue jour
 */
(function() {
    // Fonction auto-exécutée (IIFE) pour isoler le code et éviter les conflits 
    // de variables avec d'autres scripts
    
    // Vérification de sécurité: s'assurer que TUI Calendar est bien chargé
    // avant d'essayer d'appliquer le correctif
    if (typeof tui === 'undefined' || !tui.Calendar) {
        console.error("TUI Calendar n'est pas chargé. Le patch ne peut pas être appliqué.");
        return;
    }

    // === PREMIÈRE PARTIE: CORRECTIF POUR createSchedules ===
    
    // Sauvegarde de la méthode originale pour pouvoir l'appeler plus tard
    // et préserver le comportement de base
    var originalCreateSchedules = tui.Calendar.prototype.createSchedules;
    
    // Remplacement de la méthode par notre version améliorée qui corrige
    // les problèmes d'affichage des événements en vue jour
    tui.Calendar.prototype.createSchedules = function(schedules, silent) {
        // Appel de la méthode originale pour maintenir toutes les fonctionnalités de base
        var result = originalCreateSchedules.call(this, schedules, silent);
        
        // Correctif spécifique uniquement appliqué en vue jour, où le problème se produit
        if (this.getViewName() === 'day') {
            // Accès aux composants internes du calendrier pour forcer le re-rendu
            // Cette partie est fragile et peut nécessiter des ajustements si la structure interne change
            var timeGridView = this._controller.view.children.single('timeGrid');
            
            if (timeGridView) {
                // Utilisation d'un délai pour s'assurer que le DOM est prêt
                // et que les autres opérations asynchrones sont terminées
                setTimeout(function() {
                    // Récupérer les données des événements déjà en mémoire
                    // et forcer le rendu complet de la vue avec ces données
                    var viewModels = timeGridView._cacheParentViewModel;
                    if (viewModels) {
                        timeGridView.render(viewModels);
                    }
                }, 50);
            }
        }
        
        // Retourner le résultat original pour maintenir la compatibilité
        return result;
    };

    // === DEUXIÈME PARTIE: CORRECTIF POUR clear ===
    
    // Sauvegarde de la méthode clear originale
    var originalClear = tui.Calendar.prototype.clear;
    
    // Remplacement de la méthode clear pour ajouter une indication
    // que la vue jour a été nettoyée
    tui.Calendar.prototype.clear = function() {
        // Appel de la méthode originale avec tous les arguments reçus
        var result = originalClear.apply(this, arguments);
        
        // Ajouter un indicateur pour savoir si on doit forcer un re-rendu
        // lors du prochain appel à createSchedules
        this._dayViewNeedsRerender = this.getViewName() === 'day';
        
        // Retourner le résultat original
        return result;
    };
})();