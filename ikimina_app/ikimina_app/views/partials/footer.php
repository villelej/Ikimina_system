</div> <!-- Closes the .container div -->

    <!-- Global JavaScript files can go here if needed -->
    <!-- For example: <script src="../assets/js/global.js"></script> -->

    <!-- Specific dashboard JS for sidebar toggle -->
    <script>
        const sideMenu = document.querySelector("aside");
        const menuBtn = document.querySelector("#menu-btn");
        const closeBtn = document.querySelector("#close-btn");

        // Open sidebar on menu button click (for mobile)
        menuBtn.addEventListener('click', () => {
            sideMenu.style.display = 'block';
        });

        // Close sidebar on close button click (for mobile)