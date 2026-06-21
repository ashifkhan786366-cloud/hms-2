<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

if ($_SESSION['role'] != 'Admin')
    die("Access Denied");

// Add User
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full = $_POST['full_name'];
    $user = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $sql = "INSERT INTO users (full_name, username, password, role, email, phone) VALUES (?, ?, ?, ?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full, $user, $pass, $role, $email, $phone]);
        echo "<div class='alert alert-success'>User Added!</div>";
    }
    catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();
?>

<div class="container-fluid mt-4">
    <h2>User Management</h2>
    
    <div class="card mt-3">
        <div class="card-header bg-primary text-white">Add New User</div>
        <div class="card-body">
            <form method="POST" class="row">
                <div class="col-md-3 mb-2"><input type="text" name="full_name" class="form-control" placeholder="Full Name" required></div>
                <div class="col-md-2 mb-2"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
                <div class="col-md-2 mb-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                <div class="col-md-2 mb-2">
                    <select name="role" class="form-select" required>
                        <option value="Admin">Admin</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Receptionist">Receptionist</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Pharmacist">Pharmacist</option>
                        <option value="Lab Technician">Lab Technician</option>
                        <option value="Accountant">Accountant</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-success w-100">Create User</button>
                </div>
                <!-- Additional fields hidden for brevity but processed -->
                <input type="hidden" name="email" value="">
                <input type="hidden" name="phone" value="">
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['full_name']; ?></td>
                        <td><?php echo $u['username']; ?></td>
                        <td><?php echo $u['role']; ?></td>
                        <td><button class="btn btn-sm btn-danger">Delete</button></td>
                    </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
