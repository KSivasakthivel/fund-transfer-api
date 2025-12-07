#!/bin/bash
# Fund Transfer API - CURL Test Commands
# Run this script in bash/terminal

BASE_URL="http://localhost:8000"

echo "=== Fund Transfer API - CURL Test Examples ==="
echo ""

# 1. Health Check
echo "1. Health Check:"
echo "   curl $BASE_URL/health"
curl -s $BASE_URL/health | jq '.'
echo ""

# 2. List All Accounts
echo "2. List All Active Accounts:"
echo "   curl $BASE_URL/api/v1/accounts"
curl -s $BASE_URL/api/v1/accounts | jq '.'
echo ""

# 3. Get Account Details
echo "3. Get Account Details:"
echo "   curl $BASE_URL/api/v1/accounts/ACC0000001"
curl -s $BASE_URL/api/v1/accounts/ACC0000001 | jq '.'
echo ""

# 4. Get Account Balance
echo "4. Get Account Balance:"
echo "   curl $BASE_URL/api/v1/accounts/ACC0000001/balance"
curl -s $BASE_URL/api/v1/accounts/ACC0000001/balance | jq '.'
echo ""

# 5. Create a Transfer
echo "5. Create a Transfer:"
echo '   curl -X POST $BASE_URL/api/v1/transfers -H "Content-Type: application/json" -d @transfer.json'
TRANSFER_RESPONSE=$(curl -s -X POST $BASE_URL/api/v1/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "sourceAccountNumber": "ACC0000001",
    "destinationAccountNumber": "ACC0000002",
    "amount": "25.00",
    "description": "Test payment from curl script"
  }')
echo $TRANSFER_RESPONSE | jq '.'
REF_NUMBER=$(echo $TRANSFER_RESPONSE | jq -r '.data.referenceNumber')
echo ""

# 6. Get Transaction by Reference Number
if [ ! -z "$REF_NUMBER" ] && [ "$REF_NUMBER" != "null" ]; then
  echo "6. Get Transaction Details (Reference: $REF_NUMBER):"
  echo "   curl $BASE_URL/api/v1/transfers/$REF_NUMBER"
  curl -s $BASE_URL/api/v1/transfers/$REF_NUMBER | jq '.'
  echo ""
fi

# 7. Get Account Transaction History
echo "7. Get Account Transaction History:"
echo "   curl '$BASE_URL/api/v1/transfers/account/ACC0000001?limit=10'"
curl -s "$BASE_URL/api/v1/transfers/account/ACC0000001?limit=10" | jq '.'
echo ""

# 8. Test Error - Insufficient Funds
echo "8. Test Error - Insufficient Funds:"
echo '   curl -X POST $BASE_URL/api/v1/transfers -H "Content-Type: application/json" -d @error.json'
curl -s -X POST $BASE_URL/api/v1/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "sourceAccountNumber": "ACC0000001",
    "destinationAccountNumber": "ACC0000002",
    "amount": "99999.00",
    "description": "Should fail - insufficient funds"
  }' | jq '.'
echo ""

# 9. Test Error - Invalid Account
echo "9. Test Error - Invalid Account:"
echo '   curl -X POST $BASE_URL/api/v1/transfers -H "Content-Type: application/json" -d @invalid.json'
curl -s -X POST $BASE_URL/api/v1/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "sourceAccountNumber": "INVALID123",
    "destinationAccountNumber": "ACC0000002",
    "amount": "10.00",
    "description": "Should fail - invalid account"
  }' | jq '.'
echo ""

# 10. Test Error - Same Account Transfer
echo "10. Test Error - Same Account Transfer:"
echo '   curl -X POST $BASE_URL/api/v1/transfers -H "Content-Type: application/json" -d @same-account.json'
curl -s -X POST $BASE_URL/api/v1/transfers \
  -H "Content-Type: application/json" \
  -d '{
    "sourceAccountNumber": "ACC0000001",
    "destinationAccountNumber": "ACC0000001",
    "amount": "10.00",
    "description": "Should fail - same account"
  }' | jq '.'
echo ""

echo "=== All Tests Complete ==="
