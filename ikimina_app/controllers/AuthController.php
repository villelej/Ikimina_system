<?php
session_start();
require_once __DIR__ . '/../config/db.php';   // Database connection
require_once __DIR__ . '/../models/User.php';

$userModel = new User($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ================= REGISTER =================
    if (isset($_POST['register'])) {

        // 1️⃣ Password match check
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $_SESSION['error'] = "Passwords do not match!";
            header("Location: ../views/auth/register.php");
            exit();
        }

        // 2️⃣ Collect data safely
        $data = [
            'first_name'    => $_POST['first_name']   ?? null,
            'middle_name'   => $_POST['middle_name']  ?? null,
            'last_name'     => $_POST['last_name']    ?? null,
            'username'      => $_POST['username']     ?? null,
            'email'         => $_POST['email']        ?? null,
            'phone'         => $_POST['phone']        ?? null,
            'national_id'   => $_POST['national_id']  ?? null,
            'date_of_birth' => $_POST['date_of_birth']?? null,
            'gender'        => $_POST['gender']       ?? null,
            // ✅ correct: store hashed password under password_hash
            'password_hash' => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT)
        ];

        // 3️⃣ Check for duplicates
        if ($userModel->exists('email', $data['email'])) {
            $_SESSION['error'] = "Email already exists. Please use another one.";
            header("Location: ../views/auth/register.php");
            exit();
        }

        if ($userModel->exists('phone', $data['phone'])) {
            $_SESSION['error'] = "Phone number already exists. Please use another one.";
            header("Location: ../views/auth/register.php");
            exit();
        }

        if ($userModel->exists('national_id', $data['national_id'])) {
            $_SESSION['error'] = "National ID already registered.";
            header("Location: ../views/auth/register.php");
            exit();
        }

        // 4️⃣ Create the user
        if ($userModel->create($data)) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: ../views/auth/login.php");
        } else {
            $_SESSION['error'] = "Registration failed! Please try again.";
            header("Location: ../views/auth/register.php");
        }
        exit();
    }

    // ================= LOGIN =================
    if (isset($_POST['login'])) {

        $username_email = $_POST['username_email'] ?? null;
        $password       = $_POST['password']       ?? null;

        $user = $userModel->findByUsernameEmailOrPhone($username_email);

        if ($user && password_verify($password, $user['password_hash'])) {
            // ✅ Set session variables
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role_global'] ?? 'member';

            // Redirect to dashboard
            header("Location: ../views/dashboards/main_dashboard.php");
        } else {
            $_SESSION['error'] = "Invalid credentials!";
            header("Location: ../views/auth/login.php");
        }
        exit();
    }
}
?>
