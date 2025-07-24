<?php

defined( 'ABSPATH' ) || exit;

class VARGAL_Admin_Recommend {
	protected $dismiss;
	protected static $settings;
    public static $plugins=[];

	public function __construct() {
		$this->dismiss  = 'villatheme_vargal_install_recommended_plugins_dismiss';
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}


	public function admin_enqueue_scripts() {
		$prefix = 'vargal';
		$dismiss_nonce = isset( $_REQUEST[$prefix.'_dismiss_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST[$prefix.'_dismiss_nonce'] ) ) : '';
		if ( wp_verify_nonce( $dismiss_nonce,  $prefix.'_dismiss_nonce' ) && ! get_option( $this->dismiss ) ) {
			update_option( $this->dismiss , time() , 'no');
		}
		if (! get_option( $this->dismiss ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	public function admin_notices() {
		global $pagenow;
        if ( $pagenow === 'update.php'){
            return;
        }
		$installed_plugins = get_plugins();
        $active_plugins = self::get_active_plugins();
		$recommended_plugins = self::recommended_plugins();
        $notices =[];
        $prefix = 'vargal';
		foreach ( $recommended_plugins as $recommended_plugin ) {
			$plugin_slug = $recommended_plugin['slug'];
            if (empty( $recommended_plugin['message_not_install'] ) && empty( $recommended_plugin['message_not_active'] )){
                continue;
            }
			if ( ! get_option( "{$this->dismiss}__{$plugin_slug}" ) ) {
                $pro_install = false;
				$button = '';
				if ( ! empty( $recommended_plugin['pro'] )  ) {
					$pro_file = "{$recommended_plugin['pro']}/{$recommended_plugin['pro']}.php";
					if (isset($installed_plugins[$pro_file])) {
                        $pro_install = true;
	                    if ( ! empty( $recommended_plugin['message_not_active'] ) && ! isset($active_plugins[$recommended_plugin['pro']]) && ! isset($active_plugins[$plugin_slug] ) ){
                            if (current_user_can( 'activate_plugin',$pro_file)) {
	                            $button = sprintf( '<br><br> <a href="%s" target="_blank" class="button button-primary">%s %s</a>',
		                            esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'plugin' => $pro_file ), self_admin_url( 'plugins.php' ) ), "activate-plugin_{$pro_file}" ) ),
		                            esc_html__( 'Activate', 'vargal-additional-variation-gallery-for-woo' ),
		                            $recommended_plugin['name']);
                            }
                            $notices[]= $recommended_plugin['message_not_active'] . $button;
	                    }
                    }
				}
                if ($pro_install ){
                    continue;
                }
				$plugin_file = "{$plugin_slug}/{$plugin_slug}.php";
				if ( !isset($installed_plugins[$plugin_file]) && ! empty( $recommended_plugin['message_not_install'] ) ){
					if ( current_user_can( 'install_plugins' )  ) {
						$button = sprintf( '<br><br> <a href="%s" target="_blank" class="button button-primary">%s %s</a>',
							esc_url( wp_nonce_url( network_admin_url( "update.php?action=install-plugin&plugin={$plugin_slug}" ), "install-plugin_{$plugin_slug}" ) ),
							esc_html__( 'Install', 'vargal-additional-variation-gallery-for-woo' ),
							$recommended_plugin['name']);
					}
					$notices[] = $recommended_plugin['message_not_install']. $button;
				}elseif ( ! empty( $recommended_plugin['message_not_active'] ) && ! isset($active_plugins[$plugin_slug] ) ){
                    if ( current_user_can( 'activate_plugin', $plugin_file )) {
	                    $button = sprintf( '<br><br> <a href="%s" target="_blank" class="button button-primary">%s %s</a>',
		                    esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'plugin' => $plugin_file ), self_admin_url( 'plugins.php' ) ), "activate-plugin_{$plugin_file}" ) ),
		                    esc_html__( 'Activate', 'vargal-additional-variation-gallery-for-woo' ),
		                    $recommended_plugin['name']);
                    }
					$notices[]= $recommended_plugin['message_not_active'] . $button;
				}
			}
		}
        if (!empty($notices)){
            ?>
            <div class="notice notice-info is-dismissible">
                <?php
                if (count($notices) > 1){
                    ?>
                    <p>
		                <?php esc_html_e('VARGAL will work better with:','vargal-additional-variation-gallery-for-woo'); ?>
                    </p>
                    <ol>
                        <?php
                        foreach ( $notices as $notice ) {
	                        printf( "<li>%s</li>", wp_kses_post( $notice ) );
                        }
                        ?>
                    </ol>
                    <?php
                }else{
                    ?><p><?php
                    esc_html_e('VARGAL will work better with:','vargal-additional-variation-gallery-for-woo');
                    echo wp_kses_post( current( $notices ) );
                    ?></p><?php
                }
                ?>
                <a href="<?php echo esc_url( add_query_arg( array( $prefix.'_dismiss_nonce' => wp_create_nonce( $prefix.'_dismiss_nonce' ) ) ) ) ?>"
                   target="_self">
                    <button type="button" class="notice-dismiss"></button>
                </a>
            </div>
            <?php
        }
	}
	public static function get_active_plugins(){
		if (empty(self::$plugins['active'])){
			$active_plugins = [];
			$tmp = get_option( 'active_plugins' ,[]);
			if (is_multisite()){
				$tmp += array_keys(get_site_option( 'active_sitewide_plugins', [] ));
			}
			if (!empty($tmp)){
				foreach ($tmp as $v){
					$info = explode('/',$v);
					if (empty($info[1])){
						$info= explode(DIRECTORY_SEPARATOR, $v);
					}
					if (empty($info[1])){
						continue;
					}
					$active_plugins[$info[0]] = $v;
				}
			}
			self::$plugins['active'] = $active_plugins;
		}
		return self::$plugins['active'];
	}

	public static function recommended_plugins() {
		if (empty(self::$plugins['recommend'])){
			self::$plugins['recommend'] = [
				'exmage-wp-image-links' => [
					'slug' => 'exmage-wp-image-links',
					'pro' => 'exmage-wordpress-image-links',
					'name' => 'EXMAGE – WordPress Image Links',
					'desc' => esc_html__( 'Save storage by using external image URLs. This plugin is required if you want to use external URLs(Temu cdn image URLs) for product featured image, gallery images and variation image.',
						'vargal-additional-variation-gallery-for-woo' ),
					'message_not_install' => sprintf( "%s <strong>EXMAGE – WordPress Image Links</strong> %s </br> %s",
						esc_html__( 'Need to save your server storage?', 'vargal-additional-variation-gallery-for-woo' ),
						esc_html__( 'will help you solve the problem by using external image URLs.', 'vargal-additional-variation-gallery-for-woo' ),
						esc_html__( 'When this plugin is active, "Use external links for images" option will be available in the TMDS plugin settings/Product which allows to use original Temu product image URLs for featured image, gallery images and variation image of imported Temu products.', 'vargal-additional-variation-gallery-for-woo' )
					),
					'message_not_active'  => sprintf( "<strong>EXMAGE – WordPress Image Links</strong> %s",
						esc_html__( 'is currently inactive, external images added by this plugin(Post/product featured image, product gallery images...) will no longer work properly.', 'vargal-additional-variation-gallery-for-woo' ) ),
				],
				'product-variations-swatches-for-woocommerce'=> [
					'slug' => 'product-variations-swatches-for-woocommerce',
					'pro' => 'woocommerce-product-variations-swatches',
					'name' => 'Product Variations Swatches for WooCommerce',
					'desc' => esc_html__( 'Product Variations Swatches for WooCommerce is a professional plugin that allows you to show and select attributes for variation products. The plugin displays variation select options of the products under colors, buttons, images, variation images, radio so it helps the customers observe the products they need more visually, save time to find the wanted products than dropdown type for variations of a variable product.',
						'vargal-additional-variation-gallery-for-woo' ),
					'message_not_install' => sprintf( "%s <strong>Product Variations Swatches for WooCommerce</strong> %s",
						esc_html__( 'Need a variations swatches plugin that works perfectly with TMDS?', 'vargal-additional-variation-gallery-for-woo' ),
						esc_html__( 'is what you need.', 'vargal-additional-variation-gallery-for-woo' ) ),
					'message_not_active'  => sprintf( "<strong>Product Variations Swatches for WooCommerce</strong> %s",
						esc_html__( 'is currently inactive, this prevents variable products from displaying beautifully.', 'vargal-additional-variation-gallery-for-woo' ) ),
				],
			];
		}
		return self::$plugins['recommend'];
	}
}
