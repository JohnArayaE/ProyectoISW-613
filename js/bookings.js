document.addEventListener('DOMContentLoaded', function() {
    let currentBookingId = null;
    let currentAction = null;
    const modal = document.getElementById('confirmModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('confirmAction');
    const cancelBtn = document.getElementById('cancelAction');

    // Profile dropdown
    const avatarBtn = document.getElementById('avatarBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if (avatarBtn && profileDropdown) {
        avatarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function() {
            profileDropdown.classList.remove('show');
        });
    }

    // Event listeners para botones de acción
    document.querySelectorAll('[data-accept]').forEach(button => {
        button.addEventListener('click', function() {
            currentBookingId = this.dataset.accept;
            currentAction = 'accept';
            showModal(
                'Accept Booking',
                'Are you sure you want to accept this booking?',
                'Accept'
            );
        });
    });

    document.querySelectorAll('[data-reject]').forEach(button => {
        button.addEventListener('click', function() {
            currentBookingId = this.dataset.reject;
            currentAction = 'reject';
            showModal(
                'Reject Booking',
                'Are you sure you want to reject this booking?',
                'Reject'
            );
        });
    });

    document.querySelectorAll('[data-cancel]').forEach(button => {
        button.addEventListener('click', function() {
            currentBookingId = this.dataset.cancel;
            currentAction = 'cancel';
            showModal(
                'Cancel Booking',
                'Are you sure you want to cancel this booking?',
                'Cancel'
            );
        });
    });

    // Mostrar modal
    function showModal(title, message, confirmText) {
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        confirmBtn.textContent = confirmText;
        modal.showModal();
    }

    // Ocultar modal
    function hideModal() {
        modal.close();
        currentBookingId = null;
        currentAction = null;
    }

    // Confirmar acción
    confirmBtn.addEventListener('click', function() {
        if (currentBookingId && currentAction) {
            performAction(currentBookingId, currentAction);
        }
        hideModal();
    });

    // Cancelar acción
    cancelBtn.addEventListener('click', hideModal);

    // Cerrar modal al hacer clic fuera
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            hideModal();
        }
    });

    function performAction(bookingId, action) {
        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('action', action);

        // Mostrar loading
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        confirmBtn.disabled = true;

        fetch('./actions/BookingsAc.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            
            // Procesar respuesta en texto plano
            if (text.startsWith('SUCCESS:')) {
                const message = text.substring(8); // Remover "SUCCESS: "
                showNotification(message, 'success');
                // Recargar la página después de 1.5 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else if (text.startsWith('ERROR:')) {
                const message = text.substring(6); // Remover "ERROR: "
                showNotification(message, 'error');
            } else {
                showNotification('Respuesta no válida del servidor', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showNotification('Error de conexión: ' + error.message, 'error');
        })
        .finally(() => {
            // Restaurar botón
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        });
    }

    // Mostrar notificación
    function showNotification(message, type) {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            <span>${message}</span>
        `;

        // Estilos para las notificaciones
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
            ${type === 'success' ? 'background: #28a745;' : 'background: #dc3545;'}
        `;

        document.body.appendChild(notification);

        // Remover después de 4 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
});