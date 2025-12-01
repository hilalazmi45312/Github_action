function showAlert({ icon = 'info', title = '', text = '', footer = '', confirmButtonText = 'OK' }) {
    Swal.fire({
        icon,
        title,
        text,
        footer,
        confirmButtonText
    });
}
