<?php
include 'includes/auth.php';
include 'includes/db.php';

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

// Function to process seat upgrades
function process_seat_upgrades($showtime_id) {
    global $conn;
    
    // Get all refunded seats for this showtime
    $refunded = mysqli_query($conn,
        "SELECT seats FROM bookings 
         WHERE showtime_id = $showtime_id 
         AND payment_status = 'refunded'");
    
    $available_seats = [];
    while ($row = mysqli_fetch_assoc($refunded)) {
        $available_seats = array_merge($available_seats, explode(',', $row['seats']));
    }
    
    if (empty($available_seats)) return;
    
    // Group available seats by column and row
    $seat_groups = [];
    foreach ($available_seats as $seat) {
        $col = substr($seat, 1);
        $row = ord($seat[0]) - 64;
        $seat_groups[$col][] = $row;
    }
    
    // Process each column
    foreach ($seat_groups as $col => $rows) {
        sort($rows);
        $group_size = 1;
        $start_row = $rows[0];
        
        // Find contiguous seat blocks
        for ($i = 1; $i < count($rows); $i++) {
            if ($rows[$i] == $rows[$i-1] + 1) {
                $group_size++;
            } else {
                // Process this block
                upgrade_seats($showtime_id, $col, $start_row, $group_size);
                $start_row = $rows[$i];
                $group_size = 1;
            }
        }
        // Process last block
        upgrade_seats($showtime_id, $col, $start_row, $group_size);
    }
}

// Function to upgrade specific seat block
function upgrade_seats($showtime_id, $col, $start_row, $group_size) {
    global $conn;
    
    // Get the best candidates for upgrade (oldest first)
    $candidates = mysqli_query($conn,
        "SELECT id, seats FROM bookings 
         WHERE showtime_id = $showtime_id
         AND auto_upgrade = 1
         AND payment_status = 'paid'
         ORDER BY booking_date ASC");
    
    while ($candidate = mysqli_fetch_assoc($candidates)) {
        $current_seats = explode(',', $candidate['seats']);
        $current_col = substr($current_seats[0], 1);
        
        // Check if same column and same number of seats
        if ($current_col == $col && count($current_seats) == $group_size) {
            // Create new seat assignment
            $new_seats = [];
            for ($i = 0; $i < $group_size; $i++) {
                $new_seats[] = chr(64 + $start_row + $i) . $col;
            }
            
            // Update booking
            mysqli_query($conn,
                "UPDATE bookings SET seats = '" . implode(',', $new_seats) . "'
                 WHERE id = " . $candidate['id']);
            
            // Disable further upgrades for this booking
            mysqli_query($conn,
                "UPDATE bookings SET auto_upgrade = 0
                 WHERE id = " . $candidate['id']);
            
            break;
        }
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