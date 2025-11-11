document.addEventListener('DOMContentLoaded', function() {
    // Elementos principales
    const form = document.getElementById('vehicleForm');
    const clientMessage = document.getElementById('clientMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    // Elementos de la foto
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('photo');
    const previewImage = document.getElementById('previewImage');
    const removePhotoBtn = document.getElementById('removePhoto');
    const previewContainer = document.getElementById('previewContainer');
    
    // Avatar dropdown
    const avatarBtn = document.getElementById('avatarBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    
    // ===== INICIALIZACIÓN =====
    function initialize() {
        // Inicialmente ocultar el preview
        if (previewContainer) {
            previewContainer.style.display = 'none';
        }
        
        // Verificar que todos los elementos necesarios existen
        if (!uploadArea || !fileInput || !previewImage || !previewContainer) {
            console.error('Error: Faltan elementos del DOM necesarios para la funcionalidad de foto');
            return;
        }
        
        setupAvatarDropdown();
        setupPhotoUpload();
        setupFormValidation();
        
        console.log('Vehicle form initialized successfully');
    }

    // ===== AVATAR DROPDOWN =====
    function setupAvatarDropdown() {
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

    // ===== FUNCIONALIDAD DE FOTO =====
    function setupPhotoUpload() {
        // Upload area click
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleFileSelection(file);
            }
        });

        // Remove photo
        if (removePhotoBtn) {
            removePhotoBtn.addEventListener('click', function() {
                resetPhotoUpload();
            });
        }

        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                handleFileSelection(file);
            }
        });
    }

    // ===== MANEJO DE ARCHIVOS =====
    function handleFileSelection(file) {
        // Validar tipo de archivo
        if (!file.type.startsWith('image/')) {
            showMessage('Please select a valid image file (PNG, JPG, JPEG).', 'error');
            return;
        }
        
        // Validar tamaño (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showMessage('File size must be less than 5MB', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            showPhotoPreview();
        };
        
        reader.onerror = function() {
            showMessage('Error reading the file. Please try another image.', 'error');
        };
        
        reader.readAsDataURL(file);
    }

    function showPhotoPreview() {
        uploadArea.style.display = 'none';
        previewContainer.style.display = 'flex';
        previewContainer.classList.add('show');
    }

    function resetPhotoUpload() {
        fileInput.value = '';
        previewContainer.style.display = 'none';
        previewContainer.classList.remove('show');
        uploadArea.style.display = 'flex';
        previewImage.src = '';
    }

    // ===== VALIDACIÓN DEL FORMULARIO =====
    function setupFormValidation() {
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    showMessage('Please fix the errors before submitting.', 'error');
                } else {
                    // Mostrar loading pero permitir que el formulario se envíe normalmente
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = 'Creating Vehicle...';
                    submitBtn.disabled = true;
                }
            });
        }
    }

    function validateForm() {
        let isValid = true;

        // Validar campos requeridos
        const requiredFields = ['plate', 'color', 'brand', 'model', 'year', 'seats'];
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (!field || !field.value.trim()) {
                isValid = false;
                field.style.borderColor = 'var(--error)';
                field.addEventListener('input', function() {
                    this.style.borderColor = '';
                }, { once: true });
            }
        });

        // Validación de año
        const yearInput = document.getElementById('year');
        if (yearInput && yearInput.value) {
            const currentYear = new Date().getFullYear();
            const yearValue = parseInt(yearInput.value);
            if (yearValue < 1980 || yearValue > currentYear + 1) {
                isValid = false;
                yearInput.style.borderColor = 'var(--error)';
            }
        }

        // Validación de asientos
        const seatsInput = document.getElementById('seats');
        if (seatsInput && seatsInput.value) {
            const seatsValue = parseInt(seatsInput.value);
            if (seatsValue < 1 || seatsValue > 9) {
                isValid = false;
                seatsInput.style.borderColor = 'var(--error)';
            }
        }

        return isValid;
    }

    // ===== MOSTRAR MENSAJES =====
    function showMessage(message, type) {
        if (!clientMessage) return;
        
        clientMessage.innerHTML = `
            <div class="alert ${type}">
                ${message}
            </div>
        `;
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (clientMessage) clientMessage.innerHTML = '';
        }, 5000);
        
        // Scroll suave al mensaje
        clientMessage.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'nearest' 
        });
    }

    // Inicializar la aplicación
    initialize();
});