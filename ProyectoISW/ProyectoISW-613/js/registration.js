const photoInput = document.getElementById("photo");
const previewImg = document.getElementById("photoPreview");

photoInput.addEventListener("change", (event) => {
  const file = event.target.files[0];
  if (file) {
    previewImg.src = URL.createObjectURL(file);
    previewImg.style.display = "block";
  }
});

