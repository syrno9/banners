<?php
session_start();
require_once 'functions.php';

if (!isAdminLoggedIn()) {
    header('Location: admin_login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

if (isset($_GET['approve']) && !empty($_GET['approve'])) {
    $result = approveBanner($_GET['approve']);
    $approveMessage = $result['message'];
}

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $result = deleteBanner($_GET['delete'], null, true);
    $deleteMessage = $result['message'];
}

$banners = getAllBanners(true);

$pendingBanners = [];
$approvedBanners = [];

foreach ($banners as $banner) {
    if ($banner['approved']) {
        $approvedBanners[] = $banner;
    } else {
        $pendingBanners[] = $banner;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MODS = GODS</title>
    <link rel="stylesheet" href="/public/css/global.css">
</head>
<body>
    <div class="container">
        <div>
            <h3>Banner Moderation</h3>
            <div>
                <a href="admin.php?logout=1">Logout</a>
            </div>
        </div>
        
        <?php if (isset($approveMessage)): ?>
            <div class="success"><?php echo htmlspecialchars($approveMessage); ?></div>
        <?php endif; ?>
        
        <?php if (isset($deleteMessage)): ?>
            <div class="success"><?php echo htmlspecialchars($deleteMessage); ?></div>
        <?php endif; ?>
        
        <div>
            <h3>Pending Banners (<?php echo count($pendingBanners); ?>)</h3>
            <?php if (empty($pendingBanners)): ?>
                <p>No pending banners.</p>
            <?php else: ?>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="10%">Actions</th>
                            <th width="15%">Title</th>
                            <th width="40%">Banner</th>
                            <th width="15%">Link</th>
                            <th width="10%">Size</th>
                            <th width="10%">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingBanners as $banner): ?>
                        <tr class="banner-row">
                            <td>
                                <a href="admin.php?approve=<?php echo $banner['_id']; ?>" class="action-button approve-button">Approve</a>
                                <a href="admin.php?delete=<?php echo $banner['_id']; ?>" class="action-button delete-button" onclick="return confirm('Are you sure you want to delete this banner?');">Delete</a>
                            </td>
                            <td><?php echo !empty($banner['title']) ? htmlspecialchars($banner['title']) : '<em>No title</em>'; ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank">
                                    <img src="/.banners/<?php echo htmlspecialchars($banner['filename']); ?>" style="max-width: 100%; height: auto;">
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank">
                                    <?php echo htmlspecialchars(substr($banner['link'], 0, 30)) . (strlen($banner['link']) > 30 ? '...' : ''); ?>
                                </a>
                            </td>
                            <td><?php echo isset($banner['fileSize']) ? number_format($banner['fileSize'] / (1024 * 1024), 2) . ' MB' : '0.00 MB'; ?></td>
                            <td><?php echo date('Y-m-d', $banner['uploadDate']->toDateTime()->getTimestamp()); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div>
            <h3>Approved Banners (<?php echo count($approvedBanners); ?>)</h3>
            <?php if (empty($approvedBanners)): ?>
                <p>No approved banners.</p>
            <?php else: ?>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="10%">Actions</th>
                            <th width="15%">Title</th>
                            <th width="40%">Banner</th>
                            <th width="15%">Link</th>
                            <th width="10%">Size</th>
                            <th width="10%">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedBanners as $banner): ?>
                        <tr class="banner-row">
                            <td >
                                <a href="admin.php?delete=<?php echo $banner['_id']; ?>" onclick="return confirm('Are you sure you want to delete this banner?');">Delete</a>
                            </td>
                            <td><?php echo !empty($banner['title']) ? htmlspecialchars($banner['title']) : '<em>No title</em>'; ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank">
                                    <img src="/.banners/<?php echo htmlspecialchars($banner['filename']); ?>" style="max-width: 100%; height: auto;">
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank">
                                    <?php echo htmlspecialchars(substr($banner['link'], 0, 30)) . (strlen($banner['link']) > 30 ? '...' : ''); ?>
                                </a>
                            </td>
                            <td><?php echo isset($banner['fileSize']) ? number_format($banner['fileSize'] / (1024 * 1024), 2) . ' MB' : '0.00 MB'; ?></td>
                            <td><?php echo date('Y-m-d', $banner['uploadDate']->toDateTime()->getTimestamp()); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 