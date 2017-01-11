<?php

	class ServerClass {
		
		private $connection = null;
		private $hostname = 'localhost';
		private $database = 'inicijator';
		private $databaseUser = 'root';
		private $databasePass = '';
		
		public function __construct() {
			session_start();
		}
		
		public function login() {
			$this->connection = mysqli_connect($this->hostname, $this->databaseUser, $this->databasePass, $this->database);
			$result = array();
			
			$users = mysqli_query($this->connection, 'SELECT id, name, surname, password FROM users WHERE email = "' . $_POST['email'] . '"');
			
			if (mysqli_num_rows($users) > 0) {
				$user = mysqli_fetch_assoc($users);
				
				if (password_verify($_POST['password'], $user['password'])) {
					$result = array('type' => 'success');
					
					$_SESSION['id'] = $user['id'];
					$_SESSION['name'] = $user['name'];
					$_SESSION['surname'] = $user['surname'];
					$_SESSION['email'] = $_POST['email'];
				}
				else
					$result = array('type' => 'error', 'message' => 'Pogrešna šifra za datu email adresu.');
			}
			else {
				$result = array('type' => 'error', 'message' => 'Ne postoji korisnik sa tom email adresom u bazi.');
			}
			
			mysqli_close($this->connection);
			die (json_encode($result));
		}
		
		public function logout() {
			session_destroy();
		}
		
		public function getUserData() {
			$this->connection = mysqli_connect($this->hostname, $this->databaseUser, $this->databasePass, $this->database);
			$result = array('type' => 'success', 'user' => array('id' => ''), 'createdInits' => array(), 'supportedInits' => array());
			
			if ($_POST['userId'] == '') { //if requesting data for currently logged in user
				if (isset($_SESSION['id']))
					$result['user'] = array('id' => $_SESSION['id'], 'name' => $_SESSION['name'], 'surname' => $_SESSION['surname'], 'email' => $_SESSION['email']);
			}
			else { //if requesting data for a user with provided ID
				$users = mysqli_query($this->connection, 'SELECT * FROM users WHERE id = "' . $_POST['userId'] . '"');
					
				if ($users !== FALSE) {
					$user = mysqli_fetch_assoc($users);
					$result['user'] = array('id' => $user['id'], 'name' => $user['name'], 'surname' => $user['surname'], 'email' => $user['email'], 'index_no' => $user['index_no']);
					
					//if we need to get all the initiatives for the specified user
					if ($_POST['getInitiatives']) {
						$this->getUserInitiatives('created', $result);
						$this->getUserInitiatives('supported', $result);
					}
				}
				else
					$result['type'] = 'error';
			}
			
			mysqli_close($this->connection);
			die(json_encode($result));
		}
		
		//get initiatives associated with a user, either inits he created or supported, based on $initType
		private function getUserInitiatives($initType, &$result) {
			$query = ($initType == 'created') ? 'SELECT * FROM initiatives WHERE creator_id = "' . $result['user']['id'] . '"' :
				'SELECT * FROM initiatives INNER JOIN signatories ON (signatories.user_id = "' . $result['user']['id'] . '" AND initiatives.id = signatories.initiative_id)';
			
			$inits = mysqli_query($this->connection, $query);
			if ($inits !== FALSE) {
				while ($init = mysqli_fetch_assoc($inits)) {
					$init['text'] = $this->createExcerpt($init['text'], 15);
					
					$signatories = mysqli_query($this->connection, 'SELECT COUNT(*) AS count FROM signatories WHERE initiative_id = "' . $init['id'] . '"');
					$init['signatories'] = mysqli_fetch_assoc($signatories)['count'];
					
					$init['start_date'] = str_replace('-', '/', $init['start_date']);
					$init['end_date'] = str_replace('-', '/', $init['end_date']);
					
					if ($initType == 'supported') {
						$sUsers = mysqli_query($this->connection, 'SELECT * FROM users WHERE id = "' . $init['creator_id'] . '"');
						$sUser = mysqli_fetch_assoc($sUsers);
						$init['user'] = array('id' => $sUser['id'], 'name' => $sUser['name'], 'surname' => $sUser['surname'], 'email' => $sUser['email'], 'index_no' => $sUser['index_no']);
					}
					
					array_push($result[$initType . 'Inits'], $init);
				}
			}
		}
		
		public function createInitiative() {
			$this->connection = mysqli_connect($this->hostname, $this->databaseUser, $this->databasePass, $this->database);
			
			$values = '("' . $_POST['title'] . '", "' . $_POST['text'] . '", "' . date ("Y-m-d H:i:s") . '", "' . $_POST['enddate'] . '", "' . $_SESSION['id'] . '", "' . 
				'", "' . $_POST['category'] . '", "' . $_POST['lat'] . '", "' . $_POST['lng'] . '")';

			$initiative = mysqli_query($this->connection, 'INSERT INTO initiatives (title, text, start_date, end_date, creator_id, image_url, category, lat, lng) VALUES ' . $values);
			
			$tmp = explode('.', $_FILES['image']['name']);
			$imageName = mysqli_insert_id($this->connection) . '.' . end($tmp);
			$image = mysqli_query($this->connection, 'UPDATE initiatives SET image_url = 
				"http://' . $_SERVER['HTTP_HOST'] . '/inicijator-server/uploads/' . $imageName . '" WHERE id = "' . mysqli_insert_id($this->connection) . '"');
			
			mysqli_close($this->connection);
			
			if ($initiative != FALSE) {
				move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $imageName);
				
				die(json_encode(array('type' => 'success')));
			}
			else {
				die(json_encode(array('type' => 'error', 'message' => $error)));
			}
		}
		
		public function listInitiatives() {
			$this->connection = mysqli_connect($this->hostname, $this->databaseUser, $this->databasePass, $this->database);
			
			$list = mysqli_query($this->connection, 'SELECT *, (SELECT COUNT(*) from signatories WHERE initiative_id = initiatives.id) AS sig_num FROM initiatives' . $this->listInitiativesExtra());
			$result = array('type' => 'success', 'initiatives' => array(), 'users' => array(), 'signatories' => array(), 'comments' => array());
			
			if ($list !== FALSE) {
				while ($init = mysqli_fetch_assoc($list)) {
					
					if ($_POST['textExcerpt'])
						$init['text'] = $this->createExcerpt($init['text'], 15);
					
					if (strtotime(date("Y-m-d H:i:s")) > strtotime(date($init['end_date'])))
						$init['isOver'] = true;
					else
						$init['isOver'] = false;
					
					$init['start_date'] = str_replace('-', '/', $init['start_date']);
					$init['end_date'] = str_replace('-', '/', $init['end_date']);
					
					array_push($result['initiatives'], $init);
					
					//get the data of initiative creators
					$users = mysqli_query($this->connection, 'SELECT * FROM users WHERE id = "' . $init['creator_id'] . '"');
					if ($users !== FALSE) {
						$user = mysqli_fetch_assoc($users);
						array_push($result['users'], $user);
					}
					
					//get the data of initiative signatories
					$signatories = mysqli_query($this->connection, 'SELECT * FROM users INNER JOIN signatories ON (signatories.initiative_id = "' . $init['id'] . '" AND users.id = signatories.user_id)');
					array_push($result['signatories'], array());
					
					if ($signatories !== FALSE) {
						while ($signatory = mysqli_fetch_assoc($signatories)) {
							array_push($result['signatories'][count($result['signatories']) - 1], $signatory);
						}
					}
					
					//get the initiative comments
					$commentsQuery = 'SELECT *, (SELECT name FROM users WHERE user_id = users.id) as user_name, (SELECT surname FROM users WHERE user_id = users.id) as user_surname FROM comments WHERE initiative_id = "' . $init['id'] . '"';
					$comments = mysqli_query($this->connection, $commentsQuery);
					array_push($result['comments'], array());
					
					if ($comments !== FALSE) {
						while ($comment = mysqli_fetch_assoc($comments)) {
							array_push($result['comments'][count($result['comments']) - 1], $comment);
						}
					}
				}
			}
			else
				$result['type'] = 'error';
			
			mysqli_close($this->connection);
			die(json_encode($result));
		}
		
		//creates string of query conditions based on the parameters sent, you can see how the parameters are formatted in the listCtrl controller
		private function listInitiativesExtra() {
			$extra = ' ORDER BY start_date DESC';
			
			if ($_POST['data'] != '') {
				$extra = '';
				$_POST['data'] = substr($_POST['data'], 0, -2);
				$paramsArray = explode('##', $_POST['data']);
				
				foreach($paramsArray as $paramIndex => $param) {
					$paramArray = explode('::', $param);
					
					if ($paramIndex == 0 && $paramArray[0] != 'orderby')
						$extra .= ' WHERE ';
					else if ($paramIndex > 0 && $paramArray[0] != 'orderby')
						$extra .= ' AND ';
					
					if ($paramArray[0] == 'keywords')
						$extra .= '(title LIKE "%' . $paramArray[1] . '%" OR text LIKE "%' . $paramArray[1] . '%")';
					else if ($paramArray[0] == 'categories') {
						$extra .= '(';
						$categories = explode(',', $paramArray[1]);
						
						foreach ($categories as $catIndex => $category) {
							if ($catIndex > 0)
								$extra .= 'OR ';
							
							$extra .= 'category = "' . $category . '"';
						}
						
						$extra .= ')';
					}
					else if ($paramArray[0] == 'startdate_start')
						$extra .= 'start_date >= "' . $paramArray[1] . '"';
					else if ($paramArray[0] == 'startdate_end')
						$extra .= 'start_date <= "' . $paramArray[1] . '"';
					else if ($paramArray[0] == 'enddate_start')
						$extra .= 'end_date >= "' . $paramArray[1] . '"';
					else if ($paramArray[0] == 'enddate_end')
						$extra .= 'end_date <= "' . $paramArray[1] . '"';
					else if ($paramArray[0] == 'id')
						$extra .= 'id = "' . $paramArray[1] . '"';
					else if ($paramArray[0] == 'orderby') {
						$orderby = explode('^', $paramArray[1]);
						$extra .= ' ORDER BY ' . $orderby[0] . ' ' . $orderby[1];
					}
				}
			}
			
			return $extra;
		}
		
		public function supportInitiative() {
			$this->connection = mysqli_connect($this->hostname, $this->databaseUser, $this->databasePass, $this->database);
			
			$values = '("' . $_POST['initId'] . '", "' . $_SESSION['id'] . '")';
			$result = mysqli_query($this->connection, 'INSERT INTO signatories (initiative_id, user_id) VALUES ' . $values);
			
			mysqli_close($this->connection);
			
			if ($result != FALSE)
				die(json_encode(array('type' => 'success')));
			else
				die(json_encode(array('type' => 'error')));
		}
		
		public function sendComment() {
			$this->connection = mysqli_connect($this->hostname, $this->databaseUser, $this->databasePass, $this->database);
			
			$values = '("' . $_POST['initId'] . '", "' . $_SESSION['id'] . '", "' . date("Y-m-d H:i:s") . '", "' . $_POST['text'] . '")';
			$result = mysqli_query($this->connection, 'INSERT INTO comments (initiative_id, user_id, date, text) VALUES ' . $values);
			
			mysqli_close($this->connection);
			
			if ($result != FALSE)
				die(json_encode(array('type' => 'success')));
			else
				die(json_encode(array('type' => 'error')));
		}
		
		public function createExcerpt($text, $numOfWords) {
			$textArray = explode(' ', $text);
			$excerpt = '';
			
			if (count($textArray) > $numOfWords) {
				for ($i = 0; $i < $numOfWords; $i++)
					$excerpt .= $textArray[$i] . ' ';
				
				$excerpt .= '...';
			}
			else
				$excerpt = $text;
			
			return $excerpt;
		}
		
		public function sendEmail() {
			require 'phpmailer/PHPMailerAutoload.php';
			
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
		
		private function insertUsers() {
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
		}
	}
?>