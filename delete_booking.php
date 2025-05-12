<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'upgrade_seats.php';

$booking_id = (int)$_GET['id'] ?? 0;

// Verify booking belongs to user and is refunded
$booking = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM bookings 
     WHERE id = $booking_id 
     AND user_id = {$_SESSION['user_id']}
     AND payment_status IN ('refunded', 'pending')"));

if (!$booking) {
    $_SESSION['error'] = "Invalid booking or not eligible for deletion";
    header("Location: my_bookings.php");
    exit();
}

// Delete the booking
if (mysqli_query($conn, "DELETE FROM bookings WHERE id = $booking_id")) {
    $_SESSION['success'] = "Booking deleted successfully";
} else {
    $_SESSION['error'] = "Error deleting booking: " . mysqli_error($conn);
}

// Process seat upgrade
process_seat_upgrades($booking['showtime_id']);

header("Location: my_bookings.php");
exit();
?>