// Sort change
document.getElementById('ordenSelect').addEventListener('change', function() {
    const url = new URL(window.location);
    url.searchParams.set('orden', this.value);
    window.location.href = url.toString();
});

let rideActual = null;

// Book ride function
function reservarRide(rideId) {
    console.log('Booking ride ID:', rideId);
    
    // Get ride information
    fetch('actions/ObtenerRide.php?ride_id=' + rideId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Server error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('ObtenerRide response:', data);
            if (data.success) {
                rideActual = data.ride;
                mostrarModalReserva();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading ride information: ' + error.message, 'error');
        });
}

// Show reservation modal
function mostrarModalReserva() {
    const modal = document.getElementById('reservaModal');
    const rideInfo = document.getElementById('modalRideInfo');
    
    if (!rideActual) {
        showNotification('Error: Could not load ride information', 'error');
        return;
    }
    
    const maxEspacios = Math.min(rideActual.espacios_disponibles, rideActual.capacidad - 1);
    
    rideInfo.innerHTML = `
        <div class="ride-info-modal">
            <h4>${rideActual.nombre_ride}</h4>
            <p><strong>Route:</strong> ${rideActual.lugar_salida} → ${rideActual.lugar_llegada}</p>
            <p><strong>Day:</strong> ${rideActual.dia_semana}</p>
            <p><strong>Time:</strong> ${rideActual.hora.substring(0,5)}</p>
            <p><strong>Vehicle:</strong> ${rideActual.marca} ${rideActual.modelo}</p>
            <p><strong>Available spaces:</strong> ${rideActual.espacios_disponibles}</p>
        </div>
    `;
    
    const cantidadInput = document.getElementById('cantidadEspacios');
    cantidadInput.value = 1;
    cantidadInput.max = maxEspacios;
    cantidadInput.min = 1;
    
    document.getElementById('maxEspaciosInfo').textContent = `Maximum: ${maxEspacios} spaces`;
    document.getElementById('costoPorEspacio').textContent = `₡${parseFloat(rideActual.costo).toFixed(2)}`;
    
    actualizarPrecioTotal();
    modal.showModal();
}

// Update total price
function actualizarPrecioTotal() {
    if (!rideActual) return;
    
    const cantidad = parseInt(document.getElementById('cantidadEspacios').value) || 1;
    const costoTotal = cantidad * rideActual.costo;
    document.getElementById('costoTotal').textContent = `₡${costoTotal.toFixed(2)}`;
}

// Show notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(notification);

    // Remove after 4 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 4000);
}

// Modal configuration
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('reservaModal');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelarReserva');
    const confirmBtn = document.getElementById('confirmarReserva');
    const cantidadInput = document.getElementById('cantidadEspacios');
    
    // Close modal
    closeBtn.onclick = function() {
        modal.close();
        rideActual = null;
    }
    
    cancelBtn.onclick = function() {
        modal.close();
        rideActual = null;
    }
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.close();
            rideActual = null;
        }
    });
    
    // Update price when quantity changes
    cantidadInput.addEventListener('input', actualizarPrecioTotal);
    
    // Validate quantity input
    cantidadInput.addEventListener('change', function() {
        let value = parseInt(this.value) || 1;
        const max = parseInt(this.max) || 1;
        const min = parseInt(this.min) || 1;
        
        if (value < min) value = min;
        if (value > max) value = max;
        
        this.value = value;
        actualizarPrecioTotal();
    });
    
    // Confirm booking
    confirmBtn.onclick = function() {
        const cantidad = parseInt(cantidadInput.value) || 1;
        
        if (!rideActual) {
            showNotification('Error: No ride information', 'error');
            return;
        }
        
        if (cantidad < 1) {
            showNotification('Quantity must be at least 1', 'error');
            return;
        }
        
        if (cantidad > rideActual.espacios_disponibles) {
            showNotification('Not enough available spaces', 'error');
            return;
        }
        
        // Show loading
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        // Make reservation
        fetch('actions/reservarRide.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ride_id: rideActual.id,
                cantidad_espacios: cantidad
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            if (!response.ok) {
                throw new Error('Server error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('reservarRide response:', data);
            if (data.success) {
                showNotification(data.message, 'success');
                modal.close();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Complete error:', error);
            showNotification('Error making reservation: ' + error.message, 'error');
        })
        .finally(() => {
            // Restore button
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Confirm Booking';
        });
    }

    // Avatar dropdown menu script
    const avatarBtn = document.getElementById('avatarBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    if (avatarBtn && profileDropdown) {
        avatarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function() {
            profileDropdown.classList.remove('show');
        });

        profileDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});