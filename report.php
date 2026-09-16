<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Item - OLFU Lost & Found</title>
    <link rel="stylesheet" href="frontend/css/styles.css">
</head>
<body>

    <header class="navbar">
        <div class="nav-container">
            <h1 class="logo">OLFU <span>Lost & Found</span></h1>
            <nav>
                <a href="index.php">← Back to Feed</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="filter-card form-wrapper">
            <h2>Post a Lost or Found Item</h2>
            <p class="subtitle">Mag-upload ng picture at ilagay ang exact location sa Antipolo campus.</p>

            <form action="backend/add_item.php" method="POST" enctype="multipart/form-data">
                
                <label>Status ng Gamit:</label>
                <select name="status" required>
                    <option value="lost">Lost (Nawawala ko)</option>
                    <option value="found">Found (May napulot ako)</option>
                </select>

                <label>Item Name / Title:</label>
                <input type="text" name="item_name" placeholder="Halimbawa: Aquaflask Tumbler, Black Wallet" required>

                <label>Antipolo Campus Building / Spot:</label>
                <select name="location" required>
                    <option value="Main Building">Main Building</option>
                    <option value="St. Joseph Building">St. Joseph Building</option>
                    <option value="Library">Library</option>
                    <option value="Canteen">Canteen / Cafeteria</option>
                    <option value="Gymnasium">Gymnasium</option>
                    <option value="Guard House">Main Gate / Guard House</option>
                </select>

                <label>Specific Spot Description:</label>
                <input type="text" name="spot_details" placeholder="Halimbawa: 3rd floor hallway bench, tapat ng Room 302">

                <!-- IMAGE UPLOAD & PREVIEW BOX -->
                <label>Upload Picture:</label>
                <input type="file" id="imageInput" name="item_image" accept="image/*" required>

                <!-- DITO LILITAW ANG PREVIEW -->
                <div id="previewContainer" class="preview-box">
                    <img id="imagePreview" src="" alt="Image Preview" style="display: none;">
                    <span id="previewPlaceholder">Walang napiling image</span>
                </div>

                <button type="submit" class="btn-submit">Publish Post</button>
            </form>
        </section>
    </main>





    <script>
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        const previewPlaceholder = document.getElementById('previewPlaceholder');

        if (imageInput) {
            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        previewPlaceholder.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.src = '';
                    imagePreview.style.display = 'none';
                    previewPlaceholder.style.display = 'block';
                }
            });
        }
    </script>
</body>
</html>