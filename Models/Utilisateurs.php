<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Affichage des erreurs pour débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../DataBase/DB.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {

    case 'GET':
        $result = mysqli_query($conn, "SELECT * FROM utilisateur");
        if (!$result) { echo json_encode(["error" => mysqli_error($conn)]); exit; }
        $data = [];
        while($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
        echo json_encode($data);
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !isset($input['nom']) || !isset($input['email'])) {
            echo json_encode(["error" => "Données manquantes"]); exit;
        }
        $nom = mysqli_real_escape_string($conn, $input['nom']);
        $email = mysqli_real_escape_string($conn, $input['email']);
        $sql = "INSERT INTO utilisateurs (nom, email) VALUES ('$nom', '$email')";
        if (mysqli_query($conn, $sql)) { echo json_encode(["message" => "Utilisateur ajouté"]); }
        else { echo json_encode(["error" => mysqli_error($conn)]); }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !isset($input['id']) || !isset($input['nom']) || !isset($input['email'])) {
            echo json_encode(["error" => "Données manquantes pour modification"]); exit;
        }
        $id = intval($input['id']);
        $nom = mysqli_real_escape_string($conn, $input['nom']);
        $email = mysqli_real_escape_string($conn, $input['email']);
        $sql = "UPDATE utilisateurs SET nom='$nom', email='$email' WHERE id=$id";
        if (mysqli_query($conn, $sql)) { echo json_encode(["message" => "Utilisateur modifié"]); }
        else { echo json_encode(["error" => mysqli_error($conn)]); }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input || !isset($input['id'])) {
            echo json_encode(["error" => "ID manquant pour suppression"]); exit;
        }
        $id = intval($input['id']);
        $sql = "DELETE FROM utilisateurs WHERE id=$id";
        if (mysqli_query($conn, $sql)) { echo json_encode(["message" => "Utilisateur supprimé"]); }
        else { echo json_encode(["error" => mysqli_error($conn)]); }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Méthode non autorisée"]);
        break;
}



