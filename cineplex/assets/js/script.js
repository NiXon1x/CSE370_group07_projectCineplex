document.addEventListener('DOMContentLoaded', function () {
    // Seat selection functionality
    const seatMap = document.querySelector('.seat-map');
    if (seatMap) {
        seatMap.addEventListener('click', function (e) {
            const seat = e.target.closest('.seat');
            if (seat && seat.classList.contains('available')) {
                seat.classList.toggle('selected');
                updateSelectedSeats();
            }
        });
    }

    function updateSelectedSeats() {
        const selectedSeats = Array.from(document.querySelectorAll('.seat.selected'))
            .map(seat => seat.getAttribute('data-seat'));
        document.getElementById('selected-seats').value = selectedSeats.join(',');
    }

    // Auto-update checkbox functionality
    const autoUpdateCheckbox = document.getElementById('autoUpdate');
    if (autoUpdateCheckbox) {
        autoUpdateCheckbox.addEventListener('change', function () {
            if (this.checked) {
                alert('Your seats will be automatically updated to the best available if your selected seats are not available together.');
            }
        });
    }

    const cards = document.querySelectorAll('.animate__animated');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.2}s`;
    });
});
