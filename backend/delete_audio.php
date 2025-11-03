<?php
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(0);
require_once "connectdb.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Requête invalide."]);
    exit;
}

$id = $_POST["id"] ?? null;
if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID manquant."]);
    exit;
}

$stmt = $conn->prepare("SELECT audio_path FROM uploads WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $file = "../" . $row["audio_path"];
    if (file_exists($file)) {
        unlink($file);
    }

    $delete = $conn->prepare("DELETE FROM uploads WHERE id = ?");
    $delete->bind_param("i", $id);
    $delete->execute();
    echo json_encode(["status" => "success", "message" => "Audio supprimé."]);
} else {
    echo json_encode(["status" => "error", "message" => "Fichier introuvable."]);
}

$conn->close();
?>
