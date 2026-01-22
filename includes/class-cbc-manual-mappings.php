<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CBC_Manual_Mappings {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cbc_manual_mappings';
    }

    public function install_table() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name ( id mediumint(9) NOT NULL AUTO_INCREMENT, query_hash varchar(32) NOT NULL, query_raw text NOT NULL, product_ids text NOT NULL, created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id), KEY query_hash (query_hash) ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private function normalize( $query ) {
        return md5( preg_replace( '/[^a-z0-9]/', '', str_replace( 'benz', '', strtolower( $query ) ) ) );
    }

    public function get_mapping( $query ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT product_ids FROM $this->table_name WHERE query_hash = %s LIMIT 1", $this->normalize($query) ) );
        return $row ? $row->product_ids : false;
    }

    public function render_page() {
        global $wpdb;
        if ( isset( $_POST['add_map'] ) && check_admin_referer( 'cbc_map' ) ) {
            $h = $this->normalize( $_POST['q'] );
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $this->table_name WHERE query_hash = %s", $h ) );
            if($exists) $wpdb->update($this->table_name, ['query_raw'=>sanitize_text_field($_POST['q']),'product_ids'=>sanitize_text_field($_POST['ids'])], ['id'=>$exists]);
            else $wpdb->insert($this->table_name, ['query_hash'=>$h,'query_raw'=>sanitize_text_field($_POST['q']),'product_ids'=>sanitize_text_field($_POST['ids'])]);
            echo '<div class="notice notice-success"><p>Saved.</p></div>';
        }
        if ( isset( $_GET['del'] ) && check_admin_referer( 'del_'.$_GET['del'] ) ) {
            $wpdb->delete( $this->table_name, [ 'id' => intval( $_GET['del'] ) ] );
        }
        ?>
        <div class="wrap"><h1>Manual Mappings</h1>
        <form method="post"><?php wp_nonce_field( 'cbc_map' ); ?>
        <input type="text" name="q" placeholder="Car (e.g. BMW X5)" required class="regular-text">
        <input type="text" name="ids" id="pids" placeholder="IDs (12, 15)" required class="regular-text">
        <button type="submit" name="add_map" class="button button-primary">Save</button></form>
        <hr>
        <input type="text" id="psearch" placeholder="Search Product ID..." class="regular-text"><div id="pres"></div>
        <table class="wp-list-table widefat fixed striped"><thead><tr><th>Query</th><th>IDs</th><th>Action</th></tr></thead><tbody>
        <?php foreach($wpdb->get_results("SELECT * FROM $this->table_name ORDER BY id DESC") as $m) {
            $del = wp_nonce_url(admin_url('admin.php?page=cbc-manual-mappings&del='.$m->id), 'del_'.$m->id);
            echo "<tr><td>".esc_html($m->query_raw)."</td><td>".esc_html($m->product_ids)."</td><td><a href='$del' class='button'>Delete</a></td></tr>";
        } ?>
        </tbody></table>
        <script>jQuery('#psearch').on('input',function(){ var t=this.value; if(t.length<3)return; setTimeout(()=>{jQuery.post(ajaxurl,{action:'cbc_search_products_admin',term:t},function(r){if(r.success){var h='';r.data.forEach(p=>{h+='<div onclick="jQuery(\'#pids\').val(jQuery(\'#pids\').val()?jQuery(\'#pids\').val()+\', \'+'+p.id+':'+p.id+')" style="cursor:pointer">#'+p.id+' '+p.name+'</div>'});jQuery('#pres').html(h)}})},500)});</script>
        </div>
        <?php
    }
}