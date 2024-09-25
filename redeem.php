<?php
/* kyberia backend bot which checks whether it received some SPL tokens from a known account, if yes, executes a corresponding internal token transaction
require_once '../vendor/autoload.php';
require_once './last_tx.php';

use Kyberia\Core\Database;
use Kyberia\Core\Kybchain;
$db = new Database();

$EXCHANGE_RATE = 1; 
//ID of Kyberia node containing solana - kyberia_id couplings
$SOL_REGISTRY_NODE = """;

// Constants
define("BOT_TOKEN_ACCOUNT", "CW3GpQh2jtBSMQMPt4bLnqW2UytxfyM4pyWfEccPdWz"); 
define("MY_TOKEN", "UGYkQ1FjWDEZhL8Sgqh1KwgKFgnHkHJqWWWtewDk6t4"); #Kybchain (Ganesha)
define("SOLANA_RPC_URL", "https://api.mainnet-beta.solana.com/");
//define("SOLANA_RPC_URL", "https://solana-mainnet.g.alchemy.com/v2/0M4efZSKSJ03gTn1A8OMtBZsLRbBmdZl");
$headers = [
    "Content-Type: application/json",
];

// Functions
function sendPostRequest($url, $payload, $headers) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
function getRecentTransactionSignatures($walletAddress, $afterSignature = LAST_TX) {
    $params = [
        $walletAddress,
        [
            "limit" => 1000 // You may adjust the limit based on your needs.
        ]
    ];

    $payload = [
        "jsonrpc" => "2.0",
        "id" => 1,
        "method" => "getSignaturesForAddress",
        "params" => $params,
    ];

    global $headers;
    $allTransactions = sendPostRequest(SOLANA_RPC_URL, $payload, $headers)["result"] ?? [];

    // If afterSignature is provided, filter transactions to include only those after the specified signature
    if ($afterSignature !== null) {
        $filteredTransactions = [];
        $found = false;
        foreach ($allTransactions as $transaction) {
            if ($transaction["signature"] == $afterSignature) {
                $found = true;
                break; // Stop once we reach the afterSignature transaction
            }
            $filteredTransactions[] = $transaction; // Add transactions until we reach afterSignature
        }
        if (!$found) {
            // If afterSignature was not found, consider handling this case, e.g., fetching more transactions or an error.
        }
        $allTransactions = $filteredTransactions;
    }

    // Transactions are already in reverse chronological order
    return $allTransactions;
}


function getTransactionDetails($signature) {
    $payload = [
        "jsonrpc" => "2.0",
        "id" => 1,
        "method" => "getTransaction",
        "params" => [
            $signature,
            "jsonParsed",
        ],
    ];
    global $headers;
    return sendPostRequest(SOLANA_RPC_URL, $payload, $headers)["result"] ?? [];
}

function parseTransactionsForTokenTransfers($signatures, $tokenMint) {
        global $db;
        global $SOL_REGISTRY_NODE;
        global $EXCHANGE_RATE;
    $transactions = []; // Array to store relevant transactions
    foreach ($signatures as $sig) {
            $transactionDetail = getTransactionDetails($sig["signature"]);
            //print_r($transactionDetail);
        foreach ($transactionDetail["transaction"]["message"]["instructions"] as $instruction) {
            // Ensure this is a SPL Token program instruction
            if ($instruction["program"] === "spl-token") {
                // Attempt to parse transfer instructions
                if ($instruction["parsed"]["type"] === "transferChecked" or $instruction["parsed"]["type"] === "transfer") {
                        $transferInfo = $instruction["parsed"]["info"];
                        //print_r($instruction);
                    if ($transferInfo["mint"] === $tokenMint or $transferInfo['destination']==BOT_TOKEN_ACCOUNT) {
                        /*echo "Transaction Signature: {$sig['signature']}\n";
                        echo "Authority: {$transferInfo['authority']}\n";
                        echo "Receiver: {$transferInfo['destination']}\n";
                        echo "Amount: {$transferInfo['tokenAmount']['amount']}\n";
                        echo "------------------------------------------------\n";
                         */
                        // Instead of returning, add the transaction to the transactions array
                        $authority=$transferInfo['authority'];
                        if (!$authority) {
                                $authority=$transferInfo['multisigAuthority'];
                        }
                        $amount=$transferInfo['tokenAmount']['amount'];
                        if (!$amount) {
                                $amount=$transferInfo['amount'];
                        }
                        /*
                        $transactions[] = [
                            'signature' => $sig['signature'],
                            'authority' => $transferInfo['authority'],
                            'receiver' => $transferInfo['destination'],
                            'amount' => $transferInfo['tokenAmount']['amount'],
                        ];
                         */
                        $q="select node_creator as user_id from nodes where node_parent=".$SOL_REGISTRY_NODE." and node_content='$authority'";
                        $set=$db->query($q);
                        while($set->next()) {
                                $user_id=$set->getInt('user_id');
                                //echo "<br>Kyberia ID: {$user_id}\n";
                                $final_amount=(1/$EXCHANGE_RATE)*$amount*0.001;
                                //echo "<br>Exchanged amount: {$final_amount}\n";
                                $memo=$EXCHANGE_RATE.'*'.substr($sig['signature'],0,59);
                                Kybchain::addTransaction(77,$user_id,$final_amount,"S",$memo);
                                $fileContent = '<?php' . PHP_EOL . "define('LAST_TX', '" . $sig['signature'] . "');" . PHP_EOL . '?>';
                                file_put_contents("./last_tx.php",$fileContent);
                                echo "<br>Yo! Transaction of user $user_id of $final_amount K is now safely stored as transaction of type ☀️ in <a target=_blank href=/id/42>Kyberia's Open Ledger</a>";
                        }
                    }
                }
            }
        }
    }
    // Return all collected transactions
    return $transactions;
}
$signatures = getRecentTransactionSignatures(BOT_TOKEN_ACCOUNT);
parseTransactionsForTokenTransfers($signatures, MY_TOKEN);
