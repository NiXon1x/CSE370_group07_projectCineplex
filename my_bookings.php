<?php
include 'includes/auth.php';
include 'includes/db.php';

// Get all bookings for current user with showtime details
$bookings = mysqli_query(
    $conn,
    "SELECT b.*, m.title,m.duration, s.show_date, s.start_time, h.name as hall_name
     FROM bookings b
     JOIN showtimes s ON b.showtime_id = s.id
     JOIN movies m ON s.movie_id = m.id
     JOIN halls h ON s.hall_id = h.id
     WHERE b.user_id = {$_SESSION['user_id']}
     ORDER BY s.show_date DESC, s.start_time DESC"
);

// Then display seats from the fresh database record
// $seats = explode(',', $booking['seats']);
if (isset($booking['seats']) && !empty($booking['seats'])) {
    $seats = explode(',', $booking['seats']);
    foreach ($seats as $seat) {
        echo '<span class="seat-pill">' . htmlspecialchars($seat) . '</span>';
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>My Bookings - Cineplex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
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

        .booking-card {
            transition: all 0.3s;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 50px;
        }

        .no-bookings {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .seat-pill {
            background-color: #e9ecef;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.85rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }

        .countdown {
            font-weight: 600;
        }

        .past-show {
            color: #6c757d;
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Add a sample dropdown for testing -->
    <div class="dropdown">
        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
        </ul>
    </div>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-ticket-perforated"></i> My Bookings</h1>
            <a href="index.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Book Tickets
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (mysqli_num_rows($bookings) > 0): ?>
            <div class="row">
                <?php while ($booking = mysqli_fetch_assoc($bookings)):
                    $show_time = new DateTime($booking['show_date'] . ' ' . $booking['start_time']);
                    $current_time = new DateTime();
                    $hours_diff = ($show_time->getTimestamp() - $current_time->getTimestamp()) / 3600;
                    $is_past = $current_time > $show_time;
                    $seats = explode(',', $booking['seats']);
                    $total_amount = count($seats) * 10;
                ?>
                    <div class="col-md-6 mb-4">
                        <div class="card booking-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0"><?= $booking['title'] ?></h5>
                                    <small class="text-muted"><?= $booking['duration'] ?></small>
                                </div>
                                <span class="status-badge 
                                <?= $booking['payment_status'] == 'paid' ? 'bg-success text-white' : ($booking['payment_status'] == 'refunded' ? 'bg-secondary text-white' : 'bg-warning text-dark') ?>">
                                    <?= ucfirst($booking['payment_status'] ?? '') ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <p><i class="bi bi-calendar-date text-primary"></i> <strong>Date:</strong> <?= date('M j, Y', strtotime($booking['show_date'])) ?></p>
                                        <p><i class="bi bi-clock text-primary"></i> <strong>Time:</strong> <?= date('g:i A', strtotime($booking['start_time'])) ?></p>
                                        <p><i class="bi bi-geo-alt text-primary"></i> <strong>Hall:</strong> <?= $booking['hall_name'] ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><i class="bi bi-ticket-detailed text-primary"></i> <strong>Seats:</strong></p>
                                        <div>
                                            <?php foreach ($seats as $seat): ?>
                                                <span class="seat-pill"><?= $seat ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="mt-2"><i class="bi bi-cash-stack text-primary"></i> <strong>Total:</strong> $<?= $total_amount ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                                <?php if ($booking['payment_status'] == 'pending'): ?>
                                    <div></div> <!-- Empty div to push the button to the right -->
                                    <a href="payment.php?booking_id=<?= $booking['id'] ?>" class="btn btn-success btn-sm">
                                        <i class="bi bi-credit-card"></i> Complete Payment
                                    </a>
                                <?php else: ?>
                                    <div>
                                        <?php if ($booking['payment_status'] == 'paid' && !$is_past): ?>
                                            <?php if ($hours_diff > 6): ?>
                                                <a href="refund.php?booking_id=<?= $booking['id'] ?>" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Request Refund
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                                    <i class="bi bi-lock"></i> Refund Unavailable
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($booking['payment_status'] == 'refunded'): ?>
                                            <a href="delete_booking.php?id=<?= $booking['id'] ?>" class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('Permanently remove this booking history?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div></div> <!-- Empty div to balance alignment -->
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card no-bookings">
                <div class="card-body py-5">
                    <i class="bi bi-ticket-perforated display-4 text-muted mb-3"></i>
                    <h3 class="text-muted">No Bookings Found</h3>
                    <p class="text-muted mb-4">You haven't made any bookings yet.</p>
                    <a href="index.php" class="btn btn-primary px-4">
                        <i class="bi bi-film"></i> Browse Movies
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const links = document.querySelectorAll('a:not([data-bs-toggle])'); // Exclude dropdown toggles
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