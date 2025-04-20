document.addEventListener('DOMContentLoaded', function () {
    var successMessage = document.querySelector('.success-message,.warning-alert');
    if (successMessage) {
        setTimeout(function () {
            successMessage.style.opacity = 0; // Fade out effect
            setTimeout(function () {
                successMessage.style.display = 'none'; // Hide element after fade-out
            }, 500); // Delay for the fade-out effect
        }, 2000); // Time before the fade-out starts
    }
});

//Eye icon to shaow oassword if the user typing
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.querySelector('#togglePassword');
    const passwordField = document.querySelector('#inputPassword');

    // Show/Hide the eye icon based on input typing
    passwordField.addEventListener('input', function () {
        if (passwordField.value.length > 0) {
            togglePassword.style.display = 'inline'; // Show the eye icon
        } else {
            togglePassword.style.display = 'none';  // Hide the eye icon
            passwordField.setAttribute('type', 'password'); // Ensure it's hidden when typing is cleared
            togglePassword.classList.add('fa-eye');
            togglePassword.classList.remove('fa-eye-slash');
        }
    });

    togglePassword.addEventListener('click', function () {
        // Toggle the password field type between password and text
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);

        // Toggle the icon between eye and eye-slash
        togglePassword.classList.toggle('fa-eye');
        togglePassword.classList.toggle('fa-eye-slash');
    });
});


$(document).ready(function () {
    $('.toggle-card').click(function () {
        // Get the unique target card body from the data-target attribute
        var target = $(this).data('target');

        // Toggle collapse for the card body with a sliding motion
        $(target).slideToggle(300); // Adjust duration for smoothness

        // Toggle the icon between plus and minus
        $(this).toggleClass('fa-plus fa-minus');
    });
});

