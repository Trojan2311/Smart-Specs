<?php
require 'db.php';

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
        'CPU' => $header['SystemDescription'] ?? 'Unknown CPU',
        'RAM' => '16GB',
        'Video Card' => 'Intel Iris Xe',
        'Optical Drive' => 'None',
        'EOL' => $entitlement['EndDate'] ?? 'Unknown',
        'Second Hand Value' => '$400'
    ];
}

function fetchSpecs($brand, $serial)
{
    global $dellClientId, $dellClientSecret, $dellApiKey;

    if ($brand === 'Dell') {
        return getDellSpecsFromAPI($serial, $dellClientId, $dellClientSecret, $dellApiKey);
    }

    // Future: Add HP/Apple logic here
    return [];
}

$brand = detectBrand($serial);
$specs = fetchSpecs($brand, $serial);

if (!$serial || $brand === 'Unknown' || empty($specs)) {
    echo "<p style='color:red;'>Invalid serial or unknown brand.</p><a href='index.php'>← Back</a>";
    exit;
}

// Insert into DB
try {
    $stmt = $conn->prepare("INSERT IGNORE INTO assets (serial_number, brand, cpu, ram, video_card, optical_drive, eol, second_hand_value) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$serial, $brand, $specs['CPU'], $specs['RAM'], $specs['Video Card'], $specs['Optical Drive'], $specs['EOL'], $specs['Second Hand Value']]);
} catch (PDOException $e) {
    echo "Error inserting: " . $e->getMessage();
    exit;
}

// Display output
echo "<h2>Specs for Serial: $serial</h2><table border='1' cellpadding='10'>";
echo "<tr><th>Component</th><th>Details</th></tr>";
foreach ($specs as $key => $val) {
    echo "<tr><td><b>$key</b></td><td>$val</td></tr>";
}
echo "</table><br><a href='index.php'>← Back</a>";
?>