<?php
/**
 * Admin products template
 *
 * @package Build360_AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get token balance
$api = new Build360_AI_API();
$token_balance = $api->get_token_balance();

// Get enabled fields
$settings = new Build360_AI_Settings();
$fields = $settings->get_enabled_fields('product');

// Check if API is configured
$api_configured = $api->is_configured();

// Get products
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 20,
    'paged' => isset($_GET['paged']) ? absint($_GET['paged']) : 1,
);

$products = new WP_Query($args);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'generated'): ?>
        <div class="notice notice-success">
            <p><?php _e('Content generated successfully!', 'build360-ai'); ?></p>
        </div>
    <?php endif; ?>

    <div class="build360-ai-products">
        <form method="post" action="">
            <?php wp_nonce_field('build360_ai_generate_content', 'build360_ai_nonce'); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action">
                        <option value="-1"><?php _e('Bulk Actions', 'build360-ai'); ?></option>
                        <option value="generate"><?php _e('Generate Content', 'build360-ai'); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'build360-ai'); ?>">
                </div>
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $products->max_num_pages,
                        'current' => isset($_GET['paged']) ? absint($_GET['paged']) : 1,
                    ));
                    ?>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th scope="col" class="manage-column column-title"><?php _e('Product', 'build360-ai'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('SKU', 'build360-ai'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Categories', 'build360-ai'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Last Generated', 'build360-ai'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Actions', 'build360-ai'); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($products->have_posts()): ?>
                        <?php while ($products->have_posts()): $products->the_post(); 
                            $product = wc_get_product(get_the_ID());
                            $last_generated = get_post_meta(get_the_ID(), '_build360_ai_last_generated', true);
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="products[]" value="<?php echo esc_attr(get_the_ID()); ?>">
                                </th>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link(); ?>"><?php echo get_the_title(); ?></a>
                                    </strong>
                                </td>
                                <td><?php echo $product->get_sku(); ?></td>
                                <td>
                                    <?php
                                    $categories = get_the_terms(get_the_ID(), 'product_cat');
                                    if ($categories && !is_wp_error($categories)) {
                                        $cat_names = array();
                                        foreach ($categories as $category) {
                                            $cat_names[] = $category->name;
                                        }
                                        echo esc_html(implode(', ', $cat_names));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($last_generated) {
                                        echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_generated));
                                    } else {
                                        _e('Never', 'build360-ai');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'generate', 'product_id' => get_the_ID()), admin_url('admin.php?page=build360-ai-products')), 'build360_ai_generate_single'); ?>" class="button">
                                        <?php _e('Generate', 'build360-ai'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php _e('No products found.', 'build360-ai'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-2">
                        </td>
                        <th scope="col" class="manage-column column-title"><?php _e('Product', 'build360-ai'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('SKU', 'build360-ai'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Categories', 'build360-ai'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Last Generated', 'build360-ai'); ?></th>
                        <th scope="col" class="manage-column"><?php _e('Actions', 'build360-ai'); ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="action2">
                        <option value="-1"><?php _e('Bulk Actions', 'build360-ai'); ?></option>
                        <option value="generate"><?php _e('Generate Content', 'build360-ai'); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'build360-ai'); ?>">
                </div>
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $products->max_num_pages,
                        'current' => isset($_GET['paged']) ? absint($_GET['paged']) : 1,
                    ));
                    ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Field Selection Modal for Bulk Generation -->
    <div id="build360-ai-field-select-modal" class="build360-bulk-modal" style="display:none;">
        <div class="build360-bulk-modal-overlay"></div>
        <div class="build360-bulk-modal-content build360-field-select-content">
            <div class="build360-bulk-modal-header">
                <h2><?php _e('Select Fields to Generate', 'build360-ai'); ?></h2>
                <button type="button" class="build360-bulk-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
            <div class="build360-bulk-modal-body">
                <p class="build360-field-select-count"></p>
                <div class="build360-field-select-options">
                    <label>
                        <input type="checkbox" name="bulk_fields[]" value="description" checked>
                        <?php _e('Product Description', 'build360-ai'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="bulk_fields[]" value="short_description" checked>
                        <?php _e('Short Description', 'build360-ai'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="bulk_fields[]" value="seo_title">
                        <?php _e('SEO Title', 'build360-ai'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="bulk_fields[]" value="seo_description">
                        <?php _e('SEO Description', 'build360-ai'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="bulk_fields[]" value="image_alt">
                        <?php _e('Image Alt Text', 'build360-ai'); ?>
                    </label>
                </div>
                <p class="build360-batch-info">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('Products are processed in batches of 50 for reliability.', 'build360-ai'); ?>
                </p>
            </div>
            <div class="build360-bulk-modal-footer">
                <button type="button" class="button build360-field-select-cancel"><?php _e('Cancel', 'build360-ai'); ?></button>
                <button type="button" class="button button-primary build360-field-select-start"><?php _e('Start Generation', 'build360-ai'); ?></button>
            </div>
        </div>
    </div>

    <!-- Review Modal for Bulk Generation Results -->
    <div id="build360-ai-bulk-review-modal" class="build360-bulk-modal" style="display:none;">
        <div class="build360-bulk-modal-overlay"></div>
        <div class="build360-bulk-modal-content build360-review-content">
            <div class="build360-bulk-modal-header">
                <h2><?php _e('Review Generated Content', 'build360-ai'); ?></h2>
                <div class="build360-review-header-actions">
                    <span class="build360-review-counter"></span>
                    <button type="button" class="button button-primary build360-review-accept-all"><?php _e('Accept All Remaining', 'build360-ai'); ?></button>
                </div>
                <button type="button" class="build360-bulk-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
            <div class="build360-bulk-modal-body build360-review-body">
                <div class="build360-review-products"></div>
            </div>
            <div class="build360-bulk-modal-footer">
                <div class="build360-review-pagination"></div>
                <button type="button" class="button build360-review-close"><?php _e('Close', 'build360-ai'); ?></button>
            </div>
        </div>
    </div>
</div>