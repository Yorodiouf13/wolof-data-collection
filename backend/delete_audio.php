<?php
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(0);
require_once "connectdb.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Requête invalide."]);
    exit;
}

/* SUPPRESSION TOTALE */
if (isset($_POST["action"]) && $_POST["action"] === "delete_all") {

    // Récupérer les chemins et supprimer les fichiers physiques
    $res = $conn->query("SELECT audio_path FROM uploads");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $file = __DIR__ . "/" . $row["audio_path"]; // __DIR__ = dossier actuel
            // Si audio_path était "audios/nom.wav", file sera ".../audios/nom.wav"
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    } else {
        // log SQL error
        error_log("delete_all: query failed: " . $conn->error);
    }

    // Supprimer les enregistrements
    $conn->query("DELETE FROM uploads");

    echo json_encode([
        "status" => "success",
        "message" => "Tous les audios ont été supprimés avec succès."
    ]);

    $conn->close();
    exit;
}

/* SUPPRESSION D'UN SEUL */
$id = $_POST["id"] ?? null;
if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID manquant."]);
    exit;
}

$stmt = $conn->prepare("SELECT audio_path FROM uploads WHERE id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $file = __DIR__ . "/" . $row["audio_path"];
    if (file_exists($file)) {
        @unlink($file);
    }

    $delete = $conn->prepare("DELETE FROM uploads WHERE id = ?");
    $delete->bind_param("s", $id);
    $delete->execute();

    echo json_encode(["status" => "success", "message" => "Audio supprimé."]);
} else {
    echo json_encode(["status" => "error", "message" => "Fichier introuvable."]);
}

$conn->close();
?>
