// Script para el menú desplegable del avatar
document.addEventListener('DOMContentLoaded', function() {
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

    // Preview de imagen (igual que en registration)
    const photoInput = document.getElementById("foto");
    const previewImg = document.getElementById("photoPreview");

    if (photoInput && previewImg) {
        photoInput.addEventListener("change", function(event) {
            const file = event.target.files[0];
            if (file) {
                previewImg.src = URL.createObjectURL(file);
            }
        });
    }
});