<?php
session_start();
header('Content-Type: application/json');


require_once '../database/database.php'; 

try {
    // Check if connection from database.php is valid
    if (!$conn) {
        throw new Exception("Database connection not established.");
    }

    // Fetch dorms from the database
    // Fetch dorms and their first associated image
    $sql = "
        SELECT 
            d.dorm_id, 
            d.dorm_name, 
            d.address, 
            d.monthly_rent,
            (SELECT image_url FROM dorm_images di WHERE di.dorm_id = d.dorm_id LIMIT 1) AS image_url
        FROM dorms d 
        ORDER BY d.dorm_id DESC
    ";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception("Error executing query: " . mysqli_error($conn));
    }

    // Fetch all rows as an associative array
    $dorms = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dorms[] = $row;
    }

    $my_bookings = [];
    if (isset($_SESSION['id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
        $renter_id = (int)$_SESSION['id'];
        $b_sql = "
            SELECT 
                b.booking_id, 
                b.move_in_date, 
                d.dorm_id,
                d.dorm_name, 
                d.address, 
                d.monthly_rent,
                (SELECT image_url FROM dorm_images di WHERE di.dorm_id = d.dorm_id LIMIT 1) AS image_url
            FROM bookings b
            JOIN dorms d ON b.dorm_id = d.dorm_id
            WHERE b.renter_id = ?
            ORDER BY b.booking_id DESC
        ";
        $b_stmt = $conn->prepare($b_sql);
        if ($b_stmt) {
            $b_stmt->bind_param('i', $renter_id);
            $b_stmt->execute();
            $b_res = $b_stmt->get_result();
            while ($b_row = $b_res->fetch_assoc()) {
                $my_bookings[] = $b_row;
            }
            $b_stmt->close();
        }
    }

    // Return the data as JSON
    echo json_encode(['success' => true, 'dorms' => $dorms, 'my_bookings' => $my_bookings]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>