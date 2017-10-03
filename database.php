<?php
// Header for php-enkoding
header('Content-type: text/plain; charset=utf-8');

// Koble til databasen og returner oppkoblingen som objekt
$dbconn = new mysqli("mysql.stud.ntnu.no", "it1901group8", "nullstressjoggedress", "it1901group8_festival");

//Sjekke om oppkoblingen fungerer
if ($dbconn->connect_error){
    header('HTTP/1.0 504 Connection Failed' . $mysqli->connect_errno . " " . $dbconn->connect_error);
    die();
} 

// Databasen bruker bokstaver fra utf8-standarden. Viktig for at f.eks ÆØÅ skal fungere.
$dbconn->set_charset("utf8");

// Henter ut hvilken funksjon som skal kalles.
$method = $_GET['method']; 


switch ($method) {
    /// Dette er en metode for å sjekke at oppkobling mot serveren fungerer
case 'ping':
    echo 'hei';
    break;

    /// Metode for å logge på serveren, tar inn brukernavn og passord. Returnerer brukerobjekt.
case 'login':

    $query = "SELECT * 
        FROM bruker 
        WHERE brukernavn= ?
        AND passord= ?";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørring til databasen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {
        // Binder brukernavn og pasord som strenger
        $stmt->bind_param('ss', $username, $password);

        // Leser brukernavn og passord
        $username = $_POST['username']; 
        $password = $_POST['password']; 

        // Utfør spørringen
        $stmt->execute();

        // Hent resultatet fra spørringen
        $result = $stmt->get_result();

        // Hent ut første rad fra en spørring
        $encode = $result->fetch_assoc();

        // Hvis brukeren ikke finnes i databasen, returner en feilmelding og avslutt.
        if (empty($encode)) {
            header('HTTP/1.0 401 Unauthorized user.');
            die();
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer en komplett liste av alle scener.
case 'getListOfScenes':
    $query = "SELECT *
		FROM scene
		ORDER BY  sid DESC";

    // Gjør klar objekt for spørringen
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørring for databasen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Utfører spørring
        $stmt->execute();

        // Får resultat fra spørring
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer en liste over konserter brukeren hjelper til med rigging på.
case 'getListOfConcertsForTechs': 

    $query = "SELECT *
        FROM konsert
        INNER JOIN scene ON konsert.sid = scene.sid
        INNER JOIN konsert_band ON konsert.kid = konsert_band.kid
        INNER JOIN band ON konsert_band.bid = band.bid
        INNER JOIN konsert_rigging ON konsert_rigging.kid = konsert.kid
        WHERE konsert_rigging.uid = ?";

    // Gjør klar objekt for spørringen
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Binder brukerid som et heltall
        $stmt->bind_param('i', $brukerid);

        // Leser brukerid fra metodekallet
        $brukerid = $_POST['userid'];

        // Utfører spørringen
        $stmt->execute();

        // Får resultatet fra spørring
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer en liste over konserter som foregår på en gitt scene
case 'getListOfConcertsByScene':

    $query = "SELECT *
        FROM konsert
        INNER JOIN konsert_band ON konsert.kid = konsert_band.kid
        INNER JOIN band ON konsert_band.bid = band.bid
        WHERE konsert.sid = ?";

    // Gjør klar objekt for spørringen
    $stmt = $dbconn->stmt_init();

    // Gjør spørringen klar for databasen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Binder brukerid som heltall
        $stmt->bind_param('i', $sid);

        // Leser inn sceneid
        $sid = $_POST['sceneid'];

        // Utfører spørringen
        $stmt->execute();

        // Returnerer resultat fra spørringen
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer en liste over alle teknikere på en gitt scene
case 'getListOfTechs':

    // Gjør klar sql-setning
    $query = "SELECT * 
        FROM bruker
        INNER JOIN konsert_rigging ON bruker.uid = konsert_rigging.uid
        WHERE kid = ?
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('i', $kid);

        // Leser inn konsertid
        $kid = $_POST['concertid'];

        // Utfør sql-setning
        $stmt->execute();

        // Henter resultat fra spørring
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer en liste over alle teknikere på en gitt scene
case 'getListOfTechnicalNeeds':

    // Gjør klar sql-setning
    $query = "SELECT * 
        FROM tekniske_behov
        WHERE kid = ?
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('i', $kid);

        // Leser inn konsertid
        $kid = $_POST['concertid'];

        // Utfør sql-setning
        $stmt->execute();

        // Henter resultat fra spørring
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Sette inn tekniske behov i databasen
case 'insertTechnicalNeeds':

    // Gjør klar sql-setning
    $query = "INSERT INTO tekniske_behov
        VALUES (?,?,?)
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('iss', $kid,$behov,$tittel);

        // Leser inn konsertid
        $kid = $_POST['concertid'];

        //Leser inn behov
        $behov = $_POST['behov'];

        //Leser inn tittel
        $tittel = $_POST['tittel'];

        // Utfør sql-setning
        $stmt->execute();

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer en liste over alle teknikere på en gitt scene
case 'getListOfOlderConserts':

    // Gjør klar sql-setning
    $query = "SELECT * 
        FROM konsert
        INNER JOIN scene on konsert.sid = scene.sid
        WHERE fid != ?
        AND sjanger = ?
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('is', $kid, $sjanger);

        // Leser inn konsertid
        $kid = $_POST['concertid'];

        //Leser inn sjanger
        $sjanger = $_POST['sjanger'];

        // Utfør sql-setning
        $stmt->execute();

        // Henter resultat fra spørring
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer nøkkelinformasjon om et band
case 'getBandInfoStreams':

    // Gjør klar sql-setning
    $query = "SELECT * 
        FROM band_strommelinker
        WHERE bid = ?
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('i', $bid);

        // Leser inn konsertid
        $bid = $_POST['bid'];

        // Utfør sql-setning
        $stmt->execute();

        // Henter resultat fra spørring
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer nøkkelinformasjon om et band
case 'getBandInfoAlbum':

    // Gjør klar sql-setning
    $query = "SELECT * 
        FROM album
        WHERE bid = ?
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('i', $bid);

        // Leser inn konsertid
        $bid = $_POST['bid'];

        // Utfør sql-setning
        $stmt->execute();

        // Henter resultat fra spørring
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Returnerer nøkkelinformasjon om et band
case 'getBandInfoOldConserts':

    // Gjør klar sql-setning
    $query = "SELECT * 
        FROM band_tidligere_konserter
        WHERE bid = ?
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('i', $bid);

        // Leser inn konsertid
        $bid = $_POST['bid'];

        // Utfør sql-setning
        $stmt->execute();

        // Henter resultat fra spørring
        $result = $stmt->get_result();

        // Hent ut alle rader fra en spørring
        $encode = array();
        while ($row = $result->fetch_assoc()) {
            $encode[] = $row;
        }

        // Returner json-string med data
        echo json_encode($encode);

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

    /// Hvis det er en skrivefeil i metodekallet så returnerer vi denne feilbeskjeden.
default:
    header('HTTP/1.0 501 Not implemented method.');
    die();
    break;
}

//Lukker oppkoblingen til databasen
$dbconn->close(); 
?>
