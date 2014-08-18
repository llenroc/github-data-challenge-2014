<?php
include '../config.php';
// TODO handle post/get data from the front end.
if(isset($_POST["data"])){
	$data = json_decode($_POST["data"]);
	if($data->callType == "init"){
		fetch_chord_data();
	} else if($data->callType == "usernameSearch"){
		// TODO: protect database by parsing through the user's input.
		fetch_user_data($data->username);
	} else if($data->callType == "languageSearch"){
		// replace cp by c++
		// TODO: fix this up to be less hacky?
		$data->languages = array_replace($data->languages,
		    array_fill_keys(
		        array_keys($data->languages, "cp"),
		        "c++"
		    )
		);
		fetch_users_by_languages($data->languages);
	}
}

function fetch_chord_data(){
	global $dbuser;
	global $dbpass;
	$dbhost = "localhost";
	$dbname = "github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "SELECT * FROM `cross-languages`";
	try{
		$final = [];
		$totals = [];
		$stmt = $dbh->query($sql);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		for($i = 0; $i < count($results); $i++){
			$key = $results[$i]["languages"];
			$values = explode("|", $key);
			if($values[0] == $values[1]){
				$final[$values[0]][$values[1]] = 0;
				$totals[$values[0]] = $results[$i]["count"];
			} else{
				$final[$values[0]][$values[1]] = $results[$i]["count"];
			}
		}
		$array = [
			"final" => $final,
			"totals" => $totals
		];
		echo json_encode($array);
	} catch(PDOException $e){
		echo "db write error" . $e . "\n";
	}
}

function fetch_user_data($username){
	global $dbuser;
	global $dbpass;
	$dbhost = "localhost";
	$dbname = "github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "SELECT * FROM `languages` WHERE `login`=?";
	try {
		$query = $dbh->prepare($sql);
		$query->execute(array($username));		
		$results = $query->fetchAll();
		$user = $results[0];
		$languages = [];
		foreach ($user as $key => $value) {
			if($value == 1 && gettype($key) == "string" && $key != "login"){
				array_push($languages, $key);
			}
		}
		echo json_encode($languages);
	} catch(PDOException $e){
		echo "db fetch error" . $e . "\n";
	}

}

function fetch_users_by_languages($languages){
	global $dbuser;
	global $dbpass;
	$dbhost = "localhost";
	$dbname = "github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "SELECT login FROM `languages` WHERE ";
	for($i=0; $i < count($languages) - 1; $i++){
		$sql .= "`" . $languages[$i] . "`=1 AND ";
	}
	$sql .= "`" . $languages[count($languages)-1] . "`=1";
	try {
		$query = $dbh->prepare($sql);
		$query->execute();		
		$results = $query->fetchAll();
		
		$users = [];
		for($j=0; $j < count($results); $j++){
			array_push($users, $results[$j]["login"]);
		}
		echo json_encode($users);
	} catch(PDOException $e){
		echo "db fetch error" . $e . "\n";
	}
}

// fetch_user_data("007lva");

// fetch_users_by_languages(["c#", "javascript"]);


?>