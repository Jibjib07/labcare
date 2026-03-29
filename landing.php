<?php
// 1. Get protocol (http or https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

// 2. Auto-detect the LAN IP address instead of using 'localhost'
$host = $_SERVER['HTTP_HOST'];
if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
    // This forces PHP to fetch the machine's actual network IPv4 address
    $host = gethostbyname(gethostname());
}

// 3. Build the final shareable URL
$shareUrl = $protocol . "://" . $host . "/labcare/landing.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>LabCare - Laboratory Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/geist-font@latest/dist/geist-sans/style.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <link rel="stylesheet" href="css/landing.css">
</head>

<body>

    <nav class="nav">
        <div class="container nav-flex">
            <div class="logo">
                <img src="assets/labcare.png" alt="LabCare Logo">
            </div>

            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#about">About Us</a>
                <a href="#" id="shareAccessBtn" style="cursor: pointer;">Share Access</a>
                <a href="login.php" class="login-btn">Log In</a>
            </div>
        </div>
    </nav>

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

    <footer class="footer">
        <p>
            © 2025 CvSU-CCC LabCare System. All Rights Reserved. <br>
            Disclaimer: Authorized Personnel Only.
        </p>
    </footer>

    <div id="shareModal" class="share-modal-overlay">
        <div class="share-modal-content">
            <div class="share-modal-header">
                <h3>Share System Access</h3>
                <button id="closeShareModal" class="close-btn">&times;</button>
            </div>

            <div class="share-modal-body">
                <p class="share-instruction">Scan the QR code or copy the link below to access LabCare from another device in the network.</p>

                <div id="qrcode" class="qr-container"></div>

                <div class="copy-link-box">
                    <span id="shareLinkText" class="share-link-text"><?php echo $shareUrl; ?></span>
                    <button id="copyLinkBtn" class="copy-icon-btn" title="Copy link">
                        <span class="material-symbols-outlined icon-display">content_copy</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('shareModal');
            const openBtn = document.getElementById('shareAccessBtn');
            const closeBtn = document.getElementById('closeShareModal');
            const copyBtn = document.getElementById('copyLinkBtn');
            const linkText = document.getElementById('shareLinkText').innerText;
            const iconDisplay = copyBtn.querySelector('.icon-display');

            // 1. Generate the QR Code dynamically based on the PHP URL
            new QRCode(document.getElementById("qrcode"), {
                text: linkText,
                width: 180,
                height: 180,
                colorDark: "#1f4d3c", // LabCare Dark Green
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // 2. Open Modal
            openBtn.addEventListener('click', (e) => {
                e.preventDefault();
                modal.style.display = 'flex';
            });

            // 3. Close Modal (X Button)
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            // 4. Close Modal (Clicking outside)
            window.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });

            // 5. Copy to Clipboard Logic
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(linkText).then(() => {
                    // Change to checkmark briefly to show success
                    iconDisplay.innerText = 'check';
                    iconDisplay.style.color = '#0f9d58'; // Success green

                    setTimeout(() => {
                        // Revert back to copy icon
                        iconDisplay.innerText = 'content_copy';
                        iconDisplay.style.color = '#456de6';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            });
        });
    </script>
</body>

</html>