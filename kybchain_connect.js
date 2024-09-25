/* Vanilla Javascript based on solana-connect used in kyberia.sk/id/77 frontend for exchanging Ganeshas for internal Kyberia tokens */
                let solConnect;
                document.addEventListener('DOMContentLoaded', async () => {
                        solConnect = new window.SolanaConnect();
                        solConnect.openMenu();
                        solConnect.onVisibilityChange((isOpen) => {
                          console.log("menu visible:", isOpen);
                          document.getElementById('wallet_status').innerHTML='👍 wallet '+solConnect.getWallet().publicKey+' connected 👍';
                        });
                });

            document.getElementById('sendTokens').addEventListener('click', async () => {
            const amount = document.getElementById('tokenAmount').value;
            if (!amount) {
                alert('Please enter the amount of tokens to send.');
            }
                        await sendSPLTokens(amount);
                });
        async function sendSPLTokens(amount) {
            const wallet = solConnect.getWallet();
            console.log("sending from "+wallet.publicKey);
            const decimals = 3;
            try {
                const connection=new solanaWeb3.Connection('PUT_YOUR_RPC_ENDPOINT_HERE');
                const mintAddress = new solanaWeb3.PublicKey('UGYkQ1FjWDEZhL8Sgqh1KwgKFgnHkHJqWWWtewDk6t4');
                const recipientPublicKey = new solanaWeb3.PublicKey('BorisinvziAE8pn4izvCYDrxsWJYsYJoD37BTZEkb31G');
                const senderPublicKey = new solanaWeb3.PublicKey(wallet.publicKey.toString());
                const senderTokenAccountAddress = await window.SPLToken.getAssociatedTokenAddress(
                    mintAddress,
                    senderPublicKey
                );
                console.log(senderTokenAccountAddress.toString());
                const recipientTokenAccountAddress = await window.SPLToken.getAssociatedTokenAddress(
                    mintAddress,
                    recipientPublicKey
                );
                // Create the instruction to send SPL Tokens
                let transaction = new solanaWeb3.Transaction().add(
                    window.SPLToken.createTransferInstruction(
                        senderTokenAccountAddress,
                        recipientTokenAccountAddress,
                        senderPublicKey,
                        amount * Math.pow(10,decimals) // Assuming the token has a 1:1 ratio with SOL for this example
                    )
                );

                // Sign, send, and confirm the transaction
                //transaction.feePayer = await wallet.publicKey;
                transaction.feePayer = await senderPublicKey;
                let blockhashObj = await connection.getRecentBlockhash();
                transaction.recentBlockhash = await blockhashObj.blockhash;
               console.log(transaction);
                if (transaction) {
                    console.log("signing");
                    const signedTransaction = await wallet.signTransaction(transaction);
                    const signature=await connection.sendRawTransaction(signedTransaction.serialize());
                    document.getElementById('status').innerHTML="<h1>Solana transaction signature:: <a href=https://solscan.io/tx/"+ signature+">"+signature+"</a>. <br>Redeem Your K <a href=/redeem.php>here.</a></h1>";
                    await connection.confirmTransaction(signature);
                }
            } catch (error) {
                console.error("Failed to send tokens:", error);
                document.getElementById('status').innerHTML="<h1>Failed to send tokens. Check console for details.</h1>";
            }
        }
