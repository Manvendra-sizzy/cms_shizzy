# Proportionate Leave Calculation Implementation

## Overview
This document describes the implementation of proportionate leave calculation for new and existing employees based on their joining date.

## Problem Statement
Previously, the system was assigning full leave quotas (e.g., 5 + 5 + 6 = 16 days) to all employees regardless of when they joined during the year. This was incorrect for employees joining mid-year.

## Solution

### 1. Core Calculation Logic
Added a new method `calculateProportionateAllowance()` in `app/Services/HRMS/AttendanceLeaveSummaryService.php`

**Logic:**
- If employee joined before or at the start of the year → Full allowance
- If employee joined after the year end → 0 allowance
- If employee joined mid-year → Proportionate calculation based on remaining months
- If joined on or before 15th of the month → Count that month
- If joined after 15th of the month → Don't count that month

**Formula:**
```
Remaining Months = 12 - Joining Month + 1 (if joined <= 15th)
Remaining Months = 12 - Joining Month (if joined > 15th)
Proportionate Allowance = (Annual Allowance × Remaining Months) / 12
Final Allowance = Ceiling(Proportionate Allowance)  // Round up to next whole number
```

**Rounding Rule:**
- Fractional parts >= 0.75 are rounded UP to the next whole number
- Fractional parts < 0.75 keep their decimal value
- Examples: 
  - 0.75 → 1, 2.75 → 3, 10.75 → 11
  - 0.50 → 0.5, 2.50 → 2.5, 8.0 → 8, 10.25 → 10.25

### 2. Updated Controllers

#### HREmployeesController (Admin View)
File: `app/Http/Controllers/HRMS/HREmployeesController.php`
- Updated `show()` method to calculate proportionate allowance
- Uses employee's `joining_date` or `join_date` field

#### EmployeeLeavesController (Employee View)
File: `app/Http/Controllers/HRMS/EmployeeLeavesController.php`
- Updated `index()` method to calculate proportionate allowance
- Uses employee's `joining_date` or `join_date` field

### 3. Artisan Command for Reconciliation
Created: `app/Console/Commands/RecalculateLeaveBalances.php`

**Usage:**
```bash
# Preview changes for all employees (dry run)
php artisan hrms:recalculate-leaves --dry-run

# Preview changes for specific year
php artisan hrms:recalculate-leaves --dry-run --year=2026

# Preview for specific employee
php artisan hrms:recalculate-leaves --dry-run --employee-id=123

# Apply changes (after reviewing dry run)
php artisan hrms:recalculate-leaves
```

## Examples

### Example 1: Employee joins July 1, 2026
- Annual Leave: 16 days
- Joining Month: July (7th month)
- Remaining Months: 12 - 7 + 1 = 6 months
- Proportionate: (16 × 6) / 12 = **8 days**

Breakdown by leave type:
- CL: (5 × 6) / 12 = 2.5 days
- SL: (5 × 6) / 12 = 2.5 days
- EL: (6 × 6) / 12 = 3 days
- **Total: 8 days**

### Example 2: Employee joins March 1, 2026
- Annual Leave: 16 days
- Joining Month: March (3rd month)
- Remaining Months: 12 - 3 + 1 = 10 months
- Proportionate: (16 × 10) / 12 = 13.33 days
- **Final (0.33 < 0.75, no rounding): 13.33 days**

### Example 3: Employee joins March 20, 2026 (after 15th)
- Annual Leave: 16 days
- Joining Month: March (3rd month), but after 15th
- Remaining Months: 12 - 3 = 9 months (March not counted)
- Proportionate: (16 × 9) / 12 = **12 days**

### Example 4: Yash (joined April 2026)
- Annual Leave: 16 days
- Joining Month: April (4th month)
- Remaining Months: 12 - 4 + 1 = 9 months
- Proportionate: (16 × 9) / 12 = **12 days**

### Example 5: Employee joined in previous year (e.g., Dec 2025)
- Annual Leave: 16 days
- Joined before year start → **Full 16 days**

## Testing

A test script is provided: `test_leave_calculation.php`

Run it to verify the calculation logic:
```bash
php test_leave_calculation.php
```

## Migration Steps

### For Existing Employees
1. Run the artisan command in dry-run mode first:
   ```bash
   php artisan hrms:recalculate-leaves --dry-run --year=2026
   ```

2. Review the output to ensure calculations are correct

3. Apply the changes:
   ```bash
   php artisan hrms:recalculate-leaves --year=2026
   ```

### For New Employees
No additional steps needed. The system will automatically calculate proportionate leaves when:
- Admin views employee details
- Employee views their leave balance

## Files Modified

1. `app/Services/HRMS/AttendanceLeaveSummaryService.php`
   - Added `calculateProportionateAllowance()` method

2. `app/Http/Controllers/HRMS/HREmployeesController.php`
   - Updated `show()` method to use proportionate calculation

3. `app/Http/Controllers/HRMS/EmployeeLeavesController.php`
   - Updated `index()` method to use proportionate calculation

## Files Created

1. `app/Console/Commands/RecalculateLeaveBalances.php`
   - Artisan command for batch recalculation

2. `test_leave_calculation.php`
   - Standalone test script for validation

## Important Notes

1. **No Database Changes**: The calculation is dynamic and doesn't store proportionate values in the database
2. **Backward Compatible**: Employees who joined before the current year still get full allowance
3. **Leave Requests**: Already approved leave requests are not affected
4. **Unpaid Leave**: Unpaid leave policies (is_paid = false) are not affected by this calculation

## Future Enhancements

Consider:
- Adding a configuration for the "cutoff day" (currently 15th)
- Supporting different fiscal years (currently calendar year)
- Adding leave carry-forward logic for unused proportionate leaves
- Email notifications when leave balances are recalculated
