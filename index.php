<!DOCTYPE html>
<html lang="de">
<head>

<?php
//phpinfo();
//die();
require_once(__DIR__ . "/mods/all.head.php");
require_once(__DIR__ . "/mods/index.head.php");
require_once(__DIR__ . "/inc/include.php");


$readwrite = 0;
$selectedTableID = isset($_GET['tab']) ? $_GET['tab'] : "";
$data = array();
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

    // Schreibrechte?
    if(isset($anzuzeigendeDaten[$selectedTableID]['writeaccess'])){
        $readwrite = $anzuzeigendeDaten[$selectedTableID]['writeaccess'];
    }else{
        $readwrite = 0;
    }
    
    // Query funktioniert?
    $data = $db->query($dataquery);
    if(isset($data['error'])){
        $err = "<p>Die Konstante \$anzuzeigendeDaten[$selectedTableID]['query'] enth&auml;lt keinen g&uuml;ltiges SQL-Query:</p> <p><b>". $data['error']."</b></p>";
        dieWithError($err,__FILE__,__LINE__);
    } elseif (isset($data['message'])) {
        // Leerer Datensatz
        $err = $data['message'];
        $data = array();
    } elseif (isset($data['data'])) {
        // Datensätze vorhanden
        $data = $data['data'];

        // Gibt es eine ID-Spalte?
        if(!isset($data[0]['id'])){
            $err = "Die Konstante \$anzuzeigendeDaten[$selectedTableID]['query'] muss eine Spalte 'id' zur&uuml;ckgeben.";
            dieWithError($err,__FILE__,__LINE__);
        }
    } else {
        // Unerwarteter Fall
        $err = "Ein unbekannter Fehler ist aufgetreten."; 
        dieWithError($err,__FILE__,__LINE__);
    }
    


} else {
    $tabelle = "";
}

$tabelle_upper = strtoupper($tabelle)
?>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=TITEL?></title>

    <?php
    

    ?>

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .form-control.border-0 {
            background-color:rgba(0,0,0,0) !important;
        }
        .form-control.border-0:focus {
            background-color:rgba(0,0,0,0) !important;
        }
        .highlight-new {
            background-color: lightyellow !important;
        }
        .error-cell {
            background-color: red !important;
        }
        .toggle-btn {
            width: 40px;
            height: 40px;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .toggle-btn-header {
            color: darkgrey !important;
        }
        thead {
            position: sticky;
        }
        .table-container {
            overflow-x: auto;
            margin: 0 auto;
        }
        .table {
            table-layout: fixed;
            margin: 0 auto;
        }
        th, td {
            white-space: nowrap;
            min-width: fit-content;
        }
        td {
            vertical-align: middle !important;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: url('./inc/img/body.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .btn-group-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .btn-group-container .btn {
            flex: 1;
            margin: 5px;
        }

    </style>

    <script>
        function updateField(tabelle, id, field, value, datatype) {
            if(datatype){
                if (datatype.startsWith("decimal")) {
                    value = value.replace(',', '.'); // Replace all commas with dots
                    const match = value.match(/\d+(\.\d+)?/);
                    if (match) {
                        value = match[0];
                    } else {
                        value = ''; // Löst einen Fehler aus.
                    }
                    value = parseFloat(value);
                }
            }
            
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ajax.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            const data = JSON.stringify({
                action: 'update',
                tabelle: tabelle,
                id: id,
                field: field,
                value: value === "" ? null : value  // Sende NULL, wenn der Wert leer ist
            });

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === "success") {
                            checkRow(tabelle, id, field, value);
                        } else {
                            markCellError(id, field);
                            alert("Fehler beim Update. Stimmt das Datenformat? Für Details siehe log-Tabelle in der Datenbank.");
                        }
                    } catch (e) {
                        markCellError(id, field);
                        alert("Fehler beim Verarbeiten der Serverantwort.");
                    }
                } else if (xhr.readyState === 4 && xhr.status !== 200) {
                    markCellError(id, field);
                    alert("Serverfehler beim Update. Stimmt das Datenformat? Für Details siehe log-Tabelle in der Datenbank.");
                }
            };

            xhr.send(data);
        }

        function checkRow(tabelle, id, field, value) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ajax.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            const data = JSON.stringify({
                action: 'check',
                tabelle: tabelle,
                id: id,
                field: field,
                value: value
            });

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === "success" && response.row) {
                            updateRowColors(response.row, id, field);
                        }
                    } catch (e) {
                        alert("Fehler beim Abgleich der Zeile.");
                    }
                }
            };

            xhr.send(data);
        }

        function updateRowColors(dbRow, id, field) { 
            const row = document.querySelector(`tr[data-id='${id}']`);
            if (row) {
                const td = row.querySelector(`td[data-field='${field}']`);
                if (td) { 
                    const input = td.querySelector('input');
                    const select = td.querySelector('select');

                    if (input) { 
                        const inputValue = input.value.trim();
                        const dbValue = dbRow[field] === null ? "" : dbRow[field].toString().trim();

                        const inputNumber = parseFloat(inputValue.replace(',', '.'));
                        const dbNumber = parseFloat(dbValue.replace(',', '.'));

                        if ((!isNaN(inputNumber) && !isNaN(dbNumber) && inputNumber === dbNumber) || inputValue === dbValue) {
                            td.style.backgroundColor = 'lightgreen';
                        } else {
                            td.style.backgroundColor = 'lightcoral';
                            input.value = dbRow[field];
                        }
                    }

                    if (select) {
                        const dbValue = dbRow[field] === null ? "NULL" : dbRow[field];  // NULL wird zu ""
                        if (dbValue == select.value) {
                            td.style.backgroundColor = 'lightgreen';
                        } else {
                            td.style.backgroundColor = 'lightcoral';
                            select.value = dbValue;
                        }
                    }
                }
            }
        }

        function markCellError(id, field) {
            const row = document.querySelector(`tr[data-id='${id}']`);
            if (row) {
                const td = row.querySelector(`td[data-field='${field}']`);
                if (td) {
                    td.classList.add('error-cell');
                }
            }
        }

        function clearCellColor(input) {
            const td = input.closest('td');
            if (td) {
                td.style.backgroundColor = '';
                td.classList.remove('error-cell');
            }
        }

        function checkDubletten() {
            const checkButton = document.getElementById('check-duplicates');
            const originalText = checkButton.innerHTML;

            // Show spinner and disable button
            checkButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Suche...';
            checkButton.disabled = true;

            fetch('ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'check_duplicates', tabelle: '<?php echo $tabelle; ?>' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.duplicates.length > 0) {
                        filterRowsById(data.duplicates);
                        let text = 'Die Tabelle wurde gefiltert. Sie sehen nun alle Einträge, ' +
                        'die mehrfach vorhanden sind. Um wieder alle Daten anzuzeigen, laden ' +
                        'Sie die Daten neu.';
                        alert(text);
                    } else {
                        alert('Es wurden keine doppelten Einträge gefunden.');
                    }
                } else {
                    alert('Fehler beim Überprüfen auf doppelte Einträge.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Fehler beim Verarbeiten der Serverantwort.');
            })
            .finally(() => {
                // Reset button state
                checkButton.innerHTML = originalText;
                checkButton.disabled = false;
            });
        }

        function filterRowsById(desiredIds) {
            const table = document.querySelector('table'); // Passe den Selektor bei Bedarf an
            const rows = table.querySelectorAll('tr[data-id]');
            rows.forEach(row => {
                const id = parseInt(row.getAttribute('data-id'), 10); // 10 steht für das Dezimalsystem
                if (desiredIds.includes(id)) {
                    row.style.display = ''; // Zeige die Zeile an
                } else {
                    row.style.display = 'none'; // Blende die Zeile aus
                }
            });
        }

        let lastSortColumn = null;
        let lastSortOrder = 'asc';

        function sortTable(column, order) {
            const table = document.querySelector('table tbody');
            const rows = Array.from(table.rows);

            rows.sort((a, b) => {
                const aCell = a.querySelector(`td[data-field='${column}']`);
                const bCell = b.querySelector(`td[data-field='${column}']`);

                let aText = aCell.innerText.trim();
                let bText = bCell.innerText.trim();

                const aInput = aCell.querySelector('input, select');
                const bInput = bCell.querySelector('input, select');

                if (aInput) {
                    if (aInput.tagName.toLowerCase() === 'select') {
                        aText = aInput.options[aInput.selectedIndex].text.trim();
                    } else {
                        aText = aInput.value.trim();
                    }
                }

                if (bInput) {
                    if (bInput.tagName.toLowerCase() === 'select') {
                        bText = bInput.options[bInput.selectedIndex].text.trim();
                    } else {
                        bText = bInput.value.trim();
                    }
                }

                const aNumber = parseFloat(aText.replace(',', '.'));
                const bNumber = parseFloat(bText.replace(',', '.'));

                let primaryComparison;
                if (!isNaN(aNumber) && !isNaN(bNumber)) {
                    primaryComparison = order === 'asc' ? aNumber - bNumber : bNumber - aNumber;
                } else {
                    primaryComparison = order === 'asc' ? aText.localeCompare(bText, undefined, { numeric: true }) : bText.localeCompare(aText, undefined, { numeric: true });
                }

                if (primaryComparison !== 0 || lastSortColumn === null) {
                    return primaryComparison;
                }

                // Secondary sort by last sorted column
                const aLastCell = a.querySelector(`td[data-field='${lastSortColumn}']`);
                const bLastCell = b.querySelector(`td[data-field='${lastSortColumn}']`);

                let aLastText = aLastCell.innerText.trim();
                let bLastText = bLastCell.innerText.trim();

                const aLastInput = aLastCell.querySelector('input, select');
                const bLastInput = bLastCell.querySelector('input, select');

                if (aLastInput) {
                    if (aLastInput.tagName.toLowerCase() === 'select') {
                        aLastText = aLastInput.options[aLastInput.selectedIndex].text.trim();
                    } else {
                        aLastText = aLastInput.value.trim();
                    }
                }

                if (bLastInput) {
                    if (bLastInput.tagName.toLowerCase() === 'select') {
                        bLastText = bLastInput.options[bLastInput.selectedIndex].text.trim();
                    } else {
                        bLastText = bLastInput.value.trim();
                    }
                }

                const aLastNumber = parseFloat(aLastText.replace(',', '.'));
                const bLastNumber = parseFloat(bLastText.replace(',', '.'));

                if (!isNaN(aLastNumber) && !isNaN(bLastNumber)) {
                    return lastSortOrder === 'asc' ? aLastNumber - bLastNumber : bLastNumber - aLastNumber;
                } else {
                    return lastSortOrder === 'asc' ? aLastText.localeCompare(bLastText, undefined, { numeric: true }) : bLastText.localeCompare(aLastText, undefined, { numeric: true });
                }
            });

            // Speichere Sortierung in Session
            fetch('save_sort.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    column: column,
                    order: order
                })
            });

            rows.forEach(row => table.appendChild(row));

            lastSortColumn = column;
            lastSortOrder = order;
        }

        function addSortEventListeners() {
            const headers = document.querySelectorAll('table thead th');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const column = header.getAttribute('data-field');
                    const currentOrder = header.getAttribute('data-order') || 'asc';
                    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

                    sortTable(column, newOrder);
                    header.setAttribute('data-order', newOrder);
                });
            });
        }

        function filterTable() {
            const filterInput = document.getElementById('tableFilter');
            if (!filterInput) return;

            const filterValue = filterInput.value.toLowerCase();
            const rows = document.querySelectorAll('table tbody tr');

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let match = false;

                cells.forEach(cell => {
                    const input = cell.querySelector('input');
                    const select = cell.querySelector('select');
                    let cellValue = cell.textContent.toLowerCase();

                    if (input) {
                        cellValue = input.value.toLowerCase();
                    } else if (select) {
                        cellValue = select.options[select.selectedIndex].text.toLowerCase();
                    }

                    if (cellValue.includes(filterValue)) {
                        match = true;
                        if (filterValue) {
                            cell.style.backgroundColor = '#D3D9F2';
                        } else {
                            cell.style.backgroundColor = '';
                        }
                    } else {
                        cell.style.backgroundColor = '';
                    }
                });

                if (match) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function insertDefaultRecord(tabelle) {
            const insertButton = document.getElementById('insertDefaultButton');
            const originalText = insertButton.innerHTML;

            // Show spinner and disable button
            insertButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Füge ein...';
            insertButton.disabled = true;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ajax.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            const data = JSON.stringify({
                action: 'insert_default',
                tabelle: tabelle
            });

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === "success") {
                            resetPage(); // Reload the page to see the new record
                        } else {
                            alert("Fehler beim Einfügen des Datensatzes. Bitte prüfen Sie die log-Tabelle in der Datenbank!");
                        }
                    } catch (e) {
                        alert("Fehler beim Verarbeiten der Serverantwort.");
                    }
                } else if (xhr.readyState === 4 && xhr.status !== 200) {
                    alert("Serverfehler beim Einfügen des Datensatzes. Bitte prüfen Sie die log-Tabelle in der Datenbank!");
                }

                // Setze den Button zurück und aktiviere ihn wieder
                // Automatisch nach reload.
                //insertButton.innerHTML = originalText;
                //insertButton.disabled = false;
            };

            xhr.send(data);
        }

        function toggleSelectAll(source) {
            const buttons = document.querySelectorAll('.toggle-btn');
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const button = row.querySelector('.toggle-btn');
                    if (source.classList.contains('btn-outline-secondary')) {
                        button.classList.add('btn-light');
                        button.classList.remove('btn-outline-light');
                    } else {
                        button.classList.remove('btn-light');
                        button.classList.add('btn-outline-light');
                    }
                }
            });
            source.classList.toggle('btn-outline-secondary');
            source.classList.toggle('btn-secondary');
        }

        function toggleRowSelection(button) {
            if (button.classList.contains('btn-outline-light')) {
                button.classList.add('btn-light');
                button.classList.remove('btn-outline-light');
                button.innerText = 'X' // Selektierte
            } else {
                button.classList.remove('btn-light');
                button.classList.add('btn-outline-light');
                button.innerText = 'X'
            }
        }

        function deleteSelectedRows(tabelle) {
            const deleteButton = document.getElementById('deleteSelectedButton');
            const originalText = deleteButton.innerHTML;

            const selectedIds = Array.from(document.querySelectorAll('.toggle-btn.btn-light')).map(btn => btn.getAttribute('data-id'));
            if (selectedIds.length === 0) {
                alert('Keine Zeilen ausgewählt.');
                return;
            }

            const confirmation = confirm('Sind Sie sicher, dass Sie die ausgewählten Daten löschen möchten?');
            if (!confirmation) return;

            // Show spinner and disable button
            deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Lösche...';
            deleteButton.disabled = true;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ajax.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            const data = JSON.stringify({
                action: 'delete',
                tabelle: tabelle,
                ids: selectedIds
            });

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === "success") {
                            resetPage(); // Reload the page to see the changes
                        } else {
                            alert("Fehler beim Löschen der Daten.");
                        }
                    } catch (e) {
                        alert("Fehler beim Verarbeiten der Serverantwort.");
                    }
                } else if (xhr.readyState === 4 && xhr.status !== 200) {
                    alert("Serverfehler beim Löschen der Daten.");
                }

                // Setze den Button zurück und aktiviere ihn wieder
                // Automatisch nach reload.
                // deleteButton.innerHTML = originalText;
                // deleteButton.disabled = false;
            };

            xhr.send(data);
        }

        function resetPage(){
            const resetButton = document.getElementById('resetButton');
            const originalText = resetButton.innerHTML;

            // Show spinner and disable button
            resetButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Lade...';
            resetButton.disabled = true;

            caches.keys().then(function(names) {
                for (let name of names) caches.delete(name);
            }).then(function() {
                location.reload(true);
            });
        }

        // Neue Funktion für Container-Management
        function adjustContainer() {
            const table = document.querySelector('.table');
            const container = document.querySelector('.table-container');
            const containerParent = container.parentElement;
            
            if (!table || !container || !containerParent) return;

            // Bootstrap breakpoints
            const containerMaxWidth = {
                sm: 540,
                md: 720,
                lg: 960,
                xl: 1140
            };
            
            // Aktuelle Viewport-Breite
            const viewportWidth = window.innerWidth;
            
            // Temporär container für korrekte Messung
            containerParent.classList.remove('container-fluid');
            containerParent.classList.add('container');
            
            // Force layout recalculation
            void containerParent.offsetWidth;
            
            // Tatsächliche Tabellenbreite messen
            const tableWidth = table.offsetWidth;
            
            // Aktuelle effektive Container-Breite ermitteln
            let currentMaxWidth = containerMaxWidth.xl;
            if (viewportWidth < 1200) currentMaxWidth = containerMaxWidth.lg;
            if (viewportWidth < 992) currentMaxWidth = containerMaxWidth.md;
            if (viewportWidth < 768) currentMaxWidth = containerMaxWidth.sm;
            
            // Entscheidung: container oder container-fluid
            if (tableWidth > currentMaxWidth - 30) {
                containerParent.classList.remove('container');
                containerParent.classList.add('container-fluid');
                container.style.overflowX = 'auto';
            } else {
                containerParent.classList.remove('container-fluid');
                containerParent.classList.add('container');
                container.style.overflowX = 'visible';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Warte kurz bis Layout stabil ist
            setTimeout(adjustContainer, 100);
            
            // Bereits existierender Code
            addSortEventListeners();
            const filterInput = document.getElementById('tableFilter');
            if (filterInput) {
                filterInput.addEventListener('input', filterTable);
                filterInput.value = ''; // Clear filter field on page load
            }
            const insertButton = document.getElementById('insertDefaultButton');
            if (insertButton) {
                insertButton.addEventListener('click', function() {
                    insertDefaultRecord('<?=$tabelle?>');
                });
            }
            const deleteButton = document.getElementById('deleteSelectedButton');
            if (deleteButton) {
                deleteButton.addEventListener('click', function() {
                    deleteSelectedRows('<?=$tabelle?>');
                });
            }
            const checkDuplicatesButton = document.getElementById('check-duplicates');
            if (checkDuplicatesButton) {
                checkDuplicatesButton.addEventListener('click', checkDubletten);
            }

            // Container beim Laden anpassen
            adjustContainer();
            
            // Container bei Größenänderung anpassen
            window.addEventListener('resize', adjustContainer);
        });

        function exportData(format, spreadsheetFormat) {
            const filterInput = document.getElementById('tableFilter');
            const isFiltered = filterInput && filterInput.value.trim() !== '';
            let exportAll = true;

            if (isFiltered) {
                exportAll = !confirm('Die Daten sind gefiltert. Möchten Sie nur die gefilterten Daten exportieren?\n\nOK = Nur gefilterte Daten\nAbbrechen = Alle Daten');
            }

            const visibleRows = [];
            if (!exportAll) {
                document.querySelectorAll('table tbody tr').forEach(row => {
                    if (row.style.display !== 'none') {
                        visibleRows.push(row.getAttribute('data-id'));
                    }
                });
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export.php';
            form.target = '_blank';

            const params = {
                format: format,
                tabelle: '<?=$tabelle?>',
                tabid: '<?=$selectedTableID?>',
                exportAll: exportAll ? '1' : '0',
                ids: visibleRows.join(','),
                spreadsheet_format: spreadsheetFormat
            };

            for (const key in params) {
                if (params[key]) {  // Nur nicht-leere Werte hinzufügen
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = params[key];
                    form.appendChild(input);
                }
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    
        function filter_that(selectElement, typ){
            
            switch(typ){
                case 'select':
                    var selectedText = selectElement.options[selectElement.selectedIndex].text;
                    break;
                case 'input':
                    var selectedText = selectElement.value;
                    break;
                case 'div':
                    var selectedText = selectElement.innerHTML;
                    break;
            }

            if(document.getElementById('tableFilter').value==selectedText){
                selectedText = "";
            }

            event.preventDefault();
            document.getElementById('tableFilter').value = selectedText;
            filterTable();
            
        }

        function clearFilter() {
            const filterInput = document.getElementById('tableFilter');
            if (filterInput) {
                filterInput.value = '';
                filterTable();
            }
        }

    </script>

</head>
<body>
 
<?php
// Manche Spalten sind per ID via Fremdschlüssel zu anderen Tabellen verknüpft. Die ID anzuzeigen (und zu bearbeiten) 
// bringt dem Anwender wenig. Es muss daher in config pro FK eine Referenzquery definiert werden, die die ID in eine für den
// Anwender nützliche Information umwandelt. Diese Information wird dann in einer Select-Box angezeigt. 
// Der Query muss genau zwei Dinge liefern: id (zur Verknüpfung, ist dann der Value der Option) und anzeige (der Text der Option).


/////////////////////////////////////////////////////////////////////

$FKdata = array();

if(isset($anzuzeigendeDaten[$selectedTableID]['referenzqueries'])){
    $substitutionsQueries = $anzuzeigendeDaten[$selectedTableID]['referenzqueries'];

    
    foreach($substitutionsQueries as $SRC_ID => $query){
        $FKname = '$anzeigeSubstitutionen'."['$tabelle']['$SRC_ID']";
            
            $result = $db->query($query);
            if(!isset($result['data'])){
                $result = array();
                $result['data'][0]['id'] = 0;
                $result['data'][0]['anzeige'] = "Keine Daten vorhanden";            
            }
            $FKdarstellungAll = $result['data'];

            
            if (!$FKdarstellungAll) {
                if(isset($result['error'])){
                    $err = "Die benötigte Konstante $FKname enthält kein gültiges SQL-Statement. (Eingelesener Query: $query)";
                    if(isset($result['error'])) $err .= "<p>".$result['error']."</p>";
                    dieWithError($err,__FILE__,__LINE__);
                }else{
                    continue;
                }
            } 

            if (count($FKdarstellungAll[0])!=2){
                $err = "Der Query in der Konstante $FKname muss genau zwei Ergebnisse liefern: 'id' und 'anzeige': 'id' = ID der Datensätze und 'anzeige' = ein ggf. zusammengesetzten Text, der zur Anzeige verwendet wird. Er liefert aber ".count($FKdarstellungAll[0])." Ergebnisse.";
                dieWithError($err,__FILE__,__LINE__);
            }

            if(!isset($FKdarstellungAll[0]['id'])){
                $err = "Der Query in der Konstante $FKname muss genau zwei Ergebnisse liefern: 'id' und 'anzeige'. Er liefert aber keine Daten mit der Bezeichnung 'id'.";
                dieWithError($err,__FILE__,__LINE__);
            }

            if(!isset($FKdarstellungAll[0]['anzeige'])){
                $err = "Der Query in der Konstante $FKname muss genau zwei Ergebnisse liefern: 'id' und 'anzeige'. Er liefert aber keine Daten mit der Bezeichnung 'anzeige'.";
                dieWithError($err,__FILE__,__LINE__);
            }

            foreach($FKdarstellungAll as $row){
                // ID und Anzeige - Informationen zentral sammeln
                $FKdata[$SRC_ID][] = $row;
            }
    }
} 


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function dieWithError($err, $file, $line, $stayAlive = false) {
    global $db;
    $db->log("$file:$line - $err");
    echo("<br><div class='container'><b>Konfigurationsfehler:</b> $err</div>");
    if(!$stayAlive) die();
}

function renderTableSelectBox($db) {
    
    global $anzuzeigendeDaten;
    global $selectedTableID;
    
    echo '<p><form method="get">';
    echo '<select name="tab" class="form-control" onchange="this.form.submit()">';

    if(!isset($anzuzeigendeDaten[$selectedTableID])){
        echo '<option value="">-- Tabelle wählen --</option>';
    }
    $maxLaenge = 0;
    $trennerindizies = array();
    $options = array();
    foreach ($anzuzeigendeDaten as $index => $table) {
        if(isset($table['trenner'])){
            $trennerindizies[] = count($options);
            $options[] = $table['trenner'];
        }else{
            $tableName = htmlspecialchars($table['tabellenname']);
            $tableComment = htmlspecialchars($table['auswahltext']);
            if(strlen($tableComment) > $maxLaenge) $maxLaenge = strlen($tableComment);
            $displayText = !empty($tableComment) ? "$tableComment" : $tableName;
            $selected = ($index == $selectedTableID) ? 'selected' : '';
            $options[] = '<option value="' . $index . '" ' . $selected . '>' . $displayText . '</option>';
        }
    }

    foreach ($trennerindizies as $trennerindex) {
        $firstChar = substr($options[$trennerindex], 0, 1);
        $displayText = str_pad('', $maxLaenge, $firstChar);
        $options[$trennerindex] = '<option disabled>' . $displayText . '</option>';
    }

    echo implode("\n", $options);
    echo '</select>';
    echo '</form></p>';
    
}

function hatUserBerechtigungen(){
    # Das sieht man, wenn die FK-Spalten etwas zurückliefern. FK-Select macht ein neuer Datensatz keinen Sinn.
    global $anzuzeigendeDaten;
    global $selectedTableID;
    global $db;
    
    if(isset($anzuzeigendeDaten[$selectedTableID]['referenzqueries'])){
        $substitutionsQueries = $anzuzeigendeDaten[$selectedTableID]['referenzqueries'];
        
        foreach($substitutionsQueries as $SRC_ID => $query){
            $result = $db->query($query);
            if(isset($result['data'])) {
                return true;
            }
        }
    }
    return false;
}

function renderTableHeaders($data) {
    global $anzuzeigendeDaten;
    global $selectedTableID;
    global $readwrite;
    
    if (!empty($data)) {
        if($readwrite)
            echo "<th style='width: 60px'><button type='button' class='btn p-0 b-0 btn-outline-secondary btn-sm toggle-btn toggle-btn-header' id='selectAll' onclick='toggleSelectAll(this)'>X</button></th>"; // Toggle button for selecting all rows
        foreach (array_keys($data[0]) as $header) {
            $style = "";
            if(isset($anzuzeigendeDaten[$selectedTableID]['spaltenbreiten'][$header])) {
                $style = "style='width: ".$anzuzeigendeDaten[$selectedTableID]['spaltenbreiten'][$header]."px;'";
            }
            if (strcasecmp($header, 'id') !== 0) {
                echo "<th $style data-field='" . htmlspecialchars($header) . "'>" . htmlspecialchars($header) . "</th>";
            }
        }
    } else {
        if ($selectedTableID !== "") {
            echo "<div class='container mt-4'><div class='alert alert-light' role='alert'>Es gibt keine Datensätze, für die Sie ein Leserecht haben.</div></div>";
        } else {
            echo "<div class='container mt-4'><div class='alert alert-light' role='alert'>Bitte wählen Sie eine Tabelle aus.</div></div>";
        }
    }
}

function renderTableRows($data, $readwrite, $tabelle, $foreignKeys) {
    global $db;
    global $anzuzeigendeDaten;
    global $selectedTableID;

    // Eingabemethode (z.B. Date-Picker) nach Datentyp wählen.
    $columns = $db->query("SHOW COLUMNS FROM $tabelle"); 
    $columnTypes = [];
    foreach ($columns['data'] as $column) {
        $columnTypes[$column['Field']] = $column['Type'];
    }

    foreach ($data as $row) {
        echo '<tr data-id="' . $row['id'] . '">';
        if($readwrite)
            echo '<td><button type="button" class="btn btn-outline-light btn-sm toggle-btn" data-id="' . $row['id'] . '" onclick="toggleRowSelection(this)">X</button></td>';
        
        foreach ($row as $key => $value) {
            if ($value === null) {
                $value = "";
            }
            if (strcasecmp($key, 'id') !== 0) {
                $style = "style='";
                if(isset($anzuzeigendeDaten[$selectedTableID]['spaltenbreiten'][$key])) {
                    $style .= "width: ".$anzuzeigendeDaten[$selectedTableID]['spaltenbreiten'][$key]."px;";
                }
                // Add word-wrap styles
                $style .= "word-wrap: break-word; white-space: normal;'";
                
                echo '<td data-field="' . $key . '" ' . $style . '>';
                $data_fk_ID_key = "";
                $data_fk_ID_value = "";
               
                if(isset($foreignKeys[$key])) { 
                    foreach($foreignKeys[$key] as $fk){
                        if($fk['id'] == $value){
                            $data_fk_ID_key = $fk['id'];
                            $data_fk_ID_value = $fk['anzeige'];
                            break;
                        }
                    }

                    if ($readwrite) {
                        echo '<select oncontextmenu="filter_that(this, \'select\');" class="form-control border-0" style="background-color: inherit; word-wrap: break-word; white-space: normal;" onchange="updateField(\'' . $tabelle . '\', \'' . $row['id'] . '\', \'' . $key . '\', this.value, 0)">';
                        echo '<option  value="NULL"' . (empty($value) ? ' selected' : '') . '>'.NULL_WERT.'</option>';
                        foreach ($foreignKeys[$key] as $fk ) {
                            $fk_value = $fk['id'];
                            $fk_display = $fk['anzeige'];

                            $selected = ($fk_value == $value) ? 'selected' : '';
                            echo '<option  value="' . htmlspecialchars($fk_value) . '" ' . $selected . '>' . htmlspecialchars($fk_display) . '</option>\n';
                        }
                        echo '</select>';
                    } else {
                        //echo htmlspecialchars($data_fk_ID_value);
                        echo '<div oncontextmenu="filter_that(this, \'div\');" style="word-wrap: break-word; white-space: normal;">' . htmlspecialchars($data_fk_ID_value) . '</div>';
                    }

                } else {
                    if ($readwrite) {
                        $inputType = 'text';
                        $columnType = $columnTypes[$key];
                        if (strpos($columnType, 'date') !== false) {
                            if (strpos($columnType, 'datetime') !== false) {
                                $inputType = 'datetime-local';
                                $value = str_replace(' ', 'T', $value);
                            } else {
                                $inputType = 'date';
                            }
                        }
                        echo '<input oncontextmenu="filter_that(this, \'input\');" data-type="'.$columnType.'" data-fkIDkey="' . htmlspecialchars($data_fk_ID_key) . '" 
                              data-fkIDvalue="' . htmlspecialchars($data_fk_ID_value) . '" 
                              type="' . $inputType . '" 
                              class="form-control border-0" 
                              style="background-color: inherit; word-wrap: break-word; white-space: normal;" 
                              value="' . htmlspecialchars($value) . '"
                              onchange="updateField(\'' . $tabelle . '\', \'' . $row['id'] . '\', \'' . $key . '\', this.value, \'' . $columnType . '\')"
                              onfocus="clearCellColor(this)">';
                
                    } else {
                        if(isset($columnType)){
                            if (strpos($columnType, 'decimal') !== false || strpos($columnType, 'float') !== false) {
                                $value = number_format((float)$value, 2, '.', '');
                            }
                        }
                        echo '<div oncontextmenu="filter_that(this, \'div\');" style="word-wrap: break-word; white-space: normal;">' . htmlspecialchars($value) . '</div>';
                    }
                }
                echo '</td>';
            }
        }
        echo '</tr>';
    }
}

?>

    <div class="container-fluid mt-4">
    <!--h2><?=$tabelle_upper?><h2-->
    <div class="container mt-4" >
        <?php renderTableSelectBox($db); ?>
    

    <?php 
    if(isset($anzuzeigendeDaten[$selectedTableID]['hinweis'])){
        echo "<div class='alert alert-info'>";
        echo $anzuzeigendeDaten[$selectedTableID]['hinweis'];
        echo "</div>";
    }?>
    
    <?php 
    if($tabelle!=""){
    //echo "<div class='container mt-2'>";
    echo "    <p><input type='text' id='tableFilter' class='form-control' placeholder='Filtern entweder durch manuelle Eingabe oder Rechtsklick auf ein Datenfeld.'></p>";
    //echo "</div>";
    }
    
    ?>
    <?php if ((!empty($tabelle) && $readwrite) || hatUserBerechtigungen()): 
        $importErlaubt = true;

        if (isset($anzuzeigendeDaten[$selectedTableID]['import']))
            if ($anzuzeigendeDaten[$selectedTableID]['import'] === false)
                $importErlaubt = false;
        
        // Wenn keine Schreibrechte, dann auch keinen Import 
        if(!$readwrite) $importErlaubt = false;
        ?>
        <div class="btn-group-container">
            <button id='clearFilterButton' class='btn btn-info mb-2' onclick='clearFilter()'>Filter löschen</button>
            <button id="resetButton" class="btn btn-info mb-2" onclick="resetPage()">Daten neu laden</button>

            <!--?php if ($readwrite  || hatUserBerechtigungen()):?-->
            <?php if ($readwrite && $importErlaubt):?>
                <button id="insertDefaultButton" class="btn btn-success mb-2">Datensatz einfügen</button>
                <button id="deleteSelectedButton" class="btn btn-danger mb-2">Ausgewählte löschen</button>
            <?php endif; ?>  

            <button id="check-duplicates" class="btn btn-success mb-2">Dubletten suchen</button>
            <?php if ($importErlaubt && $readwrite):?>
                <a href="importeur.php?tab=<?= $selectedTableID ?>" class="btn btn-info mb-2">Daten importieren</a>
            <?php endif; ?> 
            <div class="btn-group mb-2">
                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Exportieren
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="exportData('pdf')">Als PDF</a>
                    <a class="dropdown-item" href="#" onclick="exportData('csv')">Als CSV</a>
                    <a class="dropdown-item" href="#" onclick="exportData('excel', 'Xlsx')">Als Excel</a>
                    <a class="dropdown-item" href="#" onclick="exportData('excel', 'Ods')">Als LibreOffice</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="statistik.php">Statistiken</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    </div>
    <div class="container-fluid table-container">
        <table class="table table-striped table-bordered">
            <thead> 
                <tr>
                    <?php renderTableHeaders($data); ?>
                </tr>
            </thead>
            <tbody>
            <?php 
                if (!empty($data)) renderTableRows($data, $readwrite, $tabelle, $FKdata);
            ?>
            </tbody>
        </table>
    </div>  

</div>

</body>
</html>