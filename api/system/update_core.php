<?php
// C:\xampp\htdocs\pos\api\system\update_core.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

// Restricted to Super Admin
require_role(['super_admin']);

$action = $_GET['action'] ?? 'check';

// 1. Version Checker
if ($action === 'check') {
    try {
        $localManifest = json_decode(file_get_contents(__DIR__ . '/../../includes/version.json'), true);
        
        // Fetch from GitHub raw
        $repoUrl = "https://raw.githubusercontent.com/thilinadias/XentraPOS/main/includes/version.json";
        
        $ctx = stream_context_create([
            "http" => ["header" => "User-Agent: XentraUpdate-Client\r\n"]
        ]);
        
        $remoteManifestJson = @file_get_contents($repoUrl, false, $ctx);
        if (!$remoteManifestJson) throw new Exception("Could not connect to GitHub repository.");
        
        $remoteManifest = json_decode($remoteManifestJson, true);
        
        $hasUpdate = version_compare($remoteManifest['version'], $localManifest['version'], '>');
        
        echo json_encode([
            'success' => true, 
            'current' => $localManifest['version'],
            'latest' => $remoteManifest['version'],
            'hasUpdate' => $hasUpdate,
            'release_date' => $remoteManifest['release_date'] ?? 'N/A'
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 2. The Great Update Loop
if ($action === 'apply') {
    // Increase memory and timeout
    set_time_limit(600);
    ini_set('memory_limit', '256M');

    try {
        if (!class_exists('ZipArchive')) throw new Exception("ZipArchive extension required on your PHP environment.");

        $zipUrl = "https://github.com/thilinadias/XentraPOS/archive/refs/heads/main.zip";
        $tmpZip = __DIR__ . '/../../temp_update.zip';
        $extractPath = __DIR__ . '/../../temp_extract/';

        // Step A: Download the latest source from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zipUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'XentraUpdate-Client');
        $zipData = curl_exec($ch);
        curl_close($ch);

        if (!$zipData) throw new Exception("Download failed. Please check your internet connection.");
        file_put_contents($tmpZip, $zipData);

        // Step B: Extract
        $zip = new ZipArchive;
        if ($zip->open($tmpZip) === TRUE) {
            if (!is_dir($extractPath)) mkdir($extractPath, 0755, true);
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            throw new Exception("Could not extract update package.");
        }

        // Clean up zip
        unlink($tmpZip);

        // Step C: Locate the extracted root (e.g. XentraPOS-main/)
        $dirs = array_filter(glob($extractPath . '*'), 'is_dir');
        $rootSource = reset($dirs);
        if (!$rootSource) throw new Exception("Malformed update package.");

        // Step D: SAFE OVERWRITE
        // Preservation list (Relative paths from base)
        $preserved = [
            'config/database.php',
            'assets/img/logo.png',
            'api/system/update_core.php', // Don't suicide mid-run
            '.git' // Preserve local git if exists
        ];

        $rootBase = realpath(__DIR__ . '/../../');
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootSource, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $fileinfo) {
            $relativePath = str_replace($rootSource . DIRECTORY_SEPARATOR, '', $fileinfo->getPathname());
            $targetPath = $rootBase . DIRECTORY_SEPARATOR . $relativePath;

            if (in_array(str_replace('\\', '/', $relativePath), $preserved)) continue;

            if ($fileinfo->isDir()) {
                if (!is_dir($targetPath)) mkdir($targetPath, 0755, true);
            } else {
                copy($fileinfo->getPathname(), $targetPath);
            }
        }

        // Step E: DATABASE MIGRATIONS (Sequential SQL Execution)
        $migrationsFolder = $rootSource . DIRECTORY_SEPARATOR . 'updates';
        if (is_dir($migrationsFolder)) {
            $sqlFiles = glob($migrationsFolder . DIRECTORY_SEPARATOR . '*.sql');
            sort($sqlFiles); // Run in order (e.g. v1.6.1 before v1.6.2)

            foreach ($sqlFiles as $sqlPath) {
                $filename = basename($sqlPath);
                
                // Check if already applied
                $stmtCheck = $pdo->prepare("SELECT id FROM migrations WHERE filename = ?");
                $stmtCheck->execute([$filename]);
                if ($stmtCheck->fetch()) continue; // Already run

                // Execute SQL
                $sql = file_get_contents($sqlPath);
                if (!empty(trim($sql))) {
                    try {
                        $pdo->exec($sql);
                        // Mark as applied
                        $stmtMark = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
                        $stmtMark->execute([$filename]);
                    } catch (PDOException $se) {
                        // Log but continue if schema already exists (Guard)
                        error_log("Migration Skip/Error ({$filename}): " . $se->getMessage());
                    }
                }
            }
        }

        // Step F: CLEAN UP
        // Delete extracted temp files
        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($objects as $object) {
            $object->isDir() ? rmdir($object->getRealpath()) : unlink($object->getRealpath());
        }
        rmdir($extractPath);

        log_activity('System Updated', "Core system & database synchronization complete.");
        
        echo json_encode(['success' => true, 'message' => 'XentraPOS has been successfully synchronized and updated!']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
