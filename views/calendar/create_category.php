<?php
$pageTitle = 'Créer une catégorie';
$breadcrumbs = [
    'Calendriers' => Routes::url('calendar', 'index'),
    'Catégories' => Routes::url('calendar', 'categories'),
    'Nouvelle catégorie' => '#'
];
include_once __DIR__ . '/../layouts/main.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0"><i class="fas fa-tag me-2"></i>Créer une nouvelle catégorie</h2>
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
                    
                    <form method="post" action="<?= Routes::url('calendar', 'createCategory'); ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom de la catégorie <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($category['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Couleur du texte</label>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color" id="text_color_white" value="#FFFFFF" checked>
                                    <label class="form-check-label" for="text_color_white">
                                        <span class="px-2 py-1 bg-dark text-white rounded">Blanc</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color" id="text_color_black" value="#000000">
                                    <label class="form-check-label" for="text_color_black">
                                        <span class="px-2 py-1 bg-light text-dark border rounded">Noir</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bg_color" class="form-label">Couleur d'arrière-plan</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="bg_color" name="bg_color" 
                                       value="<?= htmlspecialchars($category['bg_color'] ?? '#3788D8'); ?>">
                                <span class="input-group-text">Code</span>
                                <input type="text" class="form-control" id="bg-color-text" name="bg_color_text"
                                       value="<?= htmlspecialchars($category['bg_color'] ?? '#3788D8'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="border_color" class="form-label">Couleur de bordure</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="border_color" name="border_color" 
                                       value="<?= htmlspecialchars($category['border_color'] ?? '#3788D8'); ?>">
                                <span class="input-group-text">Code</span>
                                <input type="text" class="form-control" id="border-color-text" name="border_color_text"
                                       value="<?= htmlspecialchars($category['border_color'] ?? '#3788D8'); ?>">
                            </div>
                            <small class="form-text text-muted">La bordure est utilisée pour les événements dans le calendrier.</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Aperçu</label>
                            <div id="preview" style="
                                padding: 10px 15px;
                                background-color: <?= htmlspecialchars($category['bg_color'] ?? '#3788D8'); ?>;
                                color: <?= htmlspecialchars($category['color'] ?? '#FFFFFF'); ?>;
                                border: 1px solid <?= htmlspecialchars($category['border_color'] ?? '#3788D8'); ?>;
                                border-radius: 4px;
                                display: inline-block;
                            ">
                                Exemple d'événement
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Créer la catégorie
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assurez-vous que jQuery est inclus avant ce script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Synchroniser les champs de couleur avec les champs texte
    function setupColorSync(colorPicker, textField) {
        $(colorPicker).on('input', function() {
            $(textField).val($(this).val());
            updatePreview();
        });
        
        $(textField).on('input', function() {
            // Vérifier si la valeur est un code hexadécimal valide
            const regex = /^#[0-9A-Fa-f]{6}$/;
            if (regex.test($(this).val())) {
                $(colorPicker).val($(this).val());
                updatePreview();
            }
        });
    }
    
    // Fonction pour mettre à jour l'aperçu
    function updatePreview() {
        // Récupérer la couleur de texte sélectionnée (noir ou blanc)
        const textColor = $('input[name="color"]:checked').val();
        const bgColor = $('#bg_color').val();
        const borderColor = $('#border_color').val();
        
        $('#preview').css({
            'color': textColor,
            'background-color': bgColor,
            'border-color': borderColor
        });
    }
    
    // Synchroniser les champs de couleur pour l'arrière-plan et la bordure
    setupColorSync('#bg_color', '#bg-color-text');
    setupColorSync('#border_color', '#border-color-text');
    
    // Écouter les changements de couleur de texte
    $('input[name="color"]').on('change', function() {
        updatePreview();
    });
    
    // Initialiser l'aperçu
    updatePreview();
    
    // Afficher un message de débogage
    console.log('Script d\'initialisation des couleurs chargé (noir/blanc uniquement)');
});
</script>