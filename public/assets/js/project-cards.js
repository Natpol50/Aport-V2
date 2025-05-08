/**
 * Project card interactions
 * 
 * This script handles the interactive elements of project cards including:
 * - Entrance animations as cards scroll into view
 * - Hover effects with 3D-like transforms
 * - Skill tag highlighting
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize project card animations and interactions
    initProjectCards();
    
    // Set up filtering functionality
    initProjectFilters();
});

function initProjectCards() {
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');

    // Force all cards to be immediately visible with no animations
    projectCards.forEach((card) => {
        // Add visible class
        card.classList.add('visible');
        
        // Force immediate visibility with inline styles
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
        card.style.transition = 'none';
        
        // Re-enable transitions only for hover effects after a small delay
        setTimeout(() => {
            card.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
        }, 100);
    });

    // Keep only the hover effects
    projectCards.forEach((card) => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = `translateY(-5px) rotateY(1deg)`;
            this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.15)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

/**
 * Initialize project filtering functionality
 */
function initProjectFilters() {
    const filterButtons = document.querySelectorAll('.project-filter-btn');
    const projectCards = document.querySelectorAll('.project-card');
    
    // Set up filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get filter value
            const filter = this.dataset.filter;
            
            // Update active button state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter projects
            projectCards.forEach(card => {
                if (filter === 'all') {
                    // Show all cards
                    card.style.display = '';
                    // Re-trigger animation
                    setTimeout(() => {
                        card.classList.add('visible');
                    }, 50);
                } else {
                    // Check if card has the selected type
                    if (card.dataset.type === filter) {
                        card.style.display = '';
                        // Re-trigger animation
                        setTimeout(() => {
                            card.classList.add('visible');
                        }, 50);
                    } else {
                        // Hide cards that don't match
                        card.classList.remove('visible');
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300); // Match transition duration
                    }
                }
            });
        });
    });
}

/**
 * Highlight matching skills when hovering over skill tags
 */
function initSkillHighlighting() {
    const skillTags = document.querySelectorAll('.skill-tag');
    
    skillTags.forEach(tag => {
        tag.addEventListener('mouseenter', function() {
            const skill = this.textContent.trim().toLowerCase();
            
            // Find all skill tags with the same skill
            const matchingTags = document.querySelectorAll(`.skill-tag[data-skill="${skill}"]`);
            
            // Highlight matching tags
            matchingTags.forEach(matchingTag => {
                matchingTag.classList.add('highlight');
            });
        });
        
        tag.addEventListener('mouseleave', function() {
            // Remove highlight from all tags
            document.querySelectorAll('.skill-tag.highlight').forEach(highlightedTag => {
                highlightedTag.classList.remove('highlight');
            });
        });
    });
}
