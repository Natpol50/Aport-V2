/**
 * project-cards.js - Enhanced Project Card Interactions
 * 
 * This script controls all project card behavior including:
 * - Immediate visibility without requiring scroll
 * - Sequentially animated entrance for visual interest
 * - Hover effects with 3D-like transforms
 * - Optional filtering functionality
 * 
 * Mathematical principles applied:
 * - Sequential timing functions for staggered animations
 * - Proportional transform values for consistent hover effects
 * - CSS transition timing coordination
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize project card animations and interactions
    initProjectCards();
    
    // Set up filtering functionality if filter buttons exist
    initProjectFilters();
});

/**
 * Initialize project card animations and interactions
 * Ensures all cards are visible immediately without requiring scroll
 * Uses a mathematical sequence for staggered appearance
 */
function initProjectCards() {
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');

    // Force all cards to be immediately visible with no animations
    projectCards.forEach((card, index) => {
        // Add visible class
        card.classList.add('visible');
        
        // Force immediate visibility with inline styles
        card.style.opacity = '0'; // Start invisible
        card.style.transform = 'translateY(20px)'; // Start slightly below final position
        
        // Apply a sequential animation delay for staggered appearance
        // This creates a mathematical progression of delays for visual interest
        const delay = 100 + (index * 150); // Base delay + 150ms between each card
        
        // Trigger the appearance after a small delay
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease'; // Add transition
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, delay);
        
        // Re-enable only hover transitions after the card has appeared
        setTimeout(() => {
            card.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
        }, delay + 600); // Wait until after the card has fully appeared
    });

    // Add hover effects
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
    
    // Log completion for debugging
    console.log(`Initialized ${projectCards.length} project cards with sequential animation`);
}

/**
 * Initialize project filtering functionality
 * Enables filtering by project type/category if filter buttons exist
 */
function initProjectFilters() {
    const filterButtons = document.querySelectorAll('.project-filter-btn');
    const projectCards = document.querySelectorAll('.project-card');
    
    // Exit if no filter buttons found
    if (filterButtons.length === 0) return;
    
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
 * Initialize skill tag highlighting
 * Optional functionality to highlight matching skills across projects
 */
function initSkillHighlighting() {
    const skillTags = document.querySelectorAll('.skill-tag');
    
    // Exit if no skill tags found
    if (skillTags.length === 0) return;
    
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