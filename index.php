<?php
session_start();
include 'db.php';

// Dell API credentials
$dellClientId = "PARTH_CLIENT_ID";
$dellClientSecret = "PARTH_CLIENT_SECRET";
$dellApiKey = "PARTH_API_KEY";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'user';

//  Mock specs integration
function generateMockSpecs($serial)
{
    return [
        'Make' => 'Dell',
        'Model' => 'Latitude 7420',
        'Submodel' => '7420 Business Edition',
        'FormFactor' => 'Laptop',
        'Colour' => 'Black',
        'Country' => 'USA',
        'WarrantyStart' => '2023-01-01',
        'WarrantyEnd' => '2026-01-01',
        'CPU' => 'Intel Core i7-1185G7',
        'Memory' => '16GB DDR4',
        'HDDCount' => '1',
        'Disk' => '512GB SSD',
        'ScreenSize' => '14"',
        'ScreenType' => 'FHD IPS',
        'VideoCard' => 'Intel Iris Xe Graphics',
        'OpticalDrive' => 'None',
        'Battery' => '4 Cell',
        'COA' => 'Windows 11 Pro',
        'Webcam' => 'Yes'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serial = strtoupper(trim($_POST['serial_number'] ?? ''));
    if ($serial) {
        // Detect brand (mock logic for Dell specs)
        function detectBrand($serial)
        {
            if (str_starts_with($serial, 'DL'))
                return 'Dell';
            return 'Unknown';
        }

        $brand = detectBrand($serial);

        if ($brand === 'Dell') {
            // Generate mock specs for Dell
            $mockSpecs = generateMockSpecs($serial);

            try {
                // Check if serial number already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM assets WHERE serial_number = ?");
                $stmt->execute([$serial]);
                $exists = $stmt->fetchColumn();

                if ($exists) {
                    echo "<script>alert('Duplicate serial number found: $serial');</script>";
                }

                // Insert mock specs into the database (allow duplicates)
                $stmt = $conn->prepare("INSERT INTO assets (serial_number, brand, model, submodel, item_type, colour, country_of_origin, warranty_start, warranty_end, cpu, ram, hdd_count, disk, screen_size, screen_type, video_card, optical_drive, battery, coa, webcam) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $serial,
                    $mockSpecs['Make'],
                    $mockSpecs['Model'],
                    $mockSpecs['Submodel'],
                    $mockSpecs['FormFactor'],
                    $mockSpecs['Colour'],
                    $mockSpecs['Country'],
                    $mockSpecs['WarrantyStart'],
                    $mockSpecs['WarrantyEnd'],
                    $mockSpecs['CPU'],
                    $mockSpecs['Memory'],
                    $mockSpecs['HDDCount'],
                    $mockSpecs['Disk'],
                    $mockSpecs['ScreenSize'],
                    $mockSpecs['ScreenType'],
                    $mockSpecs['VideoCard'],
                    $mockSpecs['OpticalDrive'],
                    $mockSpecs['Battery'],
                    $mockSpecs['COA'],
                    $mockSpecs['Webcam']
                ]);
            } catch (PDOException $e) {
                echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<script>alert('Invalid serial number or unsupported brand.');</script>";
        }
    }
}

$stmt = $conn->query("SELECT * FROM assets ORDER BY id ASC");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Smart Spec Sheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-4 mb-5">
        <div class="card shadow p-4">
            <h3 class="text-primary mb-3">📃 Smart Spec Sheet</h3>

            <form method="POST" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <input type="text" name="serial_number" class="form-control" placeholder="Enter Serial Number"
                            required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-success">Fetch & Save</button>
                    </div>
                    <div class="col-md-2 d-grid">
                        <a href="#" onclick="event.preventDefault(); document.forms[0].submit();"
                            class="btn btn-outline-primary">Legit Specs</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>#</th>
                            <th>Serial No</th>
                            <th>Make</th>
                            <th>Model</th>
                            <th>Submodel</th>
                            <th>Form Factor</th>
                            <th>Colour</th>
                            <th>Origin</th>
                            <th>Warranty Start</th>
                            <th>Warranty End</th>
                            <th>CPU</th>
                            <th>Memory</th>
                            <th>HDD Count</th>
                            <th>Disk</th>
                            <th>Screen Size</th>
                            <th>Screen Type</th>
                            <th>Video Card</th>
                            <th>Optical Drive</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $i => $row): ?>
                            <tr class="text-center">
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['serial_number']) ?></td>
                                <td><?= htmlspecialchars($row['brand']) ?></td>
                                <td><?= htmlspecialchars($row['model']) ?></td>
                                <td><?= htmlspecialchars($row['submodel']) ?></td>
                                <td><?= htmlspecialchars($row['item_type']) ?></td>
                                <td><?= htmlspecialchars($row['colour']) ?></td>
                                <td><?= htmlspecialchars($row['country_of_origin']) ?></td>
                                <td><?= htmlspecialchars($row['warranty_start']) ?></td>
                                <td><?= htmlspecialchars($row['warranty_end']) ?></td>
                                <td><?= htmlspecialchars($row['cpu']) ?></td>
                                <td><?= htmlspecialchars($row['ram']) ?></td>
                                <td><?= htmlspecialchars($row['hdd_count']) ?></td>
                                <td><?= htmlspecialchars($row['disk']) ?></td>
                                <td><?= htmlspecialchars($row['screen_size']) ?></td>
                                <td><?= htmlspecialchars($row['screen_type']) ?></td>
                                <td><?= htmlspecialchars($row['video_card']) ?></td>
                                <td><?= htmlspecialchars($row['optical_drive']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>