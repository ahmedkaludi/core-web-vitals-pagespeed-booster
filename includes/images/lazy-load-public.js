document.addEventListener("DOMContentLoaded", function() {
    // Function to lazy load images
    const lazyLoadImages = function(images) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const lazyImage = entry.target;

                    // Set src, srcset, and sizes from data attributes
                    if (lazyImage.dataset.src) {
                        lazyImage.src = lazyImage.dataset.src;
                    }
                    if (lazyImage.dataset.srcset) {
                        lazyImage.srcset = lazyImage.dataset.srcset;
                    }
                    if (lazyImage.dataset.sizes) {
                        lazyImage.sizes = lazyImage.dataset.sizes;
                    }

                    // Remove the dataset attributes to prevent re-loading on subsequent intersections
                    delete lazyImage.dataset.src;
                    delete lazyImage.dataset.srcset;
                    delete lazyImage.dataset.sizes;

                    // Stop observing this image once it's loaded
                    observer.unobserve(lazyImage);
                }
            });
        });

        // Observe lazy images
        images.forEach(image => {
            imageObserver.observe(image);
        });
    };

    // Initial lazy loading for images on page load
    lazyLoadImages(document.querySelectorAll('.cwvlazyload'));

    // MutationObserver to handle dynamically loaded content
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && node.querySelectorAll) {
                        const images = node.querySelectorAll('.cwvlazyload');
                        lazyLoadImages(images);
                    }
                });
            }
        });
    });

    // Start observing the document body for mutations
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
