=== Core Web Vitals & PageSpeed Booster ===
Contributors: magazine3
Requires at least: 3.0
Tested up to: 6.4
Stable tag: 1.0.18
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: core web vitals, optimization, pagespeed, performance, cache , cwv

== Description ==
<h4>Core Web Vitals (CWV) is the new ranking factor</h4>

Google announced that "Core Web Vitals" are going to be a significant ranking signal for websites. In fact, Core Web Vitals or the page experience signal is going to become a requirement for a page to appear in Google's Top Stories.

### Features

* <strong>Flush Cache</strong>: Using this option you can choose on which events ( Wordpress Update,Switching Theme,Post/Page Deletion )  you want to clear website cache. 
* <strong>Auto Clear Cache</strong>: Clear you website on regular intervals , this helps you to keep your website cache updated. 
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

* PHP CSS Parser library used https://github.com/sabberworm/PHP-CSS-Parser - License URI: https://github.com/sabberworm/PHP-CSS-Parser?tab=MIT-1-ov-file (PHP-CSS-Parser is freely distributable under the terms of an MIT-style license.)
* CSS from HTML extractor library used https://github.com/JanDC/css-from-html-extractor - License URI: https://github.com/JanDC/css-from-html-extractor?tab=License-1-ov-file (CSS from HTML extractor is freely distributable under the terms of an MIT-style license.)
* WebP Convert library used https://github.com/rosell-dk/webp-convert - License URI: https://github.com/rosell-dk/webp-convert?tab=MIT-1-ov-file (WebP Convert is freely distributable under the terms of an MIT-style license.)

== Changelog ==

= 1.0.18 (27 February 2024) =
* Fixed: CSS break after latest update (1.0.17) #130
* Fixed: Displaying unknown characters #133
* Improvement: Improvement in Image lazy load #131
* Improvement: Image optimization not working if html contain invalid DOM #134

= 1.0.17 (19 January 2024) =
* Fixed:  The type attribute is unnecessary for JavaScript resources. #123
* Fixed: Element script must not have attribute defer unless attribute src is also specified. #122
* Added: Option where we can set different delay JS methods on mobile and desktop. #119
* Added: Option for flush cache on a predefined schedule. #120
* Added: Option to keep  cache for a long period of time. #121
* Improvement: Automatic Resizing to fix Properly Size Image issue. #118
* Fixed: Network deactivate is not working #126
* Improvement: Code Improvement #125
* Improvement: Bulk convert to webP #127

= 1.0.16 (15 November 2023) =
* Fixed: Robots.txt error appears when you we enable our CWV plugin. #114
* Fixed: wp-content/gravatars folder not removed upon uninstall #112
* Fixed: Uninstall.php only removes main critical URLs table from database in multisite #111
* Improvement: Updated settings link #113
* Compatibility: Checked compatibility with wordpress v6.4 #115

= 1.0.15 (22 September 2023) =
* Added: Compatibility with  MYSQL v5.5 #97
* Fixed: Fatal Error on Multisite Activation: is_plugin_active_for_network() Undefined #106
* Fixed: Youtube embed video Not showing in AMP #105
* Improvement: Cache is off but still in header it's showing clear cache #104

= 1.0.14 (17 August 2023) =
* Fixed: Parse error unexpected ')' #87
* Fixed: Error in core-web-vitals-pagespeed-booster Plugin. #99
* Fixed: Compatibility with 10Web Booster #96
* Improvement: Added newsletter form  #4
* Improvement: WordPress 6.3 compatibility check #100 
* Improvement: Improved and optimized the code according to WP standards #101

= 1.0.13 (03 June 2023) =
* Improvement: Improved CSS load 
* Fixed: Redirection Issue

= 1.0.12 (14 April 2023) =
* Fixed: TypeError jQuery is not a function on console #84
* Fixed: Google fonts not loading on PHP 8.0+ #83
* Fixed: Conflict with the Google reCAPTCHA v3 #82
* Fixed: Warning Undefined array key "advance_support" #80
* Improvement : Add a label to the Exclude URL box #81
* Improvement : Exclude Google analytics from js delay #62

= 1.0.11 (17 February 2023) =
* Fixed: Woocommerce payment page is not working. #77 
* Improvement : Remove plugin dependency from file_get_contents function #78

= 1.0.10 (02 February 2023) =
* Improvement: Optimized code and fixed frontend js issue 

= 1.0.9 (17 January 2023) =
* Fixed: DataTables warning: table id=table_page_cc_style_completed – Ajax error. #73
* Fixed: In multisite setup it shows "Sorry, you are not allowed to access this page." #74

= 1.0.8 (10 January 2023) =
* Improvement: Improved Critical CSS generation.

= 1.0.7.2 (23rd December 2022) =
* Improvement: CSS optimization

= 1.0.7.1 (14th December 2022) =
* Fixed: Critical CSS not generating

= 1.0.7 (25th November 2022) =
* Improvement: Updated Critical CSS Optimisation
* Improvement: Code improvements and fixes
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