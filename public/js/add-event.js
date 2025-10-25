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

function updateRecurrenceOptions() {
    const recurrenceType = document.getElementById('recurrence_type').value;
    
    document.getElementById('weekly-options').style.display = 'none';
    document.getElementById('monthly-day-options').style.display = 'none';
    document.getElementById('monthly-date-options').style.display = 'none';
    document.getElementById('custom-options').style.display = 'none';
    
    if (recurrenceType === 'weekly') {
        document.getElementById('weekly-options').style.display = 'block';
    } else if (recurrenceType === 'monthly_day') {
        document.getElementById('monthly-day-options').style.display = 'block';
    } else if (recurrenceType === 'monthly_date') {
        document.getElementById('monthly-date-options').style.display = 'block';
    } else if (recurrenceType === 'custom') {
        document.getElementById('custom-options').style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const recurringCheckbox = document.querySelector('input[name="is_recurring"]');
    const recurringOptions = document.getElementById('recurring-options');
    const recurrenceTypeSelect = document.getElementById('recurrence_type');
    
    if (recurringCheckbox && recurringOptions) {
        recurringOptions.style.display = 'none';
        
        recurringCheckbox.addEventListener('change', function() {
            recurringOptions.style.display = this.checked ? 'block' : 'none';
        });
        
        if (recurringCheckbox.checked) {
            recurringOptions.style.display = 'block';
        }
    }
    
    if (recurrenceTypeSelect) {
        recurrenceTypeSelect.addEventListener('change', updateRecurrenceOptions);
    }
});
