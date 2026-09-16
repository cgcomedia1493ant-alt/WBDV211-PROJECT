<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OLFU Antipolo - Lost & Found</title>
    <link rel="stylesheet" href="frontend/css/styles.css">
</head>
<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="nav-container">
            <h1 class="logo">OLFU <span>Lost & Found</span></h1>
            <nav>
                <a href="index.php" class="active">Browse Items</a>
                <a href="report.php" class="btn-report">+ Post an Item</a>
            </nav>
        </div>
    </header>

    <!-- HERO SECTION & FILTERS -->
    <main class="container">
        <section class="filter-card">
            <h2>Campus Feed (Antipolo Campus)</h2>
            <div class="filters">
                <input type="text" id="searchInput" placeholder="Search item (e.g. ID, Aquaflask)...">
                
                <select id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="lost">Lost Items</option>
                    <option value="found">Found Items</option>
                </select>

                <select id="locationFilter">
                    <option value="all">All Locations</option>
                    <option value="Main Building">Main Building</option>
                    <option value="St. Joseph Building">St. Joseph Building</option>
                    <option value="Library">Library</option>
                    <option value="Canteen">Canteen / Cafeteria</option>
                    <option value="Gymnasium">Gymnasium</option>
                    <option value="Guard House">Main Gate / Guard House</option>
                </select>
            </div>
        </section>

        <!-- ITEMS GRID (Mock items muna habang wala pang DB) -->
        <section class="items-grid" id="itemsContainer">
            <!-- Sample Card 1 -->
            <article class="card">
                <span class="badge badge-lost">LOST</span>
                <div class="card-img-placeholder">📷 No Image Uploaded</div>
                <div class="card-body">
                    <h3>Navy Blue Tumbler</h3>
                    <p class="meta">📍 Canteen • 2nd Floor Bench</p>
                    <p class="desc">May sticker ng white cat sa takip. Naiwan around 1 PM.</p>
                    <button class="btn-claim" onclick="openClaimModal('Navy Blue Tumbler')">Claim This Item</button>
                </div>
            </article>

            <!-- Sample Card 2 -->
            <article class="card">
                <span class="badge badge-found">FOUND</span>
                <div class="card-img-placeholder">📷 Image Preview</div>
                <div class="card-body">
                    <h3>OLFU Student ID</h3>
                    <p class="meta">📍 Library • Reading Area</p>
                    <p class="desc">Nasa desk kaninang umaga, turned over to Library in-charge.</p>
                    <button class="btn-claim" onclick="openClaimModal('OLFU Student ID')">Verify / Claim</button>
                </div>
            </article>
        </section>
    </main>

    <!-- CLAIM MODAL POPUP -->
    <div id="claimModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeClaimModal()">&times;</span>
            <h3 id="modalItemTitle">Claim Item</h3>
            <p>Mag-provide ng proof o exact description para ma-verify sa Guard House / Finder:</p>
            <form id="claimForm">
                <label>Student Email:</label>
                <input type="email" placeholder="student@fatima.edu.ph" required>
                <label>Proof / Identifying Details:</label>
                <textarea rows="3" placeholder="Halimbawa: May scratch sa likod, ano ang nakasulat sa loob..." required></textarea>
                <button type="submit" class="btn-submit">Send Claim Request</button>
            </form>
        </div>
    </div>

    <script src="frontend/js/app.js"></script>
</body>
</html>