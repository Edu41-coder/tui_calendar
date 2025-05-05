<?php
$pageTitle = 'Modifier une catégorie';
$breadcrumbs = [
    'Calendriers' => Routes::url('calendar', 'index'),
    'Catégories' => Routes::url('calendar', 'categories'),
    'Modifier la catégorie' => '#'
];
include_once __DIR__ . '/../layouts/main.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0"><i class="fas fa-tag me-2"></i>Modifier la catégorie</h2>
                    <a href="<?= Routes::url('calendar', 'categories'); ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($category)): ?>
                        <form method="post" action="<?= Routes::url('calendar', 'editCategory', ['id' => $category['category_id']]); ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom de la catégorie <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($category['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="color" class="form-label">Couleur du texte</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="color" name="color" 
                                           value="<?= htmlspecialchars($category['color']); ?>">
                                    <span class="input-group-text">Code</span>
                                    <input type="text" class="form-control" id="color-text" 
                                           value="<?= htmlspecialchars($category['color']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bg_color" class="form-label">Couleur d'arrière-plan</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="bg_color" name="bg_color" 
                                           value="<?= htmlspecialchars($category['bg_color']); ?>">
                                    <span class="input-group-text">Code</span>
                                    <input type="text" class="form-control" id="bg-color-text" 
                                           value="<?= htmlspecialchars($category['bg_color']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="border_color" class="form-label">Couleur de bordure</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="border_color" name="border_color" 
                                           value="<?= htmlspecialchars($category['border_color'] ?? $category['bg_color']); ?>">
                                    <span class="input-group-text">Code</span>
                                    <input type="text" class="form-control" id="border-color-text" 
                                           value="<?= htmlspecialchars($category['border_color'] ?? $category['bg_color']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Aperçu</label>
                                <div id="preview" style="
                                    padding: 10px 15px;
                                    background-color: <?= htmlspecialchars($category['bg_color']); ?>;
                                    color: <?= htmlspecialchars($category['color']); ?>;
                                    border: 1px solid <?= htmlspecialchars($category['border_color'] ?? $category['bg_color']); ?>;
                                    border-radius: 4px;
                                    display: inline-block;
                                ">
                                    Exemple d'événement
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete-category-modal">
                                    <i class="fas fa-trash me-2"></i>Supprimer
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            La catégorie demandée n'a pas été trouvée.
                        </div>
                        <a href="<?= Routes::url('calendar', 'categories'); ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Retour à la liste des catégories
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="delete-category-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button