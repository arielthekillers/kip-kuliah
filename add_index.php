<?php
// Script untuk menambahkan index.php kosong ke setiap direktori.
$rootDir = __DIR__;
$excludeDirs = ['.git', 'node_modules', 'vendor'];

function addEmptyIndex($dir) {
    global $excludeDirs;
    
    $items = @scandir($dir);
    if (!$items) return;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            if (in_array($item, $excludeDirs)) continue;
            
            $indexPath = $path . DIRECTORY_SEPARATOR . 'index.php';
            if (!file_exists($indexPath)) {
                $content = "<?php\n// Silence is golden.\nheader('HTTP/1.0 403 Forbidden');\nexit;\n?>";
                file_put_contents($indexPath, $content);
                echo "Created: $indexPath\n";
            }
            addEmptyIndex($path);
        }
    }
}

echo "Memulai pemberian empty index...\n";
addEmptyIndex($rootDir);
echo "Selesai.\n";
