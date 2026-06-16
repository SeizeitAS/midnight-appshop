const express = require('express');
const app = express();
const PORT = 3000;

app.use(express.json());

app.post('/api/verify-stake', (req, res) => {
    const { mock_proof, app_id } = req.body;

    console.log(`\n[shaun3] Mottok verifiseringsforespørsel for Midnight Appshop.`);
    console.log(`[shaun3] Sjekker ZK-bevis for tilgang til app_id: ${app_id}`);

    if (mock_proof === "user_pledge_680_ada") {
        const simulated_zk_hash = require('crypto').randomBytes(32).toString('hex');
        
        console.log(`[shaun3] ✅ ZK-Proof Godkjent! Generert on-chain referanse-hash.`);
        console.log(`[shaun3] [Økonomisk Modell] Fordeler yield til Utvikler, SaaS, og DUST til IaaS.`);

        return res.json({
            status: "success",
            verified: true,
            zk_proof_hash: simulated_zk_hash,
            current_epoch: 510
        });
    }

    console.log(`[shaun3] ❌ ZK-Proof Avvist: Ugyldig bevis eller utilstrekkelig ADA-staking.`);
    return res.status(400).json({
        status: "failed",
        verified: false,
        error: "Insufficient staking or invalid Midnight ZK-proof"
    });
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`=======================================================`);
    console.log(`🚀 Midnight Appshop Kjerne-simulator er startet på shaun3!`);
    console.log(`🚀 Lytter etter anrop fra Dell på port ${PORT}...`);
    console.log(`=======================================================`);
});

