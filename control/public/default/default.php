<?php
/**
* $action variabile che contiene il nome dell'area corrente
*
*/

	add_to_debug("Azione",  $action);
	switch ($action)
	{
		default:
		case "":
			return new ReturnedPage("home.php");		
			break;
		case "enter":
			return new ReturnedArea("public", "default", "enter_game");
			break;			
	}


?>
