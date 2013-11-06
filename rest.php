<?php

class REST_Client {
	
	private $ws = "http://bytebug.de/ws"; // Edit URL here!
	private $dbname = ""; // Don't edit, its automatically!
	private $key = "sharedsecret"; // If you edit this, you need to edit it in the RESTful Webservice index.php too!
	
	// Set a Databasename for a REST Client instance
	public function setDBname($name) {
        $this->dbname = $name;
    }
	
	// Send HTTP-GET-Requests with CURL to the RESTful WS
	public function get($res, $data) {
		$data["db"] = $this->dbname;
		$data = array_filter($data);
		if (empty($data)) {
			$data['cond'] = 0;
		}
		$data_json = json_encode($data);
		$post = array('data' => $data_json);
		$url = $this->ws."/".$res;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		$response = curl_exec($ch);
		$results = json_decode($response);
		curl_close($ch);
		return $results;
	}
	
	// Send HTTP-POST-Requests with CURL to the RESTful WS
	public function post($res, $data) {
		$data_json = json_encode($data);
		$sig = hash_hmac('sha256', $data_json, $this->key);
		$post = array('data' => $data_json, 'sig' => $sig);
		$url = $this->ws."/".$res."/".$this->dbname;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		$results = json_decode($response);
		curl_close($ch);
		return $results;
	}
	
	// Send HTTP-PUT-Requests with CURL to the RESTful WS
	public function put($res, $data) {
		$data_json = json_encode($data);
		$sig = hash_hmac('sha256', $data_json, $this->key);
		$post = array('data' => $data_json, 'sig' => $sig);
		$url = $this->ws."/".$res."/".$this->dbname."/".$data->id;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		$results = json_decode($response);
		curl_close($ch);
		return $results;
	}
	
	// Send HTTP-DELETE-Requests with CURL to the RESTful WS
	public function delete($res, $data) {
		$data_json = json_encode($data);
		$post = array('data' => $data_json);
		$sig = hash_hmac('sha256', $data["id"], $this->key);
		$url = $this->ws."/".$res."/".$this->dbname."/".$data["id"]."/".$sig;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		$results = json_decode($response);
		curl_close($ch);
		return $results;
	}
	
	// Send HTTP-POST-Requests to get all Databasenames (special)
	public function meta($newdb) {
		$sig = hash_hmac('sha256', $newdb, $this->key);
		$url = $this->ws."/database/".$newdb."/".$sig;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		$response = curl_exec($ch);
		$results = json_decode($response);
		curl_close($ch);
		return $results;
	}
}

// Create instance to include in other files.
global $REST;
$REST = new REST_Client();
