document.addEventListener('DOMContentLoaded', function() {
    // Elementos del formulario
    const form = document.getElementById('vehicleForm');
    const messageContainer = document.getElementById('messageContainer');
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
    
    // Inicialmente ocultar el preview
    if (previewContainer) previewContainer.style.display = 'none';

    // ===== AVATAR DROPDOWN =====
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

    // ===== UPLOAD AREA CLICK =====
    if (uploadArea) {
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
    }

    // ===== FILE INPUT CHANGE =====
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleFileSelection(file);
            }
        });
    }

    // ===== REMOVE PHOTO =====
    if (removePhotoBtn) {
        removePhotoBtn.addEventListener('click', function() {
            resetPhotoUpload();
        });
    }

    // ===== DRAG AND DROP =====
    if (uploadArea) {
        // Prevenir comportamientos por defecto
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop area
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            uploadArea.classList.add('dragover');
        }

        function unhighlight() {
            uploadArea.classList.remove('dragover');
        }

        // Handle drop
        uploadArea.addEventListener('drop', function(e) {
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
        if (uploadArea) uploadArea.style.display = 'none';
        if (previewContainer) {
            previewContainer.style.display = 'flex';
            previewContainer.classList.add('show');
        }
    }

    function resetPhotoUpload() {
        if (fileInput) fileInput.value = '';
        if (previewContainer) {
            previewContainer.style.display = 'none';
            previewContainer.classList.remove('show');
        }
        if (uploadArea) uploadArea.style.display = 'flex';
        previewImage.src = '';
    }

    // ===== VALIDACIÓN DEL FORMULARIO =====
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault(); // ← Detener envío si hay errores
                showMessage('Please fix the errors before submitting.', 'error');
            } else {
                // Mostrar loading pero permitir que el formulario se envíe normalmente
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span>⏳</span> Creating Vehicle...';
                submitBtn.disabled = true;
                
                // El formulario se enviará normalmente (recargará la página)
                // El PHP se encargará de redirigir o mostrar mensajes
            }
        });
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
                showMessage('Please enter a valid manufacturing year (1980 - ' + (currentYear + 1) + ')', 'error');
            }
        }

        // Validación de asientos
        const seatsInput = document.getElementById('seats');
        if (seatsInput && seatsInput.value) {
            const seatsValue = parseInt(seatsInput.value);
            if (seatsValue < 1 || seatsValue > 9) {
                isValid = false;
                seatsInput.style.borderColor = 'var(--error)';
                showMessage('Seating capacity must be between 1 and 9', 'error');
            }
        }

        return isValid;
    }

    // ===== MOSTRAR MENSAJES =====
    function showMessage(message, type) {
        if (!messageContainer) return;
        
        messageContainer.innerHTML = `
            <div class="alert ${type}">
                ${message}
            </div>
        `;
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (messageContainer) messageContainer.innerHTML = '';
        }, 5000);
        
        // Scroll suave al mensaje
        messageContainer.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'nearest' 
        });
    }

    // ===== VALIDACIONES EN TIEMPO REAL =====
    
    const yearInput = document.getElementById('year');
    if (yearInput) {
        yearInput.addEventListener('blur', function() {
            const currentYear = new Date().getFullYear();
            const yearValue = parseInt(this.value);
            
            if (this.value && (yearValue < 1980 || yearValue > currentYear + 1)) {
                this.style.borderColor = 'var(--error)';
            } else {
                this.style.borderColor = '';
            }
        });
    }

    const seatsInput = document.getElementById('seats');
    if (seatsInput) {
        seatsInput.addEventListener('blur', function() {
            const seatsValue = parseInt(this.value);
            
            if (this.value && (seatsValue < 1 || seatsValue > 9)) {
                this.style.borderColor = 'var(--error)';
            } else {
                this.style.borderColor = '';
            }
        });
    }

    console.log('Vehicle form initialized successfully 🚗');
});