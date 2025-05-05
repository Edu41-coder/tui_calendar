<?php 
$pageTitle = 'Modifier le calendrier';
$breadcrumbs = [
    'Calendriers' => Routes::url('calendar', 'index'),
    'Gérer les calendriers' => Routes::url('calendar', 'calendars'),
    'Modifier le calendrier' => '#'
];
include_once __DIR__ . '/../layouts/main.php'; 
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0"><i class="fas fa-edit me-2"></i>Modifier le calendrier</h2>
                    <a href="<?php echo Routes::url('calendar', 'calendars'); ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($calendar)): ?>
                        <form method="post" action="<?php echo Routes::url('calendar', 'editCalendar', ['id' => $calendar['calendar_id']]); ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom du calendrier <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($calendar['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="color" class="form-label">Couleur</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="color" name="color" 
                                           value="<?php echo htmlspecialchars($calendar['color']); ?>">
                                    <span class="input-group-text">Code couleur</span>
                                    <input type="text" class="form-control" id="color-text" 
                                           value="<?php echo htmlspecialchars($calendar['color']); ?>">
                                </div>
                                <small class="form-text text-muted">Cette couleur sera utilisée pour identifier votre calendrier et ses événements.</small>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_visible" name="is_visible" value="1"
                                       <?php echo (isset($calendar['is_visible']) && $calendar['is_visible']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_visible">Visible par défaut</label>
                                <div class="form-text">Si coché, les événements de ce calendrier seront visibles dans la vue principale.</div>
                            </div>
                            
                            <?php if (isset($calendar['created_at'])): ?>
                            <div class="mb-3">
                                <label class="form-label">Créé le</label>
                                <p class="form-control-static">
                                    <?php echo date('d/m/Y à H:i', strtotime($calendar['created_at'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal">
                                    <i class="fas fa-trash me-2"></i>Supprimer
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Le calendrier demandé n'a pas été trouvé.
                        </div>
                        <a href="<?php echo Routes::url('calendar', 'calendars'); ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Retour à la liste des calendriers
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="confirm-delete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce calendrier ?</p>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cette action supprimera également tous les événements associés à ce calendrier et ne peut pas être annulée.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="post" action="<?php echo Routes::url('calendar', 'deleteCalendar', ['id' => $calendar['calendar_id']]); ?>">
                    <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Synchroniser les champs de couleur
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('color');
        const colorText = document.getElementById('color-text');
        
        colorPicker.addEventListener('input', function() {
            colorText.value = colorPicker.value;
        });
        
        colorText.addEventListener('input', function() {
            // Vérifier si la valeur est un code hexadécimal valide
            const regex = /^#[0-9A-Fa-f]{6}$/;
            if (regex.test(colorText.value)) {
                colorPicker.value = colorText.value;
            }
        });
    });
</script>