<?php include_once __DIR__ . '/../layouts/main.php'; ?>
<script src="/tui_calendar/assets/js/toggle-password.js"></script>
<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Menu Utilisateur</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item active">
                            <i class="fas fa-user"></i> Profil
                        </li>
                        <li class="list-group-item">
                            <a href="<?= Routes::url('user', 'settings') ?>">
                                <i class="fas fa-cog"></i> Paramètres de calendrier
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?= Routes::url('calendar', 'index') ?>">
                                <i class="fas fa-calendar-alt"></i> Mon calendrier
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?= Routes::url('user', 'logout') ?>">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Mon Profil</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= Routes::url('user', 'profile') ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                            <small class="text-muted">Le nom d'utilisateur ne peut pas être modifié.</small>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <hr>
                        <h5>Changer le mot de passe</h5>
                        <p class="text-muted">Laissez vide si vous ne souhaitez pas modifier votre mot de passe</p>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <button type="button" class="btn btn-outline-secondary password-toggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <button type="button" class="btn btn-outline-secondary password-toggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">6 caractères minimum</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                <button type="button" class="btn btn-outline-secondary password-toggle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Mettre à jour mon profil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>