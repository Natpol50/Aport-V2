/**
 * force-3d-effect.js - Direct 3D effect implementation
 * 
 * This script forces 3D effect on project cards using direct DOM manipulation
 * and bypasses potential issues with existing CSS/JS conflicts.
 */

// Self-executing function to avoid global scope pollution
(function() {
    // Execute immediately when script loads
    console.log("Force 3D effect script loaded");
    
    // Function to initialize the forced 3D effect
    function forceInitProjectCards() {
        console.log("Attempting to force 3D effect on project cards");
        
        // Find all project cards
        const projectCards = document.querySelectorAll('.project-card');
        
        if (projectCards.length === 0) {
            console.warn("No project cards found! Selector '.project-card' returned no elements.");
            return;
        }
        
        console.log(`Found ${projectCards.length} project cards. Applying forced 3D effect...`);
        
        // Directly force necessary styles for 3D effect
        projectCards.forEach((card, index) => {
            console.log(`Setting up card #${index}`);
            
            // Force 3D transforms with !important to override any conflicting styles
            card.style.cssText += `
                transform-style: preserve-3d !important;
                perspective: 1000px !important;
                transition: transform 0.3s ease, box-shadow 0.3s ease !important;
                transform: perspective(1000px) rotateX(0deg) rotateY(0deg) !important;
                will-change: transform !important;
                position: relative !important;
                z-index: 1 !important;
            `;
            
            // Create tracking function for this specific card
            function trackMouseForCard(e) {
                const rect = card.getBoundingClientRect();
                
                // Calculate relative mouse position
                const mouseX = e.clientX - rect.left;
                const mouseY = e.clientY - rect.top;
                
                // Card dimensions
                const cardWidth = rect.width;
                const cardHeight = rect.height;
                
                // Calculate rotation (with stronger effect)
                const rotateY = ((mouseX / cardWidth) * 2 - 1) * 8; // -8 to +8 degrees
                const rotateX = ((mouseY / cardHeight) * 2 - 1) * -5; // +5 to -5 degrees (inverted)
                
                // Force transform directly with !important
                card.style.transform = `perspective(1000px) rotateY(${rotateY}deg) rotateX(${rotateX}deg) !important`;
                
                // Update badge if it exists
                const badge = card.querySelector('.trapezoid-badge');
                if (badge) {
                    const badgeOffsetX = (mouseX / cardWidth - 0.5) * 5;
                    badge.style.transform = `skewX(-25deg) translateX(${badgeOffsetX}px) !important`;
                }
            }
            
            // Reset card function
            function resetCard() {
                card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg) !important';
                
                const badge = card.querySelector('.trapezoid-badge');
                if (badge) {
                    badge.style.transform = 'skewX(-20deg) !important';
                }
            }
            
            // Add mousemove with direct attribute to avoid event listener issues
            card.onmousemove = trackMouseForCard;
            card.onmouseleave = resetCard;
            
            // Force shadow and effect on hover with !important
            card.onmouseenter = function() {
                card.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.15) !important';
            };
            
            console.log(`Card #${index} set up complete.`);
        });
        
        console.log("Forced 3D effect application complete.");
    }
    
    // Function to ensure we retry even if DOM isn't ready
    function ensureInitialization() {
        // Try immediately
        forceInitProjectCards();
        
        // Retry after DOM is fully loaded
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", forceInitProjectCards);
        }
        
        // Final attempt after window is fully loaded (with all resources)
        window.addEventListener("load", forceInitProjectCards);
        
        // Last attempt after a short delay
        setTimeout(forceInitProjectCards, 1000);
    }
    
    // Start the initialization process
    ensureInitialization();
})();