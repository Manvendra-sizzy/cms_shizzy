<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HRMS\AttendanceLeaveSummaryService;

$service = app(AttendanceLeaveSummaryService::class);

echo "Testing Proportionate Leave Calculation\n";
echo "========================================\n\n";

// Test Case 1: Employee joined at start of year (should get full allowance)
$allowance1 = $service->calculateProportionateAllowance(16, '2026-01-01', '2026-01-01', '2026-12-31');
echo "Test 1 - Joined Jan 1, 2026 (start of year)\n";
echo "  Annual Allowance: 16 days\n";
echo "  Proportionate: {$allowance1} days\n";
echo "  Expected: 16 days\n";
echo "  Status: " . ($allowance1 == 16 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test Case 2: Employee joined after 6 months (should get 8 days)
$allowance2 = $service->calculateProportionateAllowance(16, '2026-07-01', '2026-01-01', '2026-12-31');
echo "Test 2 - Joined July 1, 2026 (after 6 months)\n";
echo "  Annual Allowance: 16 days\n";
echo "  Proportionate: {$allowance2} days\n";
echo "  Expected: ~8 days (6 months remaining)\n";
echo "  Status: " . ($allowance2 == 8 ? "✓ PASS" : "✗ FAIL (got {$allowance2})") . "\n\n";

// Test Case 3: Employee joined in March (should get 9 months)
$allowance3 = $service->calculateProportionateAllowance(16, '2026-03-10', '2026-01-01', '2026-12-31');
echo "Test 3 - Joined March 10, 2026 (before 15th)\n";
echo "  Annual Allowance: 16 days\n";
echo "  Proportionate: {$allowance3} days\n";
echo "  Expected: ~10.67 days (10 months: Mar-Dec)\n";
echo "  Status: " . (abs($allowance3 - 10.67) < 0.1 ? "✓ PASS" : "✗ FAIL (got {$allowance3})") . "\n\n";

// Test Case 4: Employee joined in March after 15th (should get 8.5 months)
$allowance4 = $service->calculateProportionateAllowance(16, '2026-03-20', '2026-01-01', '2026-12-31');
echo "Test 4 - Joined March 20, 2026 (after 15th)\n";
echo "  Annual Allowance: 16 days\n";
echo "  Proportionate: {$allowance4} days\n";
echo "  Expected: ~9.33 days (9 months: Apr-Dec, but March counts as half)\n";
echo "  Status: " . (abs($allowance4 - 9.33) < 0.1 ? "✓ PASS" : "✗ FAIL (got {$allowance4})") . "\n\n";

// Test Case 5: Yash - joined in March (9 months example from requirements)
$allowance5 = $service->calculateProportionateAllowance(16, '2026-04-01', '2026-01-01', '2026-12-31');
echo "Test 5 - Joined April 1, 2026 (9 months remaining)\n";
echo "  Annual Allowance: 16 days\n";
echo "  Proportionate: {$allowance5} days\n";
echo "  Expected: 12 days (9 months: Apr-Dec)\n";
echo "  Status: " . ($allowance5 == 12 ? "✓ PASS" : "✗ FAIL (got {$allowance5})") . "\n\n";

// Test Case 6: No joining date (should get full allowance)
$allowance6 = $service->calculateProportionateAllowance(16, null, '2026-01-01', '2026-12-31');
echo "Test 6 - No joining date\n";
echo "  Annual Allowance: 16 days\n";
echo "  Proportionate: {$allowance6} days\n";
echo "  Expected: 16 days (full allowance)\n";
echo "  Status: " . ($allowance6 == 16 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test Case 7: Different allowance (5 days)
$allowance7 = $service->calculateProportionateAllowance(5, '2026-07-01', '2026-01-01', '2026-12-31');
echo "Test 7 - Joined July 1 with 5 days annual allowance\n";
echo "  Annual Allowance: 5 days\n";
echo "  Proportionate: {$allowance7} days\n";
echo "  Expected: ~2.5 days (6 months)\n";
echo "  Status: " . (abs($allowance7 - 2.5) < 0.1 ? "✓ PASS" : "✗ FAIL (got {$allowance7})") . "\n\n";

echo "========================================\n";
echo "Testing Complete!\n";
