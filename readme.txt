=== Core Web Vitals & PageSpeed Booster ===
Contributors: magazine3
Requires at least: 3.0
Tested up to: 6.1
Stable tag: 1.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: core web vitals, optimization, pagespeed, performance, cache

== Description ==
<h4>Core Web Vitals is the new ranking factor</h4>

Google announced that "Core Web Vitals" are going to be a significant ranking signal for websites. In fact, Core Web Vitals or the page experience signal is going to become a requirement for a page to appear in Google's Top Stories.

### Features

* <strong>Webp images</strong>: If images are slowing down your website, then converting them to WebP format can improve your page load speed test scores. 
* <strong>Lazy Load</strong>: Lazy loading allows your website to only load images when a user scrolls down to a specific image, which reduces website load time and improves website performance.
* <strong>Minification</strong>: If you are trying to achieve 100/100 score on Google Pagespeed or GTMetrix tool, then minifying CSS and JavaScript will significantly improve your score.
* <strong>Remove Unused CSS</strong>:Unused CSS is any CSS code added by your WordPress theme or plugins that you don’t really need. Removing this CSS code improves WordPress performance and user experience.
* <strong>Google Fonts Optimizations</strong>: You may start noticing external resources like fonts affecting Google PageSpeed + load times. This is where loading Google Fonts locally comes into play.
* <strong>Delay JavaScript Execution</strong>:You can delay JavaScript based on user interaction. This can be a great way to speed up the paint of the page for Google PageSpeed when something isn't needed right away. Especially heavy third-party scripts like Google Adsense, Google Analytics etc.
* <strong>Cache</strong>: Caching is one of the most important and easiest ways to speed up WordPress! it reduces the amount of work required to generate a page view. As a result, your web pages load much faster, directly from cache.

### Support

We try our best to provide support on WordPress.org forums. However, We have a special [team support](https://webvitalsdev.com/#text-3) where you can ask us questions and get help. Delivering a good user experience means a lot to us and so we try our best to reply each and every question that gets asked.

### Bug Reports

Bug reports for Core Web Vitals & PageSpeed Booster are [welcomed on GitHub](https://github.com/ahmedkaludi/core-web-vitals-pagespeed-booster/issues). Please note GitHub is not a support forum, and issues that aren't properly qualified as bugs will be closed.

### Credits

* PHP CSS Parser library used https://github.com/sabberworm/PHP-CSS-Parser - License URI: https://github.com/sabberworm/PHP-CSS-Parser#license (PHP-CSS-Parser is freely distributable under the terms of an MIT-style license.)

== Changelog ==

= 1.0.7 (25th November 2022) =
* Improvement: Code improvements and Optimizations
* Added: Critical CSS Optimisation Tab
* Added: Support Tab

= 1.0.6 (21st April 2022) =
* Improvement: Centralize all cache in single cache folder
* Fixed: Improve exclude JS and combine 
* Added: Created option to clear all cache on one click
* Added: Added lazy load on inline style background images also
* Fixed: Improvement for AMP, AMP for WP, AMP Stories and AMPforWP Stories not apply optimization

= 1.0.5 (12th April 2022) =
* Fixed: Removed minify AMP #49
* Fixed: Removed version from exclude js for gzip
* Fixed: Critical css build by normal javascript
* Fixed: Critical css file not create if content is blank
* Added: Created Static cache method for caching strategy
* Fixed: Proper fix for image lazy load
* Fixed: Exclude merged javascript from js delay
* Fixed: create disk cache and serve disk cache on php cache
* Fixed: Minify html by replace new line
* Added: Added exclude js from delay for ads load or any thing else 

= 1.0.4 (31th March 2022) =
* Fixed: Feed url not shows properly #42
* Fixed: Minification should not apply on feed #42
* Resolved: Critical error with PHP Version 8.0 #38
* Improvement: Added Module for Critical CSS

= 1.0.3 (16th February 2022) =
* Fixed: Icons not loading issue fixed #16

= 1.0.2 (21th January 2022) =
* Improvements: Added module where we can test all optimization on a particular URL #17
* Improvements: UI Improvements #13
* Fixed: Bugs with the latest user case #16

= 1.0.1 (20th December 2021) =
* Improvements: Added Gravatar Caching #11
* Improvements: Option Panel Improved #2 #5 #6 #8
* Fixed: Debug error #7 #10

= 1.0 (24th September 2021) =
* Version 1.0 Released