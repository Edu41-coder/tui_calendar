/**
 * Correctif minimal pour TUI Calendar 
 * - Corrige les erreurs de navigation uniquement
 */
(function() {
    // Vérification de sécurité
    if (typeof tui === 'undefined' || !tui.Calendar) {
        console.error("TUI Calendar n'est pas chargé. Le patch ne peut pas être appliqué.");
        return;
    }
    
    // Patch pour la navigation (corriger l'erreur recursive)
    var originalMove = tui.Calendar.prototype.move;
    tui.Calendar.prototype.move = function(offset, unit) {
        try {
            // Capturer et gérer les erreurs lors du déplacement
            return originalMove.call(this, offset, unit);
        } catch (e) {
            console.warn("Erreur lors du déplacement du calendrier:", e);
            
            // Solution de contournement manuelle sans utiliser la méthode move qui cause l'erreur
            var date = this.getDate();
            var newDate;
            
            // Dupliquer la logique de déplacement
            switch(unit) {
                case 'day':
                    newDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() + offset);
                    break;
                case 'week':
                    newDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() + (offset * 7));
                    break;
                case 'month':
                    newDate = new Date(date.getFullYear(), date.getMonth() + offset, 1);
                    break;
                default:
                    newDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() + offset);
            }
            
            // Utiliser setDate qui n'utilise pas la fonction problématique
            this.setDate(newDate);
            return this;
        }
    };
    
    // Aussi corriger next/prev car ils utilisent move
    var originalNext = tui.Calendar.prototype.next;
    tui.Calendar.prototype.next = function() {
        try {
            return originalNext.call(this);
        } catch (e) {
            console.warn("Erreur lors du passage au suivant, utilisation de l'alternative:", e);
            return this.move(1);
        }
    };
    
    var originalPrev = tui.Calendar.prototype.prev;
    tui.Calendar.prototype.prev = function() {
        try {
            return originalPrev.call(this);
        } catch (e) {
            console.warn("Erreur lors du passage au précédent, utilisation de l'alternative:", e);
            return this.move(-1);
        }
    };
})();