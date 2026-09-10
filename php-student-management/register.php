<?php
/**
 * Public registration is disabled. Accounts are created by administrators.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';

initSession();

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . 'dashboard.php');
}

setFlash('error', 'Public registration is disabled. Please contact the school administrator to create your account.');
redirect(BASE_URL . 'login.php');
exit;
