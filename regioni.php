<?php
// ===========================================================
// FILE: regioni.php
// SCOPO: Gestire le regioni italiane tramite API REST
// METODI: GET, POST, PUT, PATCH, DELETE
// ===========================================================

// 1. Connessione al database
$ConnessioneDB = new mysqli("localhost", "root", "", "italia_admin");

// 2. Imposto la risposta in formato JSON
header("Content-Type: application/json");

// 3. Recupero il metodo HTTP usato (GET, POST, PUT, PATCH, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

// 4. Funzione per leggere il JSON in input
function leggiInput() {
    return json_decode(file_get_contents("php://input"), true);
}

// ===================== METODO GET =====================
if ($method == "GET") {
    // Query base
    $Query = "SELECT * FROM regioni";

    // Filtro per id
    if (!empty($_GET["id"])) {
        $id = intval($_GET["id"]);
        $Query = "SELECT * FROM regioni WHERE id = $id";
    }
    // Filtro per id_nazione
    else if (!empty($_GET["id_nazione"])) {
        $id_nazione = intval($_GET["id_nazione"]);
        $Query = "SELECT * FROM regioni WHERE id_nazione = $id_nazione";
    }
    // Filtro per nome
    else if (!empty($_GET["nome"])) {
        $nome = $ConnessioneDB->real_escape_string($_GET["nome"]);
        $Query = "SELECT * FROM regioni WHERE nome LIKE '%$nome%'";
    }

    $risultato = $ConnessioneDB->query($Query);
    $Lista = [];
    while ($Riga = $risultato->fetch_assoc()) {
        $Lista[] = $Riga;
    }
    echo json_encode($Lista);
}

// ===================== METODO POST =====================
else if ($method == "POST") {
    $dati = leggiInput();
    if (!$dati) {
        echo json_encode(["error" => "Body JSON mancante o non valido"]);
        exit;
    }

    $nome = $ConnessioneDB->real_escape_string($dati["nome"] ?? "");
    $id_nazione = intval($dati["id_nazione"] ?? 0);

    if ($nome == "" || $id_nazione == 0) {
        echo json_encode(["error" => "Campi obbligatori mancanti"]);
        exit;
    }

    $ConnessioneDB->query("INSERT INTO regioni (nome, id_nazione) VALUES ('$nome', $id_nazione)");
    $nuovo_id = $ConnessioneDB->insert_id;

    $risultato = $ConnessioneDB->query("SELECT * FROM regioni WHERE id=$nuovo_id");
    echo json_encode($risultato->fetch_assoc());
}

// ===================== METODO PUT =====================
else if ($method == "PUT") {
    $dati = leggiInput();
    if (!$dati) {
        echo json_encode(["error" => "Body JSON mancante o non valido"]);
        exit;
    }

    $id = intval($dati["id"] ?? 0);
    $nome = $ConnessioneDB->real_escape_string($dati["nome"] ?? "");
    $id_nazione = intval($dati["id_nazione"] ?? 0);

    if ($id == 0 || $nome == "" || $id_nazione == 0) {
        echo json_encode(["error" => "Campi obbligatori mancanti"]);
        exit;
    }

    $ConnessioneDB->query("UPDATE regioni SET nome='$nome', id_nazione=$id_nazione WHERE id=$id");
    $risultato = $ConnessioneDB->query("SELECT * FROM regioni WHERE id=$id");
    echo json_encode($risultato->fetch_assoc());
}

// ===================== METODO PATCH =====================
else if ($method == "PATCH") {
    $dati = leggiInput();
    if (!$dati) {
        echo json_encode(["error" => "Body JSON mancante o non valido"]);
        exit;
    }

    $id = intval($dati["id"] ?? 0);
    if ($id == 0) {
        echo json_encode(["error" => "ID obbligatorio"]);
        exit;
    }

    $campi = [];
    if (!empty($dati["nome"])) $campi[] = "nome='" . $ConnessioneDB->real_escape_string($dati["nome"]) . "'";
    if (!empty($dati["id_nazione"])) $campi[] = "id_nazione=" . intval($dati["id_nazione"]);

    if (!empty($campi)) {
        $ConnessioneDB->query("UPDATE regioni SET " . implode(",", $campi) . " WHERE id=$id");
        $risultato = $ConnessioneDB->query("SELECT * FROM regioni WHERE id=$id");
        echo json_encode($risultato->fetch_assoc());
    } else {
        echo json_encode(["error" => "Nessun campo da aggiornare"]);
    }
}

// ===================== METODO DELETE =====================
else if ($method == "DELETE") {
    if (!empty($_GET["id"])) {
        $id = intval($_GET["id"]);
        $ConnessioneDB->query("DELETE FROM regioni WHERE id=$id");
        echo json_encode(["deleted" => $ConnessioneDB->affected_rows]);
    } else {
        echo json_encode(["error" => "ID mancante per DELETE"]);
    }
}

// ===================== Chiusura connessione =====================
$ConnessioneDB->close();
?>

