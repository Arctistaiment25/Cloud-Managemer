<?php
// Überprüfen, ob der 'file'-Parameter gesetzt ist
if (isset($_GET['file'])) {
    // Den Dateipfad aus der URL holen
    $filePath = urldecode($_GET['file']);
    
    // Überprüfen, ob die Datei existiert
    if (file_exists($filePath)) {
        // Den Dateinamen aus dem vollständigen Dateipfad extrahieren
        $fileName = basename($filePath);
        
        // Header setzen, um den Download der Datei zu ermöglichen
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        
        // Datei ausgeben (herunterladen)
        readfile($filePath);
        exit;
    } else {
        // Falls die Datei nicht gefunden wird
        echo "Die angeforderte Datei existiert nicht.";
    }
} else {
    // Wenn der 'file'-Parameter nicht gesetzt ist
    echo "Kein Dateipfad angegeben.";
}
?>
