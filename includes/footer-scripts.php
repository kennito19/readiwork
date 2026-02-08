<?php
/**
 * Readiwork AI - Shared Footer Scripts Include
 * Include this at the bottom of every page
 */
if (!isset($base_path)) {
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($base_path === '.') $base_path = '';
}
?>
<?php include __DIR__ . '/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base_path ?>/assets/js/main.js"></script>
<?php if (isset($extra_js)): ?>
<script><?= $extra_js ?></script>
<?php endif; ?>
</body>
</html>
