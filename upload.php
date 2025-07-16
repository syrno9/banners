<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = isset($_FILES['image']) ? $_FILES['image'] : null;
    $link = isset($_POST['link']) ? trim($_POST['link']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($file) || empty($link) || empty($password)) {
        header('Location: index.php?error=All fields are required except title');
        exit;
    }
    
    $result = uploadBanner($file, $link, $title, $password);
    
    if ($result['success']) {
        header('Location: index.php?success=' . urlencode($result['message']));
    } else {
        header('Location: index.php?error=' . urlencode($result['message']));
    }
    exit;
} else {
    header('Location: index.php');
    exit;
}
?> 