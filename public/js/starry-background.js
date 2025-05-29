document.addEventListener('DOMContentLoaded', function() {
    // Preload the starry background image
    const img = new Image();
    img.src = '/media/night_stars.png';

    // Apply a class to indicate the image is loaded
    img.onload = function() {
        document.body.classList.add('bg-loaded');
    };
});
