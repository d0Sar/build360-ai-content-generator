<?php
/**
 * Build360 AI Content Generator Tests
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Build360_AI_Test
 */
class Build360_AI_Test {
    /**
     * Run tests
     */
    public function run_tests() {
        $this->setup();
        
        // Run individual tests
        $this->test_api_configuration();
        $this->test_generator_methods();
        $this->test_product_integration();
        
        $this->teardown();
        
        echo "All tests completed.\n";
    }
    
    /**
     * Setup test environment
     */
    private function setup() {
        echo "Setting up test environment...\n";
        
        // Save original options
        $this->original_api_key = get_option('build360_ai_api_key');
        $this->original_domain = get_option('build360_ai_domain');
        
        // Set test options
        update_option('build360_ai_api_key', 'test_api_key');
        update_option('build360_ai_domain', 'https://api.build360.ai');
        
        echo "Test environment setup complete.\n";
    }
    
    /**
     * Teardown test environment
     */
    private function teardown() {
        echo "Tearing down test environment...\n";
        
        // Restore original options
        update_option('build360_ai_api_key', $this->original_api_key);
        update_option('build360_ai_domain', $this->original_domain);
        
        echo "Test environment teardown complete.\n";
    }
    
    /**
     * Test API configuration
     */
    private function test_api_configuration() {
        echo "Testing API configuration...\n";
        
        $api = new Build360_AI_API();
        
        // Test is_configured
        $is_configured = $api->is_configured();
        echo "API is configured: " . ($is_configured ? "Yes" : "No") . "\n";
        
        // Test get_api_url (using reflection to access private method)
        $reflection = new ReflectionClass($api);
        $method = $reflection->getMethod('get_api_url');
        $method->setAccessible(true);
        $url = $method->invoke($api, 'test');
        echo "API URL: " . $url . "\n";
        
        echo "API configuration tests complete.\n";
    }
    
    /**
     * Test generator methods
     */
    private function test_generator_methods() {
        echo "Testing generator methods...\n";
        
        $generator = new Build360_AI_Generator();
        
        // Mock a product
        $product_data = [
            'name' => 'Test Product',
            'category' => 'Test Category',
            'attributes' => [
                'Color' => 'Red',
                'Size' => 'Large'
            ],
            'keywords' => ['test', 'product']
        ];
        
        // Test build_product_prompt (using reflection to access private method)
        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('build_product_prompt');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($generator, $product_data);
        echo "Product prompt generated: " . (strlen($prompt) > 0 ? "Yes" : "No") . "\n";
        
        $short_prompt = $method->invoke($generator, $product_data, true);
        echo "Short product prompt generated: " . (strlen($short_prompt) > 0 ? "Yes" : "No") . "\n";
        
        echo "Generator method tests complete.\n";
    }
    
    /**
     * Test product integration
     */
    private function test_product_integration() {
        echo "Testing product integration...\n";
        
        // Skip if WooCommerce is not active
        if (!class_exists('WooCommerce')) {
            echo "WooCommerce not active, skipping product integration tests.\n";
            return;
        }
        
        $integration = new Build360_AI_Product_Integration();
        
        // Test get_settings method (using reflection to access private method)
        $reflection = new ReflectionClass($integration);
        $method = $reflection->getMethod('get_settings');
        $method->setAccessible(true);
        
        $settings = $method->invoke($integration);
        echo "Settings retrieved: " . (!empty($settings) ? "Yes" : "No") . "\n";
        
        echo "Product integration tests complete.\n";
    }
}

// Run tests if this file is executed directly
if (isset($argv) && basename($argv[0]) === basename(__FILE__)) {
    $test = new Build360_AI_Test();
    $test->run_tests();
}