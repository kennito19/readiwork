<?php
require_once 'config.php';

function get_service_price($key) {
    return service_price($key) ?? 1;
}

$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '.') $base_path = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All AI Verification Services – Readiwork AI | 61+ AI-Powered Services</title>
    <meta name="description" content="Explore all 61+ AI-powered verification, credit, and compliance services by Readiwork AI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/theme.css">
    
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding-top: 80px;
        }

        /* COMPACT HERO */
        .page-hero {
            padding: 80px 0 40px;
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 100%);
            text-align: center;
        }

        .page-hero h1 {
            font-size: 2.8rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-hero p {
            font-size: 1.1rem;
            color: var(--muted);
            margin: 0;
        }

        /* STICKY SEARCH & FILTER BAR */
        .filter-bar {
            background: white;
            border-bottom: 2px solid var(--border);
            padding: 20px 0;
            position: sticky;
            top: 70px;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .search-box {
            position: relative;
            max-width: 500px;
        }

        .search-box input {
            width: 100%;
            padding: 14px 50px 14px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
        }

        .category-filter {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 8px 20px;
            border: 2px solid var(--border);
            background: white;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .filter-btn i {
            margin-right: 6px;
        }

        .services-count {
            font-size: 0.9rem;
            color: var(--muted);
            font-weight: 600;
        }

        /* SERVICES GRID - COMPACT */
        .services-section {
            padding: 40px 0 80px;
        }

        .service-card {
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 24px 20px;
            height: 100%;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            border-radius: 16px 16px 0 0;
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(34, 197, 94, 0.15);
            border-color: var(--primary);
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-card i {
            font-size: 36px;
            color: var(--primary);
            margin-bottom: 12px;
            transition: all 0.3s;
        }

        .service-card:hover i {
            transform: scale(1.1);
        }

        .service-card h4 {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 8px;
            color: var(--text);
            line-height: 1.3;
        }

        .service-card p {
            font-size: 0.85rem;
            color: var(--muted);
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .service-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .provider-label {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .label-metropol {
            background: rgba(99, 102, 241, 0.15);
            color: #4f46e5;
        }

        .label-youverify {
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
        }

        /* EMPTY STATE */
        .no-results {
            text-align: center;
            padding: 80px 20px;
            color: var(--muted);
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .page-hero h1 { font-size: 2.2rem; }
            .filter-bar { top: 60px; padding: 15px 0; }
            .category-filter { gap: 6px; }
            .filter-btn { padding: 6px 14px; font-size: 0.8rem; }
            .service-card { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<section class="page-hero">
    <div class="container">
        <h1><i class="fa-solid fa-robot" style="margin-right:10px;color:var(--green-500)"></i>All AI Verification Services</h1>
        <p>61+ AI-powered instant verification services — search or filter by category</p>
    </div>
</section>

<!-- SEARCH & FILTER BAR -->
<div class="filter-bar">
    <div class="container">
        <div class="row g-3 align-items-center">
            <div class="col-lg-5">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search services (e.g., passport, credit, bank)...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="category-filter">
                        <button class="filter-btn active" data-category="all">
                            <i class="fa-solid fa-grid"></i>All
                        </button>
                        <button class="filter-btn" data-category="kenya">
                            <i class="fa-solid fa-flag"></i>Kenya
                        </button>
                        <button class="filter-btn" data-category="credit">
                            <i class="fa-solid fa-chart-line"></i>Credit
                        </button>
                        <button class="filter-btn" data-category="identity">
                            <i class="fa-solid fa-id-card"></i>Identity
                        </button>
                        <button class="filter-btn" data-category="vehicle">
                            <i class="fa-solid fa-car"></i>Vehicle
                        </button>
                        <button class="filter-btn" data-category="africa">
                            <i class="fa-solid fa-globe-africa"></i>Africa
                        </button>
                    </div>
                    <span class="services-count"><span id="resultCount">61</span> services</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SERVICES GRID -->
<section class="services-section">
    <div class="container">
        <div class="row g-3" id="servicesGrid">
            
            <!-- KENYA SERVICES -->
            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya identity" data-search="national id verification kenya iprs">
                <a href="id-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-fingerprint"></i>
                        <h4>National ID</h4>
                        <p>Verify Kenyan ID with IPRS</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-national-id'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya identity" data-search="passport international travel document">
                <a href="passport.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-passport"></i>
                        <h4>Passport</h4>
                        <p>Verify Kenyan passport</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-passport'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya identity" data-search="alien foreigner refugee id">
                <a href="alien-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-id-card-clip"></i>
                        <h4>Alien ID</h4>
                        <p>Foreigner ID verification</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-alien-id'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya vehicle" data-search="drivers license ntsa driving">
                <a href="license.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-car"></i>
                        <h4>Driver's License</h4>
                        <p>Verify with NTSA database</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-drivers-license'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya vehicle" data-search="plate number car registration ntsa">
                <a href="carsearch.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-car-side"></i>
                        <h4>Plate Number</h4>
                        <p>Vehicle registration check</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-plate-number'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya vehicle" data-search="vehicle collateral logbook financing">
                <a href="collateral-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-car-burst"></i>
                        <h4>Vehicle Collateral</h4>
                        <p>Asset financing details</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-collateral'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya" data-search="bank account mpesa verification">
                <a href="account-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-building-columns"></i>
                        <h4>Bank Account</h4>
                        <p>Verify account ownership</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-bank-account'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya" data-search="kra pin tax number revenue">
                <a href="pin-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-receipt"></i>
                        <h4>KRA PIN</h4>
                        <p>Tax PIN verification</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-tax'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya" data-search="phone mobile number safaricom airtel">
                <a href="phone-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-mobile-screen"></i>
                        <h4>Phone Number</h4>
                        <p>Verify phone ownership</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-phone'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya" data-search="address location physical residence">
                <a href="address-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-location-dot"></i>
                        <h4>Address</h4>
                        <p>Physical address check</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-address'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya" data-search="employment job work history">
                <a href="employment-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-briefcase"></i>
                        <h4>Employment</h4>
                        <p>Verify work history</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-employment'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="kenya credit" data-search="credit history loan borrowing">
                <a href="credit-verification.php" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-scroll"></i>
                        <h4>Credit History</h4>
                        <p>Full credit report</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ke-credit-history'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <!-- METROPOL CREDIT SERVICES -->
            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="identity" data-search="national id basic verification">
                <a href="verification.php?service=id-verification" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-id-badge"></i>
                        <h4>ID Verification</h4>
                        <p>Basic ID validation</p>
                        <div class="service-price">KES <?= number_format(get_service_price('id-verification'), 0) ?></div>
                        <span class="provider-label label-metropol">Metropol</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="credit" data-search="crb clearance status listing">
                <a href="verification.php?service=crb-clearance" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-shield-halved"></i>
                        <h4>CRB Clearance</h4>
                        <p>Credit bureau check</p>
                        <div class="service-price">KES <?= number_format(get_service_price('crb-clearance'), 0) ?></div>
                        <span class="provider-label label-metropol">Metropol</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="credit" data-search="credit score metro rating">
                <a href="verification.php?service=credit-score" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-chart-line"></i>
                        <h4>Credit Score</h4>
                        <p>Metro score 0-900</p>
                        <div class="service-price">KES <?= number_format(get_service_price('credit-score'), 0) ?></div>
                        <span class="provider-label label-metropol">Metropol</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="credit" data-search="loan eligibility qualification">
                <a href="verification.php?service=loan-eligibility" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-coins"></i>
                        <h4>Loan Eligibility</h4>
                        <p>Qualification assessment</p>
                        <div class="service-price">KES <?= number_format(get_service_price('loan-eligibility'), 0) ?></div>
                        <span class="provider-label label-metropol">Metropol</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="credit" data-search="tenant screening rental">
                <a href="verification.php?service=tenant-screening" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-house-user"></i>
                        <h4>Tenant Screening</h4>
                        <p>Rental background check</p>
                        <div class="service-price">KES <?= number_format(get_service_price('tenant-screening'), 0) ?></div>
                        <span class="provider-label label-metropol">Metropol</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="credit" data-search="crb certificate status">
                <a href="verification.php?service=crb-certificate" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-certificate"></i>
                        <h4>CRB Certificate</h4>
                        <p>Official status certificate</p>
                        <div class="service-price">KES <?= number_format(get_service_price('crb-certificate'), 0) ?></div>
                        <span class="provider-label label-metropol">Metropol</span>
                    </div>
                </a>
            </div>

            <!-- PAN-AFRICAN -->
            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="africa" data-search="nigeria bvn bank verification">
                <a href="verification.php?service=yv-ng-bvn" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-building-columns"></i>
                        <h4>🇳🇬 Nigeria BVN</h4>
                        <p>Bank verification number</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ng-bvn'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="africa" data-search="nigeria nin national id">
                <a href="verification.php?service=yv-ng-nin" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-id-card"></i>
                        <h4>🇳🇬 Nigeria NIN</h4>
                        <p>National ID verification</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ng-nin'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="africa" data-search="ghana license drivers">
                <a href="verification.php?service=yv-gh-drivers-license" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-car"></i>
                        <h4>🇬🇭 Ghana License</h4>
                        <p>Driver's license check</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-gh-drivers-license'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="africa" data-search="south africa id national">
                <a href="verification.php?service=yv-za-id" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-id-card"></i>
                        <h4>🇿🇦 South Africa ID</h4>
                        <p>National ID verification</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-za-id'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-4 col-lg-3 service-item" data-category="africa" data-search="uganda nin national id">
                <a href="verification.php?service=yv-ug-nin" class="text-decoration-none">
                    <div class="service-card">
                        <i class="fa-solid fa-id-card"></i>
                        <h4>🇺🇬 Uganda NIN</h4>
                        <p>National ID verification</p>
                        <div class="service-price">KES <?= number_format(get_service_price('yv-ug-nin'), 0) ?></div>
                        <span class="provider-label label-youverify">YouVerify</span>
                    </div>
                </a>
            </div>

            <!-- Add remaining services as needed with proper links -->

        </div>

        <!-- NO RESULTS STATE -->
        <div class="no-results" id="noResults" style="display: none;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>No services found</h3>
            <p>Try adjusting your search or filter</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base_path ?>/assets/js/main.js"></script>
<script>
// SEARCH & FILTER FUNCTIONALITY
const searchInput = document.getElementById('searchInput');
const filterBtns = document.querySelectorAll('.filter-btn');
const serviceItems = document.querySelectorAll('.service-item');
const resultCount = document.getElementById('resultCount');
const noResults = document.getElementById('noResults');

let currentCategory = 'all';
let currentSearch = '';

// Search Function
searchInput.addEventListener('input', (e) => {
    currentSearch = e.target.value.toLowerCase();
    filterServices();
});

// Category Filter
filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCategory = btn.getAttribute('data-category');
        filterServices();
    });
});

// Main Filter Logic
function filterServices() {
    let visibleCount = 0;

    serviceItems.forEach(item => {
        const category = item.getAttribute('data-category');
        const searchText = item.getAttribute('data-search') || '';
        
        const matchesCategory = currentCategory === 'all' || category.includes(currentCategory);
        const matchesSearch = searchText.includes(currentSearch) || currentSearch === '';

        if (matchesCategory && matchesSearch) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    resultCount.textContent = visibleCount;
    noResults.style.display = visibleCount === 0 ? 'block' : 'none';
}

// Initialize
filterServices();
</script>
</body>
</html>