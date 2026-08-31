<?php
require_once __DIR__ . '/includes/page-helpers.php';

$user = pageUser('ADMIN');
$title = 'Admin dashboard';
$script = 'admin.js';

require __DIR__ . '/includes/dashboard-layout.php';
