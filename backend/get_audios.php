<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "connectdb.php";

$sql = "SELECT id, audio_name, audio_path, transcription, traduction FROM uploads ORDER BY id DESC";
$result = $conn->query($sql);

$audios = [];
while ($row = $result->fetch_assoc()) {
    $audios[] = $row;
}

// $audios = [];
// while ($row = $result->fetch_assoc()) {
//     if (file_exists) {
//         $audios[] = $row;
//     }
// }

echo json_encode(["status" => "success", "data" => $audios]);
$conn->close();
?>
