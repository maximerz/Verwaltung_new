<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/document_templates.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$template_types = [
    'angebot' => 'Angebot',
    'bestellung' => 'Bestellbestätigung',
    'rechnung' => 'Rechnung',
    'lieferschein' => 'Lieferschein',
];

$active_type = $_GET['type'] ?? 'rechnung';
if (!isset($template_types[$active_type])) {
    $active_type = 'rechnung';
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_type = $_POST['template_type'] ?? 'rechnung';
    if (isset($template_types[$posted_type])) {
        $active_type = $posted_type;

        $payload = [
            'name' => trim($_POST['name'] ?? ''),
            'header_title' => trim($_POST['header_title'] ?? ''),
            'firmenname' => trim($_POST['firmenname'] ?? ''),
            'firmenadresse' => trim($_POST['firmenadresse'] ?? ''),
            'intro_text' => trim($_POST['intro_text'] ?? ''),
            'payment_info' => trim($_POST['payment_info'] ?? ''),
            'footer_text' => trim($_POST['footer_text'] ?? ''),
            'primary_color' => trim($_POST['primary_color'] ?? '#5B7DB1'),
            'accent_color' => trim($_POST['accent_color'] ?? '#D4AF37'),
        ];

        save_document_template($PDO, $active_type, $payload);
        $message = 'Vorlage gespeichert.';
    } else {
        $message = 'Ungültiger Vorlagentyp.';
        $message_type = 'danger';
    }
}

$templates = [];
foreach (array_keys($template_types) as $type) {
    $templates[$type] = get_document_template($PDO, $type);
}

$current = $templates[$active_type];
$placeholder_help = [
    '{{kunde_name}}',
    '{{dokument_nummer}}',
    '{{datum}}',
    '{{faelligkeit}}',
];

$page_title = 'Dokumentvorlagen';
include 'includes/header.php';
include 'includes/table-style.php';
?>

<style>
.template-tabs { display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.template-tab {
    display:inline-flex; align-items:center; gap:0.5rem; padding:0.85rem 1rem;
    border:1px solid var(--border); border-radius:16px; background:var(--color-surface-strong);
    color:var(--text); font-weight:700;
}
.template-tab.active { background: var(--gradient-primary); color: #fff !important; border-color: transparent; }
.template-layout { display:grid; grid-template-columns:minmax(0, 1.4fr) minmax(280px, 0.8fr); gap:1rem; }
.template-form-grid { display:grid; gap:1rem; }
.template-sidebar { position:sticky; top:1rem; align-self:start; }
.template-help code { display:block; margin-bottom:0.45rem; }
@media (max-width: 991px) {
    .template-layout { grid-template-columns:1fr; }
    .template-sidebar { position:static; }
}
</style>

<div class="dashboard-card">
    <div class="section-header">
        <div>
            <h2 class="section-title"><i class="fas fa-file-signature"></i>Dokumentvorlagen</h2>
            <p class="text-muted mb-0">Hier verwaltest du zentrale Vorlagen für Angebot, Bestellbestätigung, Rechnung und Lieferschein.</p>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?> mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="template-tabs">
        <?php foreach ($template_types as $type => $label): ?>
            <a href="document_templates.php?type=<?= urlencode($type) ?>" class="template-tab <?= $active_type === $type ? 'active' : '' ?>">
                <i class="fas fa-folder-open"></i>
                <span><?= htmlspecialchars($label) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="template-layout">
        <div class="dashboard-card">
            <form method="POST" class="template-form-grid">
                <input type="hidden" name="template_type" value="<?= htmlspecialchars($active_type) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Interner Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($current['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dokumenttitel</label>
                        <input type="text" name="header_title" class="form-control" value="<?= htmlspecialchars($current['header_title']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Primärfarbe</label>
                        <input type="color" name="primary_color" class="form-control form-control-color" value="<?= htmlspecialchars($current['primary_color']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Akzentfarbe</label>
                        <input type="color" name="accent_color" class="form-control form-control-color" value="<?= htmlspecialchars($current['accent_color']) ?>">
                    </div>
                </div>

                <div>
                    <label class="form-label">Firmenname</label>
                    <input type="text" name="firmenname" class="form-control" value="<?= htmlspecialchars($current['firmenname']) ?>">
                </div>

                <div>
                    <label class="form-label">Firmenadresse / Kontaktdaten</label>
                    <textarea name="firmenadresse" class="form-control" rows="5"><?= htmlspecialchars($current['firmenadresse']) ?></textarea>
                </div>

                <div>
                    <label class="form-label">Einleitung</label>
                    <textarea name="intro_text" class="form-control" rows="6"><?= htmlspecialchars($current['intro_text']) ?></textarea>
                </div>

                <div>
                    <label class="form-label">Zahlungsinformationen</label>
                    <textarea name="payment_info" class="form-control" rows="5"><?= htmlspecialchars($current['payment_info']) ?></textarea>
                </div>

                <div>
                    <label class="form-label">Footer / Schlussblock</label>
                    <textarea name="footer_text" class="form-control" rows="5"><?= htmlspecialchars($current['footer_text']) ?></textarea>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>Vorlage speichern
                    </button>
                    <a href="<?= $active_type === 'rechnung' ? 'finanzbuchhaltung.php' : 'web_oberflaeche.php' ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>Zurück
                    </a>
                </div>
            </form>
        </div>

        <aside class="dashboard-card template-sidebar">
            <h4 class="mb-3"><i class="fas fa-circle-info me-2"></i>Platzhalter</h4>
            <div class="template-help text-muted">
                <p>Diese Platzhalter kannst du in Einleitung, Zahlungsinfo und Footer verwenden:</p>
                <?php foreach ($placeholder_help as $placeholder): ?>
                    <code><?= htmlspecialchars($placeholder) ?></code>
                <?php endforeach; ?>
                <p class="mt-3 mb-0">Die Texte werden automatisch mit den Daten des jeweiligen Dokuments ersetzt.</p>
            </div>
        </aside>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
