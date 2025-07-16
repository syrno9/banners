<?php
if (!extension_loaded('mongodb')) {
    die('MongoDB extension is not loaded');
}

$mongoHost = 'localhost';
$mongoPort = '27017';
$mongoDatabase = 'banners';

try {
    $manager = new MongoDB\Driver\Manager("mongodb://$mongoHost:$mongoPort");
    
    class MongoDBGridFS {
        private $manager;
        private $database;
        
        public function __construct($manager, $database) {
            $this->manager = $manager;
            $this->database = $database;
        }
        
        public function selectGridFSBucket() {
            return new GridFSBucket($this->manager, $this->database);
        }
        
        public function selectCollection($collection) {
            return new MongoCollection($this->manager, $this->database, $collection);
        }
    }
    
    class MongoCollection {
        private $manager;
        private $database;
        private $collection;
        
        public function __construct($manager, $database, $collection) {
            $this->manager = $manager;
            $this->database = $database;
            $this->collection = $collection;
        }
        
        public function insertOne($document) {
            $bulk = new MongoDB\Driver\BulkWrite();
            if (!isset($document['_id'])) {
                $document['_id'] = new MongoDB\BSON\ObjectId();
            }
            $bulk->insert($document);
            $this->manager->executeBulkWrite("{$this->database}.{$this->collection}", $bulk);
            return $document['_id'];
        }
        
        public function find($filter = [], $options = []) {
            $query = new MongoDB\Driver\Query($filter, $options);
            $cursor = $this->manager->executeQuery("{$this->database}.{$this->collection}", $query);
            return $cursor;
        }
        
        public function findOne($filter = []) {
            $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
            $cursor = $this->manager->executeQuery("{$this->database}.{$this->collection}", $query);
            $documents = $cursor->toArray();
            return isset($documents[0]) ? $documents[0] : null;
        }
        
        public function updateOne($filter, $update) {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update($filter, ['$set' => $update], ['multi' => false]);
            $this->manager->executeBulkWrite("{$this->database}.{$this->collection}", $bulk);
        }
        
        public function deleteOne($filter) {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->delete($filter, ['limit' => 1]);
            $this->manager->executeBulkWrite("{$this->database}.{$this->collection}", $bulk);
        }
    }
    
    class GridFSBucket {
        private $manager;
        private $database;
        private $bucketName = 'fs';
        
        public function __construct($manager, $database, $options = []) {
            $this->manager = $manager;
            $this->database = $database;
            if (isset($options['bucketName'])) {
                $this->bucketName = $options['bucketName'];
            }
        }
        
        public function uploadFromStream($filename, $stream, $options = []) {
            $chunkSizeBytes = 261120; // 255 KB
            $metadata = isset($options['metadata']) ? $options['metadata'] : [];
            $contentType = isset($options['contentType']) ? $options['contentType'] : 'application/octet-stream';
            
            $fileId = new MongoDB\BSON\ObjectId();
            
            $fileSize = 0;
            $n = 0;
            
            while (!feof($stream)) {
                $chunkData = fread($stream, $chunkSizeBytes);
                $chunkSize = strlen($chunkData);
                $fileSize += $chunkSize;
                
                $chunk = [
                    'files_id' => $fileId,
                    'n' => $n,
                    'data' => new MongoDB\BSON\Binary($chunkData, MongoDB\BSON\Binary::TYPE_GENERIC)
                ];
                
                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->insert($chunk);
                $this->manager->executeBulkWrite("{$this->database}.{$this->bucketName}.chunks", $bulk);
                
                $n++;
            }
            
            $file = [
                '_id' => $fileId,
                'length' => $fileSize,
                'chunkSize' => $chunkSizeBytes,
                'uploadDate' => new MongoDB\BSON\UTCDateTime(),
                'filename' => $filename,
                'contentType' => $contentType,
                'metadata' => $metadata
            ];
            
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->insert($file);
            $this->manager->executeBulkWrite("{$this->database}.{$this->bucketName}.files", $bulk);
            
            return $fileId;
        }
        
        public function openDownloadStream($id) {
            $file = $this->findOne(['_id' => $id]);
            if (!$file) {
                return false;
            }
            
            $stream = fopen('php://temp', 'r+');
            
            $query = new MongoDB\Driver\Query(['files_id' => $id], ['sort' => ['n' => 1]]);
            $cursor = $this->manager->executeQuery("{$this->database}.{$this->bucketName}.chunks", $query);
            
            foreach ($cursor as $chunk) {
                fwrite($stream, $chunk->data->getData());
            }
            
            rewind($stream);
            
            return $stream;
        }
        
        public function find($filter = [], $options = []) {
            $query = new MongoDB\Driver\Query($filter, $options);
            $cursor = $this->manager->executeQuery("{$this->database}.{$this->bucketName}.files", $query);
            return $cursor;
        }
        
        public function findOne($filter = []) {
            $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
            $cursor = $this->manager->executeQuery("{$this->database}.{$this->bucketName}.files", $query);
            $documents = $cursor->toArray();
            return isset($documents[0]) ? $documents[0] : null;
        }
        
        public function delete($id) {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->delete(['files_id' => $id]);
            $this->manager->executeBulkWrite("{$this->database}.{$this->bucketName}.chunks", $bulk);
            
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->delete(['_id' => $id]);
            $this->manager->executeBulkWrite("{$this->database}.{$this->bucketName}.files", $bulk);
        }
    }
    
    $mongodb = new MongoDBGridFS($manager, $mongoDatabase);
    
} catch (Exception $e) {
    die('Failed to connect to MongoDB: ' . $e->getMessage());
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$maxFileSize = 5 * 1024 * 1024;

$bannerWidth = 468;
$bannerHeight = 60;
$bannerRatio = $bannerWidth / $bannerHeight;
?> 