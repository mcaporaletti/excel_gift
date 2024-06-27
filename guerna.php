<?php
require 'vendor/autoload.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;

function readExcelFile($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception("Il file Excel non è nella cartella o non è corretto: " . $filePath);
    }

    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray();
    return $data;
}

function escapeSpecialCharacters($testo) {
    $search = ['{', '}'];
    $replace = ['\{', '\}'];
    return str_replace($search, $replace, $testo);
}

function convertToGift($data) {
    $contenutoTest = "";

    foreach ($data as $riga) {
        if (count($riga) < 2) {
            continue; // salta
        }

        // escaping dei caratteri speciali
        $question = escapeSpecialCharacters($riga[0]);
        $correctAnswer = escapeSpecialCharacters($riga[1]);

        $contenutoTest .= "::" . $question . "::" . $question . " {\n";
        $contenutoTest .= "=" . $correctAnswer . "\n";

        // inserimento nel gift delle risposte sbagliate
        for ($i = 2; $i < count($riga); $i++) {
            $risposta_sbagliata = escapeSpecialCharacters($riga[$i]);
            $contenutoTest .= "~" . $risposta_sbagliata . "\n";
        }

        $contenutoTest .= "}\n\n";
    }

    return $contenutoTest;
}

function writeGiftFile($filePath, $contenuto) {
    if (file_put_contents($filePath, $contenuto) === false) {
        throw new Exception("Impossibile scrivere il file GIFT: " . $filePath);
    }
}

// Verifica se è stato caricato un file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // Percorso del file XLSX e file di output GIFT
    $directory = __DIR__ . '/files/';
    $sorgente = $directory . $file['name'];
    $destinazione = $directory . 'file.txt';

    try {
        // Salvataggio del file XLSX caricato
        if (!move_uploaded_file($file['tmp_name'], $sorgente)) {
            throw new Exception('Errore nel salvataggio del file.');
        }

        // Conversione del file XLSX in formato GIFT
        $data = readExcelFile($sorgente);
        $contenutoTest = convertToGift($data);
        writeGiftFile($destinazione, $contenutoTest);

        echo "Conversione completata. Il file GIFT è stato salvato in: " . $destinazione . "\n";

        // Rimuovere il file XLSX dopo la conversione se non è più necessario
        unlink($sorgente);
    } catch (Exception $e) {
        echo "Errore durante la conversione: " . $e->getMessage() . "\n";
    }
}
?>
