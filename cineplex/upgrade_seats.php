<?php
include 'includes/db.php';

// This function should be called whenever a refund is processed
function process_seat_upgrades($showtime_id) {
    global $conn;
    
    // Get all refunded seats for this showtime
    $refunded_seats = [];
    $result = mysqli_query($conn, 
        "SELECT seats FROM bookings 
         WHERE showtime_id = $showtime_id 
         AND payment_status = 'refunded'
         ");
    
    while ($row = mysqli_fetch_assoc($result)) {
        $refunded_seats = array_merge($refunded_seats, explode(',', $row['seats']));
    }
    
    if (empty($refunded_seats)) return;
    
    // Sort refunded seats closer to the screen first
    usort($refunded_seats, function($a, $b) {
        $row_a = ord($a[0]);
        $row_b = ord($b[0]);
        return $row_a - $row_b;
    });
    
    // Get all bookings waiting for upgrade (ordered by booking time)
    $upgrade_queue = mysqli_query($conn,
        "SELECT * FROM bookings 
         WHERE showtime_id = $showtime_id 
         AND auto_upgrade = 1 
         AND payment_status = 'paid'
         ORDER BY booking_date ASC");
    
    // Process each refunded seat group
    $seat_groups = group_contiguous_seats($refunded_seats);
    
    foreach ($seat_groups as $group) {
        $group_size = count($group);
        
        // Find the first booking in queue that matches this group size
        while ($booking = mysqli_fetch_assoc($upgrade_queue)) {
            $current_seats = explode(',', $booking['seats']);
            
            if (count($current_seats) == $group_size) {
                // Upgrade these seats
                mysqli_query($conn,
                    "UPDATE bookings SET seats = '" . 
                    implode(',', $group) . 
                    "' WHERE id = " . $booking['id']);
                
                // Mark these seats as upgraded
                // mysqli_query($conn,
                //     "UPDATE bookings SET auto_upgrade = 0 
                //      WHERE id = " . $booking['id']);
                break;
            }
        }
        
        // Reset pointer to start of results for next group
        mysqli_data_seek($upgrade_queue, 0);
    }
}

// Helper function to group contiguous seats
function group_contiguous_seats($seats) {
    sort($seats);
    $groups = [];
    $current_group = [];
    
    foreach ($seats as $seat) {
        if (empty($current_group)) {
            $current_group[] = $seat;
        } else {
            $last_seat = end($current_group);
            $last_row = $last_seat[0];
            $current_row = $seat[0];
            $last_col = (int)substr($last_seat, 1);
            $current_col = (int)substr($seat, 1);
            
            if ($current_row == $last_row && $current_col == $last_col + 1) {
                $current_group[] = $seat;
            } else {
                $groups[] = $current_group;
                $current_group = [$seat];
            }
        }
    }
    
    if (!empty($current_group)) {
        $groups[] = $current_group;
    }
    
    return $groups;
}

// Call this function from refund.php after processing a refund
?>