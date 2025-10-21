<?php
declare(strict_types=1);
session_start();
function ensure_admin(): void {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}
