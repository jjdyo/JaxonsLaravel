// Wrap everything in DOMContentLoaded to ensure document.body exists
document.addEventListener('DOMContentLoaded', function() {
    // Check if the image has already been loaded in this session
    if (sessionStorage.getItem('starryBackgroundLoaded')) {
        // If it has, immediately add the loaded class
        document.body.classList.add('bg-loaded');
    } else {
        // If not, load the image and set the flag
        // Preload the starry background image
        const img = new Image();
        img.src = '/media/night_stars.png';

        // Apply a class to indicate the image is loaded
        img.onload = function() {
            document.body.classList.add('bg-loaded');
            // Store in session storage so we don't reload on page navigation
            sessionStorage.setItem('starryBackgroundLoaded', 'true');
        };
    }
});
