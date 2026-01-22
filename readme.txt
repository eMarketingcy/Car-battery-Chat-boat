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

This plugin adds a smart chatbot to your WooCommerce website with advanced AI capabilities.

**Key Features:**

* **Structured Form Input** - New! Users can select their car brand from a dropdown and enter model, engine, and year in separate fields for maximum accuracy
* **Form Toggle** - Switch between structured form and free-text input based on preference
* **Enhanced AI Intelligence** - Detects when multiple sub-models exist (e.g., Toyota Corolla 1.6 vs 1.6 VVT-i) and asks clarifying questions
* **Smart Clarification** - AI asks follow-up questions when needed to ensure correct battery recommendation
* **Multiple Search Methods:**
  - Direct JIS code search
  - Manual expert mappings
  - Cached results for faster responses
  - AI-powered analysis using Google Gemini 2.5 Flash

The chatbot assists customers in finding the correct battery for their vehicle by intelligently processing car details and matching them with your WooCommerce battery products.

It uses the Google Gemini API (Model: Gemini 2.5 Flash) to understand user input and determine the required battery specifications (Ah, CCA, Technology, Dimensions, Polarity).

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

= Where do I get a Gemini API Key? =

You can get an API key from Google AI Studio: https://aistudio.google.com/app/apikey

= Does this plugin require WooCommerce? =

Yes, this plugin is designed specifically to work with WooCommerce and will not function without it.

= What's new in version 2.1.0? =

Version 2.1.0 includes major improvements:
* Structured Form Input - Users can now select car brand from dropdown and enter model, engine, and year in separate fields
* Enhanced AI Intelligence - Better detection of sub-models and variants
* Modernized Codebase - Updated to PHP 8.0+ with improved performance and security
* Better Admin Interface - Enhanced manual mappings and statistics pages
* Form Toggle - Switch between structured form and free-text input

= How does the structured form work? =

When users open the chatbot, they see a form with:
1. Dropdown menu to select car brand (Toyota, BMW, VW, etc.)
2. Text field for car model (e.g., "Corolla")
3. Text field for engine (e.g., "1.6 TDI")
4. Number field for year (e.g., "2018")

This ensures more accurate data entry and better battery recommendations. Users can switch to free-text input if preferred.

= Will this work with my existing battery products? =

Yes! As long as your products have the required ACF fields configured (Ah, CCA, Technology, Dimensions, Polarity), the plugin will work with your existing products.

== Changelog ==

= 2.1.0 - 2026-01-22 =
* **New Feature:** Added structured form input with dropdown for car brand and separate fields for model, engine, and year
* **Enhancement:** Improved AI prompt to better detect sub-models and variants with different battery requirements
* **Enhancement:** AI now asks clarifying questions when multiple sub-models exist (e.g., "Which variant: 1.6 Standard or 1.6 VVT-i?")
* **Enhancement:** Modernized PHP code with type hints, constants, and PSR standards
* **Enhancement:** Enhanced admin interface for manual mappings with improved UX and product search
* **Enhancement:** Improved statistics page with better visual indicators and legend
* **Enhancement:** Added form toggle button to switch between structured and free-text input
* **Enhancement:** Better error handling and input validation throughout the plugin
* **Security:** Improved input sanitization and nonce validation
* **Performance:** Optimized code organization and reduced redundancy
* **Code Quality:** Added comprehensive PHPDoc comments and better code structure
* **Requirement:** Minimum PHP version increased to 8.0 for modern features
* **Fix:** Improved polarity display formatting
* **Fix:** Better handling of edge cases in JIS code calculation

= 2.0.5 =
* Previous stable version with basic features
* Smart Cache implementation
* Manual expert mappings
* JIS code support
* Basic clarification system
