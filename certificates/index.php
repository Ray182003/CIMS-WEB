<?php
$title = "Certificates";
require_once("../security_helper.php");
requireAuth();
include_once("../partials/html.head.php");
?>

<style>
    .certificates-bg {
        min-height: 100vh;
        background: linear-gradient(135deg, #61d2ff 0%, #7cecc2 50%, #ffe36e 100%);
        position: relative;
        overflow: hidden;
    }

    .certificates-bg::before,
    .certificates-bg::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        pointer-events: none;
        opacity: 0.3;
        background: rgba(255, 255, 255, 0.85);
    }

    .certificates-bg::before {
        width: 460px;
        height: 460px;
        top: -160px;
        right: -120px;
    }

    .certificates-bg::after {
        width: 380px;
        height: 380px;
        bottom: -140px;
        left: -110px;
    }

    .certificates-bg > * {
        position: relative;
        z-index: 1;
    }

    .certificate-card {
        border-radius: 1rem;
        box-shadow:
            0 12px 24px rgba(0, 0, 0, 0.28),
            0 18px 40px rgba(0, 0, 0, 0.25),
            0 0 28px rgba(80, 150, 255, 0.28);
        border: 1px solid rgba(0, 0, 0, 0.18);
        overflow: hidden;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(240, 240, 240, 0.92) 100%);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .certificate-card .card-header {
        border-bottom: none;
        font-weight: 600;
    }

    .certificate-card .card-body {
        background: rgba(255, 255, 255, 0.9);
        box-shadow: inset 0 1px 3px rgba(255, 255, 255, 0.65);
    }

    .certificate-card .btn {
        border-width: 2px;
        border-radius: 999px;
        box-shadow:
            inset 0 0 0 1px rgba(255, 255, 255, 0.55),
            0 6px 14px rgba(0, 0, 0, 0.22);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .certificate-card .btn:hover {
        transform: translateY(-2px);
        box-shadow:
            inset 0 0 0 1px rgba(255, 255, 255, 0.6),
            0 16px 28px rgba(0, 0, 0, 0.3);
    }

    .certificate-card:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow:
            0 18px 28px rgba(0, 0, 0, 0.42),
            0 40px 90px rgba(0, 0, 0, 0.58);
    }

    .certificate-card:active {
        transform: translateY(-2px) scale(0.99);
        box-shadow:
            0 10px 18px rgba(0, 0, 0, 0.4),
            0 28px 65px rgba(0, 0, 0, 0.5);
    }

    .certificates-title {
        font-family: 'Poppins', 'Segoe UI', sans-serif;
        font-weight: 800;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: #0a0a0a;
        text-shadow:
            0 3px 0 rgba(0, 0, 0, 0.35),
            0 8px 16px rgba(0, 0, 0, 0.35);
        transition: opacity 0.4s ease;
    }
</style>

<body class="sb-nav-fixed certificates-bg">
    <?php include_once("../partials/navbar.php"); ?>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 pt-4">
                    <h1 class="mb-4 certificates-title" id="certificatesTitle"></h1>
                    <div class="row g-4">


                        <div class="col-md-6 col-xl-4">
                            <div class="card certificate-card h-100">
                                <div class="card-header text-white" style="background-color: #87CEEB;">
                                    Baptismal
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p class="card-text flex-grow-1"></p>
                                    <a href="<?php echo BASE_URL; ?>/baptismal/" class="btn mt-3" style="border-color: #87CEEB; color: #87CEEB;">View</a>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-6 col-xl-4">
                            <div class="card certificate-card h-100">
                                <div class="card-header text-white" style="background-color: #FFB6C1;">
                                    Status of Liberty
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p class="card-text flex-grow-1"></p>
                                    <a href="<?php echo BASE_URL; ?>/liberty/" class="btn mt-3" style="border-color: #FFB6C1; color: #FFB6C1;">View</a>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-6 col-xl-4">
                            <div class="card certificate-card h-100">
                                <div class="card-header text-white" style="background-color: #98D8C8;">
                                    Confirmation
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p class="card-text flex-grow-1"></p>
                                    <a href="<?php echo BASE_URL; ?>/confirmation/" class="btn mt-3" style="border-color: #98D8C8; color: #98D8C8;">View</a>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-6 col-xl-4">
                            <div class="card certificate-card h-100">
                                <div class="card-header text-dark" style="background-color: #FFB6C1;">
                                    Permit to Marry
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p class="card-text flex-grow-1"></p>
                                    <a href="<?php echo BASE_URL; ?>/permit_to_marry/" class="btn mt-3" style="border-color: #FFB6C1; color: #FFB6C1;">View</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card certificate-card h-100">
                                <div class="card-header text-white" style="background-color: #D3D3D3; color: #333;">
                                    Death Certificate
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p class="card-text flex-grow-1"></p>
                                    <a href="<?php echo BASE_URL; ?>/cer_of_death/" class="btn mt-3" style="border-color: #D3D3D3; color: #666;">View</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
            <?php include_once("../partials/footer.php"); ?>
        </div>
    </div>
    <?php include_once("../partials/html.footer.php"); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Check if page needs refresh after backup import
            const lastBackupImport = sessionStorage.getItem('lastBackupImport');
            const currentPageVisit = Date.now();
            
            if (lastBackupImport && (currentPageVisit - parseInt(lastBackupImport)) < 10000) {
                // If backup was imported less than 10 seconds ago, refresh the page
                sessionStorage.removeItem('lastBackupImport');
                window.location.reload();
                return;
            }
            
            const titleElement = document.getElementById('certificatesTitle');
            if (!titleElement) return;

            const text = 'Certificates';
            const typeSpeed = 90;

            const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            async function runTitleAnimationOnce() {
                titleElement.style.opacity = '1';
                titleElement.textContent = '';

                for (let i = 1; i <= text.length; i++) {
                    titleElement.textContent = text.slice(0, i);
                    await wait(typeSpeed);
                }
            }

            runTitleAnimationOnce();
        });
    </script>
</body>

</html>