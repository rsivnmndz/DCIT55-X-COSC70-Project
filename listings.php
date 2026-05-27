<?php
session_start();
include ("./database/database.php");

$sql = $conn->prepare("SELECT * FROM dorms WHERE available_rooms > 0");
$sql->execute();
$result = $sql->get_result();

$dorms = [];
while ($row = $result->fetch_assoc()) {
    $dorms[] = $row;
}

$sql = $conn->prepare("SELECT * FROM dorm_amenities INNER JOIN amenities ON dorm_amenities.amenity_id = amenities.amenity_id");
$sql->execute();
$result = $sql->get_result();

$dormAmenities = [];
while ($row = $result->fetch_assoc()) {
    $dormAmenities[] = $row;
}

if (isset($_GET['json'])) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['dorms' => $dorms, 'amenities' => $dormAmenities]);
  exit;
}
?>