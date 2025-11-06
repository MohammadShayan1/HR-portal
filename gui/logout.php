<?php
/**
 * Logout Page
 */
session_start();
require_once __DIR__ . '/../functions/auth.php';

logout_user();
header('Location: login.php');
exit;
