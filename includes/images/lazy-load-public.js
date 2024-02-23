document.addEventListener("DOMContentLoaded", function() {
    // Get all elements with class cwvlazyload
    const lazyImages = document.querySelectorAll('.cwvlazyload');

    // IntersectionObserver to lazy load images
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
    lazyImages.forEach(image => {
        imageObserver.observe(image);
    });
});
