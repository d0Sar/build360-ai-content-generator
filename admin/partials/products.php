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
</div> 