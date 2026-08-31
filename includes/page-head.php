<?php
// Used by index.php, register.php and includes/dashboard-layout.php.
// Loads the page title, styles and scripts for those pages.
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?= h($_SESSION['csrf']) ?>">
        <title><?= h($title) ?> · AviTON</title>
        <!-- SB Admin 2 already includes Bootstrap CSS; loading it twice can break the layout. -->
        <link rel="stylesheet" href="assets/vendor/fontawesome-free/css/all.min.css">
        <link rel="stylesheet" href="assets/vendor/sb-admin-2/css/sb-admin-2.min.css">
        <link rel="stylesheet" href="assets/css/styles.css">
        <script src="assets/vendor/jquery/jquery.min.js" defer>
        </script>
        <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js" defer>
        </script>
        <script src="assets/vendor/sb-admin-2/js/sb-admin-2.min.js" defer>
        </script>
        <script src="assets/js/data.js" defer>
        </script>
        <script src="assets/js/<?= h($script) ?>" defer>
        </script>
    </head>
