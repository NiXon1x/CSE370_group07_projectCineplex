<?php
include '../includes/auth.php';
include '../includes/db.php';

// Check if user is admin
if ($_SESSION['username'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movie_id = (int)$_POST['movie_id'];
    $hall_id = (int)$_POST['hall_id'];
    $show_date = $_POST['show_date'];
    $start_time = $_POST['start_time'];
    
    // Calculate end time based on movie duration
    $movie = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT duration FROM movies WHERE id = $movie_id"));
    
    // Convert duration like "2h 30m" to minutes
    preg_match('/(\d+)h\s*(\d+)m/', $movie['duration'], $matches);
    $hours = $matches[1] ?? 0;
    $minutes = $matches[2] ?? 0;
    $total_minutes = ($hours * 60) + $minutes;
    
    // Calculate end time
    $start = new DateTime("$show_date $start_time");
    $end = clone $start;
    $end->add(new DateInterval("PT{$total_minutes}M"));
    $end_time = $end->format('H:i:s');
    
    // Check for time conflicts
    $conflict_sql = "SELECT id FROM showtimes 
                    WHERE hall_id = $hall_id 
                    AND show_date = '$show_date'
                    AND (
                        (start_time < '$end_time' AND end_time > '$start_time')
                    )";
    
    $conflict_check = mysqli_query($conn, $conflict_sql);
    
    if (mysqli_num_rows($conflict_check) > 0) {
        $_SESSION['error'] = "Time slot conflict in this hall";
        header("Location: add_movie.php?id=$movie_id");
        exit();
    }
    
    // Insert showtime
    $sql = "INSERT INTO showtimes (movie_id, hall_id, show_date, start_time, end_time)
            VALUES ($movie_id, $hall_id, '$show_date', '$start_time', '$end_time')";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Showtime added successfully";
    } else {
        $_SESSION['error'] = "Error adding showtime: " . mysqli_error($conn);
    }
    
    header("Location: add_movie.php?id=$movie_id");
    exit();
}

header("Location: index.php");
exit();
?>