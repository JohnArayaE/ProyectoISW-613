// Cambio de ordenamiento
document.getElementById('ordenSelect').addEventListener('change', function() {
    const url = new URL(window.location);
    url.searchParams.set('orden', this.value);
    window.location.href = url.toString();
});

// Función para reservar ride
function reservarRide(rideId) {
    if (!confirm('¿Estás seguro de que quieres reservar este ride?')) {
        return;
    }
    
    fetch('actions/reservar_ride.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ride_id: rideId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Reserva realizada exitosamente');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al realizar la reserva');
    });
}

// Animación de carga suave
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.ride-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
});