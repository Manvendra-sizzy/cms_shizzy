<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'zoho' => [
        'client_id' => env('ZOHO_CLIENT_ID'),
        'client_secret' => env('ZOHO_CLIENT_SECRET'),
        'refresh_token' => env('ZOHO_REFRESH_TOKEN'),
        'accounts_url' => env('ZOHO_ACCOUNTS_URL', env('ZOHO_ACCOUNTS_SERVER', 'https://accounts.zoho.in')),
        'api_domain' => env('ZOHO_API_DOMAIN', 'https://www.zohoapis.in'),
        'organization_id' => env('ZOHO_ORGANIZATION_ID'),
        'invoice_project_customfield_id' => env('ZOHO_INVOICE_PROJECT_CUSTOMFIELD_ID', '2454807000000565001'),
        'invoice_project_customfield_api_name' => env('ZOHO_INVOICE_PROJECT_CUSTOMFIELD_API_NAME', 'cf_project_id'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'notify_leave_applied' => filter_var(env('TELEGRAM_NOTIFY_LEAVE_APPLIED', true), FILTER_VALIDATE_BOOL),
        'notify_reimbursement_applied' => filter_var(env('TELEGRAM_NOTIFY_REIMBURSEMENT_APPLIED', true), FILTER_VALIDATE_BOOL),
        'notify_project_status_changed' => filter_var(env('TELEGRAM_NOTIFY_PROJECT_STATUS_CHANGED', true), FILTER_VALIDATE_BOOL),
    ],

];
