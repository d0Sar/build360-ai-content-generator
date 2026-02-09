<?php
/**
 * Content Generator class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Build360_AI_Generator {
    /**
     * API client instance
     *
     * @var Build360_AI_API
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new Build360_AI_API();
    }

    /**
     * Generate product description
     *
     * @param array $product_data Product data
     * @param string $agent_id The ID of the agent to use
     * @return string|WP_Error Generated description or error
     */
    public function generate_product_description($product_data, $agent_id, $extra_context = []) {
        if (empty($agent_id)) return new WP_Error('missing_agent_id', __('Agent ID is required for content generation.', 'build360-ai'));

        $prompt = $this->build_product_prompt($product_data);

        // Prepare API request data
        $api_data = array_merge([
            'prompt' => $prompt,
            'product_title' => isset($product_data['product_title']) ? $product_data['product_title'] : $product_data['name'],
            'product_description' => isset($product_data['description']) ? $product_data['description'] : '',
            'fields_requested' => ['description']
        ], $extra_context);

        return $this->api->generate_content($agent_id, 'post', $api_data);
    }

    /**
     * Generate product short description
     *
     * @param array $product_data Product data
     * @param string $agent_id The ID of the agent to use
     * @return string|WP_Error Generated short description or error
     */
    public function generate_product_short_description($product_data, $agent_id, $extra_context = []) {
        if (empty($agent_id)) return new WP_Error('missing_agent_id', __('Agent ID is required for content generation.', 'build360-ai'));

        $prompt = $this->build_product_prompt($product_data, true);

        // Prepare API request data
        $api_data = array_merge([
            'prompt' => $prompt,
            'product_title' => isset($product_data['product_title']) ? $product_data['product_title'] : $product_data['name'],
            'product_description' => isset($product_data['description']) ? $product_data['description'] : '',
            'short' => true,
            'fields_requested' => ['short_description']
        ], $extra_context);

        return $this->api->generate_content($agent_id, 'post', $api_data);
    }

    /**
     * Generate product content
     *
     * @param WC_Product $product WooCommerce product
     * @param string $field Field to generate (description, short_description)
     * @param string $agent_id The ID of the agent to use
     * @return string|WP_Error Generated content or error
     */
    public function generate_product_content($product, $field, $agent_id) {
        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product', 'build360-ai'));
        }

        $product_data = [
            'name' => $product->get_name(),
            'category' => $this->get_product_categories($product),
            'attributes' => $this->get_product_attributes($product),
            'keywords' => []
        ];

        switch ($field) {
            case 'description':
                return $this->generate_product_description($product_data, $agent_id);
            case 'short_description':
                return $this->generate_product_short_description($product_data, $agent_id);
            default:
                return new WP_Error('invalid_field', __('Invalid field', 'build360-ai'));
        }
    }

    /**
     * Generate SEO meta description
     *
     * @param array $product_data Product data
     * @param string $agent_id The ID of the agent to use
     * @return string|WP_Error Generated meta description or error
     */
    public function generate_seo_meta_description($product_data, $agent_id, $extra_context = []) {
        if (empty($agent_id)) return new WP_Error('missing_agent_id', __('Agent ID is required for content generation.', 'build360-ai'));

        $prompt = $this->build_seo_prompt($product_data);
        $api_data = array_merge([
            'prompt' => $prompt,
            'product_title' => isset($product_data['name']) ? $product_data['name'] : '',
            'product_description' => isset($product_data['description']) ? $product_data['description'] : '',
            'type' => 'meta_description',
            'fields_requested' => ['seo_description']
        ], $extra_context);
        return $this->api->generate_content($agent_id, 'post', $api_data);
    }

    /**
     * Generate SEO title
     *
     * @param array $product_data Product data
     * @param string $agent_id The ID of the agent to use
     * @return string|WP_Error Generated SEO title or error
     */
    public function generate_seo_title($product_data, $agent_id, $extra_context = []) {
        if (empty($agent_id)) return new WP_Error('missing_agent_id', __('Agent ID is required for content generation.', 'build360-ai'));

        $prompt = $this->build_seo_prompt($product_data, true);
        $api_data = array_merge([
            'prompt' => $prompt,
            'product_title' => isset($product_data['name']) ? $product_data['name'] : '',
            'product_description' => isset($product_data['description']) ? $product_data['description'] : '',
            'type' => 'title',
            'fields_requested' => ['seo_title']
        ], $extra_context);
        return $this->api->generate_content($agent_id, 'post', $api_data);
    }

    /**
     * Generate image alt text
     *
     * @param array $product_data Product data
     * @param string $agent_id The ID of the agent to use
     * @return string|WP_Error Generated alt text or error
     */
    public function generate_image_alt_text($product_data, $agent_id, $extra_context = []) {
        if (empty($agent_id)) return new WP_Error('missing_agent_id', __('Agent ID is required for content generation.', 'build360-ai'));

        $prompt = $this->build_image_prompt($product_data);
        $api_data = array_merge([
            'prompt' => $prompt,
            'product_title' => isset($product_data['name']) ? $product_data['name'] : '',
            'product_description' => isset($product_data['description']) ? $product_data['description'] : '',
            'fields_requested' => ['image_alt']
        ], $extra_context);
        return $this->api->generate_content($agent_id, 'post', $api_data);
    }

    /**
     * Generate blog post
     *
     * @param array $post_data Post data
     * @param string $agent_id The ID of the agent to use
     * @return string|WP_Error Generated blog post or error
     */
    public function generate_blog_post($post_data, $agent_id) {
        if (empty($agent_id)) return new WP_Error('missing_agent_id', __('Agent ID is required for content generation.', 'build360-ai'));

        $prompt = $this->build_blog_prompt($post_data);
        return $this->api->generate_content($agent_id, 'post', ['prompt' => $prompt, 'fields_requested' => ['content']]);
    }

    /**
     * Orchestrates content generation for a specific product field using an agent.
     *
     * @param WC_Product $product WooCommerce product object.
     * @param string $field The specific field to generate (e.g., 'description', 'seo_title').
     * @param string $agent_id The ID of the agent to use.
     * @param array $keywords Optional keywords to pass as part of product_data.
     * @return string|WP_Error Generated content or WP_Error.
     */
    public function generate_product_content_with_agent($product, $field, $agent_id, $keywords = []) {
        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product provided.', 'build360-ai'));
        }
        if (empty($agent_id)) {
            return new WP_Error('missing_agent_id', __('Agent ID is required for product content generation.', 'build360-ai'));
        }

        // Prepare base product data for context, including current description for context
        $product_data = [
            'name'        => $product->get_name(),
            'description' => $product->get_description(), // Existing description for context
            'category'    => $this->get_product_categories($product),
            'attributes'  => $this->get_product_attributes($product),
            'keywords'    => $keywords
        ];

        // Build extra context for API enrichment (categories, attributes, tags, keywords)
        $attrs = $product_data['attributes'];
        $attr_strings = array();
        if (is_array($attrs)) {
            foreach ($attrs as $attr_name => $attr_value) {
                if (is_array($attr_value)) {
                    $attr_strings[] = $attr_name . ': ' . implode(', ', $attr_value);
                } else {
                    $attr_strings[] = $attr_name . ': ' . $attr_value;
                }
            }
        }

        // Get product tags
        $tags_terms = get_the_terms($product->get_id(), 'product_tag');
        $tags_str = '';
        if ($tags_terms && !is_wp_error($tags_terms)) {
            $tags_str = implode(', ', wp_list_pluck($tags_terms, 'name'));
        }

        $extra_context = array(
            'categories' => $product_data['category'],
            'attributes' => implode('; ', $attr_strings),
            'tags'       => $tags_str,
            'keywords'   => !empty($keywords) ? implode(', ', $keywords) : '',
        );

        switch ($field) {
            case 'description':
                return $this->generate_product_description($product_data, $agent_id, $extra_context);
            case 'short_description':
                return $this->generate_product_short_description($product_data, $agent_id, $extra_context);
            case 'seo_title':
                return $this->generate_seo_title($product_data, $agent_id, $extra_context);
            case 'seo_description':
                return $this->generate_seo_meta_description($product_data, $agent_id, $extra_context);
            case 'image_alt':
                return $this->generate_image_alt_text($product_data, $agent_id, $extra_context);
            default:
                return new WP_Error('invalid_field', sprintf(__('Field "%s" is not supported for product content generation with agents.', 'build360-ai'), $field));
        }
    }

    /**
     * Generate content for a specific field
     *
     * @param string $content_type Type of content (product, post, page, etc.)
     * @param string $field Field to generate (description, title, etc.)
     * @param array $data Data for content generation
     * @param string $agent_id The ID of the agent to use
     * @return string|WP_Error Generated content or error
     */
    public function generate_content_field($content_type, $field, $data, $agent_id = null) {
        if (empty($agent_id)) {
            // Attempt to get a default agent for the content type if not provided
            // This is a fallback and might not be ideal; explicit agent_id is better.
            $agent_assignments = get_option('build360_ai_agent_assignments', array());
            foreach ($agent_assignments as $assignment) {
                if ($assignment['type'] === $content_type) {
                    $agent_id = $assignment['agent_id'];
                    break;
                }
            }
            if (empty($agent_id)) {
                 return new WP_Error('missing_agent_id', __('Agent ID is required and could not be determined for content type.', 'build360-ai'));
            }
        }

        switch ($content_type) {
            case 'product':
                switch ($field) {
                    case 'description':
                        return $this->generate_product_description($data, $agent_id);
                    case 'short_description':
                        return $this->generate_product_short_description($data, $agent_id);
                    case 'seo_title':
                        return $this->generate_seo_title($data, $agent_id);
                    case 'seo_description':
                    case 'seo_meta_description': // Handle both common keys
                        return $this->generate_seo_meta_description($data, $agent_id);
                    case 'image_alt':
                        return $this->generate_image_alt_text($data, $agent_id);
                    default:
                        return new WP_Error('invalid_field', __('Invalid product field for agent generation', 'build360-ai'));
                }
            case 'post': // Assuming post/page use similar logic
            case 'page':
                switch ($field) {
                    case 'content': // Main content for a blog post
                        return $this->generate_blog_post($data, $agent_id);
                    case 'seo_title':
                        return $this->generate_seo_title($data, $agent_id); // Needs product_data like structure with 'title' and 'description' for context
                    case 'seo_description':
                        return $this->generate_seo_meta_description($data, $agent_id);
                    default:
                        return new WP_Error('invalid_field', __('Invalid post/page field for agent generation', 'build360-ai'));
                }
            // Add cases for other content_types like 'category' if needed
            default:
                return new WP_Error('invalid_content_type', __('Content type not supported for agent generation', 'build360-ai'));
        }
    }

    /**
     * Build product prompt
     *
     * @param array $product_data Product data
     * @param bool $is_short Whether to generate short description
     * @return string Generated prompt
     */
    private function build_product_prompt($product_data, $is_short = false) {
        $prompt = "Generate a " . ($is_short ? "short " : "") . "product description for:\n\n";
        $prompt .= "Product Name: " . $product_data['name'] . "\n";

        if (!empty($product_data['category'])) {
            $prompt .= "Category: " . $product_data['category'] . "\n";
        }

        if (!empty($product_data['attributes'])) {
            $prompt .= "Attributes:\n";
            foreach ($product_data['attributes'] as $attr => $value) {
                $prompt .= "- $attr: $value\n";
            }
        }

        if (!empty($product_data['keywords'])) {
            $prompt .= "Keywords: " . implode(", ", $product_data['keywords']) . "\n";
        }

        $prompt .= "\nTone: " . get_option('build360_ai_text_style', 'professional') . "\n";
        $prompt .= "Max Length: " . ($is_short ?
            get_option('build360_ai_max_product_text', '150') :
            get_option('build360_ai_max_product_desc_text', '300')) . " words";

        return $prompt;
    }

    /**
     * Build SEO prompt
     *
     * @param array $data Content data
     * @param bool $is_title Whether to generate title
     * @return string Generated prompt
     */
    private function build_seo_prompt($data, $is_title = false) {
        $prompt = "Generate a " . ($is_title ? "SEO title" : "meta description") . " for:\n\n";
        $prompt .= "Content Title: " . $data['title'] . "\n";

        if (!empty($data['type'])) {
            $prompt .= "Content Type: " . $data['type'] . "\n";
        }

        if (!empty($data['keywords'])) {
            $prompt .= "Target Keywords: " . implode(", ", $data['keywords']) . "\n";
        }

        if (!empty($data['description'])) {
            $prompt .= "Content Summary: " . $data['description'] . "\n";
        }

        $prompt .= "\nMax Length: " . ($is_title ? "60" : "160") . " characters";

        return $prompt;
    }

    /**
     * Build image prompt
     *
     * @param array $image_data Image data
     * @return string Generated prompt
     */
    private function build_image_prompt($image_data) {
        $prompt = "Generate SEO-friendly alt text for an image with:\n\n";

        if (!empty($image_data['title'])) {
            $prompt .= "Image Title: " . $image_data['title'] . "\n";
        }

        if (!empty($image_data['context'])) {
            $prompt .= "Context: " . $image_data['context'] . "\n";
        }

        if (!empty($image_data['keywords'])) {
            $prompt .= "Keywords: " . implode(", ", $image_data['keywords']) . "\n";
        }

        $prompt .= "\nMax Length: 125 characters";

        return $prompt;
    }

    /**
     * Build blog prompt
     *
     * @param array $post_data Post data
     * @return string Generated prompt
     */
    private function build_blog_prompt($post_data) {
        $prompt = "Generate a blog post about:\n\n";
        $prompt .= "Title: " . $post_data['title'] . "\n";

        if (!empty($post_data['keywords'])) {
            $prompt .= "Target Keywords: " . implode(", ", $post_data['keywords']) . "\n";
        }

        if (!empty($post_data['outline'])) {
            $prompt .= "Outline:\n";
            foreach ($post_data['outline'] as $section) {
                $prompt .= "- $section\n";
            }
        }

        $prompt .= "\nTone: " . get_option('build360_ai_text_style', 'professional') . "\n";
        $prompt .= "Max Length: " . get_option('build360_ai_max_blog_text', '1000') . " words";

        return $prompt;
    }

    /**
     * Get product categories as a string
     *
     * @param WC_Product $product WooCommerce product
     * @return string Categories separated by comma
     */
    private function get_product_categories($product) {
        $categories = [];
        $terms = get_the_terms($product->get_id(), 'product_cat');

        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = $term->name;
            }
        }

        return implode(', ', $categories);
    }

    /**
     * Get product attributes as an array
     *
     * @param WC_Product $product WooCommerce product
     * @return array Attributes as key-value pairs
     */
    private function get_product_attributes($product) {
        $attributes = [];

        // Get product attributes for variable products
        if ($product->is_type('variable')) {
            $attrs = $product->get_attributes();
            if (!empty($attrs)) {
                foreach ($attrs as $attr_name => $attr) {
                    if ($attr->is_taxonomy()) {
                        $terms = wc_get_product_terms($product->get_id(), $attr_name, ['fields' => 'names']);
                        if (!empty($terms)) {
                            $attributes[wc_attribute_label($attr_name)] = implode(', ', $terms);
                        }
                    } else {
                        $values = $attr->get_options();
                        if (!empty($values)) {
                            $attributes[wc_attribute_label($attr_name)] = implode(', ', $values);
                        }
                    }
                }
            }
        }

        // Get product meta data
        $meta_data = $product->get_meta_data();
        if (!empty($meta_data)) {
            foreach ($meta_data as $meta) {
                $data = $meta->get_data();
                if (!empty($data['key']) && !empty($data['value']) && is_string($data['value'])) {
                    // Skip internal meta
                    if (substr($data['key'], 0, 1) !== '_' && strlen($data['value']) < 100) {
                        $attributes[$data['key']] = $data['value'];
                    }
                }
            }
        }

        return $attributes;
    }
}
