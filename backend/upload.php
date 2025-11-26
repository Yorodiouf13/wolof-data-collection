<?php
// --- Forcer le retour JSON et masquer les erreurs HTML ---
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once "connectdb.php";

// --- Fonction pour renvoyer une réponse JSON propre ---
function jsonResponse($status, $message) {
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

// --- Fonction GUID (ton code) ---
function GUID()
{
    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf(
        '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
        mt_rand(0, 65535), mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(16384, 20479),
        mt_rand(32768, 49151),
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
    );
}

// --- Vérifier la méthode ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse("error", "Requête invalide (POST requis).");
}

// --- Vérifier la connexion ---
if (!$conn || $conn->connect_error) {
    jsonResponse("error", "Erreur de connexion MySQL.");
}

// --- Vérifier les champs obligatoires ---
if (empty($_POST["transcription"]) || empty($_POST["traduction"])) {
    jsonResponse("error", "Tous les champs sont obligatoires.");
}

// --- Vérifier la présence du fichier ---
if (!isset($_FILES["audio"]) || $_FILES["audio"]["error"] !== UPLOAD_ERR_OK) {
    jsonResponse("error", "Aucun fichier audio reçu ou erreur d’upload.");
}

// === Traitement du fichier ===
$transcription  = trim($_POST["transcription"]);
$traduction     = trim($_POST["traduction"]);
$original_name  = basename($_FILES["audio"]["name"]);
$audio_tmp      = $_FILES["audio"]["tmp_name"];
$upload_dir     = "../audios/";

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
if (!in_array($ext, ["wav", "mp3"])) {
    jsonResponse("error", "Format non supporté. Seuls WAV et MP3 sont acceptés.");
}

// === GUID pour l'ID et le nom de fichier ===
$guid = GUID();

$final_name = $guid . ".wav"; // Tout est converti en WAV
$final_path = $upload_dir . $final_name;

// === Conversion ou déplacement ===
if ($ext === "mp3") {

    $tmp_mp3_path = $upload_dir . $guid . ".mp3";
    move_uploaded_file($audio_tmp, $tmp_mp3_path);

    $ffmpegPath = "C:\\ffmpeg-2025-10-27-git-68152978b5-full_build\\bin\\ffmpeg.exe";

    if (!file_exists($ffmpegPath)) {
        unlink($tmp_mp3_path);
        jsonResponse("error", "FFmpeg introuvable. Vérifie le chemin dans upload.php.");
    }

    $command = "\"$ffmpegPath\" -y -i " . escapeshellarg($tmp_mp3_path) . " -ar 16000 -ac 1 " . escapeshellarg($final_path);
    exec($command, $output, $return_var);
    unlink($tmp_mp3_path);

    if ($return_var !== 0 || !file_exists($final_path)) {
        jsonResponse("error", "Erreur lors de la conversion du fichier audio.");
    }

} else {
    // Si déjà wav
    move_uploaded_file($audio_tmp, $final_path);
}

// --- Enregistrement MySQL ---
$audio_path_db = "audios/" . $final_name;
$unique_id = $guid;

$stmt = $conn->prepare("INSERT INTO uploads (id, audio_name, original_name, audio_path, transcription, traduction) 
                        VALUES (?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    jsonResponse("error", "Erreur interne SQL (préparation).");
}

$stmt->bind_param("ssssss", 
    $unique_id, 
    $final_name, 
    $original_name, 
    $audio_path_db, 
    $transcription, 
    $traduction
);

if ($stmt->execute()) {
    jsonResponse("success", "Formulaire enregistré avec succès sous l’ID : $unique_id");
} else {
    jsonResponse("error", "Erreur interne SQL (exécution).");
}

$stmt->close();
$conn->close();
?>
