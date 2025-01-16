<?php
ob_start();  // Ausgabe-Pufferung starten vor allen includes
require_once(__DIR__ . "/mods/all.head.php");
require_once(__DIR__ . "/mods/ajax.head.php");
require_once(__DIR__ . "/inc/include.php");
ob_clean();  // Löschen aller bisherigen Ausgaben

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch($action) {
    case 'update':
        $id = $data['id'];
        //$id = intval($id, 10);
        $field = $data['field'];
        $value = $data['value'];
        $tabelle = $data['tabelle'];

        $query = "UPDATE `$tabelle` SET `$field` = ? WHERE `id` = ?";
        $args = array($value, $id);
        try {
            $result = $db->query($query, $args);
            $response = ["status" => $result ? "success" : "error"];
        } catch (Exception $e) {
            $db->log("Update error: " . $e->getMessage());
            $response = ["status" => "error", "message" => "Fehler beim Update. Stimmt das Datenformat? Für Details siehe log-Tabelle in der Datenbank."];
        }
        ob_end_clean();
        echo json_encode($response);
        break;

    case 'check':
        $id = $data['id'];
        $field = $data['field'];
        $tabelle = $data['tabelle'];

        $query = "SELECT * FROM `$tabelle` WHERE `id` = ?";
        $args = array($id);
        try {
            $result = $db->query($query, $args);
            if ($result && count($result['data']) > 0) {
                $response = ["status" => "success", "row" => $result['data'][0]];
            } else {
                $response = ["status" => "error", "message" => "Keine Zeile gefunden"];
            }
        } catch (Exception $e) {
            $db->log("Check error: " . $e->getMessage());
            $response = ["status" => "error", "message" => "Fehler beim Zeilenprüfen."];
        }
        ob_end_clean();
        echo json_encode($response);
        break;

    case 'insert':
        $tabelle = $data['tabelle'];
        $defaultValues = $data['defaultValues'];

        $fields = implode(", ", array_keys($defaultValues));
        $placeholders = implode(", ", array_fill(0, count($defaultValues), "?"));
        $values = array_values($defaultValues);

        $query = "INSERT INTO `$tabelle` ($fields) VALUES ($placeholders)";
        try {
            $result = $db->query($query, $values);
            if ($result['data']) {
                $response = ["status" => "success"];
            } else {
                $errorInfo = $db->errorInfo();
                $db->log("Insert error: " . json_encode($errorInfo));
                $response = ["status" => "error", "message" => "Fehler beim Einfügen des Datensatzes. Bitte prüfen Sie die log-Tabelle in der Datenbank!"];
            }
        } catch (Exception $e) {
            $db->log("Exception: " . $e->getMessage());
            $response = ["status" => "error", "message" => "Fehler beim Einfügen des Datensatzes. Bitte prüfen Sie die log-Tabelle in der Datenbank!"];
        }
        ob_end_clean();
        echo json_encode($response);
        break;

    case 'insert_default':
        $tabelle = $data['tabelle'];

        // Insert an empty dataset to let the database take the default values
        $query = "INSERT INTO `$tabelle` () VALUES ()";
        try {
            $result = $db->query($query);
            if ($result) {
                $response = ["status" => "success"];
            } else {
                $errorInfo = $db->errorInfo();
                $db->log("Insert error: " . json_encode($errorInfo));
                $response = ["status" => "error", "message" => "Fehler beim Einfügen des Datensatzes. Bitte prüfen Sie die log-Tabelle in der Datenbank!"];
            }
        } catch (Exception $e) {
            $db->log("Exception: " . $e->getMessage());
            $response = ["status" => "error", "message" => "Fehler beim Einfügen des Datensatzes. Bitte prüfen Sie die log-Tabelle in der Datenbank!"];
        }
        ob_end_clean();
        echo json_encode($response);
        break;

    case 'delete':
        $tabelle = $data['tabelle'];
        $ids = $data['ids'];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "DELETE FROM `$tabelle` WHERE `id` IN ($placeholders)";
        try {
            $result = $db->query($query, $ids);
            $response = ["status" => "success"];
        } catch (Exception $e) {
            $db->log("Delete error: " . $e->getMessage());
            $response = ["status" => "error", "message" => "Fehler beim Löschen der Daten."];
        }
        ob_end_clean();
        echo json_encode($response);
        break;

    case 'check_duplicates':
        $tabelle = $data['tabelle'];

        // Get columns excluding auto-increment columns
        $columnsQuery = "SHOW COLUMNS FROM `$tabelle`";
        $columnsResult = $db->query($columnsQuery);
        $columns = array_filter($columnsResult, function($column) {
            return $column['Extra'] !== 'auto_increment';
        });
        $columns = array_column($columns, 'Field');

        // Build query to find duplicates
        $columnsList = implode(", ", $columns);
        $duplicatesQuery = "
            SELECT id
            FROM (
                SELECT id, COUNT(*) OVER (PARTITION BY $columnsList) AS cnt
                FROM `$tabelle` 
            ) sub
            WHERE cnt > 1 
        ";
        try {
            $duplicatesResult = $db->query($duplicatesQuery);
            $duplicateIds = array_column($duplicatesResult, 'id');
            $response = ["status" => "success", "duplicates" => $duplicateIds];
        } catch (Exception $e) {
            $db->log("Check duplicates error: " . $e->getMessage());
            $response = ["status" => "error", "message" => "Fehler beim Überprüfen auf doppelte Einträge."];
        }
        ob_end_clean();
        echo json_encode($response);
        break;

    /*case 'import':
        $tabelle = $data['tabelle'];
        $header = $data['header'];
        $values = $data['values'];
        
        try {
            // Baue INSERT Query
            $columns = implode(', ', $header);
            $valueStrings = [];
            
            foreach($values as $row) {
                $rowValues = array_map(function($val) {
                    if($val === '') return 'NULL';
                    return "'" . addslashes($val) . "'";
                }, $row);
                $valueStrings[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            $valuesSql = implode(",\n", $valueStrings);
            $query = "INSERT INTO $tabelle ($columns) VALUES $valuesSql";
            
            $result = $db->query($query);
            
            if(isset($result['error'])) {
                $response = ['status' => 'error', 'message' => $result['error']];
            } else {
                $count = count($values);
                $response = ['status' => 'success', 'message' => "$count Datensätze wurden importiert"];
            }
        } catch(Exception $e) {
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }
        ob_end_clean();
        echo json_encode($response);
        break;*/

    case 'validate':
        $response = checkDaten($data, $db);
        ob_end_clean();
        echo json_encode($response);
        break;

    case 'import':
        $response = checkDaten($data, $db);
        if($response['status'] == "success"){
            $insertQuery = $response['insert_query'];
            $zaehler = 0;
            foreach($response['args'] as $args){
                $result = $db->query($insertQuery, $args);}
                $zaehler ++;
            }
            if($result['data']){
                $response = ["status" => "success", "message" => "$zaehler Datensätze wurden importiert."];
            }else{
                $response = ["status" => "error", "message" => "Fehler beim Importieren der Daten. Bitte prüfen Sie die log-Tabelle in der Datenbank!"];
            }
        ob_end_clean();
        echo json_encode($response);
        exit;
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Ungültige Aktion']);
        break;
}
function checkDaten($data, $db){
    $importDatenzeilen = $data['rows'];

    $importDatensätze = array_map(function($row) {
        return explode(',', $row);
    }, $importDatenzeilen);


    $suchQueries = $data['suchQueries'];
    $tabelle = $data['tabelle'];
    $suchStrings = [];
    
    foreach ($suchQueries as $index => $query) { 
        $result = $db->query($query);
        foreach($result as $row){
            foreach ($row as $item) {
                $id = $item['id'] ?? $item['ID'] ?? null;
                if ($id === null) {
                    $response = ["status" => "error", "message" => "Der Suchquery, der in der config definiert ist, muss ein Feld 'id' zurückliefern, damit das Suchergebnis zugewiesen werden kann."];
                    ob_end_clean();
                    echo json_encode($response);
                    exit;
                }
                unset($item['id'], $item['ID']);
                $suchStrings[$index][$id] = implode(' ', $item);
            }
        }
    }
        

    $spalten = array();
    $insertQuery = "INSERT INTO `$tabelle` (";
    foreach($importDatensätze[0] as $spalte)
    {
        $insertQuery .= "`$spalte`, ";
        $spalten[] = $spalte;
    }
    $insertQuery = rtrim($insertQuery, ", ");
    $insertQuery .= ") VALUES (";
    foreach($importDatensätze[0] as $spalte)
    {
        $insertQuery .= "?, ";
    }
    $insertQuery = rtrim($insertQuery, ", ");
    $insertQuery .= ")";
    
    unset($importDatensätze[0]);
    /*
    echo "------------------";
    show($spalten);
    echo "+++++++<br>";
    show($importDatensätze);
    echo "+++++++<br>";
    show($suchStrings);
    */

    /*
    Sparte,Mitglied,Freitext
    Fuß,Berecht, Hallo Welt
    Fuß, Ditte, Mister 300
    */
    // Nachdem alles gesetzt ist (Was wird wo gesucht), gehe jetzt den Import Zeile für Zeile und Feld für Feld durch
    
    $ERROR = false;
    $error_msg = "";
    $alleArgs = array();
    $zeile = 1; // Header-Zeile wird rausgeschnitten, daher beginnen die Daten bei Zeile 2
    foreach($importDatensätze as $importZeile)
    {
        $zeile ++;

        $Datensatz_kann_importiert_werden = true;
        $ID_bereits_gefunden = false;
        $datenSatzArgs = array();
        
        foreach($importZeile as $FeldIndex => $importFeld)
        {
            
            // $zeile = $FeldIndex + 2; // +1 weil 0-basiert, +1 weil Header-Zeile weggeschnitten wurde
            $ID_bereits_gefunden = false;
            $spalte = $spalten[$FeldIndex];
            // Ist es eine FK-Spalte?
            if(isset($suchStrings[$spalte]))
            {
                $suchString = $suchStrings[$spalte];
                //Gehe jede einzelne ID durch und schaue, ob das passt
                foreach($suchString as $id => $suchFeld)
                {
                    $words = array();
                    // Match quoted strings first, then unquoted words
                    $pattern = '/["\']([^"\']+)["\']|\S+/';
                    preg_match_all($pattern, $importFeld, $matches);
                    // Use only words from inside quotes or standalone words
                    $words = $matches[1];  // Get quoted content
                    $words = array_merge($words, array_diff($matches[0], array_map(function($w) { return "\"$w\""; }, $matches[1]))); // Add unquoted words
                    $words = array_filter($words);
                    
                    /*$pattern = '/["\']([^"\']+)["\']|\S+/';
                    if(preg_match_all($pattern, $importFeld, $matches)) {
                        $words = array_merge($matches[1], array_diff($matches[0], $matches[1]));
                    }
                    $words = array_filter($words);*/
                    $allWordsFound = true;
                    foreach($words as $word) {
                        if(stripos($suchFeld, $word) === false) {
                            $allWordsFound = false;
                            break;
                        }
                    }
                    if($allWordsFound) {
                        if($ID_bereits_gefunden){
                            $ERROR = true;
                            $tmperr = "<p>Der Import <b>$importFeld</b> in Zeile $zeile ($spalte) liefert kein eindeutiges Ergebnis. Bitte pr&auml;zisieren.</p>";
                            // Mehrfachausgaben vermeiden.
                            if(strpos($error_msg, $tmperr) === false)
                                $error_msg .= $tmperr;
                        }else{
                            $datenSatzArgs[] = $id;
                            $ID_bereits_gefunden = true;
                        }
                    }
                }
                if(!$ID_bereits_gefunden){
                    $ERROR = true;
                    $error_msg .= "<p>Der Import <b>$importFeld</b> in Zeile $zeile ($spalte) liefert kein Ergebnis. Es muss zur gegebenen Auswahlmöglichkeit der Spalte $spalte passen. Bitte pr&uuml;fen.</p>";
                }
            }
            else // Keine FK-Spalte (Einfach Inhalt importieren)
            {
                $datenSatzArgs[] = $importFeld;
            }


        }
        $alleArgs[] = $datenSatzArgs;
    }

    if($ERROR){
        $response = ["status" => "error", "message" => $error_msg];
    }else{
        $response = ["status" => "success", "insert_query" => $insertQuery, "args" => $alleArgs];
    }
    return $response;
}
?>
