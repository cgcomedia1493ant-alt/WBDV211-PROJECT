// Open Claim Modal
function openClaimModal(itemTitle) {
    const modal = document.getElementById('claimModal');
    document.getElementById('modalItemTitle').innerText = 'Claim: ' + itemTitle;
    modal.style.display = 'flex';
}

// Close Claim Modal
function closeClaimModal() {
    document.getElementById('claimModal').style.display = 'none';
}

// Close when clicking outside box
window.onclick = function(event) {
    const modal = document.getElementById('claimModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

// Form submit test
document.getElementById('claimForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Claim request sent! Pakidala ang iyong school ID sa designated location para sa verification.');
    closeClaimModal();
});

// REAL-TIME IMAGE PREVIEW LOGIC
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
            imagePreview.style.display = 'none';
            previewPlaceholder.style.display = 'block';
        }
    });
}