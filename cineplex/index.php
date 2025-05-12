<?php 
include 'includes/db.php';
include 'includes/session.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cineplex - Movie Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <h1 class="text-center mb-4">Now Showing</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="row">
            <?php
            $sql = "SELECT m.*, 
                   (SELECT COUNT(*) FROM showtimes s 
                    WHERE s.movie_id = m.id 
                    AND s.show_date >= CURDATE()) as showtime_count
                   FROM movies m
                   HAVING showtime_count > 0
                   ORDER BY m.title";
            
            $result = mysqli_query($conn, $sql);
            
            while ($movie = mysqli_fetch_assoc($result)):
                // Get next 3 showtimes
                $showtimes_sql = "SELECT s.*, h.name as hall_name 
                                 FROM showtimes s
                                 JOIN halls h ON s.hall_id = h.id
                                 WHERE s.movie_id = {$movie['id']}
                                 AND s.show_date >= CURDATE()
                                 ORDER BY s.show_date, s.start_time
                                 LIMIT 3";
                $showtimes = mysqli_query($conn, $showtimes_sql);
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= $movie['title'] ?></h5>
                        <p class="card-text"><?= $movie['description'] ?></p>
                        <p class="text-muted">Duration: <?= $movie['duration'] ?></p>
                        
                        <?php if (mysqli_num_rows($showtimes) > 0): ?>
                        <div class="showtimes mt-3">
                            <h6>Upcoming Showtimes:</h6>
                            <ul class="list-unstyled">
                                <?php while ($st = mysqli_fetch_assoc($showtimes)): ?>
                                <li class="mb-2">
                                    <small>
                                        <?= date('D, M j', strtotime($st['show_date'])) ?><br>
                                        <?= date('g:i A', strtotime($st['start_time'])) ?> - 
                                        <?= date('g:i A', strtotime($st['end_time'])) ?><br>
                                        <em><?= $st['hall_name'] ?></em>
                                    </small>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="book.php?movie_id=<?= $movie['id'] ?>" class="btn btn-primary">View All Showtimes & Book</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>