<?php
/**
* Funzione per inizializzare l'area e la site view
*/
function inizializza()
{
	return new ReturnedArea("biblio_mai","default");	
}

define("INDEX", "http://ltnc.beedoo.org/index.php");
//define("INDEX", basename($_SERVER['SCRIPT_FILENAME']));
include("support.php");

//inclusione libreria, nel caso in cui è installata l'estensione mb_string è semplicemente un wrapper
require_once 'utf8/utf8.php';
setlocale(LC_COLLATE, 'C');	

//$stringa = 'Iñtër  nâtiônàl\'izætiøn Haendel and also Hàndel dell\'orto';
$table_prefix = "idx_";


//genero nuovo flusso di esecuzione
$new = new Flusso("biblio_mai","nodo_principale");
$nome_file = $new->elaborates();
include($nome_file);

?>

<?php

	echo "<pre>";
	echo "SESSION ";
	print_r($_SESSION);

	echo "new ";
	print_r($new);

	print_debug();
	
	print_r($_REQUEST);
	
	
	echo "</pre>";

?>
