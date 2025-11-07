document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const rideForm = document.getElementById('rideForm');
    const avatarBtn = document.getElementById('avatarBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const submitBtn = document.getElementById('submitBtn');
    const clientMessage = document.getElementById('clientMessage');
    const vehicleSelect = document.getElementById('id_vehiculo');
    const seatsInput = document.getElementById('espacios_totales');
    const dayCheckboxes = document.querySelectorAll('.day-input');

    // Inicializar funcionalidades
    initProfileMenu();
    initFormValidation();
    initVehicleCapacity();
    handleUrlMessages();

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
     * INICIALIZAR CAPACIDAD DEL VEHÍCULO
     */
    function initVehicleCapacity() {
        if (vehicleSelect && seatsInput) {
            vehicleSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    const capacity = parseInt(selectedOption.getAttribute('data-capacity'));
                    // Establecer el máximo basado en la capacidad del vehículo
                    seatsInput.max = capacity - 1; // -1 porque el conductor ocupa un asiento
                    seatsInput.placeholder = `Max ${capacity - 1}`;
                    
                    // Si el valor actual es mayor que el nuevo máximo, ajustarlo
                    if (parseInt(seatsInput.value) > capacity - 1) {
                        seatsInput.value = capacity - 1;
                    }
                }
            });
        }
    }

    /**
     * INICIALIZAR VALIDACIÓN DEL FORMULARIO
     */
    function initFormValidation() {
        if (rideForm) {
            rideForm.addEventListener('submit', handleFormSubmit);
            
            // Validación en tiempo real
            const inputs = rideForm.querySelectorAll('input:not(.day-input), select');
            inputs.forEach(input => {
                input.addEventListener('blur', validateField);
                input.addEventListener('input', clearFieldError);
            });

            // Validar días cuando cambian
            dayCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', validateDaySelection);
            });
        }
    }

    /**
     * MANEJAR ENVÍO DEL FORMULARIO
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        if (validateForm()) {
            // Mostrar loading
            submitBtn.innerHTML = '<span>⏳</span> Creating Ride...';
            submitBtn.disabled = true;
            
            // Enviar formulario
            rideForm.submit();
        }
    }

    /**
     * VALIDAR FORMULARIO COMPLETO
     */
    function validateForm() {
        let isValid = true;
        const fields = [
            'nombre_ride', 'id_vehiculo', 'lugar_salida', 'lugar_llegada', 
            'hora', 'espacios_totales', 'costo'
        ];

        fields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (!validateField(field)) {
                isValid = false;
            }
        });

        // Validar días de la semana
        if (!validateDaySelection()) {
            isValid = false;
        }

        return isValid;
    }

    /**
     * VALIDAR SELECCIÓN DE DÍAS
     */
    function validateDaySelection() {
        const checkedDays = Array.from(dayCheckboxes).filter(cb => cb.checked);
        
        if (checkedDays.length === 0) {
            showClientMessage('Please select at least one day of the week', 'error');
            return false;
        }
        
        return true;
    }

    /**
     * VALIDAR CAMPO INDIVIDUAL
     */
    function validateField(e) {
        const field = e.target ? e.target : e;
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Remover estado de error previo
        clearFieldError(field);

        // Validaciones específicas por campo
        switch (field.id) {
            case 'nombre_ride':
                if (!value) {
                    errorMessage = 'Ride name is required';
                    isValid = false;
                } else if (value.length < 2) {
                    errorMessage = 'Ride name must be at least 2 characters';
                    isValid = false;
                }
                break;

            case 'id_vehiculo':
                if (!value) {
                    errorMessage = 'Please select a vehicle';
                    isValid = false;
                }
                break;

            case 'lugar_salida':
            case 'lugar_llegada':
                if (!value) {
                    errorMessage = 'Location is required';
                    isValid = false;
                } else if (value.length < 3) {
                    errorMessage = 'Location must be at least 3 characters';
                    isValid = false;
                }
                break;

            case 'hora':
                if (!value) {
                    errorMessage = 'Please select a time';
                    isValid = false;
                }
                break;

            case 'espacios_totales':
                if (!value || value < 1) {
                    errorMessage = 'Available seats must be at least 1';
                    isValid = false;
                } else {
                    const selectedVehicle = vehicleSelect.options[vehicleSelect.selectedIndex];
                    if (selectedVehicle && selectedVehicle.value) {
                        const capacity = parseInt(selectedVehicle.getAttribute('data-capacity'));
                        if (value > capacity - 1) {
                            errorMessage = `Available seats cannot exceed vehicle capacity (max ${capacity - 1})`;
                            isValid = false;
                        }
                    }
                }
                break;

            case 'costo':
                if (!value || value < 0) {
                    errorMessage = 'Cost must be a positive number';
                    isValid = false;
                } else if (value > 1000) {
                    errorMessage = 'Cost seems too high';
                    isValid = false;
                }
                break;
        }

        // Mostrar error si es inválido
        if (!isValid) {
            showFieldError(field, errorMessage);
        } else {
            showFieldSuccess(field);
        }

        return isValid;
    }

    /**
     * MOSTRAR ERROR EN CAMPO
     */
    function showFieldError(field, message) {
        field.style.borderColor = 'var(--error)';
        field.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.2)';
        
        // Crear elemento de error si no existe
        let errorElement = field.parentNode.querySelector('.field-error');
        if (!errorElement) {
            errorElement = document.createElement('span');
            errorElement.className = 'field-error';
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        errorElement.style.color = 'var(--error)';
        errorElement.style.fontSize = '12px';
        errorElement.style.marginTop = '4px';
        errorElement.style.fontWeight = '600';
    }

    /**
     * MOSTRAR ÉXITO EN CAMPO
     */
    function showFieldSuccess(field) {
        field.style.borderColor = 'var(--success)';
        field.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.2)';
        
        // Remover mensaje de error si existe
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    /**
     * LIMPIAR ERROR DEL CAMPO
     */
    function clearFieldError(e) {
        const field = e.target ? e.target : e;
        field.style.borderColor = '';
        field.style.boxShadow = '';
        
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    /**
     * MOSTRAR MENSAJE AL CLIENTE
     */
    function showClientMessage(message, type) {
        clientMessage.innerHTML = `
            <div class="alert ${type}">
                ${message}
            </div>
        `;
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            clientMessage.innerHTML = '';
        }, 5000);
    }

    /**
     * MANEJAR MENSAJES DE LA URL (éxito/error)
     */
    function handleUrlMessages() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success')) {
            const successMessages = {
                'ride_created': 'Ride created successfully!',
                'rides_created': 'Rides created successfully!',
                'rides_partially_created': 'Some rides were created successfully!',
                'ride_updated': 'Ride updated successfully!',
                'ride_deleted': 'Ride deleted successfully!'
            };
            
            const message = successMessages[urlParams.get('success')];
            if (message) {
                showClientMessage(message, 'success');
            }
        }
        
        if (urlParams.has('error')) {
            const errorMessages = {
                'missing_fields': 'Please fill in all required fields',
                'invalid_vehicle': 'Invalid vehicle selected',
                'database_error': 'Error saving ride. Please try again.',
                'ride_exists': 'A similar ride already exists for this day and time',
                'invalid_time': 'Invalid time selected',
                'no_days_selected': 'Please select at least one day of the week',
                'invalid_seats': 'Invalid number of seats',
                'exceeds_capacity': 'Number of seats exceeds vehicle capacity',
                'invalid_cost': 'Invalid cost amount',
                'ride_creation_failed': 'Failed to create ride. Please try again.'
            };
            
            const message = errorMessages[urlParams.get('error')];
            if (message) {
                showClientMessage(message, 'error');
            }
        }
    }

    // Manejar mensajes de la URL al cargar la página
    handleUrlMessages();
});