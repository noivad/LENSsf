function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.className = 'preview-box has-image';
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        
        reader.readAsDataURL(file);
    } else {
        preview.className = 'preview-box';
        preview.innerHTML = '📷 No image selected - Choose a file to preview';
    }
}
