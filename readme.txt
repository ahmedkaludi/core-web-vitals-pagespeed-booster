=== Core Web Vitals & PageSpeed Booster ===
Contributors: magazine3
Requires at least: 3.0
Tested up to: 6.7
Stable tag: 1.0.21
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: core web vitals, optimization, pagespeed, performance, cache
Core Web Vitals (CWV) is the new ranking factor
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

= 1.0.21 (30 September 2024) =
* New: Option to exclude the images from lazyloading #155
* New: add missing alt tag in images. #156
* Improvement: Image lazy load improvement #157
* Fixed: Some images are breaking #159
* Fixed: Easy TOC table is breaking on product category pages when plugin is activated #158
* Fixed: Warnings: PHP Deprecated: mb_convert_encoding() #153
* Fixed: Conflict with WooCommerce PayPal Payments on checkout #154
* Fixed: Some icons are breaking on activation of plugin #150
* Fixed: Few improvements required #151

= 1.0.20 (30 July 2024) =
* Fixed: Some warnings and notices on front end #145
* Fixed: Embedded video is not visible on front end #144
* Test: Test with WordPress version 6.6 #147

= 1.0.19 (07 May 2024) =
* Fixed: Cant over ride the width of .cwvpsb_iframe due to the important property used #132
* Fixed: Compatibility with PHP 8.3 version #140
* Test: Test with new version of wordpress i.e. 6.5v #139
* Fixed:  PHP Warning: Undefined variable $img_srcset #141
* Improvement : Option to exclude lazyloading #91

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

Full changelog available at [changelog.txt](https://plugins.svn.wordpress.org/core-web-vitals-pagespeed-booster/trunk/changelog.txt)