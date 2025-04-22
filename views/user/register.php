<?php 
$pageTitle = 'Inscription';
include_once __DIR__ . '/../layouts/main.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Créer un compte</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?= Routes::url('user', 'register') ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($formData['username'] ?? '') ?>" required autofocus>
                            </div>
                            <small class="text-muted">Lettres, chiffres et underscores uniquement. 3-20 caractères.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nom complet</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <small class="text-muted">6 caractères minimum avec lettres et chiffres.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                J'accepte les <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">conditions d'utilisation</a>
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Créer mon compte
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Vous avez déjà un compte ? <a href="<?= Routes::url('user', 'login') ?>">Connectez-vous</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal des conditions d'utilisation -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Conditions d'utilisation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <h4>Conditions générales d'utilisation de TUI Calendar</h4>
                <p>Dernière mise à jour : <?= date('d/m/Y') ?></p>
                
                <h5>1. Acceptation des conditions</h5>
                <p>En vous inscrivant à TUI Calendar, vous acceptez d'être lié par ces conditions d'utilisation. 
                   Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser ce service.</p>
                
                <h5>2. Description du service</h5>
                <p>TUI Calendar est un service de gestion de calendrier personnel qui permet aux utilisateurs de créer, 
                   modifier et partager des événements.</p>
                
                <h5>3. Comptes utilisateurs</h5>
                <p>Pour utiliser TUI Calendar, vous devez créer un compte. Vous êtes responsable du maintien de la confidentialité 
                   de vos informations de connexion et de toutes les activités qui se produisent sous votre compte.</p>
                
                <h5>4. Politique de confidentialité</h5>
                <p>Votre utilisation de TUI Calendar est également soumise à notre politique de confidentialité, qui régit la collecte 
                   et l'utilisation de vos informations personnelles.</p>
                
                <h5>5. Propriété intellectuelle</h5>
                <p>Tous les droits de propriété intellectuelle relatifs à TUI Calendar appartiennent à leurs propriétaires respectifs.</p>
                
                <h5>6. Résiliation</h5>
                <p>Nous nous réservons le droit de suspendre ou de résilier votre compte à tout moment, pour quelque raison que ce soit.</p>
                
                <h5>7. Limitation de responsabilité</h5>
                <p>TUI Calendar est fourni "tel quel" sans garantie d'aucune sorte. Nous ne sommes pas responsables des dommages directs, 
                   indirects, accessoires ou consécutifs résultant de votre utilisation du service.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="acceptTerms">J'accepte</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script pour cocher automatiquement la case des conditions lorsque l'utilisateur clique sur "J'accepte"
        document.getElementById('acceptTerms').addEventListener('click', function() {
            document.getElementById('terms').checked = true;
        });
        
        // Validation côté client du formulaire
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const username = document.getElementById('username').value;
            
            // Vérifier que les mots de passe correspondent
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            
            // Vérifier la longueur du mot de passe
            if (password.length < 6) {
                event.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return;
            }
            
            // Vérifier le format du nom d'utilisateur
            if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
                event.preventDefault();
                alert('Le nom d\'utilisateur doit contenir entre 3 et 20 caractères (lettres, chiffres et underscores uniquement).');
                return;
            }
        });
    });
</script>