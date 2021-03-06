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
			$status = get_current_turn_and_action(session_id());
			
			if ( $status["substatus"] != "thinking")
				return new ReturnedArea("game", "game", $status["substatus"]);	
				
//			$player_order = get_gamer_order(session_id());
			$player_info = get_gamer_info();
			$player_order = $player_info["order"];
			$units= get_units_disposition($status["id_game"]);

			if ($player_order == $status["current_gamer"])
				$currently_playing = true;
			else
				$currently_playing = false;
		
			$json_data = array();
			
			$json_data["gamer_turn"]=$currently_playing;
			$json_data["gamer_order"]= (int) $player_order;
			$json_data["units"]= $units;
			$return = json_encode(array ('user_info'=> $player_info,'status'=>"game", "substatus"=>"thinking", "data"=>$json_data));
			return new ReturnedAjax($return);
			break;
		case "attack":
			//@TODO: verificare che l'id del giocatore sia quello corrente e verificare che abbia almento due unità sul territorio
			$player_order = get_gamer_order(session_id());
			$status = get_current_turn_and_action(session_id());

			//Controllo di essere effettivamente il giocatore corrente
			if ($status["current_gamer"] != $player_order)
			{
				return new ReturnedArea("game", "game","thinking");
				break;			
			}
			
			$attacker_iso_code =  $_REQUEST["attacker_iso_country"];
			$defender_country_name =  $_REQUEST["defender_country"];
			
			//Prelevo i codici delle nazioni vicine all'attaccante e le informazioni sul difensore
			$attacker_neighbors = get_country_neighbors_from_iso_code($attacker_iso_code);
			$defender_country = get_country_code_and_owner($status["id_game"], $defender_country_name);
			
			//Verifico se la nazione chiamata è effettivamente vicina
			$found = false;
			foreach ($attacker_neighbors as $neighbor)
			{
			
				if ($neighbor == $defender_country["iso_code"])
				{
		
					$found = true;
					break;
				}
			}
			
			//In caso negativo ritorno allo stato di default
			if (!$found)
			{
				return new ReturnedArea("game", "game","thinking");
				break;
			}
			
			//Prelevo il le informazioni sulla nazione dell'attaccante
			$attacker_country =get_country_units_and_owner($status["id_game"], $attacker_iso_code);
			//Controllo se ho almeno due unità a disposizione per effettuare l'attacco
			if ($attacker_country["units"] <= 1)
			{
				return new ReturnedArea("game", "game","thinking");
				break;				
			}

			//Controllo se la nazione su cui ho mosso il marker e' nemica oppure e' dello stesso attaccante
			if ($defender_country["owner"] == $player_order)
			{
				$status["data"]=array();
				$status["data"]["move"]=array(
				"from"=>array(
				"iso_code"=> $attacker_iso_code,
				 "name"=>$attacker_country["name"],
				"units"=>$attacker_country["units"]),
				 "to"=>array(
				 "iso_code"=> $defender_country["iso_code"],
				 "name"=>$defender_country_name,
				"units"=>$defender_country["units"]));
				
				set_current_status($status["id_game"], "game", "move_units", serialize($status["data"]));
				//Restituisci l'area per spostare le unit�
				return new ReturnedArea("game", "game","move_units");
				break;				
			}
			
			//echo "OK";
			//Aggiungo una voce ai dati riguardanti l'attacco che si sta compiendo
			$status["data"]=array();
			$status["data"]["attack"]= array();
			$status["data"]["attack"]["attacker"] = array("player"=> (int)$player_order,"available_units"=>($attacker_country["units"] - 1), "country"=>array("iso_code"=> $attacker_iso_code, "name"=>$attacker_country["name"]));
			$status["data"]["attack"]["defender"] = array("player"=>  (int)$defender_country["owner"],"available_units"=>$defender_country["units"], "country"=>array("iso_code"=> $defender_country["iso_code"], "name"=>$defender_country_name));
			
			set_current_status($status["id_game"], "game", "attack", serialize($status["data"]));
			return new ReturnedArea("game", "game", "attack");
			break;
		case "pass_turn":

			$status = get_current_turn_and_action(session_id());
			$current_user = get_current_gamer();

			if ($current_user == $status["current_gamer"])
			{				
				if (is_max_gamer())
				{
					//Prendo il giocatore con id piu basso
					$first_gamer = get_first_gamer($status["id_game"]);
					set_next_gamer($status["id_game"], $first_gamer["order"]);
					
					//Incremento il turno
					set_next_turn();
				}
				else
				{
					//Prendo il prossimo partecipante e gli passo lo stato attivo
					$next_gamer = get_next_gamer($status["id_game"], $current_user);
					set_next_gamer($status["id_game"], $next_gamer);
				}
			}
			
			return new ReturnedArea("game", "game", "thinking");
			break;
		

	}


?>
