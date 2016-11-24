<?php
class MaltegoEntity
{
	var $attributes = array();
	var $properties = array();
	var $type = "";
	var $id = "";
	
	function addAttribute($key,$value)
	{
		$this->attributes[$key] = $value;
	}
	
	function addProperty($key,$value)
	{
		$this->properties[$key] = $value;
	}
	
}

class MaltegoLink
{
	var $entitySource = 0;
	var $entityTarget = 0;
}


function utf8_for_xml($string)
{
    return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
}


function getLeaves($root,$branchNum = 1,&$outputArray)
{
	global $links,$entities;
	foreach($links as $l)
	{
		if($l->entitySource == $root)
		{
			if(array_key_exists($l->entityTarget,$entities))
			{
				$entity = $entities[$l->entityTarget];
				//print_r($entity);
				
				$entity = $entities[$l->entityTarget];
				$outputArray["types"][$branchNum][] = $entity->type;
				if($entity->type == "maltego.EmailAddress")
				{
					if($outputArray["defaultEmail"] == "")
					{
						$outputArray["defaultEmail"] = $entity->properties["email"];
					}
				}
				$outputArray["alltypes"][] = $entity->type;
				$outputArray["Branches"]["Level:" . $branchNum][] = $entity;
				$newBranch = $branchNum + 1;
				getLeaves($l->entityTarget,$newBranch,$outputArray);
			}
		}
	}
	return false;
	
}

function fetchXML($key)
{
	global $sqliteDatabase;
	$db = new SQLite3('mapme.db');
	$stmt = $db->prepare('SELECT XML FROM xml WHERE Key=:key');
	$stmt->bindValue(':key', $key,SQLITE3_TEXT);
	$result = $stmt->execute();
	if($result === False)
	{
		return False;
	}
	else
	{
		$row = $result->fetchArray(SQLITE3_ASSOC);
		return $row["XML"];
	}
	
	
}


function ParseXML($xml)
{
	global $entities,$trees,$links;
	/*
		Lets get all the entities
	*/
	foreach($xml->entity as $e)
	{
		$tempEntity = new MaltegoEntity();
		
		$attribs = $e->attributes();
		
		$entityID = (string)$attribs["id"];
		$entityType = (string)$attribs["type"];
		
		$tempEntity->type = $entityType;
		$tempEntity->id = $entityID;
		
		foreach($attribs as $key=>$value)
		{
			$tempEntity->addAttribute((string)$key,(string)$value);
			
		}
		
		foreach($e->prop as $p)
		{
			$key = (string)$p["name"];
			$value = (string)$p;
			$tempEntity->addProperty($key,$value);
		}
		
		foreach($e->dprop as $p)
		{
			$key = (string)$p["name"];
			$value = (string)$p;
			$tempEntity->addProperty($key,$value);
		}
		
		$entities[$entityID] = $tempEntity;
		
	}
	/*
		Lets get all the links
	*/
	foreach($xml->link as $l)
	{
		
		$source = (string)$l["from"];
		$target = (string)$l["to"];
		$link = new MaltegoLink();
		$link->entitySource = $source;
		$link->entityTarget = $target;
		$links[] = $link;
		
	}



	/*

	Lets work out root nodes -- without nodes above em

	*/

	$allEnts = array_keys($entities);

	$linkedNodes = array();
	foreach($links as $l)
	{
		$linkedNodes[] = $l->entityTarget;
	}


	$rootEntities = array_diff($allEnts,$linkedNodes);

	
	/*

	Lets get every tree now 

	*/


	$trees = array();



	foreach($rootEntities as $r)
	{
		$startEnt = $entities[$r];
		$tree = array();
		$tree["defaultTwit"] = "";
		if($startEnt->type == "maltego.Twit")
		{
			$tree["defaultTwit"] = $startEnt->properties["title"];
		}
		
		getLeaves($r,1,$tree);
		$tree["Branches"]["level:0"][] = $startEnt;
		
		$tree["alltypes"][] = $startEnt->type;
		
		$tree["types"][0] = array($startEnt->type);
		
		foreach($tree["types"] as $key=>$typeArr)
		{
			$tree["types"][$key] = array_unique($tree["types"][$key]);
		}
		
		$tree["alltypes"] = array_unique($tree["alltypes"]);
		
		ksort($tree);
		
		if($tree["defaultTwit"] !== "")
		{
			$trees[] = $tree;
		}
		
	}
	//print_r($trees);
}

function importTemplates()
{
	global $templates;
	$numTemplates = 0;
	foreach (glob("templates/*.php") as $filename)
	{
		$numTemplates++;
		include $filename;
	}
	return $numTemplates;
}



function listEntities($treeID)
{
	global $trees;
	$returnArray = array();
	//print_r($trees[$treeID]);
	foreach($trees[$treeID]["Branches"] as $branch)
	{
		foreach($branch as $entity)
		{
			$returnArray[] = $entity;
		}
	}
	return json_encode($returnArray);
}
