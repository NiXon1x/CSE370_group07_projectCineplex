<?php
include '../includes/auth.php';
include '../includes/db.php';

// Check if user is admin
if ($_SESSION['username'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$movie_id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_movie'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $trailer = mysqli_real_escape_string($conn, $_POST['trailer']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    
    $sql = "INSERT INTO movies (title, description, trailer_link, duration) 
            VALUES ('$title', '$description', '$trailer', '$duration')";
    
    if (mysqli_query($conn, $sql)) {
        $movie_id = mysqli_insert_id($conn);
        $success = "Movie added successfully!";
    } else {
        $error = "Error adding movie: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Movie - Cineplex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4"><?= $movie_id ? 'Edit Movie' : 'Add New Movie' ?></h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="add_movie" value="1">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Trailer Link (YouTube)</label>
                <input type="text" name="trailer" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Duration (e.g., 2h 30m)</label>
                <input type="text" name="duration" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Movie</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>

        <?php if ($movie_id): ?>
        <hr class="my-5">
        
        <h3 class="mb-4">Manage Showtimes</h3>
        <?php
        $showtimes = mysqli_query($conn, 
            "SELECT s.*, h.name as hall_name 
             FROM showtimes s
             JOIN halls h ON s.hall_id = h.id
             WHERE s.movie_id = $movie_id
             ORDER BY s.show_date, s.start_time");
        
        if (mysqli_num_rows($showtimes) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Hall</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($st = mysqli_fetch_assoc($showtimes)): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($st['show_date'])) ?></td>
                            <td><?= date('g:i A', strtotime($st['start_time'])) ?> - <?= date('g:i A', strtotime($st['end_time'])) ?></td>
                            <td><?= $st['hall_name'] ?></td>
                            <td>
                                <a href="delete_showtime.php?id=<?= $st['id'] ?>&movie_id=<?= $movie_id ?>" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No showtimes scheduled yet.</p>
        <?php endif; ?>
        
        <h4 class="mt-5">Add New Showtime</h4>
        <form action="add_showtime.php" method="POST">
            <input type="hidden" name="movie_id" value="<?= $movie_id ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Hall</label>
                    <select name="hall_id" class="form-select" required>
                        <?php 
                        $halls = mysqli_query($conn, "SELECT * FROM halls");
                        while ($hall = mysqli_fetch_assoc($halls)): ?>
                            <option value="<?= $hall['id'] ?>"><?= $hall['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="show_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary mt-4">Add</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>