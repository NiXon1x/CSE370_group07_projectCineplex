<?php
include 'includes/auth.php';
include 'includes/db.php';

// Get booking ID from URL or session
$booking_id = $_GET['booking_id'] ?? $_SESSION['booking_id'] ?? null;

if (!$booking_id) {
    $_SESSION['error'] = "No booking selected for payment";
    header("Location: my_bookings.php");
    exit();
}

// Get booking details
$booking = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT b.*, m.title, s.show_date, s.start_time, h.name as hall_name
     FROM bookings b
     JOIN showtimes s ON b.showtime_id = s.id
     JOIN movies m ON s.movie_id = m.id
     JOIN halls h ON s.hall_id = h.id
     WHERE b.id = " . (int)$booking_id . "
     AND b.user_id = " . (int)$_SESSION['user_id']
));

if (!$booking) {
    $_SESSION['error'] = "Booking not found";
    header("Location: my_bookings.php");
    exit();
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "UPDATE bookings SET payment_status = 'paid' WHERE id = " . (int)$booking_id;

    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Payment successful! Booking confirmed.";
        header("Location: my_bookings.php");
        exit();
    } else {
        $error = "Payment failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Complete Payment - Cineplex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
</head>

<style>
    body {
        font-family: 'Lexend', monospace;
        background-color: #f8f9fa;
        opacity: 0; /* Start with opacity 0 */
        animation: fadeIn 0.5s forwards; /* Fade-in animation */
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .fade-out {
        animation: fadeOut 0.5s forwards;
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
</style>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3>Complete Payment</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <h4><?= $booking['title'] ?></h4>
                        <p class="mb-1"><strong>Date:</strong> <?= date('F j, Y', strtotime($booking['show_date'])) ?></p>
                        <p class="mb-1"><strong>Time:</strong> <?= date('g:i A', strtotime($booking['start_time'])) ?></p>
                        <p class="mb-1"><strong>Hall:</strong> <?= $booking['hall_name'] ?></p>
                        <p class="mb-3"><strong>Seats:</strong> <?= $booking['seats'] ?></p>

                        <hr>

                        <h5 class="mb-3">Payment Details</h5>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" value="4242 4242 4242 4242" readonly>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" value="12/25" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control" value="123" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Total Amount</label>
                                <input type="text" class="form-control"
                                    value="$<?= count(explode(',', $booking['seats'])) * 10 ?>" readonly>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-credit-card"></i> Confirm Payment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const links = document.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    if (link.href && link.href !== window.location.href) {
                        e.preventDefault();
                        document.body.classList.add('fade-out');
                        setTimeout(() => {
                            window.location.href = link.href;
                        }, 500);
                    }
                });
            });
        });
    </script>
</body>

</html>