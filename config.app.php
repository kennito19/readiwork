<?php
/**
 * ======================================================
 * READIWORK AI – APPLICATION LOGIC & SERVICES
 * ======================================================
 * This file contains all non-secret application logic:
 * - Database connection (using constants from secrets)
 * - Helper functions
 * - Service definitions (ALL_SERVICES)
 * - API helper functions (YouVerify, M-Pesa, Metropol)
 *
 * This file is TRACKED in git (no secrets here).
 * It expects the secret constants to already be defined
 * before this file is included.
 * ======================================================
 */

/* ======================================================
 * PDO DATABASE CONNECTION
 * ====================================================== */
$pdo = null;
try {
    if (DB_HOST && DB_NAME) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_ALL_TABLES'",
            ]
        );
    }
} catch (PDOException $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    $pdo = null;
}

/* ======================================================
 * GLOBAL HELPER FUNCTIONS
 * ====================================================== */

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function client_ip(): ?string {
    return $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? null;
}

function user_agent(): ?string {
    return $_SERVER['HTTP_USER_AGENT'] ?? null;
}

/* ======================================================
 * ADMIN AUTH HELPERS
 * ====================================================== */

function admin_logged_in(): bool {
    return isset($_SESSION[ADMIN_SESSION_NAME]['id'], $_SESSION[ADMIN_SESSION_NAME]['email']);
}

function require_admin(): void {
    if (!admin_logged_in()) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }

    if (
        isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > ADMIN_SESSION_TIMEOUT
    ) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

/* ======================================================
 * VERIFICATION PROVIDERS
 * ====================================================== */
const PROVIDERS = [
    'metropol' => 'Metropol CRB',
    'youverify' => 'YouVerify'
];

/* ======================================================
 * VERIFICATION SERVICES - ALL SERVICES + YOUVERIFY
 * Each service specifies its provider and API configuration
 * ====================================================== */

const ALL_SERVICES = [
    // === METROPOL SERVICES (Existing 30 services) ===

    // === 1. NATIONAL ID VERIFICATION ===
    'id-verification' => [
        'name' => 'National ID Verification',
        'price' => 2,
        'description' => 'Basic identity verification - SIM registration, fintech onboarding',
        'category' => 'identity',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1]
        ]
    ],

    // === 2. IDENTITY CONSISTENCY CHECK ===
    'identity-check' => [
        'name' => 'Identity Consistency Check',
        'price' => 1,
        'description' => 'Detect fake IDs - name/phone/address verification',
        'category' => 'scrub',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/identity/scrub', 'report_type' => 6]
        ]
    ],

    // === 3. CRB CLEARANCE CHECK ===
    'crb-clearance' => [
        'name' => 'CRB Clearance Check',
        'price' => 1,
        'description' => 'Check if person is CRB listed - current or historical NPA',
        'category' => 'crb',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    // === 4. CRB STATUS CERTIFICATE ===
    'crb-certificate' => [
        'name' => 'CRB Status Certificate',
        'price' => 1,
        'description' => 'Digital CRB certificate with full verification',
        'category' => 'full_json',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/delinquency/status', 'report_type' => 2],
            ['endpoint' => '/report/json', 'report_type' => 14]
        ]
    ],

    // === 5. CREDIT SCORE LOOKUP ===
    'credit-score' => [
        'name' => 'Credit Score Lookup',
        'price' => 1,
        'description' => 'Metro score (0-900) for loans, BNPL, hire purchase',
        'category' => 'score',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3]
        ]
    ],

    // === 6. LOAN ELIGIBILITY CHECK ===
    'loan-eligibility' => [
        'name' => 'Loan Eligibility Check',
        'price' => 1,
        'description' => 'Credit score based loan eligibility assessment',
        'category' => 'score',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3],
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    // === 7. FULL CREDIT HISTORY CHECK ===
    'full-credit-history' => [
        'name' => 'Full Credit History',
        'price' => 1,
        'description' => 'Complete credit history for banks and asset financing',
        'category' => 'json_report',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/json', 'report_type' => 5]
        ]
    ],

    // === 8. ENHANCED CREDIT RISK REPORT ===
    'enhanced-risk-report' => [
        'name' => 'Enhanced Credit Risk Report',
        'price' => 1,
        'description' => 'Comprehensive: ID verify + scrub + credit + 12-month score trend',
        'category' => 'full_enhanced',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/credit_info_enhanced', 'report_type' => 10]
        ]
    ],

    // === 9. BORROWER RISK PROFILING ===
    'borrower-profiling' => [
        'name' => 'Borrower Risk Profiling',
        'price' => 1,
        'description' => 'Full profile with identity, scrub, credit, score history',
        'category' => 'full_enhanced',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3],
            ['endpoint' => '/report/credit_info', 'report_type' => 8]
        ]
    ],

    // === 10. TENANT SCREENING ===
    'tenant' => [
        'name' => 'Tenant Verification',
        'price' => 1,
        'description' => 'Tenant screening with CRB check',
        'category' => 'crb',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/delinquency/status', 'report_type' => 2],
            ['endpoint' => '/identity/scrub', 'report_type' => 6]
        ]
    ],

    // === 11. RENT DEFAULT HISTORY ===
    'rent-default' => [
        'name' => 'Rent Default History',
        'price' => 1,
        'description' => 'CRB delinquency check for property managers',
        'category' => 'crb',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    // === 12. EMPLOYMENT BACKGROUND SCREENING ===
    'employment-screening' => [
        'name' => 'Employment Background Screening',
        'price' => 1,
        'description' => 'Background check with employment, contacts, addresses',
        'category' => 'scrub',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/identity/scrub', 'report_type' => 6]
        ]
    ],

    // === 13. JOB APPLICANT CREDIT CHECK ===
    'job-credit-check' => [
        'name' => 'Job Applicant Credit Check',
        'price' => 1,
        'description' => 'Credit score for employment screening',
        'category' => 'score',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3],
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    // === 14. GUARANTOR VERIFICATION ===
    'guarantor-verify' => [
        'name' => 'Guarantor Verification',
        'price' => 1,
        'description' => 'Enhanced check with guarantor info',
        'category' => 'enhanced',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3],
            ['endpoint' => '/report/credit_info', 'report_type' => 8]
        ]
    ],

    // === 15. BUSINESS OWNER CREDIT CHECK ===
    'business-owner-check' => [
        'name' => 'Business Owner Credit Check',
        'price' => 1,
        'description' => 'Enhanced SME check with stakeholder info',
        'category' => 'enhanced',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3],
            ['endpoint' => '/report/credit_info', 'report_type' => 8]
        ]
    ],

    // === 16. DEBT EXPOSURE ANALYSIS ===
    'debt-exposure' => [
        'name' => 'Debt Exposure Analysis',
        'price' => 1,
        'description' => 'Account-level credit info for debt analysis',
        'category' => 'credit_info',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/credit_info', 'report_type' => 8]
        ]
    ],

    // === 17. FINANCIAL STRESS INDICATOR ===
    'financial-stress' => [
        'name' => 'Financial Stress Indicator',
        'price' => 1,
        'description' => 'Detect financial stress and over-leverage',
        'category' => 'crb',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/delinquency/status', 'report_type' => 2],
            ['endpoint' => '/score/consumer', 'report_type' => 3]
        ]
    ],

    // === 18. CREDIT REPORT PDF ===
    'credit-report-pdf' => [
        'name' => 'Credit Report (PDF)',
        'price' => 1,
        'description' => 'Official PDF credit report for legal, audit, personal records',
        'category' => 'pdf_report',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/pdf', 'report_type' => 4]
        ]
    ],

    // === 19. CREDIT REPORT JSON ===
    'credit-report-json' => [
        'name' => 'Credit Report (JSON)',
        'price' => 1,
        'description' => 'Full credit report in JSON format for systems',
        'category' => 'json_report',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/json', 'report_type' => 5]
        ]
    ],

    // === 20. CREDITWORTHINESS CERTIFICATE ===
    'creditworthiness' => [
        'name' => 'Creditworthiness Certificate',
        'price' => 1,
        'description' => 'Credit score certificate for visa, tenders, contracts',
        'category' => 'score',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3],
            ['endpoint' => '/report/json', 'report_type' => 5]
        ]
    ],

    // === 21. LOAN DEFAULTER VERIFICATION ===
    'loan-defaulter' => [
        'name' => 'Loan Defaulter Check',
        'price' => 1,
        'description' => 'Verify loan default status before issuing credit',
        'category' => 'crb',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    // === 22. REPEAT BORROWER ASSESSMENT ===
    'repeat-borrower' => [
        'name' => 'Repeat Borrower Assessment',
        'price' => 1,
        'description' => 'Credit info for returning customers',
        'category' => 'credit_info',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/credit_info', 'report_type' => 8],
            ['endpoint' => '/score/consumer', 'report_type' => 3]
        ]
    ],

    // === 23. FINANCIAL REPUTATION SCORE ===
    'financial-reputation' => [
        'name' => 'Financial Reputation Score',
        'price' => 1,
        'description' => 'Credit score for trust platforms',
        'category' => 'score',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3],
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    // === 24. FRAUD RISK PRE-SCREENING ===
    'fraud-prescreening' => [
        'name' => 'Fraud Risk Pre-Screening',
        'price' => 1,
        'description' => 'Identity scrub for fraud detection in fintech',
        'category' => 'scrub',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/identity/scrub', 'report_type' => 6],
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    // === 25. CREDIT EXPOSURE SUMMARY ===
    'credit-exposure' => [
        'name' => 'Credit Exposure Summary',
        'price' => 1,
        'description' => 'Credit exposure for lender dashboards',
        'category' => 'credit_info',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/credit_info', 'report_type' => 8]
        ]
    ],

    // === 26. SOFT BACKGROUND CHECK ===
    'soft-background' => [
        'name' => 'Soft Background Check',
        'price' => 1,
        'description' => 'Non-invasive identity verification',
        'category' => 'identity',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/identity/scrub', 'report_type' => 6]
        ]
    ],

    // === 27. HIGH-RISK BORROWER DETECTION ===
    'high-risk-detection' => [
        'name' => 'High-Risk Borrower Detection',
        'price' => 1,
        'description' => 'Comprehensive risk detection',
        'category' => 'full_enhanced',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/delinquency/status', 'report_type' => 2],
            ['endpoint' => '/score/consumer', 'report_type' => 3]
        ]
    ],

    // === 28. CONSUMER FINANCIAL PROFILE ===
    'consumer-profile' => [
        'name' => 'Consumer Financial Profile',
        'price' => 1,
        'description' => 'Complete credit info profile',
        'category' => 'credit_info',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/json', 'report_type' => 5],
            ['endpoint' => '/score/consumer', 'report_type' => 3]
        ]
    ],

    // === 29. CREDIT MONITORING ===
    'credit-monitoring' => [
        'name' => 'Credit Monitoring Check',
        'price' => 1,
        'description' => 'On-demand credit score monitoring',
        'category' => 'score',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/score/consumer', 'report_type' => 3],
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    // === 30. FINANCIAL DUE DILIGENCE ===
    'financial-due-diligence' => [
        'name' => 'Financial Due Diligence',
        'price' => 1,
        'description' => 'Complete due diligence with all available data',
        'category' => 'full_enhanced',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/report/credit_info_enhanced', 'report_type' => 10],
            ['endpoint' => '/report/json', 'report_type' => 14]
        ]
    ],

    // === ADDITIONAL METROPOL SERVICES ===
    'crb' => [
        'name' => 'CRB Status Check',
        'price' => 1,
        'description' => 'Basic CRB status check',
        'category' => 'crb',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    'tenant-screening' => [
        'name' => 'Tenant Screening',
        'price' => 1,
        'description' => 'Delinquency check for rental screening',
        'category' => 'crb',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/delinquency/status', 'report_type' => 2]
        ]
    ],

    'job' => [
        'name' => 'Job Verification',
        'price' => 1,
        'description' => 'Employment verification with background check',
        'category' => 'scrub',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/identity/scrub', 'report_type' => 6]
        ]
    ],

    'background' => [
        'name' => 'Background Check',
        'price' => 1,
        'description' => 'Identity scrub background check',
        'category' => 'scrub',
        'provider' => 'metropol',
        'api_calls' => [
            ['endpoint' => '/identity/verify', 'report_type' => 1],
            ['endpoint' => '/identity/scrub', 'report_type' => 6]
        ]
    ],

    // ===================================================
    // YOUVERIFY SERVICES - COMPLETE LIST
    // ===================================================

    // === KENYA PASSPORT ===
    'yv-ke-passport' => [
        'name' => 'Kenya Passport Verification',
        'price' => 1,
        'description' => 'Verify Kenyan international passport',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'ke_passport',
        'yv_endpoint' => '/v2/api/identity/ke/passport',
    ],

    // === KENYA NATIONAL ID ===
    'yv-ke-national-id' => [
        'name' => 'Kenya National ID Verification',
        'provider' => 'youverify',
        'price' => 1,
        'yv_endpoint' => 'https://api.youverify.co/v2/api/identity/ke/id-scrub',
        'description' => 'Verify Kenyan National Identity Card',
        'fields' => ['national_id']
    ],

    // === ALIEN ID ===
    'yv-ke-alien-id' => [
        'name'        => 'Kenya Alien ID Verification',
        'price'       => 1,
        'description' => 'Verify Alien / Foreigner ID in Kenya',
        'category'    => 'identity',
        'provider'    => 'youverify',
        'yv_type'     => 'keAlienId',
        'yv_endpoint' => '/v2/api/identity/ke/alien-id',
        'requires_consent' => true
    ],

    // === KENYA DRIVERS LICENSE ===
    'yv-ke-drivers-license' => [
        'name' => 'Kenya Driver\'s License Verification',
        'price' => 1,
        'description' => 'Verify Kenya driver\'s license with NTSA records',
        'category' => 'vehicle',
        'provider' => 'youverify',
        'yv_type' => 'keDriversLicense',
        'yv_endpoint' => '/v2/api/identity/ke/drivers-license',
        'fields' => ['license_number'],
        'status' => 'active'
    ],

    // === KENYA BANK ACCOUNT ===
    'yv-ke-bank-account' => [
        'name' => 'Kenya Bank Account Verification',
        'price' => 1,
        'description' => 'Verify Kenyan bank account details',
        'category' => 'financial',
        'provider' => 'youverify',
        'yv_type' => 'keBAV',
        'yv_endpoint' => '/v2/api/identity/ke/bav',
        'fields' => ['account_number', 'bank_id'],
        'status' => 'active'
    ],

    // === KENYA CREDIT HISTORY ===
    'yv-ke-credit-history' => [
        'name' => 'Kenya Credit History Verification',
        'price' => 1,
        'description' => 'Comprehensive credit history with contract summary and inquiry statistics',
        'category' => 'credit_info',
        'provider' => 'youverify',
        'yv_type' => 'keCreditHistory',
        'yv_endpoint' => '/v2/verifications/identity/ke/credit-history',
        'fields' => ['id_number', 'id_type'],
        'status' => 'active'
    ],

    // === KENYA TAX VERIFICATION (KRA PIN) ===
    'yv-ke-tax' => [
        'name' => 'Kenya Tax Verification (KRA PIN)',
        'price' => 1,
        'description' => 'Verify KRA PIN with Kenya Revenue Authority',
        'category' => 'tax',
        'provider' => 'youverify',
        'yv_type' => 'ke_tax_verification',
        'yv_endpoint' => '/v2/api/identity/ke/kra-pin'
    ],

    // === KENYA ADDRESS VERIFICATION ===
    'yv-ke-address' => [
        'name' => 'Kenya Address Verification',
        'price' => 1,
        'description' => 'Verify address information using official records',
        'category' => 'address',
        'provider' => 'youverify',
        'yv_type' => 'ke_address_verification',
        'yv_endpoint' => '/v2/api/identity/ke/address'
    ],

    // === KENYA PHONE VERIFICATION ===
    'yv-ke-phone' => [
        'name' => 'Kenya Phone Verification',
        'price' => 1,
        'description' => 'Verify Kenyan phone number ownership',
        'category' => 'contact',
        'provider' => 'youverify',
        'yv_type' => 'ke_phone_verification',
        'yv_endpoint' => '/v2/api/identity/ke/phone'
    ],

    // === KENYA EMPLOYMENT VERIFICATION ===
    'yv-ke-employment' => [
        'name' => 'Kenya Employment Verification',
        'price' => 1,
        'description' => 'Verify employment status and history in Kenya',
        'category' => 'employment',
        'provider' => 'youverify',
        'yv_type' => 'ke_employment_verification',
        'yv_endpoint' => '/v2/verifications/identity/ke/employment'
    ],

    // === KENYA PLATE NUMBER ===
    'yv-ke-plate-number' => [
        'name' => 'Kenya Vehicle Plate Number Verification',
        'price' => 1,
        'description' => 'Verify vehicle registration with NTSA',
        'category' => 'vehicle',
        'provider' => 'youverify',
        'yv_type' => 'ke_plate_number',
        'yv_endpoint' => '/v2/api/identity/ke/plate-number'
    ],

    // === KENYA VEHICLE COLLATERAL ===
    'yv-ke-collateral' => [
        'name' => 'Kenya Vehicle Collateral Verification',
        'price' => 1,
        'description' => 'Verify Kenya vehicle collateral registration and security interests',
        'category' => 'vehicle',
        'provider' => 'youverify',
        'yv_type' => 'keVehicleCollateral',
        'yv_endpoint' => '/v2/api/identity/ke/vehicle-collateral',
        'fields' => ['collateral_id'],
        'status' => 'active'
    ],

    // === NIGERIA BVN ===
    'yv-ng-bvn' => [
        'name' => 'Nigeria BVN Verification',
        'price' => 50,
        'description' => 'Verify Nigerian Bank Verification Number with NIBSS',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'ng_bvn',
        'yv_endpoint' => '/v2/identities/verifications/ng/bvn'
    ],

    // === NIGERIA NIN ===
    'yv-ng-nin' => [
        'name' => 'Nigeria NIN Verification',
        'price' => 50,
        'description' => 'Verify Nigerian National Identification Number with NIMC',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'ng_nin',
        'yv_endpoint' => '/v2/identities/verifications/ng/nin'
    ],

    // === GHANA DRIVERS LICENSE ===
    'yv-gh-drivers-license' => [
        'name' => 'Ghana Drivers License Verification',
        'price' => 50,
        'description' => 'Verify Ghana driving license',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'gh_drivers_license',
        'yv_endpoint' => '/v2/identities/verifications/gh/drivers-license'
    ],

    // === GHANA VOTERS ID ===
    'yv-gh-voters-id' => [
        'name' => 'Ghana Voters ID Verification',
        'price' => 50,
        'description' => 'Verify Ghana Electoral Commission voters ID',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'gh_voters_id',
        'yv_endpoint' => '/v2/identities/verifications/gh/voters-id'
    ],

    // === SOUTH AFRICA ID ===
    'yv-za-id' => [
        'name' => 'South Africa ID Verification',
        'price' => 50,
        'description' => 'Verify South African national ID',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'za_national_id',
        'yv_endpoint' => '/v2/identities/verifications/za/national-id'
    ],

    // === UGANDA NIN ===
    'yv-ug-nin' => [
        'name' => 'Uganda NIN Verification',
        'price' => 50,
        'description' => 'Verify Uganda National Identification Number',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'ug_nin',
        'yv_endpoint' => '/v2/identities/verifications/ug/nin'
    ],

    // === TANZANIA NIN ===
    'yv-tz-nin' => [
        'name' => 'Tanzania NIN Verification',
        'price' => 50,
        'description' => 'Verify Tanzania National Identification Number',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'tz_nin',
        'yv_endpoint' => '/v2/identities/verifications/tz/nin'
    ],

    // === RWANDA NIN ===
    'yv-rw-nin' => [
        'name' => 'Rwanda NIN Verification',
        'price' => 50,
        'description' => 'Verify Rwanda National Identification Number',
        'category' => 'identity',
        'provider' => 'youverify',
        'yv_type' => 'rw_nin',
        'yv_endpoint' => '/v2/identities/verifications/rw/nin'
    ],

    // === BUSINESS VERIFICATION ===
    'yv-ke-business' => [
        'name' => 'Kenya Business Verification',
        'price' => 100,
        'description' => 'Verify business registration with BRS Kenya',
        'category' => 'business',
        'provider' => 'youverify',
        'yv_type' => 'ke_business_registry',
        'yv_endpoint' => '/v2/business/verifications/ke/business-registry'
    ],

    // === LIVENESS CHECK ===
    'yv-liveness' => [
        'name' => 'Biometric Liveness Check',
        'price' => 30,
        'description' => 'Anti-spoofing face liveness detection',
        'category' => 'biometric',
        'provider' => 'youverify',
        'yv_type' => 'liveness_check',
        'yv_endpoint' => '/v2/identities/verifications/liveness'
    ],

    // === DOCUMENT VERIFICATION ===
    'yv-document' => [
        'name' => 'Document Verification',
        'price' => 50,
        'description' => 'AI-powered document authenticity check',
        'category' => 'document',
        'provider' => 'youverify',
        'yv_type' => 'document_verification',
        'yv_endpoint' => '/v2/identities/verifications/document'
    ],

    // === SANCTIONS & PEP SCREENING ===
    'yv-sanctions-pep' => [
        'name' => 'Sanctions & PEP Screening',
        'price' => 150,
        'description' => 'Screen against global sanctions and PEP lists',
        'category' => 'compliance',
        'provider' => 'youverify',
        'yv_type' => 'sanctions_pep_screening',
        'yv_endpoint' => '/v2/identities/verifications/sanctions-pep'
    ],

    // === ADVERSE MEDIA SCREENING ===
    'yv-adverse-media' => [
        'name' => 'Adverse Media Screening',
        'price' => 120,
        'description' => 'Screen for negative news and media mentions',
        'category' => 'compliance',
        'provider' => 'youverify',
        'yv_type' => 'adverse_media_screening',
        'yv_endpoint' => '/v2/identities/verifications/adverse-media'
    ],
];

// For backward compatibility
define('SERVICES', ALL_SERVICES);

/* ======================================================
 * SERVICE HELPER FUNCTIONS
 * ====================================================== */

function service_price(string $key): int {
    return ALL_SERVICES[$key]['price'] ?? 1;
}

function service_name(string $key): string {
    return ALL_SERVICES[$key]['name'] ?? 'Unknown Service';
}

function get_service_config(string $service_key): ?array {
    return ALL_SERVICES[$service_key] ?? null;
}

function is_valid_service(string $service_key): bool {
    return isset(ALL_SERVICES[$service_key]);
}

function get_service_provider(string $service_key): ?string {
    return ALL_SERVICES[$service_key]['provider'] ?? null;
}

/* ======================================================
 * SERVICE API CALLS HELPER
 * ====================================================== */

function get_service_api_calls(string $service_key): array {
    if (!isset(ALL_SERVICES[$service_key])) {
        return [];
    }
    return ALL_SERVICES[$service_key]['api_calls'] ?? [];
}

/* ======================================================
 * YOUVERIFY HELPER FUNCTIONS
 * ====================================================== */

function youverify_api_request(string $endpoint, array $data = [], string $method = 'POST'): array {
    $url = YOUVERIFY_BASE_URL . $endpoint;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'token: ' . YOUVERIFY_PUBLIC_KEY
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 60
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if (YOUVERIFY_DEBUG) {
        error_log('=== YOUVERIFY API REQUEST ===');
        error_log('Endpoint: ' . $endpoint);
        error_log('Method: ' . $method);
        error_log('Request Data: ' . json_encode($data));
        error_log('HTTP Code: ' . $http_code);
        error_log('Response: ' . $response);
    }

    if (!$response || $curl_error) {
        return [
            'success' => false,
            'error' => $curl_error ?: 'Network error',
            'http_code' => $http_code
        ];
    }

    $result = json_decode($response, true);

    return [
        'success' => $http_code >= 200 && $http_code < 300,
        'http_code' => $http_code,
        'data' => $result
    ];
}

function verify_youverify_webhook(string $payload, string $signature): bool {
    $computed_signature = hash_hmac('sha512', $payload, YOUVERIFY_WEBHOOK_SECRET);
    return hash_equals($computed_signature, $signature);
}

function youverify_verify(string $service_key, array $params): array {
    $service = get_service_config($service_key);
    if (!$service || $service['provider'] !== 'youverify') {
        return ['success' => false, 'error' => 'Invalid YouVerify service'];
    }

    $yv_type = $service['yv_type'];
    $endpoint = $service['yv_endpoint'] ?? null;

    if (!$endpoint) {
        return ['success' => false, 'error' => 'Missing endpoint configuration'];
    }

    $params['isSubjectConsent'] = true;

    return youverify_api_request($endpoint, $params);
}

function youverify_verify_ke_id(string $id_number): array {
    $data = [
        'id' => $id_number,
        'idType' => 'national-id',
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/api/identity/ke/id-scrub', $data);
}

function youverify_verify_ke_passport(string $passport_number): array {
    $data = [
        'id' => $passport_number,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/api/identity/ke/passport', $data);
}

function youverify_verify_ke_alien_id(string $alien_id, string $first_name, string $last_name): array {
    $data = [
        'alienId' => $alien_id,
        'firstName' => $first_name,
        'lastName' => $last_name,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/api/identity/ke/alien-id', $data);
}

function youverify_verify_ke_drivers_license(string $license_id): array {
    $data = [
        'id' => $license_id,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/api/identity/ke/drivers-license', $data);
}

function youverify_verify_ke_bank_account(string $account_number, string $bank_id): array {
    $data = [
        'accountNumber' => $account_number,
        'bankId' => $bank_id,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/api/identity/ke/bav', $data);
}

function youverify_verify_ke_credit_history(string $id_number, string $id_type): array {
    $data = [
        'id' => $id_number,
        'idType' => $id_type,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/verifications/identity/ke/credit-history', $data);
}

function youverify_verify_ke_kra_pin(string $kra_pin, string $full_name): array {
    $data = [
        'kraPin' => $kra_pin,
        'fullName' => $full_name,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/identities/verifications/ke/kra-pin', $data);
}

function youverify_verify_ke_address(string $id_number, string $address, array $supporting_docs = []): array {
    $data = [
        'idNumber' => $id_number,
        'address' => $address,
        'supportingDocuments' => $supporting_docs,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/identities/verifications/ke/address', $data);
}

function youverify_verify_ke_phone(string $phone_number, string $id_number): array {
    $data = [
        'phoneNumber' => $phone_number,
        'idNumber' => $id_number,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/identities/verifications/ke/phone', $data);
}

function youverify_verify_ke_employment(string $id_number, string $employer_name, string $position): array {
    $data = [
        'idNumber' => $id_number,
        'employerName' => $employer_name,
        'position' => $position,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/verifications/identity/ke/employment', $data);
}

function youverify_verify_ke_plate_number(string $plate_number): array {
    $data = [
        'plateNumber' => $plate_number,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/api/identity/ke/plate-number', $data);
}

function youverify_verify_ke_vehicle_collateral(string $collateral_id): array {
    $data = [
        'id' => $collateral_id,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/api/identity/ke/vehicle-collateral', $data);
}

function youverify_verify_ng_bvn(string $bvn, string $first_name, string $last_name): array {
    $data = [
        'bvn' => $bvn,
        'firstName' => $first_name,
        'lastName' => $last_name,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/identities/verifications/ng/bvn', $data);
}

function youverify_verify_ng_nin(string $nin, string $first_name, string $last_name): array {
    $data = [
        'nin' => $nin,
        'firstName' => $first_name,
        'lastName' => $last_name,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/identities/verifications/ng/nin', $data);
}

function youverify_verify_ke_business(string $business_number, string $business_name): array {
    $data = [
        'registrationNumber' => $business_number,
        'businessName' => $business_name,
        'isSubjectConsent' => true
    ];
    return youverify_api_request('/v2/business/verifications/ke/business-registry', $data);
}

function youverify_check_status(string $reference_id): array {
    return youverify_api_request('/v2/identities/verifications/' . $reference_id, [], 'GET');
}

/* ======================================================
 * M-PESA HELPER FUNCTIONS
 * ====================================================== */

function get_mpesa_token(): ?string {
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        error_log('M-Pesa token error: HTTP ' . $http_code);
        return null;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function send_stk_push(string $phone, int $amount, int $requestId): array {
    $token = get_mpesa_token();
    if (!$token) {
        return [
            'success' => false,
            'message' => 'Failed to authenticate with M-Pesa',
            'error_code' => 'AUTH_FAILED'
        ];
    }

    $phone = preg_replace('/\D/', '', $phone);

    if (strlen($phone) === 10 && $phone[0] === '0') {
        $phone = '254' . substr($phone, 1);
    }
    elseif (strlen($phone) === 9 && ($phone[0] === '7' || $phone[0] === '1')) {
        $phone = '254' . $phone;
    }

    if (!preg_match('/^254[17]\d{8}$/', $phone)) {
        error_log("Invalid phone format after normalization: $phone");
        return [
            'success' => false,
            'message' => 'Invalid phone number format. Use 254712345678 or 0712345678',
            'error_code' => '20'
        ];
    }

    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

    $payload = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline',
        'Amount'            => $amount,
        'PartyA'            => $phone,
        'PartyB'            => MPESA_SHORTCODE,
        'PhoneNumber'       => $phone,
        'CallBackURL'       => MPESA_CALLBACK_URL,
        'AccountReference'  => 'READIWORK-' . $requestId,
        'TransactionDesc'   => 'Verification Service'
    ];

    error_log('=== STK PUSH REQUEST ===');
    error_log('Request ID: ' . $requestId);
    error_log('Phone: ' . $phone);
    error_log('Amount: ' . $amount);
    error_log('Payload: ' . json_encode($payload));

    $ch = curl_init('https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if (!$response) {
        error_log("M-Pesa STK Push CURL Error: $curl_error");
        return [
            'success' => false,
            'message' => 'Failed to connect to M-Pesa. Please try again.',
            'error_code' => 'NETWORK_ERROR'
        ];
    }

    $result = json_decode($response, true);
    error_log('=== STK PUSH RESPONSE ===');
    error_log('HTTP Code: ' . $http_code);
    error_log('Response: ' . print_r($result, true));

    if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
        error_log('STK Push SUCCESS for RID ' . $requestId);
        return [
            'success' => true,
            'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $result['MerchantRequestID'] ?? null,
            'message' => 'STK Push sent successfully'
        ];
    }

    $errorMessage = $result['errorMessage'] ?? $result['ResponseDescription'] ?? $result['errorCode'] ?? 'Payment request failed';
    $errorCode = $result['errorCode'] ?? $result['ResponseCode'] ?? 'UNKNOWN';

    error_log("STK Push FAILED for RID $requestId - Code: $errorCode, Message: $errorMessage");

    return [
        'success' => false,
        'message' => $errorMessage,
        'error_code' => $errorCode
    ];
}
