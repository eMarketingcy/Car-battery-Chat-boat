<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CBC_Search_Stats {
    public function log_query( $term, $response, $ip, $source ) {
        if ( empty( $term ) ) return;
        $log = get_option( 'cbc_search_log', [] );
        array_unshift( $log, [ 'term' => $term, 'date' => current_time( 'mysql' ), 'response' => $response, 'ip' => $ip, 'source' => $source ] );
        update_option( 'cbc_search_log', array_slice( $log, 0, 200 ), false );
    }

    public function render_page() {
        ?>
        <div class="wrap"><h1>Chatbot Stats</h1>
        <p><span style="background:#d1fae5;padding:2px 5px">⚡ Cached</span> <span style="background:#dbeafe;padding:2px 5px">🧠 Expert</span> <span style="background:#fce7f3;padding:2px 5px">🎯 JIS</span></p>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Query</th><th>Result</th><th>IP</th><th>Date</th></tr></thead>
            <tbody>
            <?php
            $log = get_option( 'cbc_search_log', [] );
            foreach ( $log as $entry ) {
                $badge = ($entry['source']=='cache')?'⚡':($entry['source']=='manual'?'🧠':($entry['source']=='jis_direct'?'🎯':''));
                $res = isset($entry['response']['success']) ? 'OK' : 'Error';
                if(isset($entry['response']['data']['type']) && $entry['response']['data']['type'] === 'clarification') $res = 'Clarify';
                echo "<tr><td>{$badge} ".esc_html($entry['term'])."</td><td>{$res}</td><td>".esc_html($entry['ip'])."</td><td>{$entry['date']}</td></tr>";
            }
            ?>
            </tbody></table></div>
        <?php
    }
}