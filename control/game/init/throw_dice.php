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
			//Invio al client lo stato corrente specificando se è quello con l'ordine più basso
			//@TODO: scrivere una funzione che fa una sola chiamata al DB3
			$status_data= array("dice"=>array());
//			$player_order = get_gamer_order(session_id());
			$player_info = get_gamer_info();
			$player_order = $player_info["order"];
			$players_info = get_co_gamers();
			$game_info = get_current_turn_and_action(session_id());
			//Controllo se non sono in un nuovo 
			if ($game_info["status"] != "init")
			{
				return new ReturnedArea("game", $game_info["status"],$game_info["substatus"]);
			}
			
			//Controllo se non sono in un nuovo 
			if ($game_info["substatus"] != "throw_dice")
			{
				return new ReturnedArea("game", "init", $game_info["substatus"]);
			}
						
			if ($game_info["data"] != "")
			{
				$status_data = unserialize($game_info["data"]);
			}
			
			if ($player_order == $game_info["current_gamer"])
				$currently_playing = true;
			else
				$currently_playing = false;
			$json_data = array();
			

			$json_data["gamer_turn"]=$currently_playing;
			$json_data["gamer_order"]= (int) $player_order;
			$json_data["dice"]=$status_data["dice"];
			$json_data["players_info"]=$players_info;
			//$json_data["dice"]["gamer"] =array_keys($status_data["dice"]);
			//$json_data["dice"]["values"] =array_values($status_data["dice"]);
			$return = json_encode(array ('user_info'=> $player_info, 'status'=>"init", "substatus"=>"throw_dice", "data"=>$json_data));
			return new ReturnedAjax($return);
//				return new ReturnedArea("game", "view");			
			break;
			
		case "launch_die":
		
			//Con il generatore casuale genero il lancio del dado
			$roll = rand(1,6);
			
			//Prelevo lo stato corrente dal database e l'utente con il turno corrente
			//$game_info = get_current_turn_and_action(session_id());
			$game_info =get_game_status_from_user();
			$current_user = get_current_gamer();
			
			//Prova
			$player_info = get_gamer_info();
			//Inserisco il risultato del lancio nell'array
			if ($game_info["data"] == "")
			{
				$status_data = array("dice"=>array( $player_info["nickname"]=>$roll));
				set_current_status($game_info["id_game"], "init", "throw_dice", serialize($status_data));
			}
			else
			{
				$status_data = unserialize($game_info["data"]);
				$status_data["dice"][$player_info["nickname"]] = $roll;
				set_current_status($game_info["id_game"], "init", "throw_dice", serialize($status_data));
			}

			//Controllo se sono l'ultimo giocatore ad effettuare il lancio del dado			
			if (is_max_gamer())
			{
				//echo "max_gamer";
				//Ordino i giocatori in base al risultato dei lanci
				compute_gamer_order($game_info["id_game"], $status_data["dice"]);
				//Distribuisco le unita tra i giocatori
				assign_country_and_units($game_info["id_game"], "EU");
				$min_player = get_first_gamer($game_info["id_game"]);
				
//				set_current_status($game_info["id_game"], "game", "thinking", serialize(array()),$min_player["order"], 0);
				set_current_status($game_info["id_game"], "init", "view_init_result", null,$min_player["order"], 0);
	
			//	return new ReturnedArea("game", "game","thinking");
	
				//Passo alla fase di visualizzazione dell'esito del gioco
				return new ReturnedArea("game", "init", "view_init_result");				
			}
			else
			{
				//Prendo il prossimo partecipante e gli passo lo stato attivo
				$next_gamer = get_next_gamer($game_info["id_game"], $current_user);
			
				set_next_gamer($game_info["id_game"], $next_gamer);
				return new ReturnedArea("game", "init", "throw_dice");
			}
			
			break;
	}


?>
