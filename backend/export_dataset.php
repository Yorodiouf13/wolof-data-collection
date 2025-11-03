<?php
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(0);
require_once "connectdb.php";

$exportDir = "../dataset_creation/audios/";
if (!file_exists($exportDir)) {
    mkdir($exportDir, 0777, true);
}

$query = "SELECT id, audio_path, transcription, traduction FROM uploads";
$result = $conn->query($query);

$dataset = [];
while ($row = $result->fetch_assoc()) {
    $source = "../" . $row["audio_path"];
    $dest = $exportDir . basename($row["id"]);
    if (file_exists($source)) {
        copy($source, $dest);
    }
    $dataset[] = [
        "audio_path" => "dataset_audios/" . basename($row["id"]),
        "transcription" => $row["transcription"],
        "traduction" => $row["traduction"]
    ];
}

$filePath = "../dataset_creation/dataset.json";
file_put_contents($filePath, json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(["status" => "success", "message" => "Export terminé avec succès.", "file" => "dataset.json"]);
$conn->close();
?>
