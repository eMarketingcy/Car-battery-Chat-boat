<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CBC_Manual_Mappings {
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cbc_manual_mappings';
    }

    /**
     * Install the manual mappings database table
     *
     * @return void
     */
    public function install_table(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            query_hash varchar(32) NOT NULL,
            query_raw text NOT NULL,
            product_ids text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY query_hash (query_hash)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Normalize query for consistent hashing
     *
     * @param string $query The search query
     * @return string MD5 hash of normalized query
     */
    private function normalize( string $query ): string {
        $clean = strtolower( $query );
        $clean = str_replace( 'benz', '', $clean );
        $clean = preg_replace( '/[^a-z0-9]/', '', $clean );
        return md5( $clean );
    }

    /**
     * Get product mapping for a query
     *
     * @param string $query The search query
     * @return string|false Product IDs or false if not found
     */
    public function get_mapping( string $query ) {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT product_ids FROM {$this->table_name} WHERE query_hash = %s LIMIT 1",
                $this->normalize( $query )
            )
        );
        return $row ? $row->product_ids : false;
    }

    /**
     * Render the manual mappings admin page
     *
     * @return void
     */
    public function render_page(): void {
        global $wpdb;

        // Handle form submission
        if ( isset( $_POST['add_map'] ) && check_admin_referer( 'cbc_map' ) ) {
            $query = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
            $product_ids = isset( $_POST['ids'] ) ? sanitize_text_field( $_POST['ids'] ) : '';

            if ( $query && $product_ids ) {
                $hash = $this->normalize( $query );
                $exists = $wpdb->get_var(
                    $wpdb->prepare( "SELECT id FROM {$this->table_name} WHERE query_hash = %s", $hash )
                );

                if ( $exists ) {
                    $wpdb->update(
                        $this->table_name,
                        [
                            'query_raw' => $query,
                            'product_ids' => $product_ids
                        ],
                        [ 'id' => $exists ],
                        [ '%s', '%s' ],
                        [ '%d' ]
                    );
                } else {
                    $wpdb->insert(
                        $this->table_name,
                        [
                            'query_hash' => $hash,
                            'query_raw' => $query,
                            'product_ids' => $product_ids
                        ],
                        [ '%s', '%s', '%s' ]
                    );
                }

                echo '<div class="notice notice-success"><p>Mapping saved successfully.</p></div>';
            }
        }

        // Handle deletion
        if ( isset( $_GET['del'] ) ) {
            $del_id = intval( $_GET['del'] );
            if ( check_admin_referer( 'del_' . $del_id ) ) {
                $wpdb->delete( $this->table_name, [ 'id' => $del_id ], [ '%d' ] );
                echo '<div class="notice notice-success"><p>Mapping deleted.</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1>Manual Battery Mappings</h1>
            <p>Map specific car queries to battery products. This overrides AI recommendations.</p>

            <form method="post" style="margin: 20px 0;">
                <?php wp_nonce_field( 'cbc_map' ); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="q">Car Query</label></th>
                        <td>
                            <input type="text" name="q" id="q" placeholder="e.g., BMW X5 2018 3.0d" required class="regular-text" />
                            <p class="description">Enter the car description exactly as users would search for it.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pids">Product IDs</label></th>
                        <td>
                            <input type="text" name="ids" id="pids" placeholder="e.g., 123, 456" required class="regular-text" />
                            <p class="description">Comma-separated WooCommerce product IDs.</p>
                        </td>
                    </tr>
                </table>
                <button type="submit" name="add_map" class="button button-primary">Save Mapping</button>
            </form>

            <hr>

            <h2>Product Search</h2>
            <input type="text" id="psearch" placeholder="Search products by name or ID..." class="regular-text" style="margin-bottom: 10px;" />
            <div id="pres" style="margin-bottom: 20px;"></div>

            <h2>Existing Mappings</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%;">Car Query</th>
                        <th style="width: 40%;">Product IDs</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $mappings = $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY created_at DESC" );
                if ( empty( $mappings ) ) {
                    echo '<tr><td colspan="3" style="text-align: center;">No mappings found. Add your first mapping above.</td></tr>';
                } else {
                    foreach ( $mappings as $mapping ) {
                        $delete_url = wp_nonce_url(
                            admin_url( 'admin.php?page=cbc-manual-mappings&del=' . $mapping->id ),
                            'del_' . $mapping->id
                        );
                        printf(
                            '<tr><td>%s</td><td>%s</td><td><a href="%s" class="button button-small" onclick="return confirm(\'Are you sure?\');">Delete</a></td></tr>',
                            esc_html( $mapping->query_raw ),
                            esc_html( $mapping->product_ids ),
                            esc_url( $delete_url )
                        );
                    }
                }
                ?>
                </tbody>
            </table>

            <script>
            jQuery(document).ready(function($) {
                let searchTimeout;
                $('#psearch').on('input', function() {
                    const term = this.value;
                    if (term.length < 2) {
                        $('#pres').html('');
                        return;
                    }

                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        $.post(ajaxurl, {
                            action: 'cbc_search_products_admin',
                            term: term
                        }, function(response) {
                            if (response.success && response.data) {
                                let html = '<div style="border: 1px solid #ddd; padding: 10px; background: #fff; max-height: 200px; overflow-y: auto;">';
                                response.data.forEach(product => {
                                    html += `<div style="padding: 5px; cursor: pointer; border-bottom: 1px solid #eee;"
                                                  onclick="addProductId(${product.id})">
                                               #${product.id} - ${product.name}
                                             </div>`;
                                });
                                html += '</div>';
                                $('#pres').html(html);
                            }
                        });
                    }, 300);
                });
            });

            function addProductId(productId) {
                const currentIds = jQuery('#pids').val();
                const newIds = currentIds ? currentIds + ', ' + productId : productId;
                jQuery('#pids').val(newIds);
                jQuery('#pres').html('');
                jQuery('#psearch').val('');
            }
            </script>
        </div>
        <?php
    }
}