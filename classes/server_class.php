<?php

class ServerClass {
	
	public function __construct() {
		session_start();
	}
	
	public function login() {
		$user = new User();
		$dbResult = $user->getFromDB('email', $_POST['email']);
		$result = array('type' => 'error', 'message' => '');
		
		if ($dbResult !== FALSE) {
			if ($user->comparePassword($_POST['password'])) {
				$userArray = $user->getAsArray();
				$_SESSION['id'] = $userArray['id'];
				$_SESSION['email'] = $userArray['email'];
				
				$result['type'] = 'success';
			}
			else {
				$result['message'] = 'Pogrešna šifra za datu email adresu.';
			}
		}
		else
			$result['message'] = 'Ne postoji korisnik sa tom email adresom u bazi.';
		
		die (json_encode($result));
	}
	
	public function logout() {
		session_destroy();
	}
	
	public function getUserData() {
		$result = array('type' => 'success', 'user' => array());
		$user = new User();
		$dbResult = FALSE;
		
		if ($_POST['userId'] == '') {
			if (isset($_SESSION['id']))
				$dbResult = $user->getFromDB('id', $_SESSION['id']);
		}
		else
			$dbResult = $user->getFromDB('id', $_POST['userId']);
				
		if ($dbResult !== FALSE) {
			if ($_POST['getInitiatives']) {
				$user->getInitiatives('created');
				$user->getInitiatives('supported');
			}
			
			$result['user'] = $user->getAsArray();
		}
		else
			$result['type'] = 'error';
		
		die(json_encode($result));
	}
	
	public function createInitiative() {
		$properties = array('title' => $_POST['title'], 'text' => $_POST['text'], 'start_date' => date('Y-m-d'), 'end_date' => $_POST['end_date'], 'creator_id' => $_SESSION['id'], 
			'image_url' => '', 'category' => $_POST['category'], 'lat' => $_POST['lat'], 'lng' => $_POST['lng']);
		
		$result = array('type' => 'success');
		$initiative = new Initiative($properties);
		$resultDB = $initiative->saveToDB();
		
		if ($resultDB == FALSE)
			$result['type'] = 'error';
		
		die (json_encode($result));
	}
	
	public function singleInitiative() {
		$result = array('type' => 'success', 'initiative' => array());
		
		$init = new Initiative();
		$resultDB = $init->getFromDB('id', $_POST['id']);
		
		if ($resultDB !== FALSE) {
			$init->getSignatories();
			$init->getComments();
			
			$result['initiative'] = $init->getAsArray();
		}
		else
			$result['type'] = 'error';
		
		die (json_encode($result));
	}
	
	public function listInitiatives() {
		$result = array('type' => 'success', 'initiatives' => array());
		
		$initRepo = new InitiativeRepository();
		$resultDB = $initRepo->getFromDB();
		
		if ($resultDB !== FALSE) {
			if ($_POST['orderby'] != '') {
				$orderby = explode('^', $_POST['orderby']);
				$initRepo->orderBy($orderby[0], $orderby[1]);
			}
			else
				$initRepo->orderBy('start_date_time', 'desc');
			
			foreach($initRepo->initiatives() as $init) {
				if ($init->compareInit($_POST['keywords'], $_POST['categories'], $_POST['startdate_start'], $_POST['startdate_end'], $_POST['enddate_start'], $_POST['enddate_end'])) {
					array_push($result['initiatives'], $init->getAsArray());
				}
			}
		}
		else
			$result['type'] = 'error';
		
		die (json_encode($result));
	}
	
	public function supportInitiative() {
		$result = array('type' => 'error');
		$init = new Initiative(array('id' => $_POST['initId']));
		$resultDB = $init->supportInit($_SESSION['id']);
		
		if ($resultDB !== FALSE)
			$result['type'] = 'success';
		
		die (json_encode($result));
	}
	
	public function sendComment() {
		$result = array('type' => 'error');
		$init = new Initiative(array('id' => $_POST['initId']));
		$resultDB = $init->addComment($_SESSION['id'], $_POST['text']);
		
		if ($resultDB !== FALSE)
			$result['type'] = 'success';
		
		die (json_encode($result));
	}
	
	public function sendEmail() {
		$mail = new PHPMailer();
		
		$mail->isSMTP();
		$mail->Host = 'aspmx.l.google.com';
		$mail->Port = 25;
		
		$mail->setFrom($_SESSION['email']);
		$mail->addAddress('tosha90@gmail.com');
		$mail->Subject = $_POST['title'];
		$mail->Body = $_POST['text'];
		
		if ($mail->send())
			die(json_encode(array('type' => 'success')));
		else
			die(json_encode(array('type' => 'error', 'error' => $mail->ErrorInfo)));
	}
	
	/*private function insertUsers() {
		include 'users.php';
		$q = '';
		
		$users = new SimpleXMLElement($xmlStr);
		foreach ($users->record as $user) {
			$queryText = 	'INSERT INTO users (name, surname, email, password, index_no) 
							VALUES ("' . $user->name . '", "' . $user->surname . '", "' . $user->email . '", "' . password_hash($user->password, PASSWORD_DEFAULT) . '", "' . $user->index . '")';
							
			$query = mysqli_query($this->connection, $queryText);
			$q = mysqli_error($this->connection);
		}
		
		return $q;
	}*/
}
?>