<?php
include '../includes/auth.php';
include '../includes/db.php';

// Check if user is admin
if ($_SESSION['username'] != 'admin') {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Cineplex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Admin Dashboard</h1>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Movies</h5>
                        <p class="card-text">Manage all movies</p>
                        <a href="add_movie.php" class="btn btn-primary">Add Movie</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Halls</h5>
                        <p class="card-text">Manage cinema halls</p>
                        <a href="add_hall.php" class="btn btn-primary">Add Hall</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Showtime</h5>
                        <p class="card-text">Add showtime</p>
                        <a href="add_showtime.php" class="btn btn-primary">Add new showtime</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>