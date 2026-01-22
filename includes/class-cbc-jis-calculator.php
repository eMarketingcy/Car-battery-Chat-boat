<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CBC_JIS_Calculator {
    public function auto_calculate_jis_code( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( $post->post_type !== 'product' ) return;

        $target_field = get_option( 'cbc_acf_field_jis_code' );
        if ( empty( $target_field ) ) return; 

        $product = wc_get_product( $post_id );
        if ( ! $product ) return;
        
        $width  = floatval( $product->get_width() );
        $length = floatval( $product->get_length() );
        
        if ( empty( $length ) || empty( $width ) ) return;

        $ah_val = get_post_meta( $post_id, get_option( 'cbc_acf_field_ah' ), true ); 
        $pol_val = get_post_meta( $post_id, get_option( 'cbc_acf_field_polarity' ), true );

        $rank = intval( $ah_val ); 
        if ( $rank == 0 ) return; 

        $widths = [ 'A' => 12.7, 'B' => 12.9, 'C' => 13.5, 'D' => 17.3, 'E' => 17.5, 'F' => 18.2, 'G' => 22.2, 'H' => 27.8 ];
        
        $closest_letter = '';
        $min_diff = 999;

        foreach ( $widths as $letter => $val ) {
            $diff = abs( $width - $val );
            if ( $diff < $min_diff ) {
                $min_diff = $diff;
                $closest_letter = $letter;
            }
        }

        $len_code = round( $length );
        $term_code = ($pol_val === '0' || $pol_val === 0) ? 'R' : (($pol_val === '1' || $pol_val === 1) ? 'L' : '');
        
        if ( empty( $closest_letter ) || empty( $term_code ) ) return;

        update_post_meta( $post_id, $target_field, $rank . $closest_letter . $len_code . $term_code );
    }
}