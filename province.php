<<?php
// ================================================
// FILE: province.php
// SCOPO: Gestire le province italiane tramite API REST
// METODI SUPPORTATI: GET, POST, PUT, PATCH, DELETE
// ================================================


// -------------------- 1. Connessione al database --------------------
// La parola "$ConnessioneDB" è il nome della variabile che useremo per tenere la connessione al database
// new mysqli(...) → "new" significa: crea un nuovo oggetto
// mysqli → è la libreria di PHP che serve per collegarsi a MySQL
// "localhost" → significa: il database è nello stesso computer
// "root" → è l’utente con cui accedo
// "" → è la password (vuota in questo caso)
// "italia_admin" → è il nome del database
$ConnessioneDB = new mysqli("localhost", "root", "", "italia_admin");

// La parola "if" significa: SE
// $ConnessioneDB->connect_error → controlla se ci sono errori di connessione
if ($ConnessioneDB->connect_error) {
    // "die" significa: FERMA subito il programma e mostra il messaggio
    die("Connessione fallita: " . $ConnessioneDB->connect_error);
}


// -------------------- 2. Header JSON --------------------
// La parola "header" serve per dire al browser come interpretare la risposta
// "Content-Type: application/json" → significa che i dati che restituiamo sono in formato JSON
header("Content-Type: application/json");


// -------------------- 3. Metodo HTTP --------------------
// $_SERVER → è una variabile speciale che contiene informazioni sulla richiesta
// ['REQUEST_METHOD'] → dentro c’è scritto se la richiesta è GET, POST, PUT, PATCH o DELETE
$method = $_SERVER['REQUEST_METHOD'];


// -------------------- 4. Funzione per leggere il body JSON --------------------
// La parola "function" serve per definire una nuova funzione
// leggiInput è il nome della funzione
function leggiInput() {
    // file_get_contents → legge il contenuto di un file o di uno stream
    // "php://input" → è uno stream speciale che contiene i dati inviati dal client
    // json_decode → trasforma una stringa JSON in un array di PHP
    // true → significa che vogliamo un array associativo (chiave → valore), non un oggetto
    return json_decode(file_get_contents("php://input"), true);
}


// ===================== 5. METODO GET =====================
// La parola "if" significa SE
// "==" significa uguale
if ($method == "GET") { 
    // Traduzione letteraria: se il metodo della richiesta è GET allora esegui questo blocco

    // Creo una query SQL → "SELECT * FROM province LIMIT 20"
    // "SELECT *" → prendi tutte le colonne
    // "FROM province" → dalla tabella province
    // "LIMIT 20" → massimo 20 righe
    $Query = "SELECT * FROM province LIMIT 20";

    // La parola "if" significa SE
    // !empty($_GET["id"]) → SE la variabile id passata nell’URL non è vuota
    if (!empty($_GET["id"])) {
        // intval → trasforma una stringa in numero intero
        $id = intval($_GET["id"]);
        // Creo la query per prendere SOLO la provincia con quell’id
        $Query = "SELECT * FROM province WHERE id = $id";
    }
    // La parola "else if" significa: ALTRIMENTI, SE...
    else if (!empty($_GET["id_regione"])) {
        $id_regione = intval($_GET["id_regione"]);
        $Query = "SELECT * FROM province WHERE id_regione = $id_regione";
    }
    // Altro controllo: se è stato passato il parametro nome
    else if (!empty($_GET["nome"])) {
        // real_escape_string → pulisce i caratteri speciali per evitare errori o attacchi
        $nome = $ConnessioneDB->real_escape_string($_GET["nome"]);
        // LIKE '%$nome%' → cerca un testo che contiene $nome
        $Query = "SELECT * FROM province WHERE nome LIKE '%$nome%'";
    }

    // Eseguo la query con "query"
    $risultato = $ConnessioneDB->query($Query);

    // Creo un array vuoto
    $Lista = [];

    // La parola "while" significa: finché
    // fetch_assoc → prende una riga alla volta come array associativo
    while ($Riga = $risultato->fetch_assoc()) {
        // [] = aggiungi un elemento alla lista
        $Lista[] = $Riga;
    }

    // json_encode → trasforma un array in una stringa JSON
    echo json_encode($Lista);
}


// ===================== 6. METODO POST =====================
else if ($method == "POST") { 
    // Leggo i dati dal body della richiesta
    $dati = leggiInput();

    // Se i dati non ci sono
    if (!$dati) {
        echo json_encode(["error"=>"Body JSON mancante o non valido"]);
        // "exit" significa: ferma qui il programma
        exit;
    }

    // Estraggo nome e id_regione dal JSON
    // ?? significa: se non esiste, usa questo valore di default
    $nome = $ConnessioneDB->real_escape_string($dati["nome"] ?? "");
    $id_regione = intval($dati["id_regione"] ?? 0);

    // Controllo se mancano i dati obbligatori
    if ($nome == "" || $id_regione == 0) {
        echo json_encode(["error"=>"Campi obbligatori mancanti"]);
        exit;
    }

    // Eseguo la query di inserimento
    $ConnessioneDB->query("INSERT INTO province (nome, id_regione) VALUES ('$nome', $id_regione)");

    // Prendo l’id dell’ultimo record inserito
    $nuovo_id = $ConnessioneDB->insert_id;

    // Recupero i dati appena inseriti
    $risultato = $ConnessioneDB->query("SELECT * FROM province WHERE id=$nuovo_id");
    $riga = $risultato->fetch_assoc();

    // Restituisco i dati in JSON
    echo json_encode($riga);
    exit;
}


// ===================== 7. METODO PUT =====================
else if ($method == "PUT") { 
    $dati = leggiInput();

    if (!$dati) {
        echo json_encode(["error"=>"Body JSON mancante o non valido"]);
        exit;
    }

    $id = intval($dati["id"] ?? 0);
    $nome = $ConnessioneDB->real_escape_string($dati["nome"] ?? "");
    $id_regione = intval($dati["id_regione"] ?? 0);

    if ($id == 0 || $nome == "" || $id_regione == 0) {
        echo json_encode(["error"=>"Campi obbligatori mancanti"]);
        exit;
    }

    // Aggiorno completamente il record
    $ConnessioneDB->query("UPDATE province SET nome='$nome', id_regione=$id_regione WHERE id=$id");

    // Recupero i dati aggiornati
    $risultato = $ConnessioneDB->query("SELECT * FROM province WHERE id=$id");
    $riga = $risultato->fetch_assoc();

    echo json_encode($riga);
}


// ===================== 8. METODO PATCH =====================
else if ($method == "PATCH") {
    $dati = leggiInput();

    if (!$dati) {
        echo json_encode(["error"=>"Body JSON mancante o non valido"]);
        exit;
    }

    $id = intval($dati["id"] ?? 0);
    if ($id == 0) {
        echo json_encode(["error"=>"ID obbligatorio"]);
        exit;
    }

    $campi = [];

    if (!empty($dati["nome"])) {
        $campi[] = "nome='" . $ConnessioneDB->real_escape_string($dati["nome"]) . "'";
    }

    if (!empty($dati["id_regione"])) {
        $campi[] = "id_regione=" . intval($dati["id_regione"]);
    }

    if (!empty($campi)) {
        $ConnessioneDB->query("UPDATE province SET " . implode(",", $campi) . " WHERE id=$id");

        $risultato = $ConnessioneDB->query("SELECT * FROM province WHERE id=$id");
        $riga = $risultato->fetch_assoc();

        echo json_encode($riga);
    } else {
        echo json_encode(["error"=>"Nessun campo da aggiornare"]);
    }
}


// ===================== 9. METODO DELETE =====================
else if ($method == "DELETE") {
    if (!empty($_GET["id"])) {
        $id = intval($_GET["id"]);
        $ConnessioneDB->query("DELETE FROM province WHERE id=$id");
        echo json_encode(["deleted" => $ConnessioneDB->affected_rows]);
    } else {
        echo json_encode(["error"=>"ID mancante per DELETE"]);
    }
}


// ===================== 10. Chiusura connessione --------------------
$ConnessioneDB->close();
?>
