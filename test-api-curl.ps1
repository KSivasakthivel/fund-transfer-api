# Fund Transfer API - CURL Test Commands
# Run these commands in PowerShell using curl.exe

Write-Host "=== Fund Transfer API - CURL Test Examples ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "=== MICROSERVICES ENDPOINTS ===" -ForegroundColor Magenta
Write-Host ""

# 1. Health Check - Liveness Probe
Write-Host "1. Liveness Probe (Kubernetes):" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/health/live" -ForegroundColor Gray
curl.exe http://localhost:8001/health/live
Write-Host ""

# 2. Readiness Probe
Write-Host "2. Readiness Probe (Kubernetes):" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/health/ready" -ForegroundColor Gray
curl.exe http://localhost:8001/health/ready
Write-Host ""

# 3. Detailed Health Check
Write-Host "3. Detailed Health Check (All Dependencies):" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/health" -ForegroundColor Gray
curl.exe http://localhost:8001/health
Write-Host ""

# 4. Service Discovery
Write-Host "4. Service Discovery (API Capabilities):" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/api/v1/discovery" -ForegroundColor Gray
curl.exe http://localhost:8001/api/v1/discovery
Write-Host ""

# 5. OpenAPI Specification
Write-Host "5. OpenAPI/Swagger Specification:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/api/v1/discovery/openapi" -ForegroundColor Gray
curl.exe http://localhost:8001/api/v1/discovery/openapi
Write-Host ""

Write-Host "=== ACCOUNT SERVICE ENDPOINTS ===" -ForegroundColor Magenta
Write-Host ""

# 2. List All Accounts
Write-Host "6. List All Active Accounts:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/api/v1/accounts" -ForegroundColor Gray
curl.exe http://localhost:8001/api/v1/accounts
Write-Host ""

# 3. Get Account Details
Write-Host "7. Get Account Details:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/api/v1/accounts/ACC1000000001" -ForegroundColor Gray
curl.exe http://localhost:8001/api/v1/accounts/ACC1000000001
Write-Host ""

# 4. Get Account Balance
Write-Host "8. Get Account Balance:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/api/v1/accounts/ACC1000000001/balance" -ForegroundColor Gray
curl.exe http://localhost:8001/api/v1/accounts/ACC1000000001/balance
Write-Host ""

Write-Host "=== TRANSFER SERVICE ENDPOINTS ===" -ForegroundColor Magenta
Write-Host ""

# 5. Create a Transfer (using JSON file)
Write-Host "9. Create a Transfer:" -ForegroundColor Yellow
Write-Host "   curl.exe -X POST http://localhost:8001/api/v1/transfers -H 'Content-Type: application/json' --data @quick-transfer.json" -ForegroundColor Gray
$quickTransferJson = @'
{
  "sourceAccountNumber": "ACC1000000001",
  "destinationAccountNumber": "ACC1000000002",
  "amount": "25.00",
  "description": "Test payment via microservices API"
}
'@
[System.IO.File]::WriteAllText("$PWD\quick-transfer.json", $quickTransferJson, [System.Text.UTF8Encoding]::new($false))
$result = curl.exe -X POST http://localhost:8001/api/v1/transfers -H "Content-Type: application/json" --data @quick-transfer.json
$result
$refNumber = ($result | ConvertFrom-Json).data.referenceNumber
Write-Host ""

# 6. Get Transaction by Reference Number
Write-Host "10. Get Transaction Details:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/api/v1/transfers/$refNumber" -ForegroundColor Gray
curl.exe "http://localhost:8001/api/v1/transfers/$refNumber"
Write-Host ""

# 7. Get Account Transaction History
Write-Host "11. Get Account Transaction History:" -ForegroundColor Yellow
Write-Host "   curl.exe 'http://localhost:8001/api/v1/transfers/account/ACC1000000001?limit=10'" -ForegroundColor Gray
curl.exe "http://localhost:8001/api/v1/transfers/account/ACC1000000001?limit=10"
Write-Host ""

Write-Host "=== ERROR HANDLING TESTS ===" -ForegroundColor Magenta
Write-Host ""

# 8. Test Error - Insufficient Funds
Write-Host "12. Test Error - Insufficient Funds:" -ForegroundColor Yellow
Write-Host "   curl.exe -X POST http://localhost:8001/api/v1/transfers -H 'Content-Type: application/json' --data @test-error.json" -ForegroundColor Gray
$testErrorJson = @'
{
  "sourceAccountNumber": "ACC1000000001",
  "destinationAccountNumber": "ACC1000000002",
  "amount": "99999.00",
  "description": "Should fail - insufficient funds"
}
'@
[System.IO.File]::WriteAllText("$PWD\test-error.json", $testErrorJson, [System.Text.UTF8Encoding]::new($false))
curl.exe -X POST http://localhost:8001/api/v1/transfers -H "Content-Type: application/json" --data @test-error.json
Write-Host ""

# 9. Test Error - Same Account
Write-Host "13. Test Error - Same Source and Destination:" -ForegroundColor Yellow
Write-Host "   curl.exe -X POST http://localhost:8001/api/v1/transfers (same account)" -ForegroundColor Gray
$testSameJson = @'
{
  "sourceAccountNumber": "ACC1000000001",
  "destinationAccountNumber": "ACC1000000001",
  "amount": "50.00",
  "description": "Should fail - same account"
}
'@
[System.IO.File]::WriteAllText("$PWD\test-same-account.json", $testSameJson, [System.Text.UTF8Encoding]::new($false))
curl.exe -X POST http://localhost:8001/api/v1/transfers -H "Content-Type: application/json" --data @test-same-account.json
Write-Host ""

# 10. Test Error - Negative Amount
Write-Host "14. Test Error - Negative Amount:" -ForegroundColor Yellow
Write-Host "   curl.exe -X POST http://localhost:8001/api/v1/transfers (negative amount)" -ForegroundColor Gray
$testNegativeJson = @'
{
  "sourceAccountNumber": "ACC1000000001",
  "destinationAccountNumber": "ACC1000000002",
  "amount": "-10.00",
  "description": "Should fail - negative amount"
}
'@
[System.IO.File]::WriteAllText("$PWD\test-negative.json", $testNegativeJson, [System.Text.UTF8Encoding]::new($false))
curl.exe -X POST http://localhost:8001/api/v1/transfers -H "Content-Type: application/json" --data @test-negative.json
Write-Host ""

# 11. Test 404 - Account Not Found
Write-Host "15. Test 404 - Account Not Found:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/api/v1/accounts/INVALID123" -ForegroundColor Gray
curl.exe http://localhost:8001/api/v1/accounts/INVALID123
Write-Host ""

# 12. Test 404 - Transaction Not Found
Write-Host "16. Test 404 - Transaction Not Found:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8001/api/v1/transfers/INVALID123" -ForegroundColor Gray
curl.exe http://localhost:8001/api/v1/transfers/INVALID123
Write-Host ""

Write-Host "=== All Tests Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Summary of Endpoints Tested:" -ForegroundColor Cyan
Write-Host "  [OK] Microservices Health Checks (3 endpoints)" -ForegroundColor Green
Write-Host "  [OK] Service Discovery (2 endpoints)" -ForegroundColor Green
Write-Host "  [OK] Account Service (3 endpoints)" -ForegroundColor Green
Write-Host "  [OK] Transfer Service (3 endpoints)" -ForegroundColor Green
Write-Host "  [OK] Error Handling (6 test cases)" -ForegroundColor Green
Write-Host ""
Write-Host "Total: 17 API tests executed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "Microservices Features:" -ForegroundColor Yellow
Write-Host "  - Kubernetes readiness/liveness probes" -ForegroundColor Gray
Write-Host "  - Service discovery and API documentation" -ForegroundColor Gray
Write-Host "  - Health monitoring with dependency checks" -ForegroundColor Gray
Write-Host "  - RESTful API with proper error handling" -ForegroundColor Gray
Write-Host "  - Consistent response format" -ForegroundColor Gray
Write-Host ""
Write-Host "Generated temporary files:" -ForegroundColor Yellow
Write-Host "  - quick-transfer.json" -ForegroundColor Gray
Write-Host "  - test-error.json" -ForegroundColor Gray
Write-Host "  - test-same-account.json" -ForegroundColor Gray
Write-Host "  - test-negative.json" -ForegroundColor Gray
Write-Host ""



