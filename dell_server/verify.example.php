<?php
header('Content-Type: application/json');

// Sette opp tilkoblingen til vår nye MariaDB-database på DELL
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Legg inn MariaDB-passordet ditt her hvis du har satt et
$db_name = 'sisoft_midnight_appshop';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode(["error" => "Databaseforbindelse feilet på Dell"]));
}

// Ta imot JSON-data fra brukergrensesnittet
$input = json_decode(file_get_contents('php://input'), true);
$mock_proof = $input['mock_proof'] ?? '';
$app_id = $input['app_id'] ?? 0;

if (empty($mock_proof) || empty($app_id)) {
    echo json_encode(["status" => "error", "message" => "Manglende parametere i forespørselen"]);
    exit;
}

// 1. Send ZK-beviset til herdet Ubuntu-node (shaun3) over lokalnettet for verifisering
// MERK: BYTT UT <SHAUN3_IP_ADRESSE> med din reelle IP eller domene i produksjon!
$shaun3_url = 'http://<SHAUN3_IP_ADRESSE>:3000/api/verify-stake';
$data = json_encode(['mock_proof' => $mock_proof, 'app_id' => $app_id]);

$ch = curl_init($shaun3_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// 2. Hvis shaun3 godkjenner ZK-beviset, kjører vi den økonomiske modellen
if ($result && $result['verified'] === true) {
    $zk_hash = $result['zk_proof_hash'];
    $epoch = $result['current_epoch'];
    $expiration = date('Y-m-d H:i:s', strtotime('+30 days'));

    // Hent informasjon om appen fra MariaDB for å vite prisen
    $app_stmt = $conn->prepare("SELECT developer_id, monthly_cost_nok FROM apps WHERE app_id = ?");
    $app_stmt->bind_param("i", $app_id);
    $app_stmt->execute();
    $app_res = $app_stmt->get_result()->fetch_assoc();
    
    if (!$app_res) {
        echo json_encode(["status" => "error", "message" => "Fant ikke appen i databasen"]);
        exit;
    }

    $dev_id = $app_res['developer_id'];
    $total_cost = $app_res['monthly_cost_nok'];

    // --- PROTOKOLL-BASERT YIELD SPLITTING ---
    $amount_developer = $total_cost * 0.70; // 70 % til utvikleren
    $amount_saas = $total_cost * 0.20;      // 20 % til SaaS-leverandøren (Plattformdrift)
    $amount_iaas_dust = 0.050000;           // Simulert DUST til Seize it for node/docker-drift

    // 3. Lagre anonym tilgangsrettighet i MariaDB (Gyldig i 30 dager)
    $stmt = $conn->prepare("INSERT INTO app_access (zk_proof_hash, app_id, expiration_date, last_verified_epoch) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE expiration_date = ?");
    $stmt->bind_param("sisss", $zk_hash, $app_id, $expiration, $epoch, $expiration);
    $stmt->execute();

    // 4. Opprett splittet regnskapsbilag for Business 2.0 (Modul 5)
    $bill_stmt = $conn->prepare("INSERT INTO billing_ledger (zk_proof_hash, developer_id, app_id, amount_developer_nok, amount_saas_nok, amount_iaas_dust) VALUES (?, ?, ?, ?, ?, ?)");
    $bill_stmt->bind_param("siiidd", $zk_hash, $dev_id, $app_id, $amount_developer, $amount_saas, $amount_iaas_dust);
    $bill_stmt->execute();

    echo json_encode([
        "status" => "success",
        "message" => "Suksess! Yield-splitting utført og bilag sendt til Business 2.0.",
        "zk_proof_hash" => $zk_hash
    ]);
} else {
    echo json_encode(["status" => "denied", "message" => "ZK-verifisering feilet på shaun3"]);
}
?>

