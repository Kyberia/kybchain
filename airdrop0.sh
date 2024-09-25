#Kybchain / GANESHA community airdrop solana-cli bash script
#used in seeding phases of the project to distribute tokens to Kyberia community members listed in the AIDROP.csv file having form
#user_id;solana_address;token_amount;user_login

token="UGYkQ1FjWDEZhL8Sgqh1KwgKFgnHkHJqWWWtewDk6t4"

while IFS=';' read -r id address amount nick; do
  airdrop_amount=$(echo "$amount * 1" | bc) # Calculate 1.25 times the amount in the third column
  echo "Processing $address with amount $airdrop_amount"
  # Check if the address has an associated token account for the given mint
  associated_address=$(spl-token accounts $address | grep $token | awk '{print $1}')
  if [ -z "$associated_address" ]; then
    # If no associated account, create one (assumes you're willing to pay for the rent-exemption)
    spl-token --fee-payer WALLET.json create-account --owner $address $token
  fi
  # Perform the airdrop
  spl-token transfer --fund-recipient $token $airdrop_amount $address --allow-unfunded-recipient
done < AIRDROP.csv
