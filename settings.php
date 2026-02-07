<?php include 'includes/layout-top.php'; ?>

<h2 class="fw-bold mb-4">System Settings</h2>

<div class="row g-4">
  <!-- General Settings -->
  <div class="col-lg-8">
    <div class="stat mb-4">
      <h5 class="mb-4">General Settings</h5>
      <form>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Site Name</label>
            <input type="text" class="form-control" value="Readiwork" placeholder="Readiwork">
          </div>
          <div class="col-md-6">
            <label class="form-label">Support Email</label>
            <input type="email" class="form-control" value="support@readiwork.co.ke" placeholder="support@readiwork.co.ke">
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Support Phone</label>
            <input type="text" class="form-control" value="+254 700 000 000" placeholder="+254 700 000 000">
          </div>
          <div class="col-md-6">
            <label class="form-label">Default Currency</label>
            <select class="form-select">
              <option selected>KES - Kenyan Shilling</option>
              <option>USD - US Dollar</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Maintenance Mode</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="maintenanceMode">
            <label class="form-check-label" for="maintenanceMode">Enable maintenance mode (users will see a message)</label>
          </div>
        </div>
        <button type="submit" class="btn btn-success">Save General Settings</button>
      </form>
    </div>

    <!-- Pricing & Fees -->
    <div class="stat mb-4">
      <h5 class="mb-4">Pricing & Fees</h5>
      <form>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Credit / Blacklist Check (KES)</label>
            <input type="number" class="form-control" value="150">
          </div>
          <div class="col-md-4">
            <label class="form-label">Credit Health Score (KES)</label>
            <input type="number" class="form-control" value="200">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tenant Verification (KES)</label>
            <input type="number" class="form-control" value="300">
          </div>
          <div class="col-md-4">
            <label class="form-label">Job Verification (KES)</label>
            <input type="number" class="form-control" value="250">
          </div>
          <div class="col-md-4">
            <label class="form-label">Domestic Staff Check (KES)</label>
            <input type="number" class="form-control" value="400">
          </div>
          <div class="col-md-4">
            <label class="form-label">Dating / Background (KES)</label>
            <input type="number" class="form-control" value="500">
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-success">Update Pricing</button>
        </div>
      </form>
    </div>

    <!-- API & Integration -->
    <div class="stat">
      <h5 class="mb-4">API & Integrations</h5>
      <form>
        <div class="mb-3">
          <label class="form-label">CRB API Key</label>
          <input type="password" class="form-control" value="sk_live_xxxxxxxxxxxxxxxxxxxx" autocomplete="off">
          <small class="text-muted">Used for credit blacklist and health score checks</small>
        </div>
        <div class="mb-3">
          <label class="form-label">M-Pesa Consumer Key</label>
          <input type="text" class="form-control" value="your_consumer_key_here">
        </div>
        <div class="mb-3">
          <label class="form-label">M-Pesa Consumer Secret</label>
          <input type="password" class="form-control" value="your_secret_here" autocomplete="off">
        </div>
        <button type="submit" class="btn btn-success">Save API Credentials</button>
      </form>
    </div>
  </div>

  <!-- Quick Stats / Actions Sidebar -->
  <div class="col-lg-4">
    <div class="stat mb-4">
      <h5>System Info</h5>
      <ul class="list-unstyled mt-3">
        <li><strong>Version:</strong> 2.4.1</li>
        <li><strong>Last Backup:</strong> 2025-12-28 02:00 AM</li>
        <li><strong>Database Size:</strong> 842 MB</li>
        <li><strong>PHP Version:</strong> 8.2.12</li>
      </ul>
      <hr>
      <a href="#" class="btn btn-outline-light w-100 mb-2">Run Database Backup</a>
      <a href="#" class="btn btn-outline-warning w-100 mb-2">Clear Cache</a>
      <a href="#" class="btn btn-outline-danger w-100">View Error Logs</a>
    </div>

    <div class="stat">
      <h5>Email Notifications</h5>
      <div class="mt-3">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" id="emailNewUser" checked>
          <label class="form-check-label" for="emailNewUser">New user registration</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" id="emailNewCheck" checked>
          <label class="form-check-label" for="emailNewCheck">New verification request</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" id="emailPayment">
          <label class="form-check-label" for="emailPayment">Payment received</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="emailAlerts" checked>
          <label class="form-check-label" for="emailAlerts">Risk alerts</label>
        </div>
      </div>
      <button type="button" class="btn btn-success w-100 mt-3">Save Notification Settings</button>
    </div>
  </div>
</div>

<?php include 'includes/layout-bottom.php'; ?>