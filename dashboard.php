<?php
require_once __DIR__ . '/includes/page-helpers.php';

$user = pageUser('USER');
$title = 'User dashboard';
$script = 'dashboard.js';

require __DIR__ . '/includes/dashboard-layout.php';
