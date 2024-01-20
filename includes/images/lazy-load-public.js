(function (root, factory) {
    if (typeof exports === "object") {
        module.exports = factory(root);
    } else if (typeof define === "function" && define.amd) {
        define([], function () {
            return factory(root);
        });
    } else {
        root.LazyLoad = factory(root);
    }
})(typeof global !== "undefined" ? global : this.window || this.global, function (root) {
    "use strict";

    const defaults = {
        src: "data-src",
        srcset: "data-srcset",
        sizes: "data-sizes",
        style: "data-style",
        selector: ".cwvlazyload"
    };

    const extend = function (target, ...sources) {
        let deep = false;

        if (typeof target === "boolean") {
            deep = target;
            target = sources.shift();
        }

        for (const source of sources) {
            for (const prop in source) {
                if (Object.prototype.hasOwnProperty.call(source, prop)) {
                    if (deep && Object.prototype.toString.call(source[prop]) === "[object Object]") {
                        target[prop] = extend(true, target[prop], source[prop]);
                    } else {
                        target[prop] = source[prop];
                    }
                }
            }
        }

        return target;
    };

    class LazyLoad {
        constructor(images, options) {
            this.settings = extend({}, defaults, options || {});
            this.images = images || document.querySelectorAll(this.settings.selector);
            this.observer = null;
            this.init();
        }

        init() {
            if (!root.IntersectionObserver) {
                this.loadImages();
                return;
            }

            this.createObserver();
            this.observeImages();
        }

        createObserver() {
            const self = this;
            const observerConfig = {
                root: null,
                rootMargin: "0px",
                threshold: [0]
            };

            this.observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.intersectionRatio > 0) {
                        self.handleIntersection(entry.target);
                    }
                });
            }, observerConfig);
        }

        observeImages() {
            const self = this;
            this.images.forEach(image => {
                self.observer.observe(image);
            });
        }

        handleIntersection(target) {
            this.observer.unobserve(target);
            const src = target.getAttribute(this.settings.src);
            const srcset = target.getAttribute(this.settings.srcset);
            const sizes = target.getAttribute(this.settings.sizes);

            if (target.tagName.toLowerCase() === "img") {
                if (src) {
                    target.src = src;
                }
                if (srcset) {
                    target.srcset = srcset;
                }
                if (sizes) {
                    target.sizes = sizes;
                }
            } else {
                target.style = target.getAttribute(this.settings.style);
            }
        }

        loadAndDestroy() {
            if (!this.settings) {
                return;
            }
            this.loadImages();
            this.destroy();
        }

        loadImages() {
            if (!this.settings) {
                return;
            }

            this.images.forEach(image => {
                this.handleIntersection(image);
            });
        }

        destroy() {
            if (!this.settings) {
                return;
            }
            this.observer.disconnect();
            this.settings = null;
        }
    }

    root.LazyLoad = LazyLoad;

    return LazyLoad;
});

// Usage
document.addEventListener("DOMContentLoaded", function () {
    const lazyLoader = new LazyLoad(document.querySelectorAll(".cwvlazyload"));
});
