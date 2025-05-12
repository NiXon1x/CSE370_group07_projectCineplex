<?php
function isAdmin() {
    return isset($_SESSION['username']) && $_SESSION['username'] == 'admin';
}

function getMovieTitle($conn, $id) {
    $sql = "SELECT title FROM movies WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result)['title'];
}

function getHallName($conn, $id) {
    $sql = "SELECT name FROM halls WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result)['name'];
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
?>