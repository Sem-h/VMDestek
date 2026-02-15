<?php
/**
 * VMDestek - Auto Update API
 * GitHub üzerinden otomatik güncelleme sistemi
 * 
 * © 2026 Semih AKBAŞ - semihakbas.com.tr
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Auth check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only admins can update
if (($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Sadece admin rolündeki kullanıcılar güncelleme yapabilir']);
    exit;
}

define('GITHUB_REPO', 'Sem-h/VMDestek');
define('GITHUB_API', 'https://api.github.com/repos/' . GITHUB_REPO);
define('GITHUB_RAW', 'https://raw.githubusercontent.com/' . GITHUB_REPO . '/main');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check':
        checkForUpdate();
        break;
    case 'apply':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        applyUpdate();
        break;
    default:
        jsonError('Invalid action', 400);
}

function checkForUpdate()
{
    // Yerel sürümü oku
    $localVersionFile = __DIR__ . '/../version.json';
    if (!file_exists($localVersionFile)) {
        jsonError('Yerel sürüm dosyası bulunamadı');
    }

    $localVersion = json_decode(file_get_contents($localVersionFile), true);
    if (!$localVersion) {
        jsonError('Yerel sürüm dosyası okunamadı');
    }

    // GitHub'dan uzak sürümü çek (API üzerinden - cache sorunu olmaz)
    $remoteVersionUrl = GITHUB_API . '/contents/version.json?ref=main&t=' . time();
    $remoteContent = fetchUrl($remoteVersionUrl);

    if ($remoteContent === false) {
        // Fallback: raw.githubusercontent.com dene (cache'li olabilir)
        $remoteVersionUrl = GITHUB_RAW . '/version.json?t=' . time();
        $remoteContent = fetchUrl($remoteVersionUrl);
    }

    if ($remoteContent === false) {
        jsonError('GitHub sunucusuna bağlanılamadı. İnternet bağlantınızı kontrol edin.');
    }

    // API response ise content base64 encoded olur
    $remoteData = json_decode($remoteContent, true);
    if ($remoteData && isset($remoteData['content'])) {
        // GitHub API response - base64 decode et
        $remoteVersion = json_decode(base64_decode($remoteData['content']), true);
    } else {
        // Raw content response
        $remoteVersion = $remoteData;
    }

    if (!$remoteVersion || !isset($remoteVersion['version'])) {
        jsonError('Uzak sürüm bilgisi okunamadı');
    }

    $hasUpdate = version_compare($remoteVersion['version'], $localVersion['version'], '>');

    // Son commit bilgisini al
    $commitInfo = null;
    $commitsUrl = GITHUB_API . '/commits/main';
    $commitData = fetchUrl($commitsUrl);
    if ($commitData) {
        $commit = json_decode($commitData, true);
        if ($commit && isset($commit['commit'])) {
            $commitInfo = [
                'sha' => substr($commit['sha'], 0, 7),
                'message' => $commit['commit']['message'],
                'date' => $commit['commit']['committer']['date'],
                'author' => $commit['commit']['committer']['name']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'has_update' => $hasUpdate,
        'local_version' => $localVersion,
        'remote_version' => $remoteVersion,
        'commit' => $commitInfo
    ]);
}

function applyUpdate()
{
    $rootDir = realpath(__DIR__ . '/..');
    $tempDir = $rootDir . '/temp_update';
    $zipFile = $rootDir . '/temp_update.zip';

    // Korunan dosyalar (güncellenmeyecek)
    $protected = [
        'config.php',
        'install.lock',
        'install.php',
        '.gitignore',
        '.git',
        'uploads',
        'temp_update',
        'temp_update.zip'
    ];

    try {
        // ZIP indir
        $zipUrl = "https://github.com/" . GITHUB_REPO . "/archive/refs/heads/main.zip";
        $zipContent = fetchUrl($zipUrl);

        if ($zipContent === false) {
            throw new Exception('GitHub\'dan güncelleme dosyası indirilemedi');
        }

        // ZIP'i kaydet
        if (file_put_contents($zipFile, $zipContent) === false) {
            throw new Exception('Güncelleme dosyası kaydedilemedi. Dizin yazma izinlerini kontrol edin.');
        }

        // ZIP'i aç
        $zip = new ZipArchive();
        $res = $zip->open($zipFile);

        if ($res !== TRUE) {
            throw new Exception('ZIP dosyası açılamadı (hata kodu: ' . $res . ')');
        }

        // Temp klasörüne çıkar
        if (is_dir($tempDir)) {
            deleteDir($tempDir);
        }
        mkdir($tempDir, 0755, true);
        $zip->extractTo($tempDir);
        $zip->close();

        // Çıkarılan klasörü bul (VMDestek-main/)
        $extractedDirs = glob($tempDir . '/*', GLOB_ONLYDIR);
        if (empty($extractedDirs)) {
            throw new Exception('Güncelleme dosyaları çıkarılamadı');
        }
        $sourceDir = $extractedDirs[0];

        // Dosyaları kopyala (korunanları atla)
        $copied = copyUpdateFiles($sourceDir, $rootDir, $protected);

        // Temizlik
        deleteDir($tempDir);
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Güncelleme başarıyla uygulandı!',
            'files_updated' => $copied
        ]);

    } catch (Exception $e) {
        // Temizlik
        if (file_exists($zipFile)) {
            @unlink($zipFile);
        }
        if (is_dir($tempDir)) {
            deleteDir($tempDir);
        }

        jsonError($e->getMessage());
    }
}

function copyUpdateFiles($source, $dest, $protected, $basePath = '')
{
    $copied = 0;
    $items = scandir($source);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..')
            continue;

        $relativePath = $basePath ? $basePath . '/' . $item : $item;
        $srcPath = $source . '/' . $item;
        $destPath = $dest . '/' . $item;

        // Korunan dosyaları atla
        if (in_array($item, $protected) && $basePath === '') {
            continue;
        }

        if (is_dir($srcPath)) {
            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }
            $copied += copyUpdateFiles($srcPath, $destPath, $protected, $relativePath);
        } else {
            if (copy($srcPath, $destPath)) {
                $copied++;
            }
        }
    }

    return $copied;
}

function deleteDir($dir)
{
    if (!is_dir($dir))
        return;

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..')
            continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            deleteDir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

function fetchUrl($url)
{
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: VMDestek-Updater/1.0\r\n",
            'timeout' => 30
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $content = @file_get_contents($url, false, $ctx);
    return $content;
}

function jsonError($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}
