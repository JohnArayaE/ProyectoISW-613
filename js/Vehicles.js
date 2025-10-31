// js/vehicles.js

// Función para manejar el menú de perfil
function initProfileMenu() {
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
}

// Función para manejar botones de editar
function initEditButtons() {
    document.querySelectorAll('[data-edit]').forEach(button => {
        button.addEventListener('click', function() {
            const vehicleId = this.getAttribute('data-edit');
            // Redirigir a la página de edición
            window.location.href = 'EditVehicle.php?id=' + vehicleId;
        });
    });
}

// Función para manejar botones de eliminar
function initDeleteButtons() {
    document.querySelectorAll('[data-delete]').forEach(button => {
        button.addEventListener('click', function() {
            const vehicleId = this.getAttribute('data-delete');
            const vehiclePlate = this.closest('.veh-card').querySelector('.veh-plate').textContent;
            
            if (confirm(`Are you sure you want to delete the vehicle with license plate ${vehiclePlate}? This action cannot be undone.`)) {
                // Mostrar loading
                this.innerHTML = 'Deleting...';
                this.disabled = true;
                
                // Redirigir para eliminar usando el sistema unificado
                window.location.href = 'actions/VehiclesAc.php?action=delete&id=' + vehicleId;
            }
        });
    });
}

// Función para mostrar mensajes de éxito/error
function initMessages() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('success')) {
        const successMessages = {
            'vehicle_created': 'Vehicle created successfully!',
            'vehicle_updated': 'Vehicle updated successfully!',
            'vehicle_deleted': 'Vehicle deleted successfully!'
        };
        
        const message = successMessages[urlParams.get('success')];
        if (message) {
            showMessage(message, 'success');
        }
    }
    
    if (urlParams.has('error')) {
        const errorMessages = {
            'vehicle_not_found': 'Vehicle not found.',
            'delete_failed': 'Error deleting vehicle.',
            'database_error': 'Database error.',
            'invalid_id': 'Invalid vehicle ID.',
            'invalid_action': 'Invalid action.'
        };
        
        const message = errorMessages[urlParams.get('error')];
        if (message) {
            showMessage(message, 'error');
        }
    }
}

// Función para mostrar mensajes temporales
function showMessage(text, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type}`;
    messageDiv.textContent = text;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        z-index: 1000;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 300px;
    `;
    
    if (type === 'success') {
        messageDiv.style.background = '#4CAF50';
    } else {
        messageDiv.style.background = '#f44336';
    }
    
    document.body.appendChild(messageDiv);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

/**
 * Función específica para EditVehicle.php
 * Maneja la inicialización de la preview de imagen y menú de perfil
 */
function initEditVehiclePage() {
    // Inicializar preview con la imagen actual si existe
    const previewContainer = document.getElementById('previewContainer');
    const currentPhoto = document.getElementById('previewImage')?.getAttribute('src');
    
    if (previewContainer && currentPhoto) {
        previewContainer.style.display = 'block';
    }

    // El menú de perfil ya está manejado por initProfileMenu()
}

// Inicializar según la página
document.addEventListener('DOMContentLoaded', function() {
    // Detectar si estamos en la página de edición
    const isEditPage = document.querySelector('input[name="vehicle_id"]') !== null;
    
    if (isEditPage) {
        initEditVehiclePage();
    }
    
    // Las siguientes funciones se ejecutan en TODAS las páginas que cargan vehicles.js
    initProfileMenu();
    initEditButtons();
    initDeleteButtons();
    initMessages();
    
    // Auto-ocultar mensajes del sistema después de 5 segundos
    const systemMessage = document.querySelector('.system-message');
    if (systemMessage) {
        setTimeout(() => {
            systemMessage.style.opacity = '0';
            setTimeout(() => {
                systemMessage.remove();
            }, 500);
        }, 5000);
    }
});