<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CBC_JIS_Calculator {

    /**
     * JIS width codes and their corresponding measurements in cm
     */
    private const WIDTH_CODES = [
        'A' => 12.7,
        'B' => 12.9,
        'C' => 13.5,
        'D' => 17.3,
        'E' => 17.5,
        'F' => 18.2,
        'G' => 22.2,
        'H' => 27.8
    ];

    /**
     * Automatically calculate and save JIS code when product is saved
     *
     * @param int $post_id The product post ID
     * @param WP_Post $post The post object
     * @param bool $update Whether this is an update or new post
     * @return void
     */
    public function auto_calculate_jis_code( int $post_id, $post, bool $update ): void {
        // Skip during autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Only process products
        if ( $post->post_type !== 'product' ) {
            return;
        }

        // Get target field from settings
        $target_field = get_option( 'cbc_acf_field_jis_code' );
        if ( empty( $target_field ) ) {
            return;
        }

        // Get product
        $product = wc_get_product( $post_id );
        if ( ! $product ) {
            return;
        }

        // Get dimensions
        $width = floatval( $product->get_width() );
        $length = floatval( $product->get_length() );

        if ( empty( $length ) || empty( $width ) ) {
            return;
        }

        // Get battery specifications
        $ah_field = get_option( 'cbc_acf_field_ah' );
        $polarity_field = get_option( 'cbc_acf_field_polarity' );

        $ah_value = get_post_meta( $post_id, $ah_field, true );
        $polarity_value = get_post_meta( $post_id, $polarity_field, true );

        $rank = intval( $ah_value );
        if ( $rank === 0 ) {
            return;
        }

        // Find closest width letter
        $closest_letter = $this->get_closest_width_letter( $width );
        if ( empty( $closest_letter ) ) {
            return;
        }

        // Calculate length code
        $length_code = round( $length );

        // Determine terminal code
        $terminal_code = $this->get_terminal_code( $polarity_value );
        if ( empty( $terminal_code ) ) {
            return;
        }

        // Construct JIS code: Rank + Width Letter + Length + Terminal
        $jis_code = $rank . $closest_letter . $length_code . $terminal_code;

        // Save JIS code
        update_post_meta( $post_id, $target_field, $jis_code );
    }

    /**
     * Get the closest JIS width letter for a given width measurement
     *
     * @param float $width Width in cm
     * @return string Width letter code or empty string
     */
    private function get_closest_width_letter( float $width ): string {
        $closest_letter = '';
        $min_difference = PHP_FLOAT_MAX;

        foreach ( self::WIDTH_CODES as $letter => $value ) {
            $difference = abs( $width - $value );
            if ( $difference < $min_difference ) {
                $min_difference = $difference;
                $closest_letter = $letter;
            }
        }

        return $closest_letter;
    }

    /**
     * Get terminal code based on polarity value
     *
     * @param mixed $polarity_value The polarity value (0 or 1)
     * @return string Terminal code ('R' or 'L') or empty string
     */
    private function get_terminal_code( $polarity_value ): string {
        // Right positive (0)
        if ( $polarity_value === '0' || $polarity_value === 0 ) {
            return 'R';
        }

        // Left positive (1)
        if ( $polarity_value === '1' || $polarity_value === 1 ) {
            return 'L';
        }

        return '';
    }
}