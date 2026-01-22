<?php
/**
 * Plugin Name:       Car Battery AI Chatbot
 * Plugin URI:        https://emarketing.cy
 * Description:       An AI-powered chatbot to help customers find the right car battery. Features Smart Cache, Expert Training, Auto-JIS Calculation, and Smart Clarification.
 * Version:           2.0.5
 * Author:            eMarketing Cyprus
 * Author URI:        https://emarketing.cy
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       carbattery-chatbot
 * Requires at least: 5.0
 * Tested up to:      6.5
 * Requires PHP:      7.4
 * Tags:              chatbot, woocommerce, ai, gemini, products, battery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

define( 'CBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CBC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CBC_DB_VERSION', '1.2' );

// Include Sub-Classes
require_once CBC_PLUGIN_PATH . 'includes/class-cbc-jis-calculator.php';
require_once CBC_PLUGIN_PATH . 'includes/class-cbc-search-stats.php';
require_once CBC_PLUGIN_PATH . 'includes/class-cbc-manual-mappings.php';

class CarBatteryChatbot {

    private $table_cache;
    public $stats;
    public $mappings;
    public $jis_calc;

    public function __construct() {
        global $wpdb;
        $this->table_cache = $wpdb->prefix . 'cbc_search_cache';

        // Initialize Sub-Modules
        $this->jis_calc = new CBC_JIS_Calculator();
        $this->stats = new CBC_Search_Stats();
        $this->mappings = new CBC_Manual_Mappings();

        // Core Hooks
        add_action( 'admin_notices', [ $this, 'check_dependencies' ] );
        add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        
        // AJAX Handlers
        add_action( 'wp_ajax_get_battery_recommendation', [ $this, 'handle_ajax_request' ] );
        add_action( 'wp_ajax_nopriv_get_battery_recommendation', [ $this, 'handle_ajax_request' ] );
        add_action( 'wp_ajax_cbc_search_products_admin', [ $this, 'handle_admin_product_search' ] );
        
        // Install DB & Auto-Calc
        add_action( 'plugins_loaded', [ $this, 'update_db_check' ] );
        add_action( 'save_post_product', [ $this, 'auto_calculate_jis_code' ], 20, 3 );
    }

    public function update_db_check() {
        if ( get_site_option( 'cbc_db_version' ) != CBC_DB_VERSION ) {
            $this->install_db();
        }
    }

    public function install_db() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_cache = "CREATE TABLE $this->table_cache (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            query_hash varchar(32) NOT NULL,
            query_raw text NOT NULL,
            specs_json text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY query_hash (query_hash)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_cache );
        
        // Trigger submodule tables
        $this->mappings->install_table();

        update_option( 'cbc_db_version', CBC_DB_VERSION );
    }

    public function check_dependencies() {
        if ( ! class_exists( 'WooCommerce' ) ) echo '<div class="notice notice-error"><p><strong>Car Battery AI Chatbot:</strong> Requires WooCommerce.</p></div>';
        if ( ! class_exists( 'ACF' ) ) echo '<div class="notice notice-error"><p><strong>Car Battery AI Chatbot:</strong> Requires ACF.</p></div>';
    }

    public function register_admin_menus() {
        add_menu_page('Car Battery Bot', 'Car Battery Bot', 'manage_options', 'cbc-search-stats', [ $this->stats, 'render_page' ], 'dashicons-format-chat', 50 );
        add_submenu_page('cbc-search-stats', 'Search Stats', 'Search Stats', 'manage_options', 'cbc-search-stats', [ $this->stats, 'render_page' ] );
        add_submenu_page('cbc-search-stats', 'Manual Mappings', 'Manual Mappings', 'manage_options', 'cbc-manual-mappings', [ $this->mappings, 'render_page' ] );
        add_submenu_page('cbc-search-stats', 'Settings', 'Settings', 'manage_options', 'cbc-chatbot-settings', [ $this, 'render_settings_page' ] );
    }

    public function register_settings() {
        register_setting( 'cbc_options', 'cbc_gemini_api_key' );
        register_setting( 'cbc_options', 'cbc_shop_page_url' );
        register_setting( 'cbc_options', 'cbc_acf_field_ah' );
        register_setting( 'cbc_options', 'cbc_acf_field_cca' );
        register_setting( 'cbc_options', 'cbc_acf_field_technology' );
        register_setting( 'cbc_options', 'cbc_acf_field_length' );
        register_setting( 'cbc_options', 'cbc_acf_field_width' );
        register_setting( 'cbc_options', 'cbc_acf_field_height' );
        register_setting( 'cbc_options', 'cbc_acf_field_polarity' );
        register_setting( 'cbc_options', 'cbc_acf_field_jis_code' ); 
        register_setting( 'cbc_options', 'cbc_contact_phone' );
        register_setting( 'cbc_options', 'cbc_contact_whatsapp' );
        register_setting( 'cbc_options', 'cbc_contact_email' );
        register_setting( 'cbc_options', 'cbc_contact_page_url' );
    }

    // --- AUTO JIS CALCULATION LOGIC ---
    public function auto_calculate_jis_code( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( $post->post_type !== 'product' ) return;

        $target_field = get_option( 'cbc_acf_field_jis_code' );
        if ( empty( $target_field ) ) return; 

        $product = wc_get_product( $post_id );
        if ( ! $product ) return;
        
        $length = $product->get_length();
        $width  = $product->get_width();
        
        if ( empty( $length ) || empty( $width ) ) return;

        $field_ah_key = get_option( 'cbc_acf_field_ah' );
        $field_pol_key = get_option( 'cbc_acf_field_polarity' );
        
        $ah_val = get_post_meta( $post_id, $field_ah_key, true ); 
        $pol_val = get_post_meta( $post_id, $field_pol_key, true );

        $rank = intval( $ah_val ); 
        if ( $rank == 0 ) return; 

        $widths = [
            'A' => 12.7, 'B' => 12.9, 'C' => 13.5, 
            'D' => 17.3, 'E' => 17.5, 'F' => 18.2, 
            'G' => 22.2, 'H' => 27.8
        ];
        
        $closest_letter = '';
        $min_diff = 999;
        $w_float = floatval( $width );

        foreach ( $widths as $letter => $val ) {
            $diff = abs( $w_float - $val );
            if ( $diff < $min_diff ) {
                $min_diff = $diff;
                $closest_letter = $letter;
            }
        }

        $len_code = round( floatval( $length ) );

        $term_code = '';
        if ( $pol_val === '0' || $pol_val === 0 ) $term_code = 'R';
        if ( $pol_val === '1' || $pol_val === 1 ) $term_code = 'L';
        
        if ( empty( $closest_letter ) || empty( $term_code ) ) return;

        $jis_code = $rank . $closest_letter . $len_code . $term_code;
        update_post_meta( $post_id, $target_field, $jis_code );
    }

    // --- MAIN AJAX HANDLER ---
    public function handle_ajax_request() {
         ob_start(); 
         if ( ! check_ajax_referer( 'cbc_ajax_nonce', 'nonce', false ) ) { ob_clean(); wp_send_json_error( [ 'message' => 'Invalid nonce.' ], 403 ); return; }
         
         $car_info = sanitize_text_field( $_POST['car_info'] );
         $api_key = get_option( 'cbc_gemini_api_key' );
         $ip_address = $this->get_user_ip_address(); 

         if ( empty( $api_key ) ) { ob_clean(); wp_send_json_error( [ 'message' => 'API Key Missing.' ], 500 ); return; }

        $source_type = 'api';

        try {
            // 0. CHECK JIS DIRECT SEARCH
            if ( preg_match( '/^([0-9]{2,3}[A-H][0-9]{2}[RL]|N-[0-9]+|Q-[0-9]+|[SST]-[0-9]+)$/i', trim($car_info) ) ) {
                $jis_results = $this->find_products_by_jis_code(strtoupper(trim($car_info)));
                if (!empty($jis_results)) {
                    $source_type = 'jis_direct';
                    $first = $jis_results[0];
                    $specs = [ 'ah' => $first['ah'], 'cca' => $first['cca'], 'technology' => $first['technology'], 'length' => $first['length'], 'width' => $first['width'], 'height' => $first['height'], 'polarity' => $first['polarity'] ];
                    $response_data = [ 'type' => 'results', 'specs' => $specs, 'display_specs' => array_merge($specs, ['display_technology' => 'JIS Code Match']), 'batteries' => $jis_results, 'view_more_url' => get_option( 'cbc_shop_page_url' ) ];
                    $this->stats->log_query( $car_info, ['success'=>true, 'data'=>$response_data], $ip_address, $source_type );
                    ob_clean(); wp_send_json_success( $response_data ); return;
                }
            }

            // 1. CHECK MANUAL MAPPINGS
            $manual_ids = $this->mappings->get_mapping($car_info);
            
            if ($manual_ids) {
                $batteries = $this->get_products_by_ids($manual_ids);
                $source_type = 'manual';
                if (!empty($batteries)) {
                    $first = $batteries[0];
                    $specs = [ 'ah' => $first['ah'], 'cca' => $first['cca'], 'technology' => $first['technology'], 'length' => $first['length'], 'width' => $first['width'], 'height' => $first['height'], 'polarity' => $first['polarity'] ];
                    $display_technology = $specs['technology'];
                } else {
                    $specs = ['ah'=>0,'cca'=>0,'technology'=>'Unknown','length'=>0,'width'=>0,'height'=>0,'polarity'=>'0'];
                    $display_technology = 'Manual Selection';
                }
                $view_more_url = get_option( 'cbc_shop_page_url' );

            } else {
                // 2. CHECK CACHE
                $specs = $this->get_cached_specs($car_info);

                if ($specs) {
                    $source_type = 'cache';
                } else {
                    // 3. ASK GEMINI (WITH FASTER TIMEOUT SETTING)
                    if ( function_exists( 'set_time_limit' ) ) @set_time_limit(30); // 30s is plenty for 2.5 Flash
                    
                    $specs = $this->get_specs_from_gemini( $car_info, $api_key );
                    
                    // Clarification Check
                    if ( isset($specs['ambiguous']) && $specs['ambiguous'] === true ) {
                        $response_data = [ 'type' => 'clarification', 'question' => $specs['question'], 'options' => $specs['options'] ];
                        ob_clean(); wp_send_json_success( $response_data ); return; 
                    }
                    
                    $this->cache_specs($car_info, $specs);
                }
                
                $display_technology = $specs['technology']; 
                $search_technology = $specs['technology']; 
                $batteries = $this->find_woo_compatible_batteries( $specs, $search_technology ); 
                $view_more_url = $this->build_view_more_url($specs, $search_technology);
            }

            $response_data_for_user = [
                'type' => 'results',
                'specs'         => $specs, 
                'display_specs' => array_merge($specs, ['display_technology' => $display_technology]),
                'batteries'     => $batteries,
                'view_more_url' => $view_more_url
            ];
            
            $this->stats->log_query( $car_info, ['success'=>true, 'data'=>$response_data_for_user], $ip_address, $source_type );
            ob_clean(); wp_send_json_success( $response_data_for_user );

        } catch ( Exception $e ) {
             $error_response = [ 'message' => $e->getMessage() ];
             $this->stats->log_query( $car_info, ['success'=>false, 'data'=>$error_response], $ip_address, 'error' );
             ob_clean(); wp_send_json_error( $error_response, 500 );
        }
    }

    // --- GEMINI & PROMPT (UPDATED TO 2.5 FLASH) ---
    private function get_specs_from_gemini( $car_info, $api_key ) {
        // Updated to gemini-2.5-flash per user request
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;
        
        $prompt = "Role: Expert Automotive Battery Technician for the Cyprus Market.
        Context: Cyprus drives on the left (RHD). High volume of Japanese imports and UK imports.
        Task: Analyze vehicle \"{$car_info}\".
        Instructions:
        1. Ambiguity Check: If key info (Petrol vs Diesel, Year) is missing, return 'ambiguous'.
        2. Standard Analysis: Provide specs. Start-Stop MUST be AGM/EFB. JIS for Japanese.
        
        Output 1 (Ambiguous): {\"ambiguous\": true, \"question\": \"...\", \"options\": [\"...\"]}
        Output 2 (Specific): {\"ambiguous\": false, \"ah\": int, \"cca\": int, \"technology\": string, \"length\": mm, \"width\": mm, \"height\": mm, \"polarity\": 0/1, \"jis_code\": string}";
        
        $body = [ 'contents' => [ 'parts' => [ [ 'text' => $prompt ] ] ], 'generationConfig' => [ 'responseMimeType' => 'application/json' ] ];
        $response = wp_remote_post( $api_url, [ 'method' => 'POST', 'headers' => [ 'Content-Type' => 'application/json' ], 'body' => json_encode( $body ), 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) throw new Exception( 'Gemini Connection Error: ' . $response->get_error_message() );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $data['error'] ) ) throw new Exception( 'Gemini API Error: ' . ($data['error']['message'] ?? 'Unknown') );
        $json_text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ( !$json_text ) throw new Exception( 'Gemini Empty Response' );
        
        $decoded = json_decode( $json_text, true );
        if ( !is_array($decoded) ) throw new Exception( 'Invalid JSON from Gemini' );

        // Strict Defaults
        $defaults = [
            'ah' => 0, 'cca' => 0, 'technology' => 'Standard', 
            'length' => 0, 'width' => 0, 'height' => 0, 'polarity' => 0, 
            'ambiguous' => false
        ];
        
        return array_merge( $defaults, $decoded );
    }

    // --- SEARCH HELPERS ---
    private function normalize_query_for_cache( $query ) {
        $clean = strtolower( $query );
        $clean = str_replace( 'benz', '', $clean );
        $clean = preg_replace( '/[^a-z0-9]/', '', $clean );
        return md5( $clean );
    }

    private function get_cached_specs($query) {
        global $wpdb;
        $query_hash = $this->normalize_query_for_cache( $query );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT specs_json FROM $this->table_cache WHERE query_hash = %s LIMIT 1", $query_hash ) );
        return $row ? json_decode($row->specs_json, true) : false;
    }

    private function cache_specs($query, $specs) {
        global $wpdb;
        if ( stripos($query, 'error') !== false || stripos($query, 'gemini') !== false || !isset($specs['ah']) ) return;
        $query_hash = $this->normalize_query_for_cache( $query );
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $this->table_cache WHERE query_hash = %s", $query_hash ) );
        if (!$existing) $wpdb->insert( $this->table_cache, [ 'query_hash' => $query_hash, 'query_raw' => $query, 'specs_json' => json_encode($specs) ], [ '%s', '%s', '%s' ] );
    }

    private function find_products_by_jis_code($jis_code) {
        $target_field = get_option('cbc_acf_field_jis_code');
        if (!$target_field) return [];
        $args = [ 'post_type' => 'product', 'posts_per_page' => 5, 'meta_query' => [[ 'key' => $target_field, 'value' => $jis_code, 'compare' => '=' ]] ];
        $query = new WP_Query($args);
        $ids = []; foreach($query->posts as $p) $ids[] = $p->ID;
        return $this->get_products_by_ids(implode(',', $ids));
    }

    private function get_products_by_ids($ids_string) {
        $ids = array_map('intval', explode(',', $ids_string));
        $batteries = [];
        $keys = [ 'ah' => get_option('cbc_acf_field_ah'), 'cca' => get_option('cbc_acf_field_cca'), 'tech' => get_option('cbc_acf_field_technology'), 'len' => get_option('cbc_acf_field_length'), 'wid' => get_option('cbc_acf_field_width'), 'hgt' => get_option('cbc_acf_field_height'), 'pol' => get_option('cbc_acf_field_polarity') ];

        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if (!$product) continue;
            $batteries[] = [
                'id' => $product->get_id(), 'name' => $product->get_name(),
                'ah' => (int)$product->get_meta($keys['ah']), 'cca' => (int)$product->get_meta($keys['cca']), 'technology' => $product->get_meta($keys['tech']) ?: 'Standard',
                'length' => (int)$product->get_meta($keys['len']), 'width' => (int)$product->get_meta($keys['wid']), 'height' => (int)$product->get_meta($keys['hgt']), 'polarity' => $product->get_meta($keys['pol']),
                'price' => (float)wc_get_price_including_tax($product), 'link' => $product->get_permalink(), 'imageUrl' => wp_get_attachment_url($product->get_image_id()) ?: wc_placeholder_img_src()
            ];
        }
        return $batteries;
    }

    private function find_woo_compatible_batteries( $specs, $search_technology ) {
        if ( ! class_exists( 'WooCommerce' ) ) return [];
        $keys = [ 'ah' => get_option('cbc_acf_field_ah'), 'cca' => get_option('cbc_acf_field_cca'), 'tech' => get_option('cbc_acf_field_technology'), 'len' => get_option('cbc_acf_field_length'), 'wid' => get_option('cbc_acf_field_width'), 'hgt' => get_option('cbc_acf_field_height'), 'pol' => get_option('cbc_acf_field_polarity') ];
        
        $ah = intval( $specs['ah'] ); $cca = intval( $specs['cca'] ); $dim_tol = 10;
        $compatible_techs = ($search_technology === 'AGM') ? ['AGM'] : ($search_technology === 'EFB' ? ['EFB','AGM'] : ($search_technology === 'Sodium-Ion' ? ['Sodium-Ion'] : ['AGM','EFB','Wet','GEL','Sodium-Ion']));

        $meta_query = [ 'relation' => 'AND',
            [ 'key' => $keys['ah'], 'value' => [$ah, $ah+5], 'type' => 'NUMERIC', 'compare' => 'BETWEEN' ],
            [ 'key' => $keys['cca'], 'value' => $cca, 'type' => 'NUMERIC', 'compare' => '>=' ],
            [ 'key' => $keys['len'], 'value' => [$specs['length']-$dim_tol, $specs['length']+$dim_tol], 'type' => 'NUMERIC', 'compare' => 'BETWEEN' ],
            [ 'key' => $keys['pol'], 'value' => $specs['polarity'], 'compare' => '=' ],
            [ 'key' => $keys['tech'], 'value' => $compatible_techs, 'compare' => 'IN' ]
        ];

        $query = new WP_Query( [ 'post_type' => 'product', 'posts_per_page' => 5, 'meta_query' => $meta_query ] );
        $ids = []; foreach($query->posts as $p) $ids[] = $p->ID;
        return $this->get_products_by_ids(implode(',', $ids));
    }

    private function build_view_more_url($specs, $tech) {
        $url = get_option( 'cbc_shop_page_url' );
        if (!$url) return null;
        return add_query_arg( [ '_ah' => $specs['ah'].','.($specs['ah']+5), '_technology' => $tech, '_polarity' => $specs['polarity'] ], $url );
    }

    // --- OTHER HELPERS ---
    private function get_all_acf_fields() { if ( $this->all_acf_fields !== null ) return $this->all_acf_fields; $this->all_acf_fields = []; if ( ! function_exists( 'acf_get_field_groups' ) ) return $this->all_acf_fields; $field_groups = acf_get_field_groups(); if ( empty( $field_groups ) ) return $this->all_acf_fields; foreach ( $field_groups as $group ) { $fields = acf_get_fields( $group['ID'] ); if ( ! empty( $fields ) ) { foreach ( $fields as $field ) { $this->all_acf_fields[ $field['name'] ] = $field['label'] . " (" . $field['name'] . ")"; } } } ksort($this->all_acf_fields); return $this->all_acf_fields; }
    
    public function render_settings_page() {
        ?>
        <div class="wrap"><h1>Car Battery AI Settings</h1><form method="post" action="options.php"><?php settings_fields( 'cbc_options' ); do_settings_sections( 'cbc_options' ); ?>
        <table class="form-table">
            <tr><th>Gemini API Key</th><td><input type="password" name="cbc_gemini_api_key" value="<?php echo esc_attr(get_option('cbc_gemini_api_key')); ?>" class="regular-text"></td></tr>
            <tr><th>Shop URL</th><td><input type="url" name="cbc_shop_page_url" value="<?php echo esc_attr(get_option('cbc_shop_page_url')); ?>" class="regular-text"></td></tr>
        </table>
        <hr><h3>ACF Mapping</h3>
        <table class="form-table">
            <?php 
            foreach(['cbc_acf_field_ah'=>'Ah','cbc_acf_field_cca'=>'CCA','cbc_acf_field_technology'=>'Technology','cbc_acf_field_length'=>'Length','cbc_acf_field_width'=>'Width','cbc_acf_field_height'=>'Height','cbc_acf_field_polarity'=>'Polarity','cbc_acf_field_jis_code'=>'JIS Code'] as $k=>$l) {
                $v = get_option($k); $opts = $this->get_all_acf_fields(); 
                echo "<tr><th>$l</th><td><select name='$k'><option value=''>--Select--</option>";
                foreach($opts as $fk=>$fl) echo "<option value='$fk' ".selected($v,$fk,false).">$fl</option>";
                echo "</select></td></tr>";
            }
            ?>
        </table>
        <hr><h3>Contact</h3>
        <table class="form-table">
            <tr><th>Phone</th><td><input type="text" name="cbc_contact_phone" value="<?php echo esc_attr(get_option('cbc_contact_phone')); ?>"></td></tr>
            <tr><th>WhatsApp</th><td><input type="text" name="cbc_contact_whatsapp" value="<?php echo esc_attr(get_option('cbc_contact_whatsapp')); ?>"></td></tr>
        </table>
        <?php submit_button(); ?></form></div>
        <?php
    }
    private function get_user_ip_address() { $ip=''; if(!empty($_SERVER['HTTP_CLIENT_IP']))$ip=$_SERVER['HTTP_CLIENT_IP'];elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];else $ip=$_SERVER['REMOTE_ADDR']; if(strpos($ip,',')!==false)$ip=trim(explode(',',$ip)[0]); return sanitize_text_field($ip); }
    public function enqueue_scripts() { wp_enqueue_style('cbc-css',CBC_PLUGIN_URL.'assets/css/chatbot.css',[],'2.0'); wp_enqueue_script('cbc-js',CBC_PLUGIN_URL.'assets/js/chatbot.js',[],'2.0',true); wp_localize_script('cbc-js','cbc_ajax',['ajax_url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('cbc_ajax_nonce'),'contact_phone'=>get_option('cbc_contact_phone'),'contact_whatsapp'=>get_option('cbc_contact_whatsapp'),'contact_email'=>get_option('cbc_contact_email'),'contact_page_url'=>get_option('cbc_contact_page_url')]); }
}
new CarBatteryChatbot();