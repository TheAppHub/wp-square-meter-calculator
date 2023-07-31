<?php
/**
 * Plugin Name:     Square Meter Calculator
 * Plugin URI:      https://github.com/TheAppHub/wp-square-meter-calculator
 * Description:     Calculator to convert square meters to product package and vice versa.
 * Author:          Christoph - The App Hub
 * Author URI:      https://theapphub.com.au
 * Text Domain:     square-meter-calculator
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Square_Meter_Calculator
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add css and js
 */
function smc_assets() {
    wp_register_style( 'smc', plugins_url( '/assets/smc-styles.css' , __FILE__ ) );
    wp_register_script( 'smc', plugins_url( '/assets/smc-script.js' , __FILE__ ) );

    wp_enqueue_style( 'smc' );
    wp_enqueue_script( 'smc' );
}
add_action( 'wp_enqueue_scripts', 'smc_assets' );



/**
 * Add custom field to product variations.
 *
 * @version	0.1.0
 */

add_action( 'woocommerce_variation_options_pricing', 'smc_add_variation_field', 10, 3 );

function smc_add_variation_field( $loop, $variation_data, $variation ) {

	woocommerce_wp_text_input(
		array(
			'id'            => 'smc_field[' . $loop . ']',
			'label'         => 'm2 per package (if applicable)',
			'wrapper_class' => 'form-row',
			'placeholder'   => 'Amount of square meters per package',
			'desc_tip'      => true,
			'description'   => 'If not empty, this will be used to calculate the number of packages needed to cover the area in square meters.',
			'value'         => get_post_meta( $variation->ID, 'smc_field', true ),
			'type'			=> 'number',
		)
	);
}

/**
 * Save custom field to product variations.
 *
 * @version	0.1.0
 */

 add_action( 'woocommerce_save_product_variation', 'smc_save_fields', 10, 2 );

 function smc_save_fields( $variation_id, $loop ) {
	 $smc_field = $_POST['smc_field'][$loop];
	 if ( ! empty( $smc_field ) ) {
		 update_post_meta( $variation_id, 'smc_field', sanitize_text_field( $smc_field ) );
	 }
 }

/**
 * Show custom field on product page.
 *
 *
 * @version	0.1.0
 */
add_filter( 'woocommerce_available_variation', function( $variation ) {

	$variation[ 'square_meters_per_pack' ] = get_post_meta( $variation[ 'variation_id' ], 'smc_field', true );
	return $variation;

} );

add_filter( 'woocommerce_get_price_html', 'smc_text_after_price', 100, 2 );

function smc_text_after_price($price, $product){
    $text_to_add_after_price  = ' <span class="sqm-price"></span>';
	return $price .   $text_to_add_after_price;
}


add_action( 'woocommerce_before_add_to_cart_button', 'smc_add_info' );

function smc_add_info(){

	echo '<div class="sqm-per-pack"></div>';
	echo '<div id="sqm-calc"></div>';

}

add_action( 'woocommerce_after_add_to_cart_button', 'smc_after_add_to_cart_btn' );

function smc_after_add_to_cart_btn(){

	global $product;
	$available_variations = $product->get_available_variations();
	?>
	<script>
		jQuery(document).ready(function() {


			let sqm = '';


			jQuery( 'input.variation_id' ).change( function(){
				if( jQuery.trim( jQuery( 'input.variation_id' ).val() )!='' ) {
					var variation = jQuery( 'input.variation_id' ).val();

					jQuery( '.sqm-per-pack' ).html('');
					jQuery( '#sqm-calc' ).html('');

					jQuery.each(<?php echo json_encode($available_variations); ?>, function(index, value) {
						if (value.variation_id == variation) {
							sqm = value.square_meters_per_pack;

							// Add price per sqm to product page
							let price = value.display_price;
							let pricePerSqm = price / sqm;
							let priceText = sqm == '' ? '' : ' | <span style="font-size: small">$' + pricePerSqm.toFixed(2) + ' per sqm</span>';
							jQuery( '.sqm-price' ).html(priceText);

							// Add sqm per pack to product page
							let text = sqm == '' ? '' : '<p>Each pack contains ' + sqm + ' sqm.</p>';
							jQuery( '.sqm-per-pack' ).html(text);

							// Add sqm calculator to product page
							let sqmCalc = sqm == '' ? '' : '<input type="number" id="sqm-input" style="width: 100px; display: inline-block; margin: 0 10px 10px 0;" class="input-text text" step="1" min="1" max="" name="sqm" value="1" title="Sqm" size="4"><div style="display: inline-block;"> m2</div>';
							jQuery( '#sqm-calc' ).html(sqmCalc);

							let sqmField = jQuery( 'input#sqm-input' );
							let packField = jQuery( 'input.qty' );

							// Update sqm input
							sqmField.change( function(sqmAmount){
								let sqms = sqmAmount.target.valueAsNumber;
								packField.val( Math.ceil( sqms / sqm ));
							});

							// Update sqm packages
							packField.change( function(packsAmount){
								let packs = packsAmount.target.valueAsNumber;
								sqmField.val(Math.floor( packs * sqm ));
							});
						}
					});
				}
			});
		});
	</script>
	<?php
}


