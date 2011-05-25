<?php
/**
* $action variabile che contiene il nome dell'area corrente
*
*/

	//add_to_debug("Azione",  $action);
	
	switch ($action)
	{
		default:
		case "":
			return new ReturnedPage("recordings.php");		
			break;
		case "detail":
			if (@is_numeric($_REQUEST["rid"]))
			{
				$recording_id = $_REQUEST["rid"];
				$_SESSION["recording"]=get_recording($recording_id);
			if (isset($_REQUEST["page"]))
			{
				$_SESSION["sentence"]["page"]="page=".$_REQUEST["page"];
			}				
				return new ReturnedPage("detail.php");						
 
			}
			else
				return new ReturnedArea("biblio","recordings");
			break;
		case "music":
			return new ReturnedArea("biblio","music");
			break;	
		case "book":
			return new ReturnedArea("biblio","book");
			break;				
		case "search_recordings":
		
		
			//valori
			$risultati_per_pagina = 10;
			//attenzione l'array deve contenere i nomi dei campi cercati e le variabili che contengono le frasi devono avere lo stesso nome dei campi
			$campi = array("titles_recordings","authors_recordings","executor_recordings");		
			$titles_recordings=trim($_REQUEST["titolo"]);
			$authors_recordings=trim($_REQUEST["autore"]);
			$media_recordings=trim($_REQUEST["supporto"]);			
			$executor_recordings=trim($_REQUEST["esecutore"]);
			
			//controllo se tutte le richieste sono vuote
			if(empty($titles_recordings) && empty($authors_recordings) && empty($executor_recordings) && empty($media_recording))
				return new ReturnedArea("biblio","recordings");
			
			insert_search_in_log("[Titolo:"  .  $titles_recordings . "][Autore:".  $authors_recordings ."][Esecutore: ". $executor_recordings . "][Supporto: " . $media_recordings . "]", "r");				
			$risultato=array();
			$i=0;
			
			//faccio l'intersezione di tutti i risultati solo se il campo cercato non è vuoto
			foreach($campi as $campo)
			{
		
				//se il campo richiest non è vuoto
				if(!empty($$campo))
				{
					//ricerco la frase contenuta nella variabile con lo stesso nome del campo
					$tmp =search_sentece($$campo,$campo);
 
					if ($i==0)
						$risultato=$tmp;					
					else
						$risultato = array_intersect($risultato,$tmp);

					//tengo conto di quanti campi sono richiesti
					$i++;
				}
			}
			
			//Ora cerco su quale supporto sono memorizzati
			/*
				1 - Cd
				2 - Dvd
				3 - Disco
				4 - Nastro
				5 - VHS
			*/
			
			if (strcmp(strtolower($media_recordings),"")!=0)
			{
				$media_code = media_code_2_media_name($media_recordings);
				$sql_string="SELECT rid FROM recordings WHERE supporto = $media_code";
				$result = mysql_query($sql_string);
				$tmp=array();

				if (!$result)
					die ("Impossibile recuperare il tipo di supporto");
						
				while($row=mysql_fetch_row($result))
				{
					$tmp[]=$row[0];
				}
			
			
				//if (!empty())
				$risultato = array_intersect($risultato,$tmp);
			}
			
		//	print_r($risultato);
			$_SESSION["sentence"]=array("titolo"=>"titolo=". urlencode($_REQUEST["titolo"]),
										"autore"=>"autore=". urlencode($_REQUEST["autore"]),
										"esecutore"=>"esecutore=". urlencode($_REQUEST["esecutore"]),
										"supporto"=>"supporto=".  urlencode($_REQUEST["supporto"])
										);
						
			if (isset($_REQUEST["page"]))
				{
					$_SESSION["recordings"]=get_recordings($risultato, $_REQUEST["page"] , $risultati_per_pagina);									
					$_SESSION["sentence"]["page"]="page=".$_REQUEST["page"];
					$_SESSION["pagine"]=array(	"record_totali"=>count($risultato),
												"totali"=>ceil(count($risultato) / $risultati_per_pagina),
												"corrente"=>$_REQUEST["page"],
												"ris_per_pagina"=> $risultati_per_pagina
												);
				}
			else
				$_SESSION["recordings"]= get_recordings($risultato);						
				
		//	$_SESSION["recordings"]=get_recordings($risultato, 0, 10);									
			return new ReturnedPage("result.php");
			break;
	}


?>



?>
