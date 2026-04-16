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

    'onboarding' => [
        'link_ttl_hours' => (int) env('ONBOARDING_LINK_TTL_HOURS', 72),
        'contract_link_ttl_hours' => (int) env('ONBOARDING_CONTRACT_LINK_TTL_HOURS', 120),
    ],

    'zoho_sign' => [
        /** API root, e.g. https://sign.zoho.in/api/v1 */
        'base_url' => rtrim((string) env('ZOHO_SIGN_BASE_URL', 'https://sign.zoho.in/api/v1'), '/'),
        'client_id' => env('ZOHO_SIGN_CLIENT_ID'),
        'client_secret' => env('ZOHO_SIGN_CLIENT_SECRET'),
        'redirect_uri' => env('ZOHO_SIGN_REDIRECT_URI'),
        'accounts_url' => env('ZOHO_ACCOUNTS_BASE_URL', env('ZOHO_ACCOUNTS_URL', env('ZOHO_ACCOUNTS_SERVER', 'https://accounts.zoho.in'))),
        'oauth_token_url' => env('ZOHO_SIGN_OAUTH_TOKEN_URL'),
        'refresh_token' => env('ZOHO_SIGN_REFRESH_TOKEN', env('ZOHO_REFRESH_TOKEN')),
        'webhook_secret' => env('ZOHO_SIGN_WEBHOOK_SECRET'),
        'api_domain' => env('ZOHO_SIGN_API_DOMAIN', env('ZOHO_API_DOMAIN', 'https://www.zohoapis.in')),
        'default_request_notes' => env('ZOHO_SIGN_DEFAULT_NOTES', 'Please review and sign your employment agreement.'),
        'request_expiration_days' => (int) env('ZOHO_SIGN_REQUEST_EXPIRATION_DAYS', 30),
        'email_reminders' => filter_var(env('ZOHO_SIGN_EMAIL_REMINDERS', true), FILTER_VALIDATE_BOOL),
        'reminder_period_days' => (int) env('ZOHO_SIGN_REMINDER_PERIOD_DAYS', 3),
        'verify_recipient' => filter_var(env('ZOHO_SIGN_VERIFY_RECIPIENT', false), FILTER_VALIDATE_BOOL),
        /** Optional second signer (sequential after employee). */
        'company_signatory_enabled' => filter_var(env('ZOHO_SIGN_COMPANY_SIGNATORY_ENABLED', false), FILTER_VALIDATE_BOOL),
        'company_signatory_name' => env('ZOHO_SIGN_COMPANY_SIGNATORY_NAME'),
        'company_signatory_email' => env('ZOHO_SIGN_COMPANY_SIGNATORY_EMAIL'),
        /** Override page index (0-based); leave unset to use last page of the generated PDF. */
        'signature_page_no' => env('ZOHO_SIGN_SIGNATURE_PAGE_NO'),
        /** Signature placement for submit (PDF coordinates). */
        'signature_field' => [
            'x_coord' => (int) env('ZOHO_SIGN_SIGNATURE_X', 72),
            'y_coord' => (int) env('ZOHO_SIGN_SIGNATURE_Y', 620),
            'abs_width' => (int) env('ZOHO_SIGN_SIGNATURE_WIDTH', 160),
            'abs_height' => (int) env('ZOHO_SIGN_SIGNATURE_HEIGHT', 28),
        ],
        'signature_field_secondary_y' => (int) env('ZOHO_SIGN_SIGNATURE_Y_SECONDARY', 520),
    ],

];
