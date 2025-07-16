<?php
require_once 'config.php';
require_once 'functions.php';

$bannersCollection = $mongodb->selectCollection('banners');
$cursor = $bannersCollection->find(['approved' => true]);

$banners = [];
foreach ($cursor as $document) {
    if (isset($document->filename)) {
        $banners[] = [
            'filename' => $document->filename,
            'link' => isset($document->link) ? $document->link : '#'
        ];
    }
}

if (empty($banners)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="width: 468px; height: 60px; background-color: #182f3f; color: #dfdfdf; display: flex; justify-content: center; align-items: center; font-family: Arial, sans-serif; font-size: 14px;">No banners available</div>';
    exit;
}

$randomBanner = $banners[array_rand($banners)];
$filename = $randomBanner['filename'];
$link = $randomBanner['link'];

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0;">
    <a href="<?php echo htmlspecialchars($link); ?>" target="_blank">
        <img src="/.banners/<?php echo htmlspecialchars($filename); ?>" width="468" height="60">
    </a>
</body>
</html> 