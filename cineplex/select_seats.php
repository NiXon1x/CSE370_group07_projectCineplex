<?php
include 'includes/auth.php';
include 'includes/db.php';

// Validate session and permissions
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if showtime is selected
if (!isset($_SESSION['selected_showtime'])) {
    $_SESSION['error'] = "Please select a showtime first";
    header("Location: book.php");
    exit();
}

// Sanitize showtime ID
$showtime_id = (int)$_SESSION['selected_showtime'];
if ($showtime_id <= 0) {
    $_SESSION['error'] = "Invalid showtime selection";
    header("Location: book.php");
    exit();
}

// Get showtime details using prepared statement
$stmt = $conn->prepare(
    "SELECT s.*, m.title, h.name as hall_name, h.rows, h.cols
     FROM showtimes s
     JOIN movies m ON s.movie_id = m.id
     JOIN halls h ON s.hall_id = h.id
     WHERE s.id = ?"
);
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();
$showtime = $result->fetch_assoc();

if (!$showtime) {
    $_SESSION['error'] = "Showtime not found";
    header("Location: book.php");
    exit();
}

// Get all booked seats (including pending and paid, excluding refunded)
$booked_seats = [];
$stmt = $conn->prepare(
    "SELECT seats FROM bookings 
     WHERE showtime_id = ? 
     AND payment_status != 'refunded'"
);
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $booked_seats = array_merge($booked_seats, explode(',', $row['seats']));
}

// Handle seat selection form
$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['seats'])) {
        $error = "Please select at least one seat";
    } else {
        $seats = $_POST['seats'];
        $auto_upgrade = isset($_POST['auto_upgrade']) ? 1 : 0;
        
        // Validate seats format
        if (!preg_match('/^[A-Z][0-9]+(,[A-Z][0-9]+)*$/', $seats)) {
            $error = "Invalid seat selection";
        } else {
            // Create booking with prepared statement
            $stmt = $conn->prepare(
                "INSERT INTO bookings (user_id, showtime_id, seats, auto_upgrade) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "iisi", 
                $_SESSION['user_id'], 
                $showtime_id, 
                $seats, 
                $auto_upgrade
            );
            
            if ($stmt->execute()) {
                $_SESSION['booking_id'] = $conn->insert_id;
                header("Location: payment.php");
                exit();
            } else {
                $error = "Error creating booking. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Select Seats - <?= htmlspecialchars($showtime['title']) ?> | Cineplex</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .screen-container { display: flex; justify-content: center; margin: 20px 0; }
        .screen {
            background: linear-gradient(to bottom, #666, #333);
            color: white;
            text-align: center;
            padding: 8px 0;
            width: <?= ($showtime['cols'] * 36) + 20 ?>px;
            max-width: 100%;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            font-size: 14px;
        }
        .seat-map-container { max-width: 100%; margin: 0 auto; }
        .seat-row { display: flex; justify-content: center; margin-bottom: 8px; }
        .row-label {
            width: 25px;
            margin-right: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .seat {
            width: 30px;
            height: 30px;
            margin: 0 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .seat.available { background-color: #28a745; color: white; }
        .seat.selected { background-color: #ffc107; color: black; transform: scale(1.1); }
        .seat.booked { background-color: #dc3545; color: white; cursor: not-allowed; }
        .seat:hover:not(.booked):not(.legend) { transform: scale(1.1); }
        .legend-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .legend-item { display: flex; align-items: center; gap: 5px; font-size: 14px; }
        .legend-seat { width: 20px; height: 20px; border-radius: 3px; }
        .seat-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .max-seats-alert { display: none; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><i class="bi bi-ticket-perforated"></i> Select Seats for <?= htmlspecialchars($showtime['title']) ?></h3>
            </div>
            <div class="card-body">
                <div class="seat-info">
                    <p class="mb-1"><strong><i class="bi bi-calendar-date"></i> Date:</strong> <?= date('l, F j, Y', strtotime($showtime['show_date'])) ?></p>
                    <p class="mb-1"><strong><i class="bi bi-clock"></i> Time:</strong> <?= date('g:i A', strtotime($showtime['start_time'])) ?></p>
                    <p class="mb-1"><strong><i class="bi bi-geo-alt"></i> Hall:</strong> <?= htmlspecialchars($showtime['hall_name']) ?></p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="alert alert-warning max-seats-alert" id="maxSeatsAlert">
                    <i class="bi bi-exclamation-triangle-fill"></i> Maximum 8 seats per booking
                </div>
                
                <div class="screen-container">
                    <div class="screen">SCREEN</div>
                </div>
                
                <div class="seat-map-container">
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="autoUpgrade" name="auto_upgrade" value="1" checked>
                        <label class="form-check-label" for="autoUpgrade">
                            <strong><i class="bi bi-arrow-up-circle"></i> Auto-upgrade to front rows</strong>
                        </label>
                        <small class="text-muted d-block mt-1">
                            If seats in front become available (from refunds), your seats will be automatically upgraded
                        </small>
                    </div>
                    
                    <div class="seat-map mb-4">
                        <?php for ($row = 1; $row <= $showtime['rows']; $row++): ?>
                        <div class="seat-row">
                            <div class="row-label"><?= chr(64 + $row) ?></div>
                            <?php for ($col = 1; $col <= $showtime['cols']; $col++): 
                                $seat = chr(64 + $row) . $col;
                                $status = in_array($seat, $booked_seats) ? 'booked' : 'available';
                            ?>
                            <div class="seat <?= $status ?>" data-seat="<?= htmlspecialchars($seat) ?>">
                                <?= $col ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="legend-container">
                        <div class="legend-item">
                            <div class="legend-seat available"></div>
                            <span>Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-seat selected"></div>
                            <span>Selected</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-seat booked"></div>
                            <span>Booked</span>
                        </div>
                    </div>
                    
                    <form method="POST" id="bookingForm">
                        <input type="hidden" name="seats" id="selectedSeats">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="bi bi-check-circle"></i> Confirm Seats
                            </button>
                            <a href="book.php?movie_id=<?= $showtime['movie_id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Showtimes
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const MAX_SEATS = 8;
        const seats = document.querySelectorAll('.seat.available');
        const selectedSeatsInput = document.getElementById('selectedSeats');
        const maxSeatsAlert = document.getElementById('maxSeatsAlert');
        const submitBtn = document.getElementById('submitBtn');
        let selectedSeats = [];
        
        seats.forEach(seat => {
            seat.addEventListener('click', function() {
                if (this.classList.contains('booked')) return;
                
                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                } else {
                    if (selectedSeats.length >= MAX_SEATS) {
                        maxSeatsAlert.style.display = 'block';
                        setTimeout(() => maxSeatsAlert.style.display = 'none', 3000);
                        return;
                    }
                    this.classList.add('selected');
                }
                updateSelectedSeats();
            });
        });
        
        function updateSelectedSeats() {
            selectedSeats = Array.from(document.querySelectorAll('.seat.selected'))
                .map(seat => seat.dataset.seat);
            selectedSeatsInput.value = selectedSeats.join(',');
            submitBtn.disabled = selectedSeats.length === 0;
        }
        
        // Initialize
        updateSelectedSeats();
    });
    </script>
</body>
</html>