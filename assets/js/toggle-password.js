document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour créer un gestionnaire d'événements pour chaque champ de mot de passe
    function setupPasswordToggle(toggleButtonClass) {
        const toggleButtons = document.querySelectorAll(toggleButtonClass);
        
        toggleButtons.forEach(toggleBtn => {
            toggleBtn.addEventListener('click', function() {
                // Trouver l'input associé (frère précédent dans le groupe)
                const inputGroup = this.closest('.input-group');
                const passwordInput = inputGroup.querySelector('input[type="password"], input[type="text"]');
                const toggleIcon = this.querySelector('i');
                
                if (!passwordInput) return;
                
                // Basculer le type de l'input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Basculer l'icône
                if (type === 'password') {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                } else {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                }
            });
        });
    }
    
    // Initialiser les toggles
    setupPasswordToggle('.password-toggle');
    
    // Pour la rétrocompatibilité avec les anciennes pages
    if (document.getElementById('togglePassword')) {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');
        
        if (togglePassword && passwordInput && toggleIcon) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                if (type === 'password') {
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                } else {
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                }
            });
        }
    }
});