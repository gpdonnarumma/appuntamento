<?php
/**
 * Logout
 */

require_once __DIR__ . '/config.php';

session_destroy();
header('Location: ' . baseUrl('index.php'));
exit;
