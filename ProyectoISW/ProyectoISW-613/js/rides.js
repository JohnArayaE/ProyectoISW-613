document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const avatarBtn = document.getElementById('avatarBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const deleteButtons = document.querySelectorAll('[data-delete]');
    const systemMessage = document.querySelector('.system-message');

    // Inicializar funcionalidades
    initProfileMenu();
    initDeleteButtons();
    autoHideMessage();

    /**
     * INICIALIZAR MENÚ DE PERFIL
     */
    function initProfileMenu() {
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

    /**
     * INICIALIZAR BOTONES DE ELIMINAR
     */
    function initDeleteButtons() {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const rideId = this.getAttribute('data-delete');
                const rideName = this.closest('.ride-card').querySelector('.ride-name').textContent;
                
                if (confirm(`Are you sure you want to delete the ride "${rideName}"?`)) {
                    deleteRide(rideId);
                }
            });
        });
    }

    /**
     * ELIMINAR RIDE - VERSIÓN CORREGIDA
     */
    function deleteRide(rideId) {
        // Mostrar loading en el botón
        const deleteBtn = document.querySelector(`[data-delete="${rideId}"]`);
        const originalText = deleteBtn.textContent;
        deleteBtn.textContent = 'Deleting...';
        deleteBtn.disabled = true;

        // Enviar solicitud de eliminación
        fetch(`actions/RidesAc.php?action=delete&id=${rideId}`, {
            method: 'GET',
            redirect: 'follow' // IMPORTANTE: Seguir redirecciones
        })
        .then(response => {
            // El PHP hace una redirección, así que recargamos la página
            // para ver el mensaje de éxito/error
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting ride. Please try again.');
            deleteBtn.textContent = originalText;
            deleteBtn.disabled = false;
        });
    }

    /**
     * AUTO-OCULTAR MENSAJES DEL SISTEMA
     */
    function autoHideMessage() {
        if (systemMessage) {
            setTimeout(() => {
                systemMessage.style.opacity = '0';
                systemMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    systemMessage.remove();
                }, 500);
            }, 5000); // Ocultar después de 5 segundos
        }
    }

    /**
     * MANEJAR MENSAJES DE LA URL
     */
    function handleUrlMessages() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Scroll to top si hay mensajes
        if (urlParams.has('success') || urlParams.has('error')) {
            window.scrollTo(0, 0);
        }
    }

    // Manejar mensajes de la URL
    handleUrlMessages();
});