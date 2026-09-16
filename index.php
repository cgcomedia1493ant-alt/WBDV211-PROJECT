<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLFU Antipolo - Lost & Found Portal</title>
    <link rel="stylesheet" href="frontend/css/styles.css">
</head>
<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="nav-container">
            <h1 class="logo">OLFU <span>Lost & Found</span></h1>
            <nav>
                <a href="report.php" class="btn-report">+ Report Item</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- FILTER SECTION -->
        <section class="filter-card">
            <h2>Campus Belongings Feed</h2>
            <p style="color:#666; font-size:0.9rem; margin-top:4px;">Tingnan ang mga nawawala at napulot na gamit sa Antipolo campus.</p>
        </section>

        <!-- ITEMS GRID FEED -->
        <section class="items-grid" id="itemsContainer">
            <?php
            $upload_dir = 'uploads/';
            $images = glob($upload_dir . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);

            if (!empty($images)):
                // Ayusin para pinakabagong upload ang mauna
                rsort($images);
                foreach ($images as $img_path):
                    $filename = basename($img_path);
                    // Tanggalin ang timestamp prefix para malinis ang title
                    $display_name = preg_replace('/^\d+_/', '', pathinfo($filename, PATHINFO_FILENAME));
            ?>
                <article class="item-card">
                    <span class="badge badge-lost">Lost</span>
                    <div class="card-img-wrapper">
                        <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($display_name); ?>">
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($display_name); ?></h3>
                        <p class="location">📍 Antipolo Campus</p>
                        <button class="btn-claim" onclick="openClaimModal('<?php echo htmlspecialchars($display_name); ?>')">Claim Item</button>
                    </div>
                </article>
            <?php 
                endforeach;
            else: 
            ?>
                <div style="grid-column: 1 / -1; text-align:center; padding:3rem; background:white; border-radius:8px;">
                    <p style="color:#888; font-size:1.1rem;">Walang naka-post na gamit sa ngayon.</p>
                    <a href="report.php" style="color:var(--olfu-green, #005a36); font-weight:600; text-decoration:none;">Mag-report ng nawawala o napulot &rarr;</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- CLAIM MODAL POPUP -->
    <div id="claimModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeClaimModal()">&times;</span>
            <h3 id="modalItemTitle">Claim Item</h3>
            <p>Mag-provide ng proof o exact description para ma-verify sa Guard House / Finder:</p>
            <form id="claimForm" onsubmit="event.preventDefault(); alert('Claim submitted! Puntahan ang Guard House para sa verification.'); closeClaimModal();">
                <label>Student Email:</label>
                <input type="email" placeholder="student@fatima.edu.ph" required>
                <label>Proof / Identifying Details:</label>
                <textarea rows="3" placeholder="Halimbawa: May scratch sa ilalim, stickers, laman sa loob..." required></textarea>
                <button type="submit" class="btn-submit">Send Claim Request</button>
            </form>
        </div>
    </div>

    <script src="frontend/js/app.js"></script>
</body>
</html>