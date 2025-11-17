<?php
/**
 * Logout
 */

session_start();
session_destroy();
header('Location: /frontend/index.php');
exit;
