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