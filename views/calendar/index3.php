<?php
$pageTitle = 'Test Calendrier';
$includeCalendarAssets = true;

// Définir $pageScripts AVANT d'inclure main.php
$pageScripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendar = new tui.Calendar('#calendar', {
        defaultView: 'week',
        useCreationPopup: true,
        useDetailPopup: true,
    });
});
</script>
HTML;

// Capturer le contenu HTML
ob_start();
?>

<div class="container py-4">
    <h1 class="text-center mb-4">Test Calendrier</h1>
    <div id="calendar" style="height: 800px;"></div>
</div>

<?php
// Stocker le contenu dans $content
$content = ob_get_clean();

// Maintenant inclure main.php
include_once __DIR__ . '/../layouts/main.php';
?>