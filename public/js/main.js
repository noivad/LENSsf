function showCalendarForm(eventId) {
    const form = document.getElementById(`calendar-form-${eventId}`);
    if (!form) return;

    form.classList.toggle('hidden');
}

function showShareForm(eventId) {
    const form = document.getElementById(`share-form-${eventId}`);
    if (!form) return;

    form.classList.toggle('hidden');
}

function showCommentForm(photoId) {
    const form = document.getElementById(`comment-form-${photoId}`);
    if (!form) return;

    form.classList.toggle('hidden');
}

const fileInputs = document.querySelectorAll('input[type="file"]');
fileInputs.forEach((input) => {
    input.addEventListener('change', () => {
        if (input.files && input.files[0]) {
            input.setAttribute('data-file-selected', input.files[0].name);
        } else {
            input.removeAttribute('data-file-selected');
        }
    });
});
