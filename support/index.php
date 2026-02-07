<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Support & Help Center | Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="description" content="Get help with Readiwork services, verification issues, payments, and business integrations.">
<meta name="keywords" content="Readiwork Support, Help Center, Verification Help, Contact Readiwork">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root{
  --green:#22c55e;
  --bg:#050b1a;
  --panel:#0b1220;
  --card:#0f172a;
  --text:#f1f5f9;
  --muted:#9ca3af;
  --border:rgba(255,255,255,.08);
}

body{
  background:var(--bg);
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
}
.nav-link{
  color:#cbd5f5!important;
}
.nav-link:hover{
  color:var(--green)!important;
}
.dropdown-menu{
  background:#020617;
  border:none;
}

/* PAGE */
.page{
  padding:150px 0 100px;
}

/* HERO */
.hero{
  text-align:center;
  margin-bottom:70px;
}
.hero h1{
  font-size:3.2rem;
  font-weight:900;
}
.hero span{color:var(--green)}
.hero p{
  max-width:760px;
  margin:16px auto 0;
  color:var(--muted);
}

/* CARDS */
.support-card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:22px;
  padding:36px;
  height:100%;
  transition:.3s;
}
.support-card:hover{
  transform:translateY(-6px);
  box-shadow:0 30px 70px rgba(34,197,94,.25);
}
.support-card i{
  font-size:30px;
  color:var(--green);
  margin-bottom:18px;
}

/* FORM */
.form-card{
  background:var(--panel);
  border:1px solid var(--border);
  border-radius:26px;
  padding:44px;
}
.form-control{
  background:#020617;
  border:1px solid var(--border);
  color:#fff;
  padding:14px;
  border-radius:14px;
}
.form-control:focus{
  border-color:var(--green);
  box-shadow:none;
}
label{
  font-weight:600;
  margin-bottom:6px;
}

/* BUTTON */
.btn-main{
  background:linear-gradient(135deg,#22c55e,#16a34a);
  border:none;
  border-radius:999px;
  padding:14px;
  font-weight:800;
}

/* FOOTER */
footer{
  background:#020617;
  border-top:1px solid var(--border);
  padding:45px 0;
  text-align:center;
  color:#94a3b8;
}
footer a{
  color:#94a3b8;
  text-decoration:none;
}
footer a:hover{
  color:var(--green);
}
</style>
</head>

<body>

<!-- NAV -->

<?php include '../includes/navbar.php'; ?>



<!-- PAGE -->
<section class="page">
<div class="container">

<!-- HERO -->
<div class="hero">
  <h1>Support & <span>Help Center</span></h1>
  <p>
    Need help with verification, payments, reports, or integrations?
    Our team and AI-powered systems are here to assist you.
  </p>
</div>

<!-- SUPPORT OPTIONS -->
<div class="row g-4 mb-5">
  <div class="col-md-4">
    <div class="support-card text-center">
      <i class="fa-solid fa-circle-question"></i>
      <h5>General Help</h5>
      <p>Questions about CRB checks, verification results, or reports.</p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="support-card text-center">
      <i class="fa-solid fa-credit-card"></i>
      <h5>Payments & Billing</h5>
      <p>Issues with payments, receipts, refunds, or invoices.</p>
    </div>
  </div>

  <div class="col-md-4">
    <div class="support-card text-center">
      <i class="fa-solid fa-code"></i>
      <h5>Business & API Support</h5>
      <p>Integration help, bulk checks, and enterprise support.</p>
    </div>
  </div>
</div>

<!-- CONTACT FORM -->
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="form-card">
      <h4 class="mb-4 text-center">Contact Support</h4>

      <form>
        <div class="mb-3">
          <label>Your Name</label>
          <input class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Email Address</label>
          <input type="email" class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Support Category</label>
          <select class="form-control" required>
            <option value="">Select category</option>
            <option>Verification Issue</option>
            <option>Payment / Billing</option>
            <option>Business / API</option>
            <option>General Question</option>
          </select>
        </div>

        <div class="mb-4">
          <label>Your Message</label>
          <textarea class="form-control" rows="4" required></textarea>
        </div>

        <button class="btn btn-main w-100">
          Submit Support Request
        </button>
      </form>
    </div>
  </div>
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
