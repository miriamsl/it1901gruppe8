<?php
	$dbconn = new mysqli("mysql.stud.ntnu.no", "it1901group8", "nullstressjoggedress", "it1901group8_festival"); //Oppkobling til database
	if ($dbconn->connect_error){
		die("Connection Failed: " . $dbconn->connect_error);
	} //Sjekke om oppkoblingen fungerer

	$method = $_GET['method']; // Henter ut hvilken funksjon som skal kalles.


	switch ($method) {
		case 'login':

			$username = $_POST['username']; //Henter ut brukernavn fra input-feltet på brukersiden
			$password = $_POST['password']; //Henter ut passord fra input-feltet på brukersiden

			$sql = "SELECT brukertype FROM bruker WHERE brukernavn='" . $username . "' AND passord='" . $password . "'" ; //Bruker variablene over for å lage sql-setningen

			$login_id = $dbconn->query($sql); //Sender query for å hente passord og brukernavn-feltet

			if ($login_id->num_rows > 0) { //Sjekker om du får noe data returnert fra databasen
					// output data of each row
					while($row = $login_id->fetch_assoc()) { //Returnerer brukertype, som er et nummer. Hvis brukertype ikke fins, får man returnert 0.
							echo $row["brukertype"];
					}
			} else {
					echo "0";
			}

			break;

		case 'getCompleteListOfConcerts':
			$sql = "SELECT * FROM konsert";
			$konserter = $dbconn->query($sql);

			$result = array($konserter->fetch_assoc());

			echo json_encode($result);

			/*if ($konserter->num_rows > 0) {
				while($row = $konserter->fetch_assoc()) {
					echo json_encode($row);
				}
			}*/


			break;

		default:
			echo "Ingen metode spesifisert";
			break;
	}


	# $konrig = "SELECT uid FROM konsert_rigging"

	# $konTek = $dbconn->query($konrig) //

	$dbconn->close(); //Lukker oppkoblingen til databasen
?>
