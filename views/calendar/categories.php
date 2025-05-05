<?php
$pageTitle = 'Gérer les catégories';
$breadcrumbs = [
    'Calendriers' => Routes::url('calendar', 'index'),
    'Paramètres' => Routes::url('user', 'settings'),
    'Catégories' => '#'
];
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
                        <li class="list-group-item">
                            <a href="<?= Routes::url('calendar', 'calendars') ?>">
                                <i class="fas fa-calendar-alt"></i> Gérer les calendriers
                            </a>
                        </li>
                        <li class="list-group-item active">
                            <i class="fas fa-tags"></i> Gérer les catégories
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
            <!-- Liste des catégories -->
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Catégories d'événements</h4>
                    <div>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <a href="<?= Routes::url('calendar', 'initializeCategories') ?>" class="btn btn-outline-secondary btn-sm me-2" 
                               onclick="return confirm('Initialiser les catégories par défaut ?');">
                                <i class="fas fa-sync me-1"></i>Initialiser
                            </a>
                        <?php endif; ?>
                        <a href="<?= Routes::url('calendar', 'createCategory') ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Nouvelle catégorie
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_SESSION['success_message']) ?>
                            <?php unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                            <?php unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($categories)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucune catégorie n'a encore été définie.
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <a href="<?= Routes::url('calendar', 'initializeCategories') ?>" class="alert-link">
                                    Initialiser les catégories par défaut
                                </a> ou 
                            <?php endif; ?>
                            <a href="<?= Routes::url('calendar', 'createCategory') ?>" class="alert-link">
                                créez votre première catégorie
                            </a>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Couleur</th>
                                        <th>Arrière-plan</th>
                                        <th>Aperçu</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td class="align-middle">
                                                <strong><?= htmlspecialchars($category['name']) ?></strong>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($category['color']) ?>; border-radius: 4px;" class="me-2"></div>
                                                    <span class="small"><?= htmlspecialchars($category['color']) ?></span>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($category['bg_color']) ?>; border: 1px solid #ccc; border-radius: 4px;" class="me-2"></div>
                                                    <span class="small"><?= htmlspecialchars($category['bg_color']) ?></span>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div style="
                                                    padding: 8px 12px;
                                                    background-color: <?= htmlspecialchars($category['bg_color']) ?>;
                                                    color: <?= htmlspecialchars($category['color']) ?>;
                                                    border: 1px solid <?= htmlspecialchars($category['border_color']) ?>;
                                                    border-radius: 4px;
                                                    font-size: 0.8rem;
                                                    display: inline-block;
                                                ">
                                                    Événement
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= Routes::url('calendar', 'editCategory', ['id' => $category['category_id']]) ?>" class="btn btn-outline-primary" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= Routes::url('calendar', 'deleteCategory', ['id' => $category['category_id']]) ?>" class="btn btn-outline-danger" title="Supprimer"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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
            
            <!-- Informations sur les catégories -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0">À propos des catégories</h4>
                </div>
                <div class="card-body">
                    <p>
                        Les catégories vous permettent d'organiser vos événements par type d'activité. 
                        Chaque catégorie possède ses propres couleurs qui seront utilisées pour identifier 
                        visuellement les événements de cette catégorie dans le calendrier.
                    </p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Conseil :</strong> Utilisez des couleurs distinctes pour chaque catégorie afin de 
                        repérer facilement vos différents types d'événements dans le calendrier.
                    </div>
                    
                    <h5>Propriétés d'une catégorie</h5>
                    <ul>
                        <li><strong>Nom :</strong> Le nom descriptif de la catégorie</li>
                        <li><strong>Couleur du texte :</strong> Couleur du texte des événements</li>
                        <li><strong>Couleur d'arrière-plan :</strong> Couleur de fond des événements</li>
                        <li><strong>Couleur de bordure :</strong> Couleur de la bordure des