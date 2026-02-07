<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>For Businesses | Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="description" content="AI-assisted credit, background and risk verification for Kenyan businesses, landlords, employers and lenders.">

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

/* NAV */
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
  padding:170px 0 120px;
}
.hero h1{
  font-size:3.4rem;
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
.section{padding:100px 0}
.section h2{font-weight:900;margin-bottom:14px}
.section p{color:var(--muted)}

/* CARDS */
.biz-card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:22px;
  padding:34px;
  height:100%;
  transition:.3s;
}
.biz-card:hover{
  transform:translateY(-6px);
  box-shadow:0 30px 60px rgba(34,197,94,.25);
}
.biz-card i{
  color:var(--green);
  font-size:30px;
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
footer a:hover{color:var(--green)}
</style>
</head>

<body>

<!-- NAVBAR -->
<?php include '../includes/navbar.php'; ?>



<!-- HERO -->
<section class="hero text-center">
<div class="container">
<h1>
AI-Assisted <span>Business Verification</span><br>
for Kenyan Organizations
</h1>

<p>
Readiwork helps lenders, employers, landlords and businesses
reduce fraud, defaults and onboarding risk using
<strong>lawful data sources</strong> and
<strong>AI-assisted risk analysis</strong>.
</p>
</div>
</section>

<!-- WHO IT'S FOR -->
<section class="section">
<div class="container">
<h2 class="text-center mb-5">Who Uses Readiwork?</h2>

<div class="row g-4">
<div class="col-md-3">
<div class="biz-card text-center">
<i class="fa-solid fa-building-columns"></i>
<h5>Banks & Lenders</h5>
<p>Assess borrower risk before loan approval</p>
</div>
</div>

<div class="col-md-3">
<div class="biz-card text-center">
<i class="fa-solid fa-briefcase"></i>
<h5>Employers</h5>
<p>Background verification before hiring</p>
</div>
</div>

<div class="col-md-3">
<div class="biz-card text-center">
<i class="fa-solid fa-house-user"></i>
<h5>Landlords & Agents</h5>
<p>Tenant screening and rental risk checks</p>
</div>
</div>

<div class="col-md-3">
<div class="biz-card text-center">
<i class="fa-solid fa-cart-shopping"></i>
<h5>Businesses</h5>
<p>Sell on credit with confidence</p>
</div>
</div>
</div>
</div>
</section>

<!-- WHY -->
<section class="section bg-dark">
<div class="container">
<h2 class="text-center mb-5">Why Businesses Trust Readiwork</h2>

<div class="row g-4">
<div class="col-md-4">
<div class="biz-card">
<strong>✔ AI-Assisted Insights</strong>
<p>
Risk indicators generated using machine learning,
combined with structured verification logic.
</p>
</div>
</div>

<div class="col-md-4">
<div class="biz-card">
<strong>✔ Compliance-First</strong>
<p>
Designed with Kenya’s data protection laws
and responsible use principles in mind.
</p>
</div>
</div>

<div class="col-md-4">
<div class="biz-card">
<strong>✔ Scalable & API-Ready</strong>
<p>
Manual checks or system integration for
high-volume verification.
</p>
</div>
</div>
</div>
</div>
</section>

<!-- CTA -->
<section id="contact" class="section">
<div class="container">
<div class="cta text-center">
<h2>Partner With Readiwork</h2>
<p class="mb-4">
Need verification at scale or API access for your platform?
</p>
<a href="/support/" class="btn btn-dark btn-lg rounded-pill px-5">
Contact Business Team
</a>
</div>
</div>
</section>

<!-- FOOTER -->
<footer>
<?php include '../includes/footer.php'; ?>


</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
