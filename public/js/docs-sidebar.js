// Docs Sidebar Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get all directory titles
    const directoryTitles = document.querySelectorAll('.docs-nav-directory-title');

    // Initialize all directories as collapsed
    directoryTitles.forEach(title => {
        const subdirectory = title.nextElementSibling;
        if (subdirectory && subdirectory.classList.contains('docs-nav-subdirectory')) {
            // Initially hide all subdirectories
            subdirectory.style.display = 'none';

            // Add collapsed class and indicator
            title.classList.add('collapsed');
            updateExpandIndicator(title, false);
        }
    });

    // Auto-expand to current page
    const activeItem = document.querySelector('.docs-nav-item.active');
    if (activeItem) {
        // If active item is in a subdirectory, expand its parent
        const parentDirectory = activeItem.closest('.docs-nav-subdirectory');
        if (parentDirectory) {
            const parentTitle = parentDirectory.previousElementSibling;
            if (parentTitle && parentTitle.classList.contains('docs-nav-directory-title')) {
                // Show the subdirectory
                parentDirectory.style.display = 'block';

                // Update the parent title state
                parentTitle.classList.remove('collapsed');
                updateExpandIndicator(parentTitle, true);
            }
        }
    }

    // Add click event listeners to directory titles
    directoryTitles.forEach(title => {
        title.addEventListener('click', function() {
            const subdirectory = this.nextElementSibling;
            if (subdirectory && subdirectory.classList.contains('docs-nav-subdirectory')) {
                // Toggle visibility
                const isCollapsed = this.classList.contains('collapsed');

                // First, collapse all other open directories
                directoryTitles.forEach(otherTitle => {
                    if (otherTitle !== this && !otherTitle.classList.contains('collapsed')) {
                        const otherSubdirectory = otherTitle.nextElementSibling;
                        if (otherSubdirectory && otherSubdirectory.classList.contains('docs-nav-subdirectory')) {
                            otherSubdirectory.style.display = 'none';
                            otherTitle.classList.add('collapsed');
                            updateExpandIndicator(otherTitle, false);
                        }
                    }
                });

                // Then toggle this directory
                if (isCollapsed) {
                    // Expand this directory
                    subdirectory.style.display = 'block';
                    this.classList.remove('collapsed');
                    updateExpandIndicator(this, true);
                } else {
                    // Collapse this directory
                    subdirectory.style.display = 'none';
                    this.classList.add('collapsed');
                    updateExpandIndicator(this, false);
                }
            }
        });
    });

    // Function to update the expand/collapse indicator
    function updateExpandIndicator(element, isExpanded) {
        // Remove existing indicator if any
        const existingIndicator = element.querySelector('.expand-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        // Create and add new indicator
        const indicator = document.createElement('span');
        indicator.className = 'expand-indicator';
        indicator.textContent = isExpanded ? ' -' : ' +';
        element.appendChild(indicator);
    }
});
