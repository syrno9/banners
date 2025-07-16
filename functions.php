<?php
require_once 'config.php';

function loadAdminConfig() {
    $configFile = __DIR__ . '/config.json';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        return $config;
    }
    return ['admin_password' => 'admin'];
}

function verifyAdminPassword($password) {
    $config = loadAdminConfig();
    return $password === $config['admin_password'];
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function uploadBanner($file, $link, $title, $password) {
    global $mongodb, $allowedTypes, $maxFileSize, $bannerRatio, $bannerWidth, $bannerHeight;
    
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed'];
    }
    
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB'];
    }
    
    list($width, $height) = getimagesize($file['tmp_name']);
    $ratio = $width / $height;
    
    if (abs($ratio - $bannerRatio) > 0.1) {
        return ['success' => false, 'message' => "Image must be $bannerWidth x $bannerHeight"];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $filename = uniqid() . '_' . basename($file['name']);
    
    $imageData = file_get_contents($file['tmp_name']);
    
    $bucket = $mongodb->selectGridFSBucket();
    
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $imageData);
    rewind($stream);
    
    $fileId = $bucket->uploadFromStream($filename, $stream, ['contentType' => $mimeType]);
    fclose($stream);
    
    $bannersCollection = $mongodb->selectCollection('banners');
    $bannerDoc = [
        'filename' => $filename,
        'link' => $link,
        'title' => $title,
        'password' => $hashedPassword,
        'uploadDate' => new MongoDB\BSON\UTCDateTime(),
        'fileId' => $fileId,
        'approved' => false
    ];
    
    $bannersCollection->insertOne($bannerDoc);
    
    return [
        'success' => true,
        'message' => 'Banner uploaded successfully now wait for approval',
        'filename' => $filename
    ];
}

function getAllBanners($includeUnapproved = false) {
    global $mongodb;
    
    $bannersCollection = $mongodb->selectCollection('banners');
    $bucket = $mongodb->selectGridFSBucket();
    
    $filter = [];
    if (!$includeUnapproved) {
        $filter['approved'] = true;
    }
    
    $cursor = $bannersCollection->find($filter, [
        'sort' => ['uploadDate' => -1]
    ]);
    
    $banners = [];
    foreach ($cursor as $document) {
        $fileSize = 0;
        if (isset($document->fileId)) {
            $file = $bucket->findOne(['_id' => new MongoDB\BSON\ObjectId($document->fileId)]);
            if ($file && isset($file->length)) {
                $fileSize = $file->length;
            }
        }
        
        $banners[] = [
            '_id' => (string)$document->_id,
            'filename' => $document->filename,
            'link' => isset($document->link) ? $document->link : '#',
            'title' => isset($document->title) ? $document->title : '',
            'uploadDate' => $document->uploadDate,
            'fileId' => isset($document->fileId) ? (string)$document->fileId : null,
            'fileSize' => $fileSize,
            'approved' => isset($document->approved) ? $document->approved : false
        ];
    }
    
    return $banners;
}

function deleteBanner($bannerId, $password = null, $isAdmin = false) {
    global $mongodb;
    
    try {
        $bannersCollection = $mongodb->selectCollection('banners');
        $bucket = $mongodb->selectGridFSBucket();
        
        $banner = $bannersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($bannerId)]);
        if (!$banner) {
            return ['success' => false, 'message' => 'Banner not found'];
        }
        
        if (!$isAdmin) {
            if (!isset($banner->password) || !password_verify($password, $banner->password)) {
                return ['success' => false, 'message' => 'Incorrect password'];
            }
        }
        
        if (isset($banner->fileId)) {
            $bucket->delete(new MongoDB\BSON\ObjectId($banner->fileId));
        }
        
        $bannersCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($bannerId)]);
        
        return ['success' => true, 'message' => 'Banner deleted successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error deleting banner: ' . $e->getMessage()];
    }
}

function approveBanner($bannerId) {
    global $mongodb;
    
    try {
        $bannersCollection = $mongodb->selectCollection('banners');
        
        $banner = $bannersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($bannerId)]);
        if (!$banner) {
            return ['success' => false, 'message' => 'Banner not found'];
        }

        $bannersCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($bannerId)], 
            ['approved' => true]
        );
        
        return ['success' => true, 'message' => 'APPROVED'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error approving banner: ' . $e->getMessage()];
    }
}

function getBannerImage($filename) {
    global $mongodb;
    
    try {
        $bannersCollection = $mongodb->selectCollection('banners');
        $bucket = $mongodb->selectGridFSBucket();
        
        $banner = $bannersCollection->findOne(['filename' => $filename]);
        if (!$banner || !isset($banner->fileId)) {
            return false;
        }
        
        $file = $bucket->findOne(['_id' => new MongoDB\BSON\ObjectId($banner->fileId)]);
        if (!$file) {
            return false;
        }
        
        $stream = $bucket->openDownloadStream(new MongoDB\BSON\ObjectId($file->_id));
        if (!$stream) {
            return false;
        }
        
        $data = stream_get_contents($stream);
        fclose($stream);
        
        if (empty($data)) {
            return false;
        }
        
        $contentType = 'image/gif';
        
        if (isset($file->contentType)) {
            $contentType = $file->contentType;
        }
        
        return [
            'data' => $data,
            'contentType' => $contentType
        ];
    } catch (Exception $e) {
        error_log('Error getting banner image: ' . $e->getMessage());
        return false;
    }
}
?> 