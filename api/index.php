<?php
// api.php
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>APIs for Businesses | Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Explore Readiwork's AI-powered verification APIs for credit, background, tenant, and employment checks. Integrate or resell with confidence.">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root{
  --green:#22c55e;
  --bg:#050b1a;
  --panel:#0b1220;
  --card:#0f172a;
  --text:#f1f5f9;
  --muted:#b6c0d1;
  --border:rgba(255,255,255,.08);
}

body{
  background:radial-gradient(70% 60% at top,#0b1220,#050b1a);
  color:var(--text);
  font-family:Inter,system-ui,sans-serif;
  line-height:1.65;
}

/* NAVBAR */
.navbar{
  background:rgba(5,11,26,.95);
  border-bottom:1px solid var(--border);
}
.navbar-brand{
  font-weight:900;
  letter-spacing:.5px;
}
.nav-link{
  color:#dbeafe !important;
  font-weight:500;
}
.nav-link:hover{color:var(--green)!important}

/* HERO */
.hero{
  padding:150px 0 100px;
}
.hero h1{
  font-size:3rem;
  font-weight:900;
}
.hero span{color:var(--green)}
.hero p{
  max-width:900px;
  margin-top:18px;
  color:var(--muted);
  font-size:1.15rem;
}

/* SECTIONS */
.section{padding:80px 0}
.section h2{font-weight:900;margin-bottom:30px;text-align:center}
.section p{color:var(--muted);text-align:center}

/* CARDS */
.api-card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:22px;
  padding:28px;
  height:100%;
  transition:.3s;
}
.api-card:hover{
  transform:translateY(-6px);
  box-shadow:0 25px 50px rgba(34,197,94,.25);
}
.api-card i{
  color:var(--green);
  font-size:28px;
  margin-bottom:14px;
}

/* CTA */
.cta{
  background:linear-gradient(135deg,#16a34a,#22c55e);
  border-radius:26px;
  padding:60px;
  color:#052e16;
}

/* FOOTER */
footer{
  background:#020617;
  border-top:1px solid var(--border);
  padding:50px 0;
  text-align:center;
  color:#9ca3af;
}
footer a{color:#9ca3af;text-decoration:none}
footer a:hover{color:#22c55e}

/* Hover animation */
.hover-opacity-100 {
  transition: opacity .3s, transform .3s;
}
.hover-opacity-100:hover {
  opacity: 1 !important;
  transform: translateY(-3px);
}
</style>
</head>
<body>

<!-- NAVBAR -->
<?php include '../includes/navbar.php'; ?>

<!-- HERO -->
<section class="hero text-center">
  <div class="container">
    <h1>
      Readiwork <span>APIs</span> for Businesses
    </h1>
    <p>
      Access AI-powered verification for credit, background checks, tenant screening, and employment verification.<br>
      Integrate directly into your platform or resell our APIs to your clients with confidence.
    </p>
  </div>
</section>

<!-- API LIST -->
<section class="section">
  <div class="container">
    <h2>Available APIs</h2>
    <div class="row g-4">
      
      <div class="col-md-4">
        <div class="api-card text-center">
          <i class="fa-solid fa-id-card"></i>
          <h5>Identity Verification</h5>
          <p>Verify national IDs, passports, and other identity documents instantly and accurately.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="api-card text-center">
          <i class="fa-solid fa-file-invoice"></i>
          <h5>Credit Report</h5>
          <p>Retrieve full consumer credit reports and scores in real-time for informed decisions.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="api-card text-center">
          <i class="fa-solid fa-briefcase"></i>
          <h5>Employment Verification</h5>
          <p>Confirm employment history and job details quickly for hiring and lending purposes.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="api-card text-center">
          <i class="fa-solid fa-house-user"></i>
          <h5>Tenant Screening</h5>
          <p>Check rental history, delinquency, and defaults to reduce leasing risk.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="api-card text-center">
          <i class="fa-solid fa-cart-shopping"></i>
          <h5>Business Verification</h5>
          <p>Validate business details, directors, and creditworthiness for partnerships or trade.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="api-card text-center">
          <i class="fa-solid fa-arrow-up-right-dots"></i>
          <h5>Reseller Program</h5>
          <p>Offer our APIs to your clients as your own service, earn revenue, and scale your business.</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta text-center">
      <h2>Get Started with Readiwork APIs</h2>
      <p class="mb-4">Contact us today to access API documentation, pricing, and integration support.</p>
      <a href="/support/" class="btn btn-dark btn-lg rounded-pill px-5">Contact API Team</a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<?php include '../includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
