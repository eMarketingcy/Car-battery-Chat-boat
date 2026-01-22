<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CBC_Search_Stats {

    private const MAX_LOG_ENTRIES = 200;

    /**
     * Log a search query and its response
     *
     * @param string $term The search term
     * @param array $response The response data
     * @param string $ip The user's IP address
     * @param string $source The source type (cache, manual, jis_direct, api, error)
     * @return void
     */
    public function log_query( string $term, array $response, string $ip, string $source ): void {
        if ( empty( $term ) ) {
            return;
        }

        $log = get_option( 'cbc_search_log', [] );

        array_unshift( $log, [
            'term' => $term,
            'date' => current_time( 'mysql' ),
            'response' => $response,
            'ip' => $ip,
            'source' => $source
        ] );

        update_option( 'cbc_search_log', array_slice( $log, 0, self::MAX_LOG_ENTRIES ), false );
    }

    /**
     * Render the statistics page
     *
     * @return void
     */
    public function render_page(): void {
        $log = get_option( 'cbc_search_log', [] );
        ?>
        <div class="wrap">
            <h1>Car Battery Chatbot Statistics</h1>

            <div style="margin: 20px 0; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
                <h2 style="margin-top: 0;">Legend</h2>
                <p>
                    <span style="background:#d1fae5; padding:4px 8px; margin-right:10px; border-radius:3px;">⚡ Cached</span>
                    <span style="background:#dbeafe; padding:4px 8px; margin-right:10px; border-radius:3px;">🧠 Manual Mapping</span>
                    <span style="background:#fce7f3; padding:4px 8px; margin-right:10px; border-radius:3px;">🎯 JIS Code</span>
                    <span style="background:#fef3c7; padding:4px 8px; margin-right:10px; border-radius:3px;">🤖 AI</span>
                </p>
            </div>

            <?php if ( empty( $log ) ) : ?>
                <div class="notice notice-info">
                    <p>No search queries recorded yet. The chatbot will start logging queries when users interact with it.</p>
                </div>
            <?php else : ?>
                <p>Showing the last <?php echo count( $log ); ?> search queries.</p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Search Query</th>
                            <th style="width: 15%;">Result</th>
                            <th style="width: 15%;">IP Address</th>
                            <th style="width: 25%;">Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ( $log as $entry ) {
                        $badge = match( $entry['source'] ?? '' ) {
                            'cache' => '⚡',
                            'manual' => '🧠',
                            'jis_direct' => '🎯',
                            'api' => '🤖',
                            default => ''
                        };

                        $result = 'Error';
                        $result_class = 'error';

                        if ( isset( $entry['response']['success'] ) && $entry['response']['success'] ) {
                            if ( isset( $entry['response']['data']['type'] ) && $entry['response']['data']['type'] === 'clarification' ) {
                                $result = 'Clarification';
                                $result_class = 'warning';
                            } else {
                                $result = 'Success';
                                $result_class = 'success';
                            }
                        }

                        printf(
                            '<tr>
                                <td>%s %s</td>
                                <td><span class="dashicons dashicons-%s"></span> %s</td>
                                <td>%s</td>
                                <td>%s</td>
                            </tr>',
                            esc_html( $badge ),
                            esc_html( $entry['term'] ),
                            $result_class === 'success' ? 'yes-alt' : ( $result_class === 'warning' ? 'warning' : 'dismiss' ),
                            esc_html( $result ),
                            esc_html( $entry['ip'] ),
                            esc_html( $entry['date'] )
                        );
                    }
                    ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}