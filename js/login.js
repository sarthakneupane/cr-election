// Simple form switching functionality
function toggleForm(formType) {
    const studentForm = document.getElementById('studentlogin');
    const adminForm = document.getElementById('register-form');
    
    if (formType === 'register') {
        // Show admin form, hide student form
        studentForm.style.display = 'none';
        adminForm.style.display = 'block';
    } else {
        // Show student form, hide admin form
        adminForm.style.display = 'none';
        studentForm.style.display = 'block';
    }
}

// Clear error messages when switching forms
document.addEventListener('DOMContentLoaded', function() {
    const switchButtons = document.querySelectorAll('.switchbutton');
    
    switchButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Clear any error messages
            const errorSpans = document.querySelectorAll('span[style*="color:red"]');
            errorSpans.forEach(span => {
                span.style.display = 'none';
            });
        });
    });
});