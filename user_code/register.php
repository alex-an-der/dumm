<!DOCTYPE html>
<html lang='de'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>Form-Vorlage</title>
<?php 
require_once(__DIR__.'/../yback/include/inc_main.php')
?>
</head>
<body>
<div class='container'><div class='row'>

<form method='post'>
	<p>Mailadresse<br><input required class='form-control' required type='email' name='mail' value='<?= isset($_POST['mail']) ? $_POST['mail'] : '' ?>'/></p>
	<p>Vorname<br><input required class='form-control' type='text' name='vname' value='<?= isset($_POST['vname']) ? $_POST['vname'] : '' ?>' /></p>
	<p>Nachname<br><input required class='form-control' type='text' name='nname' value='<?= isset($_POST['nname']) ? $_POST['nname'] : '' ?>' /></p>
	<?php
	require_once(__DIR__."/../../config/db_connect.php");
	$options = '';
	$query = "SELECT id, auswahl FROM b___geschlecht";
	$result = $db->query($query);
	foreach ($result['data'] as $row) {
		$options .= "<option value='".$row['id']."'>".$row['auswahl']."</option>";
	}
	?>
	<p>Geschlecht<br>
		<select required class='form-control' name='geschlecht'>
			<option value='' disabled selected>Bitte wählen...</option>
			<?= $options ?>
		</select>
	</p>
	<p>Geburtsdatum<br><input required class='form-control' type='date' name='gebdatum' value='<?= isset($_POST['geburtsdatum']) ? $_POST['geburtsdatum'] : '' ?>' /></p>
	<?php
	$options_an_aus = '';
	$query_an_aus = "SELECT id, wert FROM b___an_aus";
	$result_an_aus = $db->query($query_an_aus);
	foreach ($result_an_aus['data'] as $row) {
		$options_an_aus .= "<option value='".$row['id']."'>".$row['wert']."</option>";
	}
	?>
	<p>Ich bin einverstanden, &uuml;ber Veranstaltungen und relevante Turniere per Mail vom Betriebssportverband unterrichtet zu werden. Diese Einstellung kann ich jederzeit &auml;ndern. <br>
		<select required class='form-control' name='okformail'>
			<option value='' disabled selected>Bitte wählen...</option>
			<?= $options_an_aus ?>
		</select>
	</p>
	<p><button type='submit' class='btn btn-success btn-block' name='saveandmail'>Speichern und Bestätigungsmail senden</button></p>
</form>
</div></div>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if(isset($_POST['saveandmail'])){
	$datensatz = array();
try{
	$usm->writeUserData($_POST, false, true);
	$conf->redirect('registermail_sent.php');
}catch(Exception  $e){
	echo('<b>Fehler! </b>'.$e->getMessage());
}}?>

</body>
</html>