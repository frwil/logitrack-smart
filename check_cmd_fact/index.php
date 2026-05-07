<?php
/* echo "Architecture PHP : " . (PHP_INT_SIZE * 8) . " bits\n";
exit;
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log');
// Fonction adaptée au format Français (1 234,56 ou 1.234,56)
function parseFloatCustom($val)
{
    $val = trim(str_replace(["\u00A0", "\xc2\xa0", " "], '', (string)$val));
    $val = str_replace('.', '', $val);
    $val = str_replace(',', '', $val);
    return (float)$val;
}

// NOUVELLE FONCTION : Calcul de similarité sémantique (insensible à l'ordre des mots)
function calculateSimilarity($str1, $str2)
{
    // 1. Nettoyage et normalisation
    $str1 = mb_strtolower(trim((string)$str1));
    $str2 = mb_strtolower(trim((string)$str2));

    if ($str1 === $str2) return 100;

    // 2. Extraction des mots (supporte les caractères unicode/accents)
    preg_match_all('/\p{L}+|[\d,.]+/u', $str1, $m1);
    preg_match_all('/\p{L}+|[\d,.]+/u', $str2, $m2);

    $words1 = array_unique($m1[0] ?? []);
    $words2 = array_unique($m2[0] ?? []);

    if (empty($words1) || empty($words2)) return 0;

    // 3. Calcul de correspondance (Jaccard Index)
    $intersection = array_intersect($words1, $words2);
    $union = array_unique(array_merge($words1, $words2));

    if (count($union) === 0) return 0;

    return (count($intersection) / count($union)) * 100;
}

// Augmenter la limite mémoire
ini_set('memory_limit', '-1');
set_time_limit(0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    // 1. Désactiver la compression Gzip/Deflate au niveau PHP
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', 1);
    }
    @ini_set('zlib.output_compression', 'Off');
    @ini_set('output_buffering', 'Off');
    @ini_set('implicit_flush', 1);

    // 2. Nettoyer tous les tampons existants (ob_gzhandler, etc.)
    while (ob_get_level()) {
        @ob_end_clean();
    }

    // 3. En-têtes HTTP pour forcer le mode "Streaming"
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Encoding: none'); // Très important : dit au navigateur "C\'est du texte brut"
    header('Cache-Control: no-cache, must-revalidate'); // Empêche la mise en cache
    header('X-Accel-Buffering: no'); // Spécifique pour Nginx (souvent utilisé en proxy inverse)

    // 4. Astuce du "Padding" : Envoyer 4ko d'espaces pour forcer le navigateur à démarrer le rendu
    echo str_pad('', 4096);
    flush();

    require_once __DIR__ . '/vendor/autoload.php';

    $fileTmp = $_FILES['excel_file']['tmp_name'];

    if ($fileTmp) {
        try {
            echo "PROGRESS:5:Lecture du fichier Excel...\n";
            flush();

            //$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmp);
            // Détection automatique du type (Xlsx ou Xls)
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($fileTmp);
            // Création du lecteur spécifique
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            // OPTIMISATION CRUCIALE : Ne lire que les données (pas le gras, les couleurs, etc.)
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            // Chargement
            $spreadsheet = $reader->load($fileTmp);
            $sheets = $spreadsheet->getAllSheets();

            if (count($sheets) >= 2) {
                $sheet1 = $sheets[0]->toArray();
                $sheet2 = $sheets[1]->toArray();

                echo "PROGRESS:15:Analyse des données...\n";
                flush();

                $newSpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $newSheet = $newSpreadsheet->getActiveSheet();
                $rowIndex = 1;

                $header1 = $sheet1[1] ?? [];
                $header2 = $sheet2[1] ?? [];
                $mergedHeader = array_merge($header1, $header2);
                $newSheet->fromArray($mergedHeader, null, 'A' . $rowIndex);
                $rowIndex++;

                $colsToCheckSheet2 = [19, 6, 7, 17];
                $colsToCheckSheet1 = [14, 6, 7, 9];
                $sheet1Data = array_slice($sheet1, 2);
                $sheet2Data = array_slice($sheet2, 2);

                $total = count($sheet2Data);
                $current = 0;
                $notMatchedRows = [];

                // Seuil de tolérance (70% de mots communs)
                $seuilSimilarite = 70;
                $test = "";
                gc_disable();
                foreach ($sheet2Data as $row2) {
                    if (count($row2) < max($colsToCheckSheet2) + 1) continue;

                    $current++;
                    if ($current % 50 === 0 || $current === $total) {
                        $percent = 15 + intval(75 * $current / max(1, $total));
                        echo "PROGRESS:$percent:Traitement ligne $current / $total " . ($sim ? "(" . $col5feuille2 . "-" . $col2feuille1 : "") . "\n";
                        flush();
                    }

                    $key2 = '';
                    foreach ($colsToCheckSheet2 as $col) {
                        $val2 = $row2[$col] ?? '';
                        // Conversion numérique
                        if ($col === 6 || $col === 7 || $col === 9 || $col === 11 || $col === 12) {
                            $val2 = parseFloatCustom($val2);
                            $row2[$col] = $val2;
                        }
                        $key2 .= trim((string)$val2) . '|';
                    }

                    $found = false;
                    foreach ($sheet1Data as $idx1 => $row1) {
                        if (empty($row1)) continue;
                        if (count($row1) < max($colsToCheckSheet1) + 1) continue;

                        $key1 = '';
                        $col5feuille2 = $row2[19] ?? ''; // Description feuille 2
                        $col2feuille1 = $row1[14] ?? ''; // Description feuille 1

                        foreach ($colsToCheckSheet1 as $i => $col) {
                            $val = $row1[$col] ?? '';

                            // --- C'EST ICI QUE LA LOGIQUE A CHANGÉ ---
                            if ($i === 0) { // Si on est sur la colonne Description
                                // Calcul du pourcentage de similarité
                                $sim = calculateSimilarity($col2feuille1, $col5feuille2);

                                // Si > 70%, on considère que c'est la même description
                                // On remplace la valeur pour que la clé hash soit identique
                                if ($sim >= $seuilSimilarite) {
                                    $test .= ($col5feuille2 . '-' . $col2feuille1 . '\n');
                                    $val = $col5feuille2;
                                }
                            }
                            // -----------------------------------------

                            if ($col === 6 || $col === 7) {
                                $val = parseFloatCustom($val);
                                $row1[$col] = $val;
                            }
                            $key1 .= trim((string)$val) . '|';
                        }

                        // Debug optionnel (à commenter en prod)
                        // if($row1[0] === 'SO2601-94622' && $row2[0]==='IN2601-103930') echo "KEY1: $key1 | KEY2: $key2 | SIM: " . calculateSimilarity($row1[2], $row2[4]) . "\n";

                        if ($key1 === $key2) {
                            $mergedRow = array_merge($row1, $row2);
                            $newSheet->fromArray($mergedRow, null, 'A' . $rowIndex);

                            $colStart = count($row1) + 1;
                            $colEnd = $colStart + count($row2) - 1;
                            $cellRange = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colStart) . $rowIndex . ':' .
                                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colEnd) . $rowIndex;
                            $newSheet->getStyle($cellRange)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKGREEN);

                            $rowIndex++;
                            $found = true;
                            $sheet1Data[$idx1] = [];
                            break;
                        }
                    }
                    if (!$found) {
                        $notMatchedRows[] = $row2;
                    }
                }

                // --- (Le reste du code reste identique) ---

                $notMatchSheet = $newSpreadsheet->createSheet();
                $notMatchSheet->setTitle('not_match');
                $notMatchSheet->fromArray($header2, null, 'A1');
                if (!empty($notMatchedRows)) {
                    $notMatchSheet->fromArray($notMatchedRows, null, 'A2');
                }

                $analyseSheet = $newSpreadsheet->createSheet();
                $analyseSheet->setTitle('Analyse');
                $tableau = [
                    ['# Commandes', '=counta(Worksheet!A:A)-1'],
                    ['# Factures', '=counta(Worksheet!P:P)-1'],
                    ['# Différences', '=counta(not_match!A:A)-1'],
                    ['% Diff.', '=B3/B1'],
                    ['% Corresp.', '=1-B4'],
                    ['Valeur TTC Cmd', '=sum(Worksheet!H:H)'],
                    ['Valeur HT Cmd', '=sum(Worksheet!G:G)'],
                    ['Valeur TTC Fact.', '=sum(Worksheet!W:W)+sum(not_match!H:H)'],
                    ['Valeur HT Fact.', '=sum(Worksheet!V:V)+sum(not_match!G:G)'],
                    ['', ''],
                    ['Ecart TTC', '=B8-B6'],
                    ['Ecart HT', '=B9-B7'],
                ];
                $analyseSheet->fromArray($tableau, null, 'A1');

                $rowCount = count($tableau);
                $analyseSheet->getStyle('A1:B' . $rowCount)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $analyseSheet->getStyle('B1:B' . $rowCount)->getFont()->setBold(true);
                $analyseSheet->getStyle('B1:B12')->getNumberFormat()->setFormatCode('#,##0');
                $analyseSheet->getStyle('B4:B5')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);
                $analyseSheet->getColumnDimension('A')->setAutoSize(true);
                $analyseSheet->getColumnDimension('B')->setAutoSize(true);

                $emptyCmdSheet = $newSpreadsheet->createSheet();
                $emptyCmdSheet->setTitle('empty_cmd');
                $emptyCmdSheet->fromArray($header1, null, 'A1');
                $rowEmpty = 2;
                foreach ($sheet1Data as $row) {
                    if (!empty($row)) {
                        $emptyCmdSheet->fromArray($row, null, 'A' . $rowEmpty);
                        $rowEmpty++;
                    }
                }

                echo "PROGRESS:95:Génération du fichier Excel...\n";
                flush();

                $fileName = 'resultat_' . date('Ymd_His') . '.xlsx';
                $outputFile = __DIR__ . '/' . $fileName;
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($newSpreadsheet);
                $writer->save($outputFile);

                echo "PROGRESS:100:Terminé !\n";
                echo "DOWNLOAD:$fileName\n";
                flush();
            } else {
                echo "ERROR:Le fichier doit contenir au moins 2 feuilles.\n";
            }
        } catch (\Throwable $e) {
            echo "ERROR:Exception : " . str_replace("\n", " ", $e->getMessage()) . "\n";
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Comparateur Excel Avancé</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
            background-color: #f0f2f5;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 650px;
            margin: 0 auto;
            text-align: center;
        }

        h2 {
            margin-top: 0;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        p {
            color: #666;
            margin-bottom: 30px;
        }

        .file-input-wrapper {
            margin-bottom: 20px;
        }

        input[type=file] {
            padding: 10px;
            border: 2px dashed #ccc;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
            background: #fafafa;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.2s;
        }

        button:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        #progressContainer {
            display: none;
            margin-top: 30px;
            text-align: left;
        }

        .progress-wrapper {
            background: #e9ecef;
            width: 100%;
            height: 24px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        #progressBar {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            width: 0%;
            transition: width 0.4s ease;
        }

        #progressText {
            font-size: 14px;
            color: #444;
            display: block;
            margin-top: 8px;
            font-weight: 500;
            text-align: center;
        }

        .result-msg {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            text-align: left;
        }

        .result-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .result-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="card">
        <form id="excelForm" enctype="multipart/form-data">
            <h2>Comparateur Excel (Algorithmique)</h2>
            <p>Chargez un fichier contenant 2 feuilles.<br>Matching intelligent sur les descriptions.</p>
            <div class="file-input-wrapper">
                <input type="file" name="excel_file" id="excel_file" accept=".xls,.xlsx" required>
            </div>
            <button type="submit" id="submitBtn">Lancer l'analyse</button>
        </form>
        <div id="progressContainer">
            <div class="progress-wrapper">
                <div id="progressBar"></div>
            </div>
            <span id="progressText">Initialisation...</span>
        </div>
        <div id="result"></div>
    </div>
    <script>
        document.getElementById('excelForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var form = e.target;
            var fileInput = document.getElementById('excel_file');
            if (!fileInput.files.length) return;
            var formData = new FormData(form);
            var progressContainer = document.getElementById('progressContainer');
            var progressBar = document.getElementById('progressBar');
            var progressText = document.getElementById('progressText');
            var resultDiv = document.getElementById('result');
            var submitBtn = document.getElementById('submitBtn');

            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressText.textContent = 'Envoi du fichier...';
            resultDiv.innerHTML = '';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Traitement en cours...';

            fetch('', {
                method: 'POST',
                body: formData
            }).then(response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                function read() {
                    return reader.read().then(({
                        done,
                        value
                    }) => {
                        if (done) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Lancer l\'analyse';
                            return;
                        }
                        buffer += decoder.decode(value, {
                            stream: true
                        });
                        let lines = buffer.split('\n');
                        buffer = lines.pop();
                        lines.forEach(line => {
                            line = line.trim();
                            if (!line) return;
                            if (line.startsWith('PROGRESS:')) {
                                const parts = line.split(':');
                                if (parts.length >= 3) {
                                    const percent = parts[1];
                                    const msg = parts.slice(2).join(':');
                                    progressBar.style.width = percent + '%';
                                    progressText.textContent = msg;
                                }
                            } else if (line.startsWith('DOWNLOAD:')) {
                                const fileName = line.split(':')[1].trim();
                                triggerDownload(fileName);
                                resultDiv.innerHTML = '<div class="result-msg result-success">✅ <strong>Terminé !</strong> Le téléchargement a démarré.</div>';
                            } else if (line.startsWith('ERROR:')) {
                                resultDiv.innerHTML = '<div class="result-msg result-error">❌ ' + line.substring(6) + '</div>';
                                progressBar.style.backgroundColor = '#dc3545';
                            }
                        });
                        return read();
                    });
                }
                return read();
            }).catch(err => {
                resultDiv.innerHTML = '<div class="result-msg result-error">Erreur réseau : ' + err.message + '</div>';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Lancer l\'analyse';
            });
        });

        function triggerDownload(url) {
            const a = document.createElement('a');
            a.href = url;
            a.download = url;
            document.body.appendChild(a);
            a.click();
            setTimeout(() => document.body.removeChild(a), 100);
        }
    </script>
</body>

</html>