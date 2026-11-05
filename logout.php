<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

logout();
header('Location: ' . BASE_URL . 'login.php');
exit;
