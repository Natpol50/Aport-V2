/**
 * Debug Helper for 3D Effect
 *
 * This should be added to your project to help debug the 3D card effect.
 * Simply add this script to your page, open the browser console, and move
 * your mouse over project cards to see debug information.
 */

// Function to add debug helpers for 3D card effect
function debugCard3DEffect() {
    console.log("Installing 3D effect debug helpers...");
    
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');
    if (projectCards.length === 0) {
        console.warn("No project cards found to debug!");
        return;
    }
    
    console.log(`Found ${projectCards.length} project cards to debug.`);
    
    // Add a small debug panel to the page
    const debugPanel = document.createElement('div');
    debugPanel.style.cssText = `
        position: fixed;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 10px;
        border-radius: 5px;
        font-family: monospace;
        font-size: 12px;
        z-index: 9999;
        max-width: 300px;
    `;
    debugPanel.innerHTML = `
        <div>Card 3D Effect Debug</div>
        <div id="debug-info">Move mouse over a card</div>
        <div>
            <button id="debug-toggle-3d" style="margin-top: 5px; padding: 3px; background: #444; color: white; border: 1px solid #666;">
                Toggle 3D Effect
            </button>
        </div>
    `;
    document.body.appendChild(debugPanel);
    
    // Add debug info to mouse events
    const infoElement = document.getElementById('debug-info');
    
    projectCards.forEach((card, index) => {
        // Add a data attribute for identification
        card.setAttribute('data-card-index', index);
        
        // Log card dimensions
        console.log(`Card #${index} dimensions:`, {
            width: card.offsetWidth,
            height: card.offsetHeight,
            perspective: getComputedStyle(card).perspective,
            transformStyle: getComputedStyle(card).transformStyle
        });
        
        // Add an outline for debugging
        card.style.outline = '1px dashed rgba(255, 0, 0, 0.5)';
        
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            const relativeX = (mouseX / rect.width).toFixed(2);
            const relativeY = (mouseY / rect.height).toFixed(2);
            
            // Get the current transform
            const transform = card.style.transform;
            
            // Update the debug info
            infoElement.innerHTML = `
                Card #${index}<br>
                Mouse: ${mouseX.toFixed(0)}px, ${mouseY.toFixed(0)}px<br>
                Relative: ${relativeX}, ${relativeY}<br>
                Transform: ${transform}
            `;
        });
    });
    
    // Add toggle button functionality
    const toggleButton = document.getElementById('debug-toggle-3d');
    let effectEnabled = true;
    
    toggleButton.addEventListener('click', () => {
        effectEnabled = !effectEnabled;
        toggleButton.textContent = effectEnabled ? "Disable 3D Effect" : "Enable 3D Effect";
        
        projectCards.forEach(card => {
            if (!effectEnabled) {
                // Store the original transform
                card.setAttribute('data-original-transform', card.style.transform || '');
                card.style.transform = 'none';
                card.style.transition = 'none';
            } else {
                // Restore original transform
                const originalTransform = card.getAttribute('data-original-transform') || 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
                card.style.transform = originalTransform;
                card.style.transition = 'transform 0.3s ease';
            }
        });
    });
    
    console.log("Debug helpers installed. Mouse over project cards to see debug info.");
}

// Run the debug function
document.addEventListener('DOMContentLoaded', function() {
    // Wait a moment to ensure other scripts have initialized
    setTimeout(debugCard3DEffect, 1000);
});