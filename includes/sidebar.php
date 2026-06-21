<div class="bg-dark border-right" id="sidebar-wrapper">
    <div class="sidebar-heading text-white">
        <img src="assets/logo.png" alt="Logo" style="height: 40px; border-radius: 5px; background: white;" class="me-2">
        HMS Portal
    </div>
    <div class="list-group list-group-flush">
        <a href="index.php" class="list-group-item list-group-item-action bg-dark text-white">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>

        <?php if (in_array($_SESSION['role'], ['Admin', 'Receptionist', 'Doctor'])): ?>
            <a href="patients.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-user-injured me-2"></i> Patients
            </a>
            <a href="opd.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-stethoscope me-2"></i> OPD Registration
            </a>
            <?php
        endif; ?>

        <?php if (in_array($_SESSION['role'], ['Admin', 'Doctor', 'Nurse'])): ?>
            <a href="ipd.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-procedures me-2"></i> IPD / Admissions
            </a>
            <?php
        endif; ?>

        <?php if (in_array($_SESSION['role'], ['Admin', 'Receptionist', 'Accountant'])): ?>
            <a href="billing.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-file-invoice-dollar me-2"></i> Billing
            </a>
            <a href="financials.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-balance-scale me-2"></i> Financial Accounts
            </a>
            <?php
        endif; ?>

        <?php if (in_array($_SESSION['role'], ['Admin', 'Lab Technician', 'Doctor'])): ?>
            <a href="laboratory.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-flask me-2"></i> Laboratory
            </a>
            <?php
        endif; ?>

        <?php if (in_array($_SESSION['role'], ['Admin', 'Pharmacist', 'Doctor'])): ?>
            <a href="pharmacy.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-pills me-2"></i> Pharmacy
            </a>
            <?php
        endif; ?>

        <?php if ($_SESSION['role'] == 'Admin'): ?>
            <a href="users.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-users-cog me-2"></i> Users & Roles
            </a>
            <a href="reports.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-chart-line me-2"></i> Reports
            </a>
            <a href="settings.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="fas fa-cogs me-2"></i> Hospital Settings
            </a>
            <?php
        endif; ?>

        <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
    </div>
</div>