<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileId = isset($_POST['file_id']) ? $_POST['file_id'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $isAdmin = isAdminLoggedIn();
    
    if (empty($fileId) || (empty($password) && !$isAdmin)) {
        header('Location: index.php?error=File ID and password are required');
        exit;
    }
    
    $result = deleteBanner($fileId, $password, $isAdmin);
    
    $redirectTo = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin.php') !== false ? 'admin.php' : 'index.php';
    
    if ($result['success']) {
        header('Location: ' . $redirectTo . '?success=' . urlencode($result['message']));
    } else {
        header('Location: ' . $redirectTo . '?error=' . urlencode($result['message']));
    }
    exit;
} else {
    header('Location: index.php');
    exit;
}
?> 