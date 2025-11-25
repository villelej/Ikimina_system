<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Ikimina</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <!-- Material Icons for all input fields -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success">
    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="alert alert-danger">
    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
  </div>
<?php endif; ?>

    <div class="auth-container register-form">
        <div class="logo">
            <span class="material-icons">bolt</span> Ikimina
        </div>
        <h2>Create an Ikimina Account</h2>
        <p class="form-description">Join us to organize your thoughts, data, and teams effortlessly.</p>

        <!-- ================= ERROR MESSAGE ================= -->
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <!-- ================= SUCCESS MESSAGE ================= -->
        <?php if (isset($_SESSION['success'])): ?>
            <p class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <form method="POST" action="../../controllers/AuthController.php">
            <!-- First Row -->
            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="first_name" placeholder="First Name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    <span class="material-icons icon">person_outline</span>
                </div>
                <div class="form-group">
                    <input type="text" name="middle_name" placeholder="Middle Name (Optional)" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                    <span class="material-icons icon">person_outline</span>
                </div>
            </div>

            <!-- Second Row -->
            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="last_name" placeholder="Last Name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    <span class="material-icons icon">person_outline</span>
                </div>
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <span class="material-icons icon">account_circle</span>
                </div>
            </div>

            <!-- Third Row -->
            <div class="form-row">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <span class="material-icons icon">mail_outline</span>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Phone Number" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    <span class="material-icons icon">phone</span>
                </div>
            </div>

            <!-- Fourth Row (National ID) -->
            <div class="form-group">
                <input type="text" name="national_id" placeholder="National ID" required value="<?php echo htmlspecialchars($_POST['national_id'] ?? ''); ?>">
                <span class="material-icons icon">credit_card</span>
            </div>

            <!-- Fifth Row (DOB & Gender) -->
            <div class="form-row">
                <div class="form-group">
                    <input type="date" name="date_of_birth" title="Date of Birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    <span class="material-icons icon">event</span>
                </div>
                <div class="form-group">
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <span class="material-icons icon">wc</span>
                </div>
            </div>

            <!-- Password Fields -->
            <div class="form-group password-input">
                <input type="password" name="password" placeholder="Password" id="registerPassword" required onkeyup="checkPasswordStrength(this.value, 'passStrength')">
                <span class="material-icons icon lock-icon">lock</span>
                <span class="material-icons icon visibility" onclick="togglePasswordVisibility('registerPassword')">visibility_off</span>
                <div id="passStrength" class="password-strength"></div>
            </div>

            <div class="form-group password-input">
                <input type="password" name="confirm_password" placeholder="Confirm Password" id="confirmPassword" required onkeyup="checkPasswordStrength(this.value, 'confirmPassStrength')">
                <span class="material-icons icon lock-icon">lock</span>
                <span class="material-icons icon visibility" onclick="togglePasswordVisibility('confirmPassword')">visibility_off</span>
                <div id="confirmPassStrength" class="password-strength"></div>
            </div>

            <button type="submit" name="register">Register</button>
        </form>

        <div class="links" style="margin-top: 30px;">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password, id) {
            let strength = 0;
            let msg = "";
            let className = "";

            if (password.length >= 8) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[@$!%*?&]/)) strength += 1;

            switch(strength) {
                case 0: msg = "Very Weak"; className = "very-weak"; break;
                case 1: msg = "Weak"; className = "weak"; break;
                case 2: msg = "Medium"; className = "medium"; break;
                case 3: msg = "Strong"; className = "strong"; break;
                case 4: msg = "Very Strong"; className = "very-strong"; break;
            }
            const strengthElement = document.getElementById(id);
            strengthElement.innerText = msg;
            strengthElement.className = `password-strength ${className}`;
        }

        function togglePasswordVisibility(id) {
            const passwordField = document.getElementById(id);
            const icon = passwordField.parentNode.querySelector('.visibility');

            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.innerText = "visibility";
            } else {
                passwordField.type = "password";
                icon.innerText = "visibility_off";
            }
        }
    </script>
</body>
</html>
