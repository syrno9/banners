<?php
require_once 'config.php';
require_once 'functions.php';

// Router
if (preg_match('#^/\.banners/(.+)$#', $_SERVER['REQUEST_URI'], $matches)) {
    $filename = $matches[1];
    
    try {
        $image = getBannerImage($filename);
        
        if (!$image) {
            header('HTTP/1.0 404 Not Found');
            echo 'File not found';
            exit;
        }
        
        header('Content-Type: ' . $image['contentType']);
        header('Content-Length: ' . strlen($image['data']));
        header('Cache-Control: max-age=86400');
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        echo $image['data'];
        exit;
        
    } catch (Exception $e) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'Error: ' . $e->getMessage();
        exit;
    }
}

$banners = getAllBanners();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Banners</title>
    <link rel="stylesheet" href="/public/css/global.css">
</head>
<body>
    <div class="container">
        <iframe src="/img.php" class="banner"></iframe>
        [ <a href="https://hikari3.ch/">Home</a> ] [ <a href="/admin_login.php">Mod</a> ]
        <table>
            <tr>
                <td style="width: 350px;">
                <div class="upload-form">
                <h3 style="margin-top: 0;">User Banners</h3>
                <?php if (isset($_GET['error'])): ?>
                    <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>
                
                <form action="upload.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title">
                    </div>                
                    <div class="form-group">
                        <label for="link">Link:</label>
                        <input type="url" id="link" name="link" required>
                    </div>
                    <div class="form-group">
                        <label for="image">Image:</label>
                        <input type="file" id="image" name="image" required>
                    </div>
                    <p><small>(468x60 or same aspect ratio)</small></p>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit">Upload Banner</button>
                </form>
                </div>
                </td>
                <td style="vertical-align: top; width: 440px;">
                    <h3 style="margin-top: 0;">Rules</h3>
                    <p>While you cannot randomly post advertisements of your product/service on our boards, we do allow you to put up a display banner on here for free. There's just a few rules you must follow.</p>
                    <ul>
                        <li>Must look good and SFW</li>
                        <li>Shouldn't be malicious and should be truthful</li>
                        <li>Fit with all rules of our site</li>
                        <li>Should be 468x60</li>
                        <li>It should fit with hikari3</li>
                    </ul>
                </td>
            </tr>
        </table>

        <div style="text-align: center;">
            <h3>Example Banner</h3>
            <div class="example-banner">
                <img src="/public/img/example.webp" width="500">
            </div>
        </div>
        <div class="banners">
            <h3>Current Banners</h3>
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th width="4%">Delete</th>
                        <th width="8%">Title</th>
                        <th width="58%">Banner</th>
                        <th width="7%">Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($banners)): ?>
                    <tr>
                        <td colspan="4">No banners uploaded yet.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($banners as $banner): ?>
                        <tr class="banner-row">
                            <td class="delete-cell">
                                <input type="checkbox" id="delete-toggle-<?php echo $banner['_id']; ?>" class="delete-toggle">
                                <label for="delete-toggle-<?php echo $banner['_id']; ?>" class="delete-label"><img src="/public/img/menu.webp"></label>
                                <div class="delete-form-container">
                                    <form action="delete.php" method="post" class="delete-form">
                                        <input type="hidden" name="file_id" value="<?php echo $banner['_id']; ?>">
                                        <input type="password" name="password" placeholder="Password" required>
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                            <td><?php echo !empty($banner['title']) ? htmlspecialchars($banner['title']) : ''; ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank">
                                    <img src="/.banners/<?php echo htmlspecialchars($banner['filename']); ?>">
                                </a>
                            </td>
                            <td><?php echo isset($banner['fileSize']) ? number_format($banner['fileSize'] / (1024 * 1024), 2) . ' MB' : '0.00 MB'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 