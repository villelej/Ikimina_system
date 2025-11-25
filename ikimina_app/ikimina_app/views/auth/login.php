<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Ikimina</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <!-- Material Icons for eye icon, email, and lock -->
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

    <div class="auth-container">
        <div class="logo">
            <span class="material-icons">bolt</span> Ikimina
        </div>
        <h2>Sign in with email</h2>
        <!-- Corrected description text -->
        <p class="form-description">Make a new doc to control your words, data, money<br>and teams together. in Ikimina app</p>

        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <form method="POST" action="../../controllers/AuthController.php">
            <div class="form-group">
                <input type="text" name="username_email" placeholder="Username/Email/Phone" required value="<?php echo htmlspecialchars($_POST['username_email'] ?? ''); ?>">
                <span class="material-icons icon">mail_outline</span>
            </div>

            <div class="form-group password-input">
                <input type="password" name="password" placeholder="Password" id="loginPassword" required>
                <span class="material-icons icon lock-icon">lock</span>
                <span class="material-icons icon visibility" onclick="togglePasswordVisibility('loginPassword')">visibility_off</span>
            </div>

            <div class="forgot-password">
                <a href="#">Forgot password?</a>
            </div>

            <button type="submit" name="login">Get Started</button>
        </form>

        <div class="links" style="margin-top: 20px; margin-bottom: 20px;">Or sign in with</div>

        <div class="social-buttons">
            <button class="social-button">
                <img src="https://img.icons8.com/color/24/000000/google-logo.png" alt="Google">
            </button>
            <button class="social-button">
                <img src="https://img.icons8.com/ios-filled/24/000000/facebook-new.png" alt="Facebook">
            </button>
            <button class="social-button">
                <img src="https://img.icons8.com/ios-filled/24/000000/mac-os.png" alt="Apple">
            </button>
        </div>

        <div class="links" style="margin-top: 30px;">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(id) {
            const passwordField = document.getElementById(id);
            // Find the visibility icon within the same form-group
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