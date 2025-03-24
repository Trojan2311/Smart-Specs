<?php
require 'db.php';

$stmt = $conn->query("SELECT * FROM assets ORDER BY created_at DESC");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Asset Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-5">
    <h2>All Logged Asset Specifications</h2>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Serial</th>
                <th>Brand</th>
                <th>CPU</th>
                <th>RAM</th>
                <th>GPU</th>
                <th>Optical Drive</th>
                <th>EOL</th>
                <th>2nd Hand Value</th>
                <th>Logged At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['serial_number'] ?></td>
                    <td><?= $row['brand'] ?></td>
                    <td><?= $row['cpu'] ?></td>
                    <td><?= $row['ram'] ?></td>
                    <td><?= $row['video_card'] ?></td>
                    <td><?= $row['optical_drive'] ?></td>
                    <td><?= $row['eol'] ?></td>
                    <td><?= $row['second_hand_value'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-primary">← Back to Home</a>
</body>

</html>