<?php
require_once(__DIR__ . "/mods/all.head.php");
require_once(__DIR__ . "/mods/index.head.php");
require_once(__DIR__ . "/inc/include.php");

$admin = 1;
$selectedTableID = isset($_GET['tab']) ? $_GET['tab'] : "";
$data = array();
$hasForeignKeys = false;
$foreignKeyColumns = array();

if(isset($anzuzeigendeDaten[$selectedTableID])){
    // Tabellenname existiert?
    if(isset($anzuzeigendeDaten[$selectedTableID]['tabellenname'])){
        $tabelle = $anzuzeigendeDaten[$selectedTableID]['tabellenname'];
    }else{
        $err = "Die Konstante \$anzuzeigendeDaten[$selectedTableID]['tabellenname'] enth&auml;lt keinen g&uuml;ltigen Tabellennamen oder existiert nicht.";
        dieWithError($err,__FILE__,__LINE__);
    }
    // Query existiert?
    if(isset($anzuzeigendeDaten[$selectedTableID]['query'])){
        $dataquery = $anzuzeigendeDaten[$selectedTableID]['query'];
    }else{
        $err = "Die Konstante \$anzuzeigendeDaten[$selectedTableID]['query'] enth&auml;lt keinen g&uuml;ltigen Tabellennamen oder existiert nicht.";
        dieWithError($err,__FILE__,__LINE__);
    }

    // Prüfen auf FK-Spalten
    if(isset($anzuzeigendeDaten[$selectedTableID]['referenzqueries'])) {
        $hasForeignKeys = true;
        $foreignKeyColumns = array_keys($anzuzeigendeDaten[$selectedTableID]['referenzqueries']);
    }

    echo "<div class='container mt-4'>";
    if($hasForeignKeys) {
        echo "<div class='alert alert-info'>";
        echo "Diese Tabelle hat folgende Fremdschlüssel-Spalten: " . implode(", ", $foreignKeyColumns);
        echo "</div>";
    } else {
        echo "<div class='alert alert-info'>";
        echo "Diese Tabelle hat keine Fremdschlüssel-Spalten.";
        echo "</div>";
    }
    echo "</div>";

    // Hole Spalteninformationen
    $columns = $db->query("SHOW COLUMNS FROM $tabelle");
    if(!isset($columns['data'])) {
        dieWithError("Konnte Spalteninformationen nicht abrufen", __FILE__, __LINE__);
    }

    $tableColumns = array();
    foreach($columns['data'] as $col) {
        if($col['Extra'] != 'auto_increment') {
            $tableColumns[] = $col['Field'];
        }
    }
}

// Helper functions
function dieWithError($err, $file, $line, $stayAlive = false) {
    global $db;
    $db->log("$file:$line - $err");
    echo("<br><div class='container'><b>Konfigurationsfehler:</b> $err</div>");
    if(!$stayAlive) die();
}

function renderTableSelectBox($db) {
    global $anzuzeigendeDaten, $selectedTableID;
    $options = [];
    
    if(!isset($anzuzeigendeDaten[$selectedTableID])){
        $options[] = ['value' => '', 'text' => '-- Tabelle wählen --', 'selected' => true];
    }

    foreach ($anzuzeigendeDaten as $index => $table) {
        $displayText = !empty($table['auswahltext']) ? $table['auswahltext'] : $table['tabellenname'];
        $options[] = [
            'value' => $index,
            'text' => $displayText,
            'selected' => ($index == $selectedTableID)
        ];
    }
    
    return $options;
}

// Helper für FK-Matching
function findForeignKeyMatch($db, $searchValue, $referenzquery) {
    $result = $db->query($referenzquery);
    if (!isset($result['data'])) return null;

    $searchTerms = array_filter(explode(' ', strtolower($searchValue)));
    $matches = [];

    foreach ($result['data'] as $row) {
        $allFieldsMatch = true;
        $allFields = strtolower(implode(' ', $row));
        
        foreach ($searchTerms as $term) {
            if (strpos($allFields, $term) === false) {
                $allFieldsMatch = false;
                break;
            }
        }
        
        if ($allFieldsMatch) {
            $matches[] = $row['id'];
        }
    }

    return count($matches) === 1 ? $matches[0] : null;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=TITEL?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .textarea-container {
            display: flex;
            position: relative;
            margin-bottom: 1rem;
        }
        .line-numbers {
            width: 3em;
            border-radius: 0.25rem 0 0 0.25rem;
            border-right: none;
            text-align: right;
            color: #6c757d;
            background-color: #f8f9fa;
            resize: none;
            cursor: default;
            user-select: none;
            font-family: monospace;
            padding-right: 0.5rem;
        }
        #importData {
            flex-grow: 1;
            border-radius: 0 0.25rem 0.25rem 0;
            font-family: monospace;
            resize: vertical;
        }
        /* Gemeinsame Styles für beide Textareas */
        .line-numbers, #importData {
            font-size: 1rem;
            line-height: 1.5;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            min-height: 200px;
        }
    </style>
    <script>
        // Globale Variablen für PHP-Werte
        const validColumns = <?= json_encode($tableColumns ?? []) ?>;
        const hasForeignKeys = <?= json_encode($hasForeignKeys ?? false) ?>;  // Diese Zeile hinzufügen
        
        function validateImport() {
            const textarea = document.getElementById('importData');
            const data = textarea.value.trim();
            const lines = data.split('\n');
            let hasErrors = false;
            
            if(lines.length < 2) {
                showValidationResult(false, {
                    message: 'Fehler: Mindestens Header und ein Datensatz erforderlich',
                    debug: {
                        data: data,
                        lineCount: lines.length
                    }
                });
                return;
            }

            const header = parseCSVLine(lines[0]);
            
            // Prüfe nur ob die verwendeten Spalten gültig sind
            for(let col of header) {
                if(!validColumns.includes(col)) {
                    showValidationResult(false, {
                        message: 'Fehler: Ungültige Spalte im Header: ' + col + '<br>Erlaubte Spalten sind: ' + validColumns.join(', '),
                        debug: {
                            invalidColumn: col,
                            validColumns: validColumns,
                            header: header
                        }
                    });
                    return;
                }
            }

            // Prüfe Datensätze
            for(let i = 1; i < lines.length; i++) {
                if(lines[i].trim() === '') continue;
                
                const fields = parseCSVLine(lines[i]);
                if(fields.length !== header.length) {
                    showValidationResult(false, `Fehler: Zeile ${i+1} hat eine falsche Anzahl Felder (${fields.length} statt ${header.length})`);
                    return;
                }
            }

            // FK-Validierung ändern
            if (hasForeignKeys) {
                const referenzqueries = <?= json_encode($anzuzeigendeDaten[$selectedTableID]['referenzqueries'] ?? []) ?>;
                let promises = [];
                
                for (let i = 1; i < lines.length; i++) {
                    if (lines[i].trim() === '') continue;
                    const fields = parseCSVLine(lines[i]);
                    
                    for (let j = 0; j < header.length; j++) {
                        const column = header[j];
                        if (referenzqueries[column]) {
                            const value = fields[j];
                            const lineNum = i;
                            promises.push(
                                fetch('ajax.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        action: 'matchForeignKey',
                                        query: referenzqueries[column],
                                        value: value
                                    })
                                })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.status === 'error') {
                                        // Hier die komplette result-Objekt übergeben
                                        showValidationResult(false, {
                                            message: `Zeile ${lineNum+1}, Spalte ${column}: ${result.message}`,
                                            debug: result.debug
                                        });
                                        hasErrors = true;
                                    }
                                })
                            );
                        }
                    }
                }
                
                Promise.all(promises).then(() => {
                    if (!hasErrors) {
                        showValidationResult(true, {
                            message: 'Datenformat ist korrekt! Der Import kann durchgeführt werden.',
                            debug: {
                                header: header,
                                lineCount: lines.length - 1,
                                hasForeignKeys: hasForeignKeys,
                                foreignKeyColumns: referenzqueries ? Object.keys(referenzqueries) : []
                            }
                        });
                    }
                });
                return;
            }

            showValidationResult(true, {
                message: 'Datenformat ist korrekt! Der Import kann durchgeführt werden.',
                debug: {
                    header: header,
                    lineCount: lines.length - 1,
                    hasForeignKeys: hasForeignKeys,
                    foreignKeyColumns: referenzqueries ? Object.keys(referenzqueries) : []
                }
            });
        }

        function showValidationResult(isValid, messageObj) {
            const resultDiv = document.getElementById('validationResult');
            const importButton = document.getElementById('importButton');
            const importHelpContent = document.getElementById('importHelpContent');
            
            resultDiv.style.display = 'block';
            resultDiv.className = 'alert ' + (isValid ? 'alert-success' : 'alert-danger');
            
            let content = '';
            
            // Überprüfen ob messageObj ein String oder Objekt ist
            if (typeof messageObj === 'string') {
                content = messageObj;
            } else {
                content = `<div>${messageObj.message}</div>`;
                if (messageObj.debug) {
                    content += `
                        <hr>
                        <details>
                            <summary>Debug-Informationen</summary>
                            <pre style="font-size: 12px;">${JSON.stringify(messageObj.debug, null, 2)}</pre>
                        </details>
                    `;
                }
            }
            
            resultDiv.innerHTML = content;
            
            if (isValid) {
                importButton.style.display = 'inline-block';
                importHelpContent.classList.remove('show');
            } else {
                importButton.style.display = 'none';
                importHelpContent.classList.add('show');
            }
        }

        function importData() {
            const textarea = document.getElementById('importData');
            const data = textarea.value.trim();
            const lines = data.split('\n');
            const header = parseCSVLine(lines[0]);
            const values = lines.slice(1)
                .filter(line => line.trim())
                .map(line => parseCSVLine(line));

            const importButton = document.getElementById('importButton');
            importButton.disabled = true;
            importButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Importiere...';

            fetch('ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'import',
                    tabelle: '<?= $tabelle ?>',
                    header: header,
                    values: values
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    showValidationResult(true, result.message);
                } else {
                    showValidationResult(false, 'Fehler beim Import: ' + result.message);
                }
            })
            .catch(error => {
                showValidationResult(false, 'Fehler beim Import: ' + error.message);
            })
            .finally(() => {
                importButton.disabled = false;
                importButton.innerHTML = 'Daten importieren';
            });
        }

        function parseCSVLine(line) {
            const fields = [];
            let field = '';
            let inQuotes = false;
            
            for(let i = 0; i < line.length; i++) {
                const char = line[i];
                
                if(char === '\"') {
                    inQuotes = !inQuotes;
                } else if(char === ',' && !inQuotes) {
                    fields.push(field.trim());
                    field = '';
                } else {
                    field += char;
                }
            }
            
            fields.push(field.trim());
            return fields;
        }

        function updateLineNumbers() {
            const textarea = document.getElementById('importData');
            const lineNumbers = document.getElementById('line-numbers');
            const lines = textarea.value.split('\n').length;
            lineNumbers.value = Array.from({length: lines}, (_, i) => i + 1).join('\n');
            
            // Synchronisiere Scroll und Höhe
            lineNumbers.style.height = textarea.offsetHeight + 'px';
            lineNumbers.scrollTop = textarea.scrollTop;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('importData');
            textarea.addEventListener('input', updateLineNumbers);
            textarea.addEventListener('scroll', function() {
                document.getElementById('line-numbers').scrollTop = this.scrollTop;
            });
            
            // Initial update
            updateLineNumbers();
        });
    </script>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="container mt-4">
            <!-- Table Select -->
            <form method="get" class="d-flex align-items-center">
                <select name="tab" class="form-control mr-2" onchange="this.form.submit()">
                    <?php foreach(renderTableSelectBox($db) as $option): ?>
                        <option value="<?= htmlspecialchars($option['value']) ?>" 
                                <?= $option['selected'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option['text']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <a href="index.php?tab=<?= $selectedTableID ?>" class="btn btn-secondary">Zurück</a>
            </form>

            <!-- Hints -->
            <?php if(isset($anzuzeigendeDaten[$selectedTableID]['hinweis'])): ?>
                <div class="alert alert-info">
                    <?= $anzuzeigendeDaten[$selectedTableID]['hinweis'] ?>
                </div>
            <?php endif; ?>

            <!-- Import Section -->
            <?php if(isset($tableColumns)): ?>
                <div class="mt-4">
                    <h4>Datenimport für: <?= htmlspecialchars($anzuzeigendeDaten[$selectedTableID]['auswahltext']) ?></h4>
                    
                    <!-- Import Rules -->
                    <div class="alert alert-warning" id="importHelp">
                        <p class="mb-0">
                            <button class="btn btn-link p-0" type="button" data-toggle="collapse" data-target="#importHelpContent">
                                Hilfe zum Import anzeigen/ausblenden
                            </button>
                        </p>
                        <div class="collapse show" id="importHelpContent">
                            <p class="mt-3"><strong>Anleitung zum Import:</strong></p>
                            <ol>
                                <p>1. Kopfzeile erstellen mit den Spalten:</p>
                                <p><code><strong><?= implode(",", $tableColumns) ?></strong></code></p>
                                
                                <p>2. Daten einfügen:</p>
                                <ul>
                                    <li>Eine Zeile pro Datensatz</li>
                                    <li>Spalten durch Komma trennen</li>
                                    <li>Leere Felder: einfach nichts zwischen die Kommas schreiben</li>
                                    <?php if($hasForeignKeys): ?>
                                    <li>Bei Fremdschlüssel-Spalten (<?= implode(", ", $foreignKeyColumns) ?>):
                                        <ul>
                                            <li>Namen oder Bezeichnung eingeben (z.B. "Hans Müller" oder "Abteilung Nord")</li>
                                            <li>Teilwörter reichen aus (z.B. "Hans" oder "Nord")</li>
                                            <li>Groß/Kleinschreibung spielt keine Rolle</li>
                                            <li>Die Eingabe muss eindeutig auf einen Datensatz passen</li>
                                        </ul>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                                
                                <p>3. Beispiel mit Daten:</p>
                                <?php if($hasForeignKeys): ?>
                                <code>Vorname,Nachname,Abteilung<br>Hans,Meier,Nord<br>Lisa,Müller,Süd</code>
                                <?php else: ?>
                                <code>Anrede,Vorname,Nachname<br>Herr,,Meier<br>Frau,Lisa,</code>
                                <?php endif; ?>

                                <?php if($hasForeignKeys): ?>
                                <p class="mt-2"><small>Hinweis: Bei Fremdschlüssel-Feldern werden die Eingaben automatisch in IDs umgewandelt, wenn sie eindeutig zugeordnet werden können.</small></p>
                                <?php endif; ?>
                            </ol>
                        </div>
                    </div>

                    <!-- Import Form -->
                    <div class="form-group">
                        <div class="textarea-container">
                            <textarea id="line-numbers" class="line-numbers" readonly>1</textarea>
                            <textarea id="importData" class="form-control" rows="10" 
                                    placeholder="z.B.:&#10;Name,Alter,Stadt&#10;Max Müller,42,Berlin&#10;'Mustermann, Peter',23,Hamburg"></textarea>
                        </div>
                    </div>
                    
                    <div id="validationResult" class="alert" style="display:none;"></div>
                    <div class="mb-3">  <!-- Hier neues div mit margin-bottom -->
                        <button onclick="validateImport()" class="btn btn-primary">Daten prüfen</button>
                        <button onclick="importData()" class="btn btn-success ml-2" id="importButton" style="display:none;">Daten importieren</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
