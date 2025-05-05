<?php 
$pageTitle = 'Créer un calendrier';
$breadcrumbs = [
    'Calendriers' => Routes::url('calendar', 'index'),
    'Gérer les calendriers' => Routes::url('calendar', 'calendars'),
    'Nouveau calendrier' => '#'
];
include_once __DIR__ . '/../layouts/main.php'; 
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header">
                    <h2 class="h4 mb-0"><i class="fas fa-calendar-plus me-2"></i>Créer un nouveau calendrier</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo Routes::url('calendar', 'createCalendar'); ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du calendrier <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="color" class="form-label">Couleur</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="color" name="color" value="#3788d8">
                                <span class="input-group-text">Code couleur</span>
                                <input type="text" class="form-control" id="color-text" value="#3788d8">
                            </div>
                            <small class="form-text text-muted">Cette couleur sera utilisée pour identifier votre calendrier et ses événements.</small>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_visible" name="is_visible" value="1" checked>
                            <label class="form-check-label" for="is_visible">Visible par défaut</label>
                            <div class="form-text">Si coché, les événements de ce calendrier seront visibles dans la vue principale.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo Routes::url('calendar', 'calendars'); ?>" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Créer le calendrier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Synchroniser les champs de couleur
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
</script>