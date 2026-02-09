# Build360 AI Content Generator

A powerful WordPress plugin that uses AI to automatically generate high-quality content for your WooCommerce products.

## Description

Build360 AI Content Generator integrates with your WooCommerce store to automatically generate product descriptions, SEO meta data, and image alt text using advanced AI technology. The plugin helps you save time and improve your product content quality by leveraging state-of-the-art language models.

### Features

- Generate complete product descriptions
- Create concise short descriptions
- Generate SEO-optimized titles and meta descriptions
- Create relevant image alt text
- Bulk generation for multiple products
- Customizable content style and length
- Integration with popular SEO plugins
- Token-based usage tracking
- Recent activity monitoring

## Installation

1. Upload the `build360-ai-content-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Build360 AI settings page and enter your API credentials
4. Configure your preferred content generation settings

## Configuration

### API Settings

1. Get your API key from your Build360 AI account
2. Enter your API key in the plugin settings
3. Configure the API domain (default: https://api.build360.ai)

### Content Settings

- Select your preferred AI model
- Choose a text style for generated content
- Configure maximum text lengths for different content types
- Enable/disable specific content fields

## Usage

### Single Product Generation

1. Edit a product in WooCommerce
2. Find the "Build360 AI Content Generator" meta box
3. Select the fields you want to generate
4. Add optional keywords to guide the content generation
5. Click "Generate Content"

### Bulk Generation

1. Go to Products → All Products
2. Select multiple products using the checkboxes
3. Choose "Generate content with Build360 AI" from the Bulk Actions dropdown
4. Click "Apply"

### Product List Actions

- Use the "Generate with AI" quick action in the product list
- Filter products by AI-generated content status
- View generation timestamp in the products list

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher
- Valid Build360 AI API credentials

## Development

### Testing

The plugin includes a basic test suite to verify functionality. To run the tests:

1. Navigate to the plugin directory
2. Run `php tests/test-build360-ai.php` from the command line

The tests verify:
- API configuration
- Content generation methods
- WooCommerce product integration

You can extend the tests by adding more test methods to the `Build360_AI_Test` class.

## Support

For support, feature requests, or bug reports, please contact:

- Email: support@build360.ai
- Website: https://build360.ai/support
- Documentation: https://build360.ai/docs

## Changelog

### 1.1.0
- Added comprehensive product data extraction for better content generation
- Improved API integration with structured data handling
- Added unified content generation interface for different content types
- Enhanced SEO metadata generation with support for popular SEO plugins
- Added basic test suite for development and debugging
- Fixed inconsistencies between API and generator classes
- Completed product integration functionality

### 1.0.0
- Initial release
- Basic content generation features
- WooCommerce integration
- Admin interface
- Bulk generation support

## License

This plugin is licensed under the GPL v2 or later.

Copyright (c) 2024 Build360 AI

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA 
