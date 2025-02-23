<?php
if (isset($_GET['file'])) {
    $filePath = urldecode($_GET['file']);
    
    if (file_exists($filePath)) {
        $fileName = basename($filePath);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    } else {
        echo "Die angeforderte Datei existiert nicht.";
    }
} else {
    echo "Kein Dateipfad angegeben.";
}
?>
