<?php
// ===========================================================
// FILE: comuni.php
// SCOPO: Gestire i comuni italiani tramite API REST
// METODI: GET, POST, PUT, PATCH, DELETE
// ===========================================================

$ConnessioneDB = new mysqli("localhost", "root", "", "italia_admin");

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

function leggiInput() {
    return json_decode(file_get_contents("php://input"), true);
}

// ===================== GET =====================
if ($method == "GET") {
    $Query = "SELECT * FROM comuni LIMIT 20";

    if (!empty($_GET["id"])) {
        $id = intval($_GET["id"]);
        $Query = "SELECT * FROM comuni WHERE id = $id";
    }
    else if (!empty($_GET["id_provincia"])) {
        $id_provincia = intval($_GET["id_provincia"]);
        $Query = "SELECT * FROM comuni WHERE id_provincia = $id_provincia";
    }
    else if (!empty($_GET["nome"])) {
        $nome = $ConnessioneDB->real_escape_string($_GET["nome"]);
        $Query = "SELECT * FROM comuni WHERE nome LIKE '%$nome%'";
    }

    $risultato = $ConnessioneDB->query($Query);
    $Lista = [];
    while ($Riga = $risultato->fetch_assoc()) {
        $Lista[] = $Riga;
    }
    echo json_encode($Lista);
}

// ===================== POST =====================
else if ($method == "POST") {
    $dati = leggiInput();
    if (!$dati) {
        echo json_encode(["error" => "Body JSON mancante o non valido"]);
        exit;
    }

    $nome = $ConnessioneDB->real_escape_string($dati["nome"] ?? "");
    $id_provincia = intval($dati["id_provincia"] ?? 0);

    if ($nome == "" || $id_provincia == 0) {
        echo json_encode(["error" => "Campi obbligatori mancanti"]);
        exit;
    }

    $ConnessioneDB->query("INSERT INTO comuni (nome, id_provincia) VALUES ('$nome', $id_provincia)");
    $nuovo_id = $ConnessioneDB->insert_id;

    $risultato = $ConnessioneDB->query("SELECT * FROM comuni WHERE id=$nuovo_id");
    echo json_encode($risultato->fetch_assoc());
}

// ===================== PUT =====================
else if ($method == "PUT") {
    $dati = leggiInput();
    if (!$dati) {
        echo json_encode(["error" => "Body JSON mancante o non valido"]);
        exit;
    }

    $id = intval($dati["id"] ?? 0);
    $nome = $ConnessioneDB->real_escape_string($dati["nome"] ?? "");
    $id_provincia = intval($dati["id_provincia"] ?? 0);

    if ($id == 0 || $nome == "" || $id_provincia == 0) {
        echo json_encode(["error" => "Campi obbligatori mancanti"]);
        exit;
    }

    $ConnessioneDB->query("UPDATE comuni SET nome='$nome', id_provincia=$id_provincia WHERE id=$id");
    $risultato = $ConnessioneDB->query("SELECT * FROM comuni WHERE id=$id");
    echo json_encode($risultato->fetch_assoc());
}

// ===================== PATCH =====================
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
    if (!empty($dati["id_provincia"])) $campi[] = "id_provincia=" . intval($dati["id_provincia"]);

    if (!empty($campi)) {
        $ConnessioneDB->query("UPDATE comuni SET " . implode(",", $campi) . " WHERE id=$id");
        $risultato = $ConnessioneDB->query("SELECT * FROM comuni WHERE id=$id");
        echo json_encode($risultato->fetch_assoc());
    } else {
        echo json_encode(["error" => "Nessun campo da aggiornare"]);
    }
}

// ===================== DELETE =====================
else if ($method == "DELETE") {
    if (!empty($_GET["id"])) {
        $id = intval($_GET["id"]);
        $ConnessioneDB->query("DELETE FROM comuni WHERE id=$id");
        echo json_encode(["deleted" => $ConnessioneDB->affected_rows]);
    } else {
        echo json_encode(["error" => "ID mancante per DELETE"]);
    }
}

$ConnessioneDB->close();
?>
