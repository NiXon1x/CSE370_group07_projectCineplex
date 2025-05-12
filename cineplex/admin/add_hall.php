<?php
include '../includes/auth.php';
include '../includes/db.php';

// Check if user is admin
if ($_SESSION['username'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    
    $sql = "INSERT INTO halls (name) VALUES ('$name')";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Hall added successfully!";
    } else {
        $error = "Error adding hall: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Hall - Cineplex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <h1 class="mb-4">Add New Hall</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Hall Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Rows (Default: 5)</label>
                <input type="number" name="rows" class="form-control" value="5" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Columns (Default: 5)</label>
                <input type="number" name="cols" class="form-control" value="5" readonly>
            </div>
            <button type="submit" class="btn btn-primary">Add Hall</button>
        </form>
    </div>
</body>
</html>