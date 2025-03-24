<?php
session_start();
include 'db.php';

// PARTH Dell API credentials
$dellClientId = "PARTH_CLIENT_ID";
$dellClientSecret = "PARTH_CLIENT_SECRET";
$dellApiKey = "PARTH_API_KEY";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? 'user';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serial = strtoupper(trim($_POST['serial_number'] ?? ''));

    function detectBrand($serial)
    {
        if (str_starts_with($serial, 'DL'))
            return 'Dell';
        if (str_starts_with($serial, 'HP'))
            return 'HP';
        if (str_starts_with($serial, 'C0') || str_starts_with($serial, 'F'))
            return 'Apple';
        return 'Unknown';
    }

    function getDellSpecsFromAPI($serviceTag, $clientId, $clientSecret, $apiKey)
    {
        // 1. Get OAuth2 Token
        $auth = base64_encode("$clientId:$clientSecret");

        $ch = curl_init("https://apigtwb2c.us.dell.com/auth/oauth/v2/token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic $auth",
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($response['access_token'])) {
            return ['error' => 'Failed to authenticate with Dell API'];
        }

        $token = $response['access_token'];

        // 2. Fetch Asset Details
        $url = "https://apigtwb2c.us.dell.com/asset-lookup-utility/assetservice/v1/getassetdetails/$serviceTag?apikey=$apiKey";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);

        // 3. Extract values (structure may vary)
        $header = $data['AssetHeaderData'] ?? [];
        $entitlement = $data['AssetEntitlementData'][0] ?? [];

        return [
            'Make' => 'Dell',
            'Model' => $header['SystemModel'] ?? 'Unknown',
            'Submodel' => $header['SystemType'] ?? '',
            'FormFactor' => 'Laptop',
            'Colour' => 'Black',
            'Country' => $header['ShipCountry'] ?? 'Unknown',
            'WarrantyStart' => $entitlement['StartDate'] ?? null,
            'WarrantyEnd' => $entitlement['EndDate'] ?? null,
            'CPU' => $header['SystemDescription'] ?? 'Unknown CPU',
            'Memory' => '16GB',
            'HDDCount' => '1',
            'Disk' => '512GB SSD',
            'ScreenSize' => '15.6"',
            'ScreenType' => 'FHD IPS',
            'VideoCard' => 'Intel Iris Xe',
            'OpticalDrive' => 'None',
            'Battery' => '6 Cell',
            'COA' => 'Windows 11 Pro',
            'Webcam' => 'Yes'
        ];
    }

    function fetchSpecs($brand)
    {
        global $serial, $dellClientId, $dellClientSecret, $dellApiKey;

        switch ($brand) {
            case 'Dell':
                return getDellSpecsFromAPI($serial, $dellClientId, $dellClientSecret, $dellApiKey);
            case 'HP':
                return [
                    'Make' => 'HP',
                    'Model' => 'EliteBook 840',
                    'Submodel' => '840 G8',
                    'FormFactor' => 'Laptop',
                    'Colour' => 'Silver',
                    'Country' => 'USA',
                    'WarrantyStart' => '2022-01-01',
                    'WarrantyEnd' => '2025-01-01',
                    'CPU' => 'Intel Core i5',
                    'Memory' => '8GB DDR4',
                    'HDDCount' => '1',
                    'Disk' => '256GB SSD',
                    'ScreenSize' => '14"',
                    'ScreenType' => 'FHD IPS',
                    'VideoCard' => 'Intel UHD Graphics',
                    'OpticalDrive' => 'None',
                    'Battery' => '4 Cell',
                    'COA' => 'Windows 10 Pro',
                    'Webcam' => 'Yes'
                ];
            default:
                return [];
        }
    }

    $brand = detectBrand($serial);
    $specs = fetchSpecs($brand);

    if ($serial && !empty($specs)) {
        try {
            // Check if serial number already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM assets WHERE serial_number = ?");
            $stmt->execute([$serial]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                echo "<p style='color:red;'>Serial number already exists in the database.</p>";
            } else {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO assets (serial_number, brand, model, submodel, item_type, colour, country_of_origin, warranty_start, warranty_end, cpu, ram, hdd_count, disk, screen_size, screen_type, video_card, optical_drive, battery, coa, webcam) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $serial,
                    $specs['Make'] ?? 'Unknown',
                    $specs['Model'] ?? 'Unknown',
                    $specs['Submodel'] ?? '',
                    $specs['FormFactor'] ?? 'Unknown',
                    $specs['Colour'] ?? 'Unknown',
                    $specs['Country'] ?? 'Unknown',
                    $specs['WarrantyStart'] ?? null,
                    $specs['WarrantyEnd'] ?? null,
                    $specs['CPU'] ?? 'Unknown',
                    $specs['Memory'] ?? 'Unknown',
                    $specs['HDDCount'] ?? 'Unknown',
                    $specs['Disk'] ?? 'Unknown',
                    $specs['ScreenSize'] ?? 'Unknown',
                    $specs['ScreenType'] ?? 'Unknown',
                    $specs['VideoCard'] ?? 'Unknown',
                    $specs['OpticalDrive'] ?? 'Unknown',
                    $specs['Battery'] ?? 'Unknown',
                    $specs['COA'] ?? 'Unknown',
                    $specs['Webcam'] ?? 'Unknown'
                ]);
                echo "<p style='color:green;'>Serial number successfully added to the database.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red;'>Invalid serial number or no specs found.</p>";
    }
}

$records = $conn->query("SELECT * FROM assets ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
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
            <h3 class="text-primary mb-3">🧾 Smart Spec Sheet</h3>

            <form method="POST" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-10">
                        <input type="text" name="serial_number" class="form-control" placeholder="Enter Serial Number"
                            required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Fetch & Save</button>
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
                            <th>Battery</th>
                            <th>COA</th>
                            <th>Webcam</th>
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
                                <td><?= htmlspecialchars($row['battery']) ?></td>
                                <td><?= htmlspecialchars($row['coa']) ?></td>
                                <td><?= htmlspecialchars($row['webcam']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>