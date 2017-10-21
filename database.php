<?php
// Skru på debug
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

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
        ORDER BY sid DESC";

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

    /// Returnerer en liste over konserter brukeren hjelper til med rigging på en gitt festival
case 'getListOfConcertsForTechs':

    $query = "SELECT scene.navn AS snavn, band.navn, band.bid, konsert.kid, dato, start_tid, slutt_tid, scene.sid, fid, konsert_rigging.uid
        FROM konsert
        INNER JOIN scene ON konsert.sid = scene.sid
        INNER JOIN konsert_band ON konsert.kid = konsert_band.kid
        INNER JOIN band ON konsert_band.bid = band.bid
        INNER JOIN konsert_rigging ON konsert_rigging.kid = konsert.kid
        WHERE konsert_rigging.uid = ?
        AND fid = ?";

    // Gjør klar objekt for spørringen
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Binder brukerid som et heltall
        $stmt->bind_param('ii', $brukerid, $fid);

        // Leser brukerid fra metodekallet
        $brukerid = $_POST['userid'];

        // Leser inn festival
        $fid = $_POST['fid'];

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

    /// Returnerer en liste over konserter som foregår på en gitt scene på en gitt festival
case 'getListOfConcertsByScene':

    $query = "SELECT *
        FROM konsert
        INNER JOIN konsert_band ON konsert.kid = konsert_band.kid
        INNER JOIN band ON konsert_band.bid = band.bid
        WHERE konsert.sid = ?
        AND fid = ?";

    // Gjør klar objekt for spørringen
    $stmt = $dbconn->stmt_init();

    // Gjør spørringen klar for databasen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Binder brukerid som heltall
        $stmt->bind_param('ii', $sid, $fid);

        // Leser inn sceneid
        $sid = $_POST['sceneid'];

        // Leser inn festival
        $fid = $_POST['fid'];

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

case 'getListOfConcertesByFestival':

    // Gjør klar sql-setning
    $query = "SELECT k.kid, b.navn, k.dato, s.navn as snavn
        FROM konsert k
        INNER JOIN konsert_band kb ON kb.kid = k.kid
        INNER JOIN band b ON b.bid = kb.kid
        INNER JOIN scene s ON k.sid = s.sid
        WHERE fid = ?
        ORDER BY k.kid ASC
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('i', $fid);

        // Leser inn konsertid
        $fid = $_POST['fid'];

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

case 'getListOfConcertesByFestivalAndId':

    // Gjør klar sql-setning
    $query = "SELECT k.kid, b.navn, k.dato, s.navn as snavn
        FROM konsert k
        INNER JOIN konsert_band kb ON kb.kid = k.kid
        INNER JOIN band b ON b.bid = kb.kid
        INNER JOIN scene s ON k.sid = s.sid
        WHERE fid = ?
        AND b.manager_uid = ?
        ORDER BY k.kid ASC
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('ii', $fid, $uid);

        // Leser inn konsertid
        $fid = $_POST['fid'];

        // Leser inn konsertid
        $uid = $_POST['uid'];

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

    /// Returnerer en lste over tekniske behov for en gitt konsert.
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
    $query = "INSERT INTO tekniske_behov (kid, tittel, behov)
        VALUES (?,?,?)
";

    // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

    // Gjør klar spørringen for databsen
    if(!$stmt->prepare($query)) {
        header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
    } else {

        // Bind konsertid som heltall
        $stmt->bind_param('iss', $kid,$tittel,$behov);

        // Leser inn konsertid
        $kid = $_POST['concertid'];

        //Leser inn behov
        $behov = $_POST['behov'];

        if (strlen($behov) == 0) {
            header("HTTP/1.0 400 Bad Request: Zero-length string");
            die();
        }

        //Leser inn tittel
        $tittel = $_POST['tittel'];

        if (strlen($tittel) == 0) {
            header("HTTP/1.0 500 400 Bad Request: Zero-length string");
            die();
        }

        // Utfør sql-setning
        $stmt->execute();

        // Avslutt sql-setning
        $stmt->close();
    }

    break;

case 'deleteTechnicalNeed' :

$query = "DELETE FROM tekniske_behov
            WHERE tbid = ?";

            // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

        // Gjør klar spørringen for databsen
        if(!$stmt->prepare($query)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {

            // Bind konsertid som heltall
        $stmt->bind_param('i', $tbid);

                // Leser inn konsertid
                $tbid = $_POST['tbid'];

            // Utfør sql-setning
            $stmt->execute();

            // Avslutt sql-setning
            $stmt->close();
        }

break;

case 'search':
    $text = "%{$_POST['text']}%";
    $type = $_POST['type'];
    $fid = $_POST['fid'];

    switch ($type) {
    case 'band':

        $query = "SELECT navn, bid AS id FROM band WHERE navn LIKE ?";

        $stmt = $dbconn->stmt_init();

        if(!$stmt->prepare($query)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {


            $stmt->bind_param("s", $text);

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

    case 'konsert':
        $query = "SELECT knavn AS navn, kid AS id FROM konsert WHERE NOT fid = ? AND sjanger LIKE ?";

        $stmt = $dbconn->stmt_init();

        if(!$stmt->prepare($query)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {


            $stmt->bind_param("is", $fid, $text);

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
            break;
        }

    case 'scene':
        $query = "SELECT DISTINCT b.navn, b.bid AS id
        FROM band b
        INNER JOIN konsert_band kb ON kb.bid = b.bid
        INNER JOIN konsert k ON k.kid = kb.kid
        INNER JOIN scene s ON k.sid = s.sid
        WHERE s.navn LIKE ?";

        $stmt = $dbconn->stmt_init();

        if(!$stmt->prepare($query)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {


            $stmt->bind_param("s", $text);

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
            break;
        }

    default:
        // Skriv en default her
        break;
    }
    break;

case 'getBandInfo':

/*
[0] Generell informasjon om band
*/


        $query1 = "SELECT navn, bio, popularitet, sjanger, fornavn, etternavn, email, bilde_url
            FROM band b
            INNER JOIN bruker br ON b.manager_uid = br.uid
            WHERE b.bid = ?";

        $stmt1 = $dbconn->stmt_init();

        if(!$stmt1->prepare($query1)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {


            $stmt1->bind_param("i", $bid);

// Leser inn band id
$bid = $_POST['bid'];

            // Utfør sql-setning
            $stmt1->execute();

            // Henter resultat fra spørring
            $result1 = $stmt1->get_result();

            // Hent ut alle rader fra en spørring
            $encode1 = array();
            while ($row1 = $result1->fetch_assoc()) {
                $encode1[] = $row1;
            }


            // Avslutt sql-setning
            $stmt1->close();
        }

/*
[1] Medialinker tilhørende band (youtube?)
*/

        // Gjør klar sql-setning
        $query2 = "SELECT *
            FROM band_strommelinker
            WHERE bid = ?
            ORDER BY  visninger DESC
";

        // Gjør klar objekt for spørring
        $stmt2 = $dbconn->stmt_init();

        // Gjør klar spørringen for databsen
        if(!$stmt2->prepare($query2)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {

            // Bind konsertid som heltall
            $stmt2->bind_param('i', $bid);

// Leser inn band id
$bid = $_POST['bid'];

// Utfør sql-setning
$stmt2->execute();

          // Henter resultat fra spørring
          $result2 = $stmt2->get_result();

          // Hent ut alle rader fra en spørring
          $encode2 = array();
          while ($row2 = $result2->fetch_assoc()) {
              $encode2[] = $row2;
          }

            // Avslutt sql-setning
            $stmt2->close();
        }

/*
[2] Album spilt inn av band
*/

        // Gjør klar sql-setning
        $query3 = "SELECT *
            FROM album
            WHERE bid = ?
";

        // Gjør klar objekt for spørring
        $stmt3 = $dbconn->stmt_init();

        // Gjør klar spørringen for databsen
        if(!$stmt3->prepare($query3)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {

            // Bind konsertid som heltall
            $stmt3->bind_param('i', $bid);

// Leser inn band id
$bid = $_POST['bid'];

        // Utfører spørringen
        $stmt3->execute();

        // Får resultatet fra spørring
        $result3 = $stmt3->get_result();

        // Hent ut alle rader fra en spørring
        $encode3 = array();
        while ($row3 = $result3->fetch_assoc()) {
            $encode3[] = $row3;
        }


            // Avslutt sql-setning
            $stmt3->close();
        }

/*
[3] Tidligere konserter band har spilt på
*/

        // Gjør klar sql-setning
        $query4 = "SELECT *
            FROM band_tidligere_konserter
            WHERE bid = ?
";

        // Gjør klar objekt for spørring
        $stmt4 = $dbconn->stmt_init();

        // Gjør klar spørringen for databsen
        if(!$stmt4->prepare($query4)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {

            // Bind konsertid som heltall
            $stmt4->bind_param('i', $bid);

            // Leser inn band id
    $bid = $_POST['bid'];

            // Utfør sql-setning
            $stmt4->execute();

            // Henter resultat fra spørring
            $result4 = $stmt4->get_result();

            // Hent ut alle rader fra en spørring
            $encode4 = array();
            while ($row4 = $result4->fetch_assoc()) {
                $encode4[] = $row4;
            }


            // Avslutt sql-setning
            $stmt4->close();
        }


    // Koder bandinformasjon til liste over javascriptobjekter
    echo ("[" . json_encode($encode1) . "," . json_encode($encode2) . "," .json_encode($encode3) . "," . json_encode($encode4) . "]");

    break;

case 'getConcertReport':
  $query = "SELECT konsert.tilskuere, konsert.billettpris, band.kostnad
      FROM konsert
      INNER JOIN scene ON konsert.sid = scene.sid
      INNER JOIN konsert_band ON konsert.kid = konsert_band.kid
      INNER JOIN band ON konsert_band.bid = band.bid
      WHERE konsert.sid = ?
      AND fid = ?";

  // Gjør klar objekt for spørringen
  $stmt = $dbconn->stmt_init();

  // Gjør spørringen klar for databasen
  if(!$stmt->prepare($query)) {
      header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
  } else {

      // Binder brukerid som heltall
      $stmt->bind_param('ii', $sid, $fid);

      // Leser inn sceneid
      $sid = $_POST['sceneid'];

      // Leser inn festival
      $fid = $_POST['fid'];

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

case 'getOldConcertInfo' :

$query = "SELECT knavn, k.dato, k.tilskuere, k.billettpris, b.navn AS bnavn, s.navn, s.maks_plasser
          FROM konsert k
          INNER JOIN konsert_band kb ON k.kid =kb.kid
          INNER JOIN band b ON b.bid = kb.bid
          INNER JOIN scene s ON s.sid = k.sid
          WHERE k.kid = ?";

            // Gjør klar objekt for spørring
    $stmt = $dbconn->stmt_init();

        // Gjør klar spørringen for databsen
        if(!$stmt->prepare($query)) {
            header("HTTP/1.0 500 Internal Server Error: Failed to prepare statement.");
        } else {

            // Bind konsertid som heltall
            $stmt->bind_param('i', $kid);

            // Leser inn konsertid
            $kid = $_POST['kid'];

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
