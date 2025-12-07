# Fund Transfer API - CURL Test Commands
# Run these commands in PowerShell using curl.exe

Write-Host "=== Fund Transfer API - CURL Test Examples ===" -ForegroundColor Cyan
Write-Host ""

# 1. Health Check
Write-Host "1. Health Check:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8000/health" -ForegroundColor Gray
curl.exe http://localhost:8000/health
Write-Host ""

# 2. List All Accounts
Write-Host "2. List All Active Accounts:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8000/api/v1/accounts" -ForegroundColor Gray
curl.exe http://localhost:8000/api/v1/accounts
Write-Host ""

# 3. Get Account Details
Write-Host "3. Get Account Details:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8000/api/v1/accounts/ACC0000001" -ForegroundColor Gray
curl.exe http://localhost:8000/api/v1/accounts/ACC0000001
Write-Host ""

# 4. Get Account Balance
Write-Host "4. Get Account Balance:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8000/api/v1/accounts/ACC0000001/balance" -ForegroundColor Gray
curl.exe http://localhost:8000/api/v1/accounts/ACC0000001/balance
Write-Host ""

# 5. Create a Transfer (using JSON file)
Write-Host "5. Create a Transfer:" -ForegroundColor Yellow
Write-Host "   curl.exe -X POST http://localhost:8000/api/v1/transfers -H 'Content-Type: application/json' --data @test-transfer.json" -ForegroundColor Gray
@'
{
  "sourceAccountNumber": "ACC0000001",
  "destinationAccountNumber": "ACC0000002",
  "amount": "25.00",
  "description": "Test payment"
}
'@ | Out-File -FilePath "quick-transfer.json" -Encoding UTF8
$result = curl.exe -X POST http://localhost:8000/api/v1/transfers -H "Content-Type: application/json" --data @quick-transfer.json
$result
$refNumber = ($result | ConvertFrom-Json).data.referenceNumber
Write-Host ""

# 6. Get Transaction by Reference Number
Write-Host "6. Get Transaction Details:" -ForegroundColor Yellow
Write-Host "   curl.exe http://localhost:8000/api/v1/transfers/$refNumber" -ForegroundColor Gray
curl.exe "http://localhost:8000/api/v1/transfers/$refNumber"
Write-Host ""

# 7. Get Account Transaction History
Write-Host "7. Get Account Transaction History:" -ForegroundColor Yellow
Write-Host "   curl.exe 'http://localhost:8000/api/v1/transfers/account/ACC0000001?limit=10'" -ForegroundColor Gray
curl.exe "http://localhost:8000/api/v1/transfers/account/ACC0000001?limit=10"
Write-Host ""

# 8. Test Error - Insufficient Funds
Write-Host "8. Test Error - Insufficient Funds:" -ForegroundColor Yellow
Write-Host "   curl.exe -X POST http://localhost:8000/api/v1/transfers -H 'Content-Type: application/json' --data @test-insufficient.json" -ForegroundColor Gray
@'
{
  "sourceAccountNumber": "ACC0000001",
  "destinationAccountNumber": "ACC0000002",
  "amount": "99999.00",
  "description": "Should fail"
}
'@ | Out-File -FilePath "test-error.json" -Encoding UTF8
curl.exe -X POST http://localhost:8000/api/v1/transfers -H "Content-Type: application/json" --data @test-error.json
Write-Host ""

Write-Host "=== All Tests Complete ===" -ForegroundColor Green
