<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LabCare - Laboratory Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Geist Sans (Correct Version With All Weights) -->
    <link href="https://cdn.jsdelivr.net/npm/geist-font@latest/dist/geist-sans/style.css" rel="stylesheet">

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <!-- CSS -->
    <link rel="stylesheet" href="css/landing.css">
</head>
<body>

<!-- ================= NAV ================= -->
<nav class="nav">
    <div class="container nav-flex">
        <div class="logo">
            <img src="assets/labcare.png" alt="LabCare Logo">
        </div>

        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#about">About Us</a>
            <a href="login.php" class="login-btn">Log In</a>
        </div>
    </div>
</nav>

<!-- ================= HERO ================= -->
<section class="hero">
    <img src="assets/landing_bg.png" class="hero-bg" alt="">

    <div class="container hero-layout">
        <div class="hero-content">
            <h1>
                Command Center for Computer<br>
                Laboratory Assets
            </h1>

            <p>
                Streamline inventory tracking, unified maintenance reporting,
                and real-time analytics for CvSU-CCC.
            </p>

            <div class="btn-row">
                <a href="login.php" class="btn">Get Started</a>
            </div>
        </div>
    </div>
</section>

<!-- ================= FEATURES ================= -->
<section id="features" class="features-section">
    <div class="container">
        <h2>LabCare Features</h2>

        <div class="features-grid">

            <div class="feature-card">
                <span class="material-symbols-outlined feature-icon">monitor</span>
                <h3>Comprehensive Inventory</h3>
                <p>
                    <strong>Track Everything.</strong> Manage detailed specifications of Computer Units and general Facility Assets (TVs, ACs) in one centralized database.
                </p>
            </div>

            <div class="feature-card">
                <span class="material-symbols-outlined feature-icon">dashboard</span>
                <h3>Unified Maintenance</h3>
                <p>
                    <strong>From Report to Resolution.</strong> Oversee the repair lifecycle. Review issues submitted by IT Staff, authorize repairs, and track asset history.
                </p>
            </div>

            <div class="feature-card">
                <span class="material-symbols-outlined feature-icon">bar_chart</span>
                <h3>Data-Driven Insights</h3>
                <p>
                    <strong>Real-Time Analytics.</strong> View laboratory conditions at a glance. Generate statistical reports on equipment health and repair frequency.
                </p>
            </div>

            <div class="feature-card">
                <span class="material-symbols-outlined feature-icon">admin_panel_settings</span>
                <h3>User Administration</h3>
                <p>
                    <strong>Secure Access Control.</strong> Manage IT Staff accounts, assign laboratory designations, and oversee system security permissions.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- ================= ABOUT ================= -->
<section id="about" class="about-section">
    <div class="container">

        <h2>Meet the Minds Behind LabCare</h2>

        <div class="about-image-wrapper">
            <img src="assets/about.png" alt="LabCare Team">
        </div>

        <div class="about-content">

            <h3>Who We Are</h3>
            <p>
                We are the developers of LabCare—a team of 4th-year Computer Science students at CvSU-CCC.
                We are passionate about using technology to create meaningful solutions for our university.
            </p>

            <h3>Our Mission</h3>
            <p>
                To elevate laboratory management into a seamless, data-driven experience.
                We aim to modernize campus operations, ensuring that technology serves as a reliable bridge to quality education.
            </p>

            <h3>Why We Built This</h3>
            <p>
                We created this system to give back to the campus that shaped us.
                By optimizing asset tracking and maintenance, our goal is to support our Instructors and IT Staff
                in providing a world-class learning environment for the next generation of students.
            </p>

        </div>

    </div>
</section>

<!-- ================= FOOTER ================= -->
<footer class="footer">
    <p>
        © 2025 CvSU-CCC LabCare System. All Rights Reserved. <br>
        Disclaimer: Authorized Personnel Only.
    </p>
</footer>

</body>
</html>