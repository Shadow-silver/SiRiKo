<?php

require_once("manage_country.php");
require_once("manage_games.php");

$table_name_gamer_country = "gamer_country_info";
$table_name_participant="game_participants";
$table_name_country = "game_country";
$table_name_status = "game_status";

/**
* Controlla se un utente sta partecipando ad un gioco.
* @return int Restituisce -1 se non sta partecipando a nessun gioco, altrimenti restituisce il numero di partita
*/
function is_in_game($id_participant)
{
	$table_name="game_participants";
	$user_id = mysql_escape_string($id_participant);
	$sql_string="SELECT ext_game, user_session FROM $table_name WHERE user_session =\"$user_id\"";
	
	$result = mysql_query($sql_string);
	if ($result)
	{
		if (mysql_num_rows($result))
		{
			$row=mysql_fetch_row($result);
			return $row[0];
		}
		else
		{
			return -1;
		}
	}
	else
	{
		die("#1 - impossibile ottenere l'elenco dei partecipanti  " . mysql_error());
	}	
}


/**
* Questa funzione restituisce l'elenco dei partecipanti alla partita
*/
function get_gamers($id_game)
{
	$table_name="game_participants";
	$sql_string="SELECT s.user_session, s.porder FROM $table_name s WHERE (s.ext_game = $id_game)";
	$array_gamer= array();
	
	$result = mysql_query($sql_string);
	if ($result)
	{
		while ($row=mysql_fetch_row($result))
		{
			$array_gamer[$row[1]] =array("order"=> $row[1], "user_session"=>$row[0]);
		}
	}
	else
	{
		die("#1 - impossibile ottenere l'elenco dei partecipanti alla partita " . mysql_error());
	}
	
	return $array_gamer;	
}

/**
* Questa funzione restituisce l'elenco dei co-partecipanti alla partita
*/
function get_co_gamers($user_session_id)
{
	$table_name="game_participants";
	$sql_string="SELECT s.user_session FROM $table_name t, $table_name s WHERE (t.ext_game = s.ext_game) AND (t.user_session=\"$user_session_id\")";
	$array_games= array();
	
	$result = mysql_query($sql_string);
	if ($result)
	{
		while ($row=mysql_fetch_row($result))
		{
			$array_games[] = $row[0];
		}
	}
	else
	{
		die("#1 - impossibile ottenere l'elenco dei co-partecipanti alla partita " . mysql_error());
	}
	
	return $array_games;
}
/**
* Inserisce un giocatore in una partita 
*/
function insert_user_in_game($user_id, $game_id = -1)
{
	$table_name_participant="game_participants";
	$table_name_status="game_status";
	$user_id = mysql_escape_string($user_id);
	// Se non passo alcun game id allora ne assegno uno nuovo
	if ($game_id == -1)
	{
		$game_id = 0;
		$sql_string_game_id = "SELECT MAX(id_game) FROM  $table_name_status";
		$result = mysql_query($sql_string_game_id);
		if (!$result)
		{	die("#2 - impossibile ottenere la lista delle partite in corso  " . mysql_error());
		}
		else
		{
			if (mysql_num_rows($result))
			{
				$row=mysql_fetch_row($result);
				if ($row[0] != null)
					$game_id = $row[0] + 1;
			}			
		
			$sql_string_game_id = "INSERT INTO $table_name_status(id_game, round, gamer, status) VALUE ($game_id, 0, 1,\"init\")";
			$result = mysql_query($sql_string_game_id);
			if (!$result)
				die("#3 - impossibile aggiungere una nuova partita  " . mysql_error());
						
		}
	}
	
	//Cerco qualè l'ordine dell'ultimo giocatore inserito nel gioco
	$player_order = 1;
	$sql_string_order = "SELECT MAX(porder) FROM  $table_name_participant WHERE (ext_game = $game_id)";
	$result = mysql_query($sql_string_order);
	if (!$result)
		die("#4 - impossibile ottenere un ordinamento per la partita " . mysql_error());
	else
	{
		if (mysql_num_rows($result))
		{
			$row=mysql_fetch_row($result);
			if ($row[0] != null)
				$player_order = $row[0] + 1;
		}
		
	}
	
	$sql_string_participant = "INSERT INTO  $table_name_participant(ext_game, user_session, porder) VALUE ($game_id,\"$user_id\", $player_order)";
	$result = mysql_query($sql_string_participant);
	
	if (!$result)
		die("#5 - impossibile aggiungere l'utente al gioco  " . mysql_error());
	
}

/**
* Funzione che permette di sapere se l'utente corrente è quello con id più basso
* @return true|false
*/
function is_min_gamer()
{
	$result = null;
	
	$game_info = get_current_turn_and_action(session_id());
	
	if (count($game_info))
	{
		$min_gamer = get_first_gamer($game_info["id_game"]);
		if ($min_gamer["user_session"] == session_id())
			$result = true;
		else
			$result = false;
	}

	return $result;			
}

/**
* Funzione che permette di sapere se l'utente corrente è quello con id più alto nella partita corrente
* @return true|false
*/
function is_max_gamer()
{
	$result = null;
	
	$game_info = get_current_turn_and_action(session_id());
	
	if (count($game_info))
	{
		$min_gamer = get_last_gamer($game_info["id_game"]);
		if ($min_gamer["user_session"] == session_id())
			$result = true;
		else
			$result = false;
	}

	return $result;			
}


/** 
* Restituisce l'order ed il session id del primo giocatore specificando l'id della partita
* @return array porder - int, user_sessione - string
*/
function get_first_gamer($game_id)
{

	$gamer = array();
	$table_name_participant="game_participants";
	
	$sql_string="SELECT p.porder, p.user_session FROM $table_name_participant p WHERE (p.ext_game = $game_id) AND (p.porder = ( SELECT MIN(pmin.porder) FROM $table_name_participant pmin))";
	$result = mysql_query($sql_string);

	if ($result)
	{
		if (mysql_num_rows($result))
		{
			$row=mysql_fetch_row($result);
			if ($row[0] != null)
			{
				$gamer["order"] = $row[0];
				$gamer["user_session"] = $row[1];
			}		
		}	
	}
	
	return $gamer;
}

/** 
* Restituisce l'order ed il session id dell'ultimo giocatore specificando l'id della partita
* @return array porder - int, user_sessione - string
*/
function get_last_gamer($game_id)
{

	$gamer = array();
	$table_name_participant="game_participants";
	
	$sql_string="SELECT p.porder, p.user_session FROM $table_name_participant p WHERE (p.ext_game = $game_id) AND (p.porder = ( SELECT MAX(pmin.porder) FROM $table_name_participant pmin))";
	$result = mysql_query($sql_string);

	if ($result)
	{
		if (mysql_num_rows($result))
		{
			$row=mysql_fetch_row($result);
			if ($row[0] != null)
			{
				$gamer["order"] = $row[0];
				$gamer["user_session"] = $row[1];
			}		
		}	
	}
	
	return $gamer;
}

/**
*
*
*/
function get_gamer_order($user_session)
{
	$gamer_order = -1;
	
	$table_name_participant="game_participants";
	
	$sql_string="SELECT p.porder FROM $table_name_participant p  WHERE (p.user_session =\"$user_session\")";
	
	$result = mysql_query($sql_string);
	if ($result)
	{
		if (mysql_num_rows($result))
		{
			$row=mysql_fetch_row($result);
			if ($row[0] != null)
				$gamer_order  = $row[0];
		}	
	}
	
	return $gamer_order;
}

/**
* Restituisce l'order del prossimo gamer. Se non c'è alcun prossimo allora restituisce -1;
* @return int
*/
function get_next_gamer($game_id, $user_order)
{
	$next_gamer = -1;
	$table_name_participant="game_participants";
	
	$sql_string="SELECT MIN(p.porder) FROM $table_name_participant p  WHERE (p.porder > $user_order)";
	
	$result = mysql_query($sql_string);
	if ($result)
	{
		if (mysql_num_rows($result))
		{
			$row=mysql_fetch_row($result);
			if ($row[0] != null)
				$next_gamer  = $row[0];			
		}	
	}
	else
		die("#1 - [get_next_gamer] impossibile ottenere il prossimo giocatore - " . mysql_error());
	
	return $next_gamer;
}

/**
* Restituisce l'order del prossimo gamer. Se non c'è alcun prossimo allora restituisce -1;
* @return int
*/
function set_next_gamer($game_id, $user_order)
{
	$next_gamer = -1;
	$current_status = array();
	$table_name_status="game_status";
	
	$sql_string="UPDATE $table_name_status SET gamer = \"$user_order\"";
	$result = mysql_query($sql_string);
	if (!$result)
	{
		die("#1 - [set_next_gamer] Impossibile impostare prossimo gamer - " . mysql_error());	
	}
}

/**
* Restituisce lo stato corrente della partita
*/
function get_current_turn_and_action($user_id)
{
	$current_status = array();
	$table_name_participant="game_participants";
	$table_name_status="game_status";
	
	$sql_string="SELECT s.round, s.gamer, s.status, i.user_session, s.id_game, s.substatus, s.data FROM $table_name_status s, $table_name_participant p, $table_name_participant i  WHERE (p.ext_game = s.id_game) AND (p.user_session =\"$user_id\") AND (i.porder = s.gamer)";
	
	$result = mysql_query($sql_string);
	if ($result)
	{

		if (mysql_num_rows($result))
		{
			$row=mysql_fetch_row($result);
			$current_status["round"] = $row[0];
			$current_status["current_gamer"] = $row[1];
			$current_status["status"] = $row[2];
			$current_status["substatus"] = $row[5];
			$current_status["user"] = $row[3];
			$current_status["id_game"] = $row[4];
			$current_status["data"] = $row[6];
		}
	}
	else
	{
		die("#1 - [get_current_turn_and_action] impossibile ottenere l'elenco dei partecipanti  " . mysql_error());
	}
	
	return $current_status;		
}

/**
* Imposta uno stato particolare di gioco
* @TODO: aggiungere impostazione azione e dati
*/
function set_current_status($id_game, $status, $substatus = null, $data=null, $gamer = null, $round = null)
{
	$table_name_status="game_status";
	
	$sql_string_assignments= "";

	if ($substatus == null)
		$substatus = "";

	if ($gamer != null)
		$sql_string_assignments	.= " , gamer= $gamer";

	if ($round != null)
		$sql_string_assignments	.= " , round= $round";
	
	if ($data != null)
	{
		$data = mysql_escape_string($data);
		$sql_string_assignments	.= " , data=\"$data\"";
	}
		

	$sql_string="UPDATE $table_name_status SET status = \"$status\" $sql_string_assignments , substatus=\"$substatus\" WHERE (id_game = $id_game)";

	$result = mysql_query($sql_string);
	if (!$result)
	{
		die("#1 - [set_current_status] impossibile ottenere l'elenco dei partecipanti  " . mysql_error());
	}	
}

/**
* Ordina i giocatori secondo il risultato dei dadi
*/
function compute_gamer_order($id_game, $dice_array)
{
	$table_name_participant="game_participants";
	
	//ordino l'array
	asort($dice_array);
	
	$participants = get_gamers($id_game);

	$count_order = 1;
	foreach ($dice_array as $player_order=>$value )
	{
		$sql_string ="UPDATE $table_name_participant SET porder=$count_order WHERE (user_session=\"" . $participants[$player_order]["user_session"] ."\")";
		$result = mysql_query($sql_string);
		if (!$result)
			die("#1 - [compute_gamer_order] impossibile variare l'ordine dei giocatori " . mysql_error());

		$count_order++;
	}
}

?>
