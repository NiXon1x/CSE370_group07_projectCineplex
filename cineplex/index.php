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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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

    .duration_time {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .movie-poster {
        width: 100%;
        height: 300px;
        object-fit: cover;
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
                <div class="col-md-4 mb-4 animate__animated animate__fadeIn">
                    <div class="card h-100">
                        <div class="card-body">
                            <img src="<?= $movie['thumbnail'] ?>" class="movie-poster mb-3" alt="<?= $movie['title'] ?> Thumbnail">
                            <h5 class="card-title"><?= $movie['title'] ?></h5>
                            <p class="card-text"><?= $movie['description'] ?></p>
                            <p class="text-muted duration_time">Duration: <?= $movie['duration'] ?></p>

                            <?php if (mysqli_num_rows($showtimes) > 0): ?>
                                <div class="showtimes mt-3">
                                    <h6>Upcoming Showtimes:</h6>
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Hall</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($st = mysqli_fetch_assoc($showtimes)): ?>
                                                <tr>
                                                    <td><?= date('D, M j', strtotime($st['show_date'])) ?></td>
                                                    <td><?= date('g:i A', strtotime($st['start_time'])) ?></td>
                                                    <td><?= date('g:i A', strtotime($st['end_time'])) ?></td>
                                                    <td><em><?= $st['hall_name'] ?></em></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mt-3 text-center mt-5">No upcoming showtimes</p>
                            <?php endif; ?>
                        </div>
                        <?php if (mysqli_num_rows($showtimes) > 0): ?>
                        <div class="card-footer bg-transparent text-center">
                            <a href="book.php?movie_id=<?= $movie['id'] ?>" class="btn btn-outline-primary">Book <i class="fa-regular fa-calendar-days"></i></a>
                        </div>
                        <?php else: ?>
                        
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/script.js" defer></script>
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