<?php
$pageTitle = 'Gérer les calendriers';
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
                        <li class="list-group-item">
                            <a href="<?= Routes::url('user', 'settings') ?>">
                                <i class="fas fa-sliders-h"></i> Préférences générales
                            </a>
                        </li>
                        <li class="list-group-item active">
                            <i class="fas fa-calendar-alt"></i> Gérer les calendriers
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
            <!-- Liste des calendriers -->
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Mes calendriers</h4>
                    <a href="<?= Routes::url('calendar', 'createCalendar') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Nouveau calendrier
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($calendars)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Vous n'avez pas encore créé de calendrier.
                            <a href="<?= Routes::url('calendar', 'createCalendar') ?>" class="alert-link">Créez votre premier calendrier</a>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Couleur</th>
                                        <th>Événements</th>
                                        <th>Visibilité</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($calendars as $calendar): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($calendar['name']) ?></strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($calendar['color']) ?>; border-radius: 4px;" class="me-2"></div>
                                                    <span class="small"><?= htmlspecialchars($calendar['color']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= isset($calendar['event_count']) ? $calendar['event_count'] : '0' ?></span>
                                            </td>
                                            <td>
                                                <?php if (isset($calendar['is_visible']) && $calendar['is_visible']): ?>
                                                    <span class="badge bg-success">Visible</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Masqué</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= Routes::url('calendar', 'editCalendar', ['id' => $calendar['calendar_id']]) ?>" class="btn btn-outline-primary" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger toggle-delete-modal" 
                                                            data-bs-toggle="modal" data-bs-target="#delete-calendar-modal" 
                                                            data-calendar-id="<?= $calendar['calendar_id'] ?>" 
                                                            data-calendar-name="<?= htmlspecialchars($calendar['name']) ?>"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Partage & Importation -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Partage de calendrier</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Vous pouvez générer des liens de partage pour permettre à d'autres personnes de voir vos calendriers.
                    </div>
                    
                    <form class="mb-4">
                        <div class="mb-3">
                            <label for="share-calendar" class="form-label">Sélectionnez un calendrier à partager :</label>
                            <select class="form-select" id="share-calendar">
                                <?php foreach ($calendars as $calendar): ?>
                                <option value="<?= $calendar['calendar_id'] ?>"><?= htmlspecialchars($calendar['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="share-readonly" checked>
                                <label class="form-check-label" for="share-readonly">Lecture seule</label>
                            </div>
                            <div class="form-text">Si activé, les utilisateurs ne pourront que voir les événements.</div>
                        </div>
                        <button type="button" id="generate-share-link" class="btn btn-primary">
                            <i class="fas fa-link me-2"></i>Générer un lien de partage
                        </button>
                    </form>
                    
                    <div id="share-link-container" class="d-none">
                        <hr>
                        <h5>Lien de partage généré</h5>
                        <div class="input-group mb-3">
                            <input type="text" id="share-link" class="form-control" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copy-share-link">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            Ce lien reste valide jusqu'à ce que vous génériez un nouveau lien ou que vous le révoquiez.
                        </div>
                        <button type="button" id="revoke-share-link" class="btn btn-outline-danger mt-2">
                            <i class="fas fa-times-circle me-2"></i>Révoquer le lien
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="delete-calendar-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Supprimer le calendrier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le calendrier <strong id="calendar-name-to-delete"></strong> ?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cette action supprimera également tous les événements associés à ce calendrier et ne peut pas être annulée.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="delete-calendar-btn" class="btn btn-danger">Supprimer définitivement</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la modal de suppression
    const deleteButtons = document.querySelectorAll('.toggle-delete-modal');
    const calendarNameToDelete = document.getElementById('calendar-name-to-delete');
    const deleteCalendarBtn = document.getElementById('delete-calendar-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const calendarId = this.getAttribute('data-calendar-id');
            const calendarName = this.getAttribute('data-calendar-name');
            
            calendarNameToDelete.textContent = calendarName;
            deleteCalendarBtn.href = '<?= Routes::url('calendar', 'deleteCalendar', ['id' => '']) ?>' + calendarId;
        });
    });
    
    // Gestion du partage de calendrier
    const generateShareLinkBtn = document.getElementById('generate-share-link');
    const shareLinkContainer = document.getElementById('share-link-container');
    const shareLink = document.getElementById('share-link');
    const copyShareLinkBtn = document.getElementById('copy-share-link');
    const revokeShareLinkBtn = document.getElementById('revoke-share-link');
    
    generateShareLinkBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const calendarId = document.getElementById('share-calendar').value;
        const readOnly = document.getElementById('share-readonly').checked ? 1 : 0;
        
        // Générer un UUID aléatoire (simulation)
        const shareToken = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
        
        // Construire le lien de partage
        const shareUrl = window.location.origin + '/tui_calendar/share/' + shareToken;
        
        // Afficher le lien
        shareLink.value = shareUrl;
        shareLinkContainer.classList.remove('d-none');
        
        // En production, envoyer une requête AJAX pour enregistrer le lien en base de données
        console.log('Génération de lien pour le calendrier ID:', calendarId, 'en lecture seule:', readOnly);
    });
    
    copyShareLinkBtn.addEventListener('click', function() {
        shareLink.select();
        document.execCommand('copy');
        this.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => {
            this.innerHTML = '<i class="fas fa-copy"></i>';
        }, 2000);
    });
    
    revokeShareLinkBtn.addEventListener('click', function() {
        shareLinkContainer.classList.add('d-none');
        shareLink.value = '';
        
        // En production, envoyer une requête AJAX pour révoquer le lien en base de données
        console.log('Révocation du lien de partage');
    });
});
</script>