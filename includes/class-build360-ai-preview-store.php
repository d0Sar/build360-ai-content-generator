<?php
/**
 * Preview Store - Custom table for bulk generation previews
 *
 * Replaces post_meta/term_meta storage with a dedicated table for
 * cleaner data management and atomic job cleanup.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Build360_AI_Preview_Store {

    /**
     * Get the table name
     *
     * @return string
     */
    private static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'build360_ai_previews';
    }

    /**
     * Save a preview (REPLACE INTO for upsert behavior)
     *
     * @param string $job_id
     * @param int    $entity_id
     * @param string $entity_type 'product' or 'term'
     * @param string $field       e.g. 'description', 'seo_title'
     * @param string $content
     * @return bool
     */
    public function save($job_id, $entity_id, $entity_type, $field, $content) {
        global $wpdb;
        $table = self::table_name();

        $result = $wpdb->replace(
            $table,
            array(
                'job_id'      => $job_id,
                'entity_id'   => $entity_id,
                'entity_type' => $entity_type,
                'field_name'  => $field,
                'content'     => $content,
                'created_at'  => current_time('mysql', true),
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );

        return $result !== false;
    }

    /**
     * Get a single preview field
     *
     * @param int    $entity_id
     * @param string $entity_type
     * @param string $field
     * @return string|null
     */
    public function get($entity_id, $entity_type, $field) {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->get_var($wpdb->prepare(
            "SELECT content FROM {$table} WHERE entity_id = %d AND entity_type = %s AND field_name = %s",
            $entity_id,
            $entity_type,
            $field
        ));
    }

    /**
     * Get all preview fields for an entity
     *
     * @param int    $entity_id
     * @param string $entity_type
     * @return array field_name => content
     */
    public function get_all_for_entity($entity_id, $entity_type) {
        global $wpdb;
        $table = self::table_name();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT field_name, content FROM {$table} WHERE entity_id = %d AND entity_type = %s",
            $entity_id,
            $entity_type
        ), ARRAY_A);

        $result = array();
        if ($rows) {
            foreach ($rows as $row) {
                $result[$row['field_name']] = $row['content'];
            }
        }
        return $result;
    }

    /**
     * Check if an entity has any previews
     *
     * @param int    $entity_id
     * @param string $entity_type
     * @return bool
     */
    public function has_previews($entity_id, $entity_type) {
        global $wpdb;
        $table = self::table_name();

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE entity_id = %d AND entity_type = %s",
            $entity_id,
            $entity_type
        ));

        return intval($count) > 0;
    }

    /**
     * Delete a single preview field
     *
     * @param int    $entity_id
     * @param string $entity_type
     * @param string $field
     * @return bool
     */
    public function delete_field($entity_id, $entity_type, $field) {
        global $wpdb;
        $table = self::table_name();

        $result = $wpdb->delete(
            $table,
            array(
                'entity_id'   => $entity_id,
                'entity_type' => $entity_type,
                'field_name'  => $field,
            ),
            array('%d', '%s', '%s')
        );

        return $result !== false;
    }

    /**
     * Delete all preview fields for an entity
     *
     * @param int    $entity_id
     * @param string $entity_type
     * @return bool
     */
    public function delete_entity($entity_id, $entity_type) {
        global $wpdb;
        $table = self::table_name();

        $result = $wpdb->delete(
            $table,
            array(
                'entity_id'   => $entity_id,
                'entity_type' => $entity_type,
            ),
            array('%d', '%s')
        );

        return $result !== false;
    }

    /**
     * Delete all previews for a job (atomic cleanup with 1 query)
     *
     * @param string $job_id
     * @return bool
     */
    public function delete_job($job_id) {
        global $wpdb;
        $table = self::table_name();

        $result = $wpdb->delete(
            $table,
            array('job_id' => $job_id),
            array('%s')
        );

        return $result !== false;
    }

    /**
     * Delete expired previews (safety net for orphans)
     *
     * @param int $max_age_seconds Default 3600 (1 hour)
     * @return int Number of rows deleted
     */
    public function delete_expired($max_age_seconds = 3600) {
        global $wpdb;
        $table = self::table_name();

        $cutoff = gmdate('Y-m-d H:i:s', time() - $max_age_seconds);

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < %s",
            $cutoff
        ));
    }

    /**
     * Count pending previews for a job
     *
     * @param string $job_id
     * @return int
     */
    public function count_pending_for_job($job_id) {
        global $wpdb;
        $table = self::table_name();

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT entity_id) FROM {$table} WHERE job_id = %s",
            $job_id
        )));
    }

    /**
     * Get the oldest created_at timestamp for a job (UTC)
     *
     * @param string $job_id
     * @return string|null MySQL datetime string or null
     */
    public function get_oldest_created_at($job_id) {
        global $wpdb;
        $table = self::table_name();

        return $wpdb->get_var($wpdb->prepare(
            "SELECT MIN(created_at) FROM {$table} WHERE job_id = %s",
            $job_id
        ));
    }

    /**
     * Create the custom table (called on activation and migration)
     */
    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id VARCHAR(80) NOT NULL,
            entity_id BIGINT(20) UNSIGNED NOT NULL,
            entity_type VARCHAR(20) NOT NULL,
            field_name VARCHAR(40) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY entity_field (entity_id, entity_type, field_name),
            KEY job_id (job_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Drop the custom table (called on uninstall)
     */
    public static function drop_table() {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Migrate from post_meta/term_meta to custom table.
     * Deletes old preview meta keys.
     */
    public static function migrate_from_meta() {
        global $wpdb;

        // Delete old preview post meta
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_build360_ai_preview_%'"
        );

        // Delete old preview term meta
        $wpdb->query(
            "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE '_build360_ai_preview_%'"
        );
    }
}
