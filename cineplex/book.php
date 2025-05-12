<?php
include 'includes/auth.php';
include 'includes/db.php';

$movie_id = (int)$_GET['movie_id'];
$movie = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM movies WHERE id = $movie_id"));

// Get all available showtimes for this movie
$showtimes = mysqli_query($conn, 
    "SELECT s.*, h.name as hall_name, h.rows, h.cols,
            (SELECT COUNT(*) FROM bookings b 
             WHERE b.showtime_id = s.id) as booked_seats_count
     FROM showtimes s
     JOIN halls h ON s.hall_id = h.id
     WHERE s.movie_id = $movie_id
     AND s.show_date >= CURDATE()
     ORDER BY s.show_date, s.start_time");

// Handle showtime selection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['showtime_id'])) {
    $_SESSION['selected_showtime'] = (int)$_POST['showtime_id'];
    header("Location: select_seats.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Tickets - <?= $movie['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h3><?= $movie['title'] ?></h3>
                        <p><?= $movie['description'] ?></p>
                        <p><strong>Duration:</strong> <?= $movie['duration'] ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Select Showtime</h4>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($showtimes) > 0): ?>
                        <form method="POST">
                            <div class="list-group">
                                <?php while ($st = mysqli_fetch_assoc($showtimes)): 
                                    $total_seats = $st['rows'] * $st['cols'];
                                    $available = $total_seats - $st['booked_seats_count'];
                                ?>
                                <label class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <input type="radio" name="showtime_id" value="<?= $st['id'] ?>" required>
                                        <strong><?= date('l, F j', strtotime($st['show_date'])) ?></strong>
                                        <br>
                                        <?= date('g:i A', strtotime($st['start_time'])) ?> - 
                                        <?= date('g:i A', strtotime($st['end_time'])) ?>
                                        <br>
                                        <em><?= $st['hall_name'] ?></em>
                                    </div>
                                    <span class="badge bg-<?= $available > 5 ? 'success' : 'warning' ?> rounded-pill">
                                        <?= $available ?> seats left
                                    </span>
                                </label>
                                <?php endwhile; ?>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Select Seats</button>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            No available showtimes for this movie.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>