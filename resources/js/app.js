import './bootstrap';
import '../css/app.css';

// Lazy load components
const lazyLoad = (callback) => {
    if (document.readyState === 'complete') {
        callback();
    } else {
        window.addEventListener('load', callback);
    }
};

// Initialize components
const initializeComponents = () => {
    // Remove loading indicator if it exists
    const loadingIndicator = document.querySelector('.loading');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }

    // Initialize any other components here
    console.log('Application initialized');
};

// Use requestIdleCallback for non-critical initialization
if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
        lazyLoad(initializeComponents);
    });
} else {
    // Fallback for browsers that don't support requestIdleCallback
    setTimeout(() => {
        lazyLoad(initializeComponents);
    }, 1);
}
