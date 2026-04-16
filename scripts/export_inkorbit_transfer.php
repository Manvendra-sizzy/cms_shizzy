<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function sqlQuote(mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return "'" . str_replace(
        ["\\", "\0", "\n", "\r", "'", '"', "\x1a"],
        ["\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z"],
        (string) $value
    ) . "'";
}

function buildBulkInsert(string $table, array $columns, Collection $rows): string
{
    if ($rows->isEmpty()) {
        return '';
    }

    $columnSql = implode(', ', array_map(static fn (string $c): string => "`{$c}`", $columns));
    $valueSql = $rows->map(static function (array $row) use ($columns): string {
        $values = [];
        foreach ($columns as $column) {
            $values[] = sqlQuote($row[$column] ?? null);
        }

        return '(' . implode(', ', $values) . ')';
    })->implode(",\n");

    return "INSERT INTO `{$table}` ({$columnSql}) VALUES\n{$valueSql};\n";
}

$domain = '@inkorbit.in';
$timestamp = date('Ymd_His');
$exportRoot = storage_path("app/exports/inkorbit-transfer-{$timestamp}");
$docsRoot = $exportRoot . '/documents';

if (!is_dir($exportRoot) && !mkdir($exportRoot, 0775, true) && !is_dir($exportRoot)) {
    fwrite(STDERR, "Unable to create export directory: {$exportRoot}\n");
    exit(1);
}

if (!is_dir($docsRoot) && !mkdir($docsRoot, 0775, true) && !is_dir($docsRoot)) {
    fwrite(STDERR, "Unable to create documents directory: {$docsRoot}\n");
    exit(1);
}

$profiles = DB::table('employee_profiles')
    ->where('official_email', 'like', '%' . $domain)
    ->orderBy('id')
    ->get();

if ($profiles->isEmpty()) {
    fwrite(STDOUT, "No employees found for domain {$domain}\n");
    exit(0);
}

$profileIds = $profiles->pluck('id')->values()->all();
$userIds = $profiles->pluck('user_id')->filter()->values()->all();

$users = DB::table('users')
    ->whereIn('id', $userIds)
    ->orderBy('id')
    ->get();

$teams = DB::table('employee_profile_team')
    ->whereIn('employee_profile_id', $profileIds)
    ->orderBy('id')
    ->get();

$uploadedDocs = DB::table('employee_uploaded_documents')
    ->whereIn('employee_profile_id', $profileIds)
    ->orderBy('id')
    ->get();

$profileById = $profiles->keyBy('id');
$officialEmailByProfileId = $profiles->mapWithKeys(static fn ($p): array => [(int) $p->id => (string) $p->official_email]);

// Collect all referenced document paths that should be copied.
$docPaths = collect();
foreach ($profiles as $profile) {
    foreach (['pan_card_path', 'id_card_path', 'profile_image_path', 'signed_contract_path'] as $field) {
        $path = trim((string) ($profile->{$field} ?? ''));
        if ($path !== '') {
            $docPaths->push($path);
        }
    }
}
foreach ($uploadedDocs as $doc) {
    $path = trim((string) ($doc->file_path ?? ''));
    if ($path !== '') {
        $docPaths->push($path);
    }
}
$docPaths = $docPaths->unique()->values();

$publicStorageRoot = storage_path('app/public');
$copiedFiles = [];
$missingFiles = [];

foreach ($docPaths as $relativePath) {
    $cleanRelativePath = ltrim((string) $relativePath, '/');
    $sourcePath = $publicStorageRoot . '/' . $cleanRelativePath;
    $targetPath = $docsRoot . '/' . $cleanRelativePath;

    if (!is_file($sourcePath)) {
        $missingFiles[] = $cleanRelativePath;
        continue;
    }

    $targetDir = dirname($targetPath);
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        fwrite(STDERR, "Unable to create target directory: {$targetDir}\n");
        exit(1);
    }

    if (!copy($sourcePath, $targetPath)) {
        fwrite(STDERR, "Failed to copy file: {$cleanRelativePath}\n");
        exit(1);
    }

    $copiedFiles[] = $cleanRelativePath;
}

// Create SQL export.
$sql = [];
$sql[] = "-- Inkorbit employee transfer export generated at " . date('c');
$sql[] = "-- Source filter: employee_profiles.official_email LIKE '%{$domain}'";
$sql[] = 'START TRANSACTION;';
$sql[] = '';

$tmpUsersColumns = ['name', 'email', 'password', 'role', 'email_verified_at', 'remember_token', 'created_at', 'updated_at'];
$tmpUsersRows = $users->map(static fn ($u): array => [
    'name' => $u->name,
    'email' => $u->email,
    'password' => $u->password,
    'role' => $u->role,
    'email_verified_at' => $u->email_verified_at,
    'remember_token' => $u->remember_token,
    'created_at' => $u->created_at,
    'updated_at' => $u->updated_at,
]);

$sql[] = "DROP TEMPORARY TABLE IF EXISTS `tmp_inkorbit_users`;";
$sql[] = <<<SQL
CREATE TEMPORARY TABLE `tmp_inkorbit_users` (
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(32) NOT NULL,
  `email_verified_at` timestamp NULL,
  `remember_token` varchar(100) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);
SQL;
$sql[] = buildBulkInsert('tmp_inkorbit_users', $tmpUsersColumns, $tmpUsersRows);
$sql[] = <<<SQL
INSERT INTO `users` (`name`, `email`, `password`, `role`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`)
SELECT `name`, `email`, `password`, `role`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`
FROM `tmp_inkorbit_users`
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `password` = VALUES(`password`),
  `role` = VALUES(`role`),
  `email_verified_at` = VALUES(`email_verified_at`),
  `remember_token` = VALUES(`remember_token`),
  `updated_at` = VALUES(`updated_at`);
SQL;
$sql[] = '';

$tmpProfilesColumns = [
    'official_email',
    'employee_id',
    'department_id',
    'team_id',
    'designation_id',
    'reporting_manager_official_email',
    'personal_email',
    'personal_mobile',
    'joining_date',
    'pan_card_path',
    'id_card_path',
    'profile_image_path',
    'signed_contract_path',
    'bank_account_number',
    'bank_ifsc_code',
    'bank_name',
    'status',
    'inactive_at',
    'inactive_remarks',
    'separation_type',
    'separation_effective_at',
    'separation_remarks',
    'current_salary',
    'is_remote',
    'date_of_birth',
    'phone',
    'address',
    'emergency_contact_name',
    'emergency_contact_phone',
    'created_at',
    'updated_at',
];

$tmpProfileRows = $profiles->map(static function ($p) use ($profileById): array {
    $managerEmail = null;
    if (!empty($p->reporting_manager_employee_profile_id)) {
        $manager = $profileById->get((int) $p->reporting_manager_employee_profile_id);
        $managerEmail = $manager->official_email ?? null;
    }

    return [
        'official_email' => $p->official_email,
        'employee_id' => $p->employee_id,
        'department_id' => $p->department_id,
        'team_id' => $p->team_id,
        'designation_id' => $p->designation_id,
        'reporting_manager_official_email' => $managerEmail,
        'personal_email' => $p->personal_email,
        'personal_mobile' => $p->personal_mobile,
        'joining_date' => $p->joining_date,
        'pan_card_path' => $p->pan_card_path,
        'id_card_path' => $p->id_card_path,
        'profile_image_path' => $p->profile_image_path,
        'signed_contract_path' => $p->signed_contract_path,
        'bank_account_number' => $p->bank_account_number,
        'bank_ifsc_code' => $p->bank_ifsc_code,
        'bank_name' => $p->bank_name,
        'status' => $p->status,
        'inactive_at' => $p->inactive_at,
        'inactive_remarks' => $p->inactive_remarks,
        'separation_type' => $p->separation_type,
        'separation_effective_at' => $p->separation_effective_at,
        'separation_remarks' => $p->separation_remarks,
        'current_salary' => $p->current_salary,
        'is_remote' => $p->is_remote,
        'date_of_birth' => $p->date_of_birth,
        'phone' => $p->phone,
        'address' => $p->address,
        'emergency_contact_name' => $p->emergency_contact_name,
        'emergency_contact_phone' => $p->emergency_contact_phone,
        'created_at' => $p->created_at,
        'updated_at' => $p->updated_at,
    ];
});

$sql[] = "DROP TEMPORARY TABLE IF EXISTS `tmp_inkorbit_profiles`;";
$sql[] = <<<SQL
CREATE TEMPORARY TABLE `tmp_inkorbit_profiles` (
  `official_email` varchar(255) NOT NULL,
  `employee_id` varchar(32) NOT NULL,
  `department_id` bigint unsigned NULL,
  `team_id` bigint unsigned NULL,
  `designation_id` bigint unsigned NULL,
  `reporting_manager_official_email` varchar(255) NULL,
  `personal_email` varchar(255) NULL,
  `personal_mobile` varchar(32) NULL,
  `joining_date` date NULL,
  `pan_card_path` varchar(255) NULL,
  `id_card_path` varchar(255) NULL,
  `profile_image_path` varchar(255) NULL,
  `signed_contract_path` varchar(255) NULL,
  `bank_account_number` varchar(64) NULL,
  `bank_ifsc_code` varchar(32) NULL,
  `bank_name` varchar(255) NULL,
  `status` varchar(32) NULL,
  `inactive_at` date NULL,
  `inactive_remarks` text NULL,
  `separation_type` varchar(32) NULL,
  `separation_effective_at` date NULL,
  `separation_remarks` text NULL,
  `current_salary` decimal(12,2) NULL,
  `is_remote` tinyint(1) NULL,
  `date_of_birth` date NULL,
  `phone` varchar(32) NULL,
  `address` text NULL,
  `emergency_contact_name` varchar(255) NULL,
  `emergency_contact_phone` varchar(32) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);
SQL;
$sql[] = buildBulkInsert('tmp_inkorbit_profiles', $tmpProfilesColumns, $tmpProfileRows);
$sql[] = <<<SQL
INSERT INTO `employee_profiles` (
  `user_id`,
  `employee_id`,
  `department_id`,
  `team_id`,
  `designation_id`,
  `reporting_manager_employee_profile_id`,
  `personal_email`,
  `personal_mobile`,
  `official_email`,
  `joining_date`,
  `pan_card_path`,
  `id_card_path`,
  `profile_image_path`,
  `signed_contract_path`,
  `bank_account_number`,
  `bank_ifsc_code`,
  `bank_name`,
  `status`,
  `inactive_at`,
  `inactive_remarks`,
  `separation_type`,
  `separation_effective_at`,
  `separation_remarks`,
  `current_salary`,
  `is_remote`,
  `date_of_birth`,
  `phone`,
  `address`,
  `emergency_contact_name`,
  `emergency_contact_phone`,
  `created_at`,
  `updated_at`
)
SELECT
  u.id AS user_id,
  p.`employee_id`,
  p.`department_id`,
  p.`team_id`,
  p.`designation_id`,
  mgr.id AS reporting_manager_employee_profile_id,
  p.`personal_email`,
  p.`personal_mobile`,
  p.`official_email`,
  p.`joining_date`,
  p.`pan_card_path`,
  p.`id_card_path`,
  p.`profile_image_path`,
  p.`signed_contract_path`,
  p.`bank_account_number`,
  p.`bank_ifsc_code`,
  p.`bank_name`,
  p.`status`,
  p.`inactive_at`,
  p.`inactive_remarks`,
  p.`separation_type`,
  p.`separation_effective_at`,
  p.`separation_remarks`,
  p.`current_salary`,
  p.`is_remote`,
  p.`date_of_birth`,
  p.`phone`,
  p.`address`,
  p.`emergency_contact_name`,
  p.`emergency_contact_phone`,
  p.`created_at`,
  p.`updated_at`
FROM `tmp_inkorbit_profiles` p
INNER JOIN `users` u ON u.`email` = p.`official_email`
LEFT JOIN `employee_profiles` mgr ON mgr.`official_email` = p.`reporting_manager_official_email`
ON DUPLICATE KEY UPDATE
  `user_id` = VALUES(`user_id`),
  `employee_id` = VALUES(`employee_id`),
  `department_id` = VALUES(`department_id`),
  `team_id` = VALUES(`team_id`),
  `designation_id` = VALUES(`designation_id`),
  `reporting_manager_employee_profile_id` = VALUES(`reporting_manager_employee_profile_id`),
  `personal_email` = VALUES(`personal_email`),
  `personal_mobile` = VALUES(`personal_mobile`),
  `official_email` = VALUES(`official_email`),
  `joining_date` = VALUES(`joining_date`),
  `pan_card_path` = VALUES(`pan_card_path`),
  `id_card_path` = VALUES(`id_card_path`),
  `profile_image_path` = VALUES(`profile_image_path`),
  `signed_contract_path` = VALUES(`signed_contract_path`),
  `bank_account_number` = VALUES(`bank_account_number`),
  `bank_ifsc_code` = VALUES(`bank_ifsc_code`),
  `bank_name` = VALUES(`bank_name`),
  `status` = VALUES(`status`),
  `inactive_at` = VALUES(`inactive_at`),
  `inactive_remarks` = VALUES(`inactive_remarks`),
  `separation_type` = VALUES(`separation_type`),
  `separation_effective_at` = VALUES(`separation_effective_at`),
  `separation_remarks` = VALUES(`separation_remarks`),
  `current_salary` = VALUES(`current_salary`),
  `is_remote` = VALUES(`is_remote`),
  `date_of_birth` = VALUES(`date_of_birth`),
  `phone` = VALUES(`phone`),
  `address` = VALUES(`address`),
  `emergency_contact_name` = VALUES(`emergency_contact_name`),
  `emergency_contact_phone` = VALUES(`emergency_contact_phone`),
  `updated_at` = VALUES(`updated_at`);
SQL;
$sql[] = '';

$tmpTeamColumns = ['employee_official_email', 'organization_team_id', 'created_at', 'updated_at'];
$tmpTeamRows = $teams
    ->filter(static fn ($row): bool => isset($officialEmailByProfileId[(int) $row->employee_profile_id]))
    ->map(static function ($row) use ($officialEmailByProfileId): array {
        return [
        'employee_official_email' => $officialEmailByProfileId[(int) $row->employee_profile_id],
        'organization_team_id' => $row->organization_team_id,
        'created_at' => $row->created_at,
        'updated_at' => $row->updated_at,
        ];
    });

$sql[] = "DROP TEMPORARY TABLE IF EXISTS `tmp_inkorbit_profile_teams`;";
$sql[] = <<<SQL
CREATE TEMPORARY TABLE `tmp_inkorbit_profile_teams` (
  `employee_official_email` varchar(255) NOT NULL,
  `organization_team_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);
SQL;
$sql[] = buildBulkInsert('tmp_inkorbit_profile_teams', $tmpTeamColumns, $tmpTeamRows);
$sql[] = <<<SQL
INSERT INTO `employee_profile_team` (`employee_profile_id`, `organization_team_id`, `created_at`, `updated_at`)
SELECT ep.`id`, t.`organization_team_id`, t.`created_at`, t.`updated_at`
FROM `tmp_inkorbit_profile_teams` t
INNER JOIN `employee_profiles` ep ON ep.`official_email` = t.`employee_official_email`
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);
SQL;
$sql[] = '';

$tmpUploadedColumns = ['employee_official_email', 'title', 'file_path', 'uploaded_at', 'created_at', 'updated_at'];
$tmpUploadedRows = $uploadedDocs
    ->filter(static fn ($row): bool => isset($officialEmailByProfileId[(int) $row->employee_profile_id]))
    ->map(static function ($row) use ($officialEmailByProfileId): array {
        return [
        'employee_official_email' => $officialEmailByProfileId[(int) $row->employee_profile_id],
        'title' => $row->title,
        'file_path' => $row->file_path,
        'uploaded_at' => $row->uploaded_at,
        'created_at' => $row->created_at,
        'updated_at' => $row->updated_at,
        ];
    });

$sql[] = "DROP TEMPORARY TABLE IF EXISTS `tmp_inkorbit_uploaded_documents`;";
$sql[] = <<<SQL
CREATE TEMPORARY TABLE `tmp_inkorbit_uploaded_documents` (
  `employee_official_email` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);
SQL;
$sql[] = buildBulkInsert('tmp_inkorbit_uploaded_documents', $tmpUploadedColumns, $tmpUploadedRows);
$sql[] = <<<SQL
INSERT INTO `employee_uploaded_documents` (
  `employee_profile_id`,
  `title`,
  `file_path`,
  `uploaded_by_user_id`,
  `uploaded_at`,
  `created_at`,
  `updated_at`
)
SELECT
  ep.`id`,
  d.`title`,
  d.`file_path`,
  NULL AS uploaded_by_user_id,
  d.`uploaded_at`,
  d.`created_at`,
  d.`updated_at`
FROM `tmp_inkorbit_uploaded_documents` d
INNER JOIN `employee_profiles` ep ON ep.`official_email` = d.`employee_official_email`
WHERE NOT EXISTS (
  SELECT 1
  FROM `employee_uploaded_documents` eud
  WHERE eud.`employee_profile_id` = ep.`id`
    AND eud.`title` = d.`title`
    AND eud.`file_path` = d.`file_path`
);
SQL;
$sql[] = '';
$sql[] = 'COMMIT;';

$sqlPath = $exportRoot . '/inkorbit_employees_transfer.sql';
file_put_contents($sqlPath, implode("\n", $sql));

$zipPath = $exportRoot . '/inkorbit_documents.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Unable to create zip file: {$zipPath}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($docsRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $fileInfo) {
    /** @var SplFileInfo $fileInfo */
    $absolutePath = $fileInfo->getPathname();
    $relativePath = substr($absolutePath, strlen($docsRoot) + 1);
    $zip->addFile($absolutePath, $relativePath);
}

$zip->close();

$manifestPath = $exportRoot . '/export_manifest.txt';
$manifest = [];
$manifest[] = "Domain filter: %{$domain}";
$manifest[] = 'Employees exported: ' . $profiles->count();
$manifest[] = 'Users exported: ' . $users->count();
$manifest[] = 'Team mappings exported: ' . $tmpTeamRows->count();
$manifest[] = 'Uploaded-doc metadata exported: ' . $tmpUploadedRows->count();
$manifest[] = 'Document paths referenced: ' . $docPaths->count();
$manifest[] = 'Document files copied: ' . count($copiedFiles);
$manifest[] = 'Document files missing: ' . count($missingFiles);
$manifest[] = '';
$manifest[] = 'Exported official emails:';
foreach ($profiles->pluck('official_email') as $email) {
    $manifest[] = '- ' . $email;
}
$manifest[] = '';
$manifest[] = 'Missing files:';
if (count($missingFiles) === 0) {
    $manifest[] = '- none';
} else {
    foreach ($missingFiles as $missingFile) {
        $manifest[] = '- ' . $missingFile;
    }
}

file_put_contents($manifestPath, implode("\n", $manifest) . "\n");

fwrite(STDOUT, "Export completed\n");
fwrite(STDOUT, "SQL: {$sqlPath}\n");
fwrite(STDOUT, "ZIP: {$zipPath}\n");
fwrite(STDOUT, "Manifest: {$manifestPath}\n");
