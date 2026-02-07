<?php
/**
 * Readiwork AI - Shared Header Include
 * Include this at the top of every page for consistent theming
 * Usage: $page_title = 'Page Name'; include 'includes/header.php';
 */
if (!isset($base_path)) {
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($base_path === '' || $base_path === '.') $base_path = '/readiwork';
}
$page_title = $page_title ?? 'Readiwork AI';
$page_description = $page_description ?? "Africa's AI-powered verification platform";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($page_title) ?> — Readiwork AI</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= htmlspecialchars($page_description) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- Unified Theme CSS -->
<link rel="stylesheet" href="<?= $base_path ?>/assets/css/theme.css">
<?php if (isset($extra_css)): ?>
<style><?= $extra_css ?></style>
<?php endif; ?>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
