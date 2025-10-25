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

document.addEventListener('DOMContentLoaded', function() {
    const recurringCheckbox = document.querySelector('input[name="is_recurring"]');
    const recurringOptions = document.getElementById('recurring-options');
    
    if (recurringCheckbox) {
        recurringCheckbox.addEventListener('change', function() {
            recurringOptions.style.display = this.checked ? 'block' : 'none';
        });
        
        if (recurringCheckbox.checked) {
            recurringOptions.style.display = 'block';
        }
    }
});
