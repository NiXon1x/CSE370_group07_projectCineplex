<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'upgrade_seats.php';

$booking_id = (int)$_GET['booking_id'];
$booking = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM bookings WHERE id = $booking_id AND user_id = {$_SESSION['user_id']}"));

if (!$booking) {
    $_SESSION['error'] = "Booking not found";
    header("Location: my_bookings.php");
    exit();
}

// Process refund
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update booking status to refunded
    $sql = "UPDATE bookings SET payment_status = 'refunded' WHERE id = $booking_id";
    
    if (mysqli_query($conn, $sql)) {
        // Process seat upgrades after refund
        process_seat_upgrades($booking['showtime_id']);
        
        $_SESSION['success'] = "Refund processed successfully!";
        header("Location: my_bookings.php");
        exit();
    } else {
        $error = "Refund failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Refund - Cineplex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3>Request Refund</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <h5>Refund Policy</h5>
                            <p>You will receive a full refund, and your seats may be automatically assigned to other customers.</p>
                        </div>
                        
                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger btn-lg">Confirm Refund</button>
                                <a href="my_bookings.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>