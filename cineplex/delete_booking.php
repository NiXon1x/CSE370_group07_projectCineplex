<?php
include 'includes/auth.php';
include 'includes/db.php';

$booking_id = (int)$_GET['id'] ?? 0;

// Verify booking belongs to user and is refunded
$booking = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM bookings 
     WHERE id = $booking_id 
     AND user_id = {$_SESSION['user_id']}
     AND payment_status = 'refunded'"));

if (!$booking) {
    $_SESSION['error'] = "Invalid booking or not eligible for deletion";
    header("Location: my_bookings.php");
    exit();
}

// Delete the booking
if (mysqli_query($conn, "DELETE FROM bookings WHERE id = $booking_id")) {
    $_SESSION['success'] = "Booking history removed successfully";
} else {
    $_SESSION['error'] = "Error deleting booking: " . mysqli_error($conn);
}

header("Location: my_bookings.php");
exit();
?>