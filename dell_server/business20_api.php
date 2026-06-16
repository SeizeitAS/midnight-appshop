<?php
header('Content-Type: application/json');

// SIKRER CLI-TESTING: Lar skriptet lese argumenter fra terminalen
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    parse_str($argv[1], $_GET);
}

// 1. Enkel API-nøkkel for sikkerhet mellom dine egne systemer
$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'MidnightSafe2026_BusinessToken') {
    echo json_encode(["status" => "error", "message" => "Uautorisert tilgang til Business 2.0 API"]) . "\n";
    exit;
}

// 2. Database-tilkobling med vår dedikerte Appshop-bruker
$db_host = 'localhost';
$db_user = 'Appshop_Admin';
$db_pass = 'MidnightSafe2026!';
$db_name = 'sisoft_midnight_appshop';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode(["error" => "Databaseforbindelse feilet for API"]));
}

// 3. Hent alle bilag som venter på å bli synkronisert med Kryptoforvaltningen (Modul 5)
$sql = "SELECT ledger_id, zk_proof_hash, developer_id, app_id, amount_developer_nok, amount_saas_nok, amount_iaas_dust FROM billing_ledger WHERE sync_status = 'pending'";
$result = $conn->query($sql);

$payouts = [];
$ids_to_update = [];

while ($row = $result->fetch_assoc()) {
    $payouts[] = $row;
    $ids_to_update[] = $row['ledger_id'];
}

// 4. Hvis det finnes nye bilag, oppdaterer vi statusen til 'synced' slik at de ikke hentes dobbelt
if (!empty($ids_to_update)) {
    $id_list = implode(',', $ids_to_update);
    $conn->query("UPDATE billing_ledger SET sync_status = 'synced' WHERE ledger_id IN ($id_list)");
}

// 5. Returner dataene i et strukturert format som Business 2.0 forstår
echo json_encode([
    "status" => "success",
    "timestamp" => time(),
    "new_records_count" => count($payouts),
    "ledger_data" => $payouts
]) . "\n";
?>

