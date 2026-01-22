=== Car Battery AI Chatbot ===
Contributors: eMarketing Cyprus by Saltpixel Ltd
Tags: chatbot, woocommerce, ai, gemini, products, battery
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 2.1.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An AI-powered chatbot to help customers find the right car battery in your WooCommerce store using Google's Gemini API. Features Smart Cache, Manual Expert Training, Structured Form Input, and Enhanced Sub-Model Detection.

== Description ==

This plugin adds a smart chatbot to your WooCommerce website.

The chatbot assists customers in finding the correct battery for their vehicle by asking for the car's make, model, year, and engine details.

It uses the Google Gemini API (Model: Gemini 3 Pro Preview) to understand the user's input and determine the required battery specifications (Ah, CCA, Technology).

Then, it searches your WooCommerce product catalog to find and recommend compatible batteries available in your shop.

This provides a seamless, interactive user experience that helps drive sales and reduce customer support inquiries.

== Installation ==

1.  Upload the `carbattery-chatbot` directory to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Car Battery Bot > Settings** (or just click the menu) and enter your Google Gemini API key.
4.  Ensure your WooCommerce battery products have the required custom fields (see below).

== IMPORTANT: Product Setup ==

For the chatbot to find your products, you MUST use Advanced Custom Fields (ACF) to store your battery data on your WooCommerce products.

1.  Ensure you have **ACF (free or pro)** installed and active.
2.  Create ACF fields for your battery products.
3.  Go to **Settings** in your WordPress admin.
4.  Use the dropdown menus to map the chatbot's required specs to the ACF fields you created:
    * Ah (Number)
    * CCA (Number)
    * Technology (Text/Select)
    * Length (Number)
    * Width (Number)
    * Height (Number)
    * Polarity (Text/Select/Number)
5.  Fill in the data for these ACF fields on all your battery products.

**Field Value Examples:**
* The 'Technology' field must contain one of: `AGM`, `EFB`, `Wet`, `GEL`, `Sodium-Ion`.
* The 'Polarity' field must contain the numeric value for the positive terminal: `0` (for right positive) or `1` (for left positive).

== Frequently Asked Questions ==

= Where do I get a Gemini API Key?
=

You can get an API key from Google AI Studio: https://aistudio.google.com/app/apikey

= Does this plugin require WooCommerce?
=

Yes, this plugin is designed specifically to work with WooCommerce and will not function without it.

= What's new in version 2.1.0?
=

Version 2.1.0 includes major improvements:
* **Structured Form Input** - Users can now select car brand from dropdown and enter model, engine, and year in separate fields for more accurate results
* **Enhanced AI Intelligence** - Improved sub-model detection to identify when multiple variants exist (e.g., Toyota Corolla 1.6 vs 1.6 VVT-i)
* **Modernized Codebase** - Updated to PHP 8.0+ with type hints, better security, and improved performance
* **Better Admin Interface** - Enhanced manual mappings and statistics pages
* **Form Toggle** - Users can switch between structured form and free-text input

== Changelog ==

= 2.1.0 - 2026-01-22 =
* **New Feature:** Added structured form input with dropdown for car brand and separate fields for model, engine, and year
* **Enhancement:** Improved AI prompt to better detect sub-models and variants with different battery requirements
* **Enhancement:** Modernized PHP code with type hints, constants, and PSR standards
* **Enhancement:** Enhanced admin interface for manual mappings with better UX
* **Enhancement:** Improved statistics page with better visual indicators
* **Enhancement:** Added form toggle to switch between structured and free-text input
* **Security:** Improved input validation and sanitization
* **Performance:** Better error handling and code organization
* **Requirement:** Minimum PHP version increased to 8.0

= 2.0.5 =
* Previous stable version with basic features
