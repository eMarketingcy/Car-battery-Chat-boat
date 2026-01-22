# Changelog

All notable changes to the Car Battery AI Chatbot plugin are documented in this file.

## [2.1.0] - 2026-01-22

### Major Updates

This release brings significant improvements to user experience, code quality, and AI intelligence.

### Added

#### Structured Form Input
- **New User Interface**: Added structured form with separate fields for better accuracy
  - Dropdown menu for car brand selection (30+ popular brands pre-loaded)
  - Dedicated input field for car model
  - Dedicated input field for engine specification
  - Dedicated input field for year
- **Form Toggle**: Users can switch between structured form and free-text input
- **Brand Loading**: AJAX endpoint to dynamically load popular car brands
- **Better Validation**: Client-side validation ensures all required fields are filled

#### Enhanced AI Intelligence
- **Sub-Model Detection**: AI now recognizes when multiple variants exist
  - Example: Toyota Corolla 1.6 Standard vs 1.6 VVT-i
  - Example: VW Golf 1.4 TSI Standard vs BlueMotion
  - Example: BMW 320d N47 vs B47 engine
- **Smarter Clarification**: Enhanced prompt instructs AI to ask about specific sub-models
- **Priority-Based Questions**: AI checks for sub-models first, then missing info
- **Lower Temperature**: Set to 0.1 for more consistent AI responses

#### Admin Interface Improvements
- **Manual Mappings Page**:
  - Better layout with form table design
  - Improved product search with real-time results
  - Click to add product IDs to mapping
  - Confirmation dialog before deletion
  - Empty state message
- **Statistics Page**:
  - Visual legend with colored badges
  - Better status indicators using WordPress dashicons
  - Improved table layout
  - Empty state message
  - More informative result types

### Changed

#### Code Modernization
- **PHP 8.0+ Features**:
  - Added type hints to all methods (string, int, bool, array, void)
  - Used `match` expressions for cleaner code
  - Added class constants for magic numbers
  - Property type declarations
  - Return type declarations
- **Better Documentation**:
  - PHPDoc comments for all methods
  - Parameter and return type documentation
  - Clear explanation of functionality
- **Security Improvements**:
  - Better input sanitization using `sanitize_text_field()`
  - Improved nonce validation
  - Used `wp_json_encode()` instead of `json_encode()`
  - Prepared SQL statements with proper formatting
  - Escaped output with `esc_html()`, `esc_url()`, `esc_attr()`
- **Code Organization**:
  - Extracted helper methods from long functions
  - Improved variable naming
  - Better error handling with try-catch blocks
  - Separated concerns (form creation, event binding, processing)

#### Performance Improvements
- **Constants for Configuration**:
  - `MAX_SEARCH_RESULTS = 5`
  - `DIMENSION_TOLERANCE = 10`
  - `CACHE_LIMIT = 200`
  - `API_TIMEOUT = 30`
- **Optimized JIS Width Codes**: Moved to class constant
- **Better Caching**: Improved cache key normalization
- **Efficient Array Operations**: Used `array_map()` with arrow functions

#### User Experience
- **Better Error Messages**: More informative dependency checks
- **Improved Styling**:
  - Modern form design with rounded corners
  - Better focus states with colored shadows
  - Responsive design for mobile devices
  - Hover effects on buttons
  - Toggle button with underline style
- **Loading States**: Proper disable states during form submission
- **Progressive Enhancement**: Form works with or without JavaScript

### Fixed

- **IP Address Detection**: Better handling of proxy headers
- **Polarity Display**: Consistent formatting (0 = Right+, 1 = Left+)
- **JIS Calculator**: More robust error handling
- **Form Validation**: Prevents submission of empty fields
- **CSS Specificity**: Better scoping to avoid conflicts

### Technical Details

#### Files Modified

**Core Files:**
- `carbattery-chatbot.php` - Main plugin file with modernized code
- `includes/class-cbc-manual-mappings.php` - Enhanced admin interface
- `includes/class-cbc-search-stats.php` - Better statistics display
- `includes/class-cbc-jis-calculator.php` - Improved calculator logic

**Frontend Files:**
- `assets/js/chatbot.js` - Added structured form functionality
- `assets/css/chatbot.css` - New styles for structured form

**Documentation:**
- `README.md` - Updated with new features
- `readme.txt` - WordPress plugin directory format
- `CHANGELOG.md` - This file

#### Breaking Changes

- **Minimum PHP Version**: Increased from 7.4 to 8.0
  - Required for type hints and modern PHP features
  - Ensure your server runs PHP 8.0 or higher before updating

#### Migration Guide

If upgrading from 2.0.5 or earlier:

1. **Check PHP Version**: Ensure server runs PHP 8.0+
2. **Backup Database**: Always backup before major updates
3. **Test After Update**: Try the chatbot with various car queries
4. **Check Manual Mappings**: Verify existing mappings still work
5. **Review Settings**: Ensure ACF field mappings are correct

### Developer Notes

#### New JavaScript Functions

```javascript
createInputAreaHTML() - Generates form HTML based on mode
bindInputAreaEvents() - Binds event listeners to form
loadCarBrands() - Fetches brands via AJAX
handleStructuredFormSubmit() - Processes structured form
processUserMessage() - Common message processing logic
cbcToggleFormMode() - Global toggle function
```

#### New PHP Methods

```php
handle_get_car_brands() - AJAX endpoint for brands
get_closest_width_letter() - JIS calculator helper
get_terminal_code() - JIS polarity helper
```

#### New AJAX Endpoints

- `cbc_get_car_brands` - Returns popular car brands array

### Backwards Compatibility

- All existing features remain functional
- Manual mappings data is preserved
- Cache data continues to work
- ACF field mappings unchanged
- WooCommerce integration unchanged

### Future Enhancements

Planned for upcoming releases:

- Multi-language support
- Car database integration
- Bulk import tool for manual mappings
- Advanced analytics dashboard
- Email notifications for searches
- Export search statistics

---

## [2.0.5] - Previous Release

- Basic chatbot functionality
- Smart cache system
- Manual expert mappings
- JIS code search
- Basic clarification system
- Gemini 2.5 Flash integration

---

## Installation & Update

### Requirements

- WordPress 5.0+
- WooCommerce (latest version recommended)
- Advanced Custom Fields (ACF) - Free or Pro
- PHP 8.0 or higher
- Google Gemini API key

### Update Process

1. Backup your WordPress site
2. Update via WordPress admin plugins page
3. Plugin will automatically update database if needed
4. Clear any caching plugins
5. Test the chatbot functionality

### Support

For issues or questions:
- GitHub Issues: https://github.com/eMarketingcy/Car-battery-Chat-boat
- Email: support@emarketing.cy
- Website: https://emarketing.cy

---

**Note**: This plugin requires proper configuration of ACF fields and WooCommerce products to function correctly. See README.md for detailed setup instructions.
