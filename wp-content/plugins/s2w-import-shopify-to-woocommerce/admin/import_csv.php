<?php

/**
 * Class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Import_Csv
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*Check if php version smaller 8.0.1*/
if ( PHP_VERSION_ID < 80100 ) {
	ini_set( 'auto_detect_line_endings', true );
}

class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Import_Csv {
	protected static $settings;
	protected static $import_inventory_by_csv;
	public static $process;
	protected $step;
	protected $file_url;
	protected $header;
	protected $error;
	protected $index;
	protected $nonce;

	public function __construct() {
		self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ), 19 );
		add_action( 'admin_init', array( $this, 'import_csv' ) );
		add_action( 'wp_ajax_s2w_import_shopify_to_woocommerce_import', array( $this, 'import' ) );
		add_action( 's2w_import_shopify_to_woocommerce_importer_scheduled_cleanup', array(
			$this,
			'scheduled_cleanup'
		) );
	}

	private static function set( $name, $set_name = false ) {
		return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
	}

	/**
	 * Delete uploaded CSV file after 24 hours
	 *
	 * @param $attachment_id
	 */
	public function scheduled_cleanup( $attachment_id ) {
		if ( $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}

	/**
	 * Notice of images importing status or progress
	 */
	public function admin_notices() {
		if ( self::$process->is_downloading() ) {
			?>
            <div class="updated">
                <p>
					<?php printf( esc_html__( 'S2W - Import Shopify to WooCommerce: %s images are being imported in the background.', 's2w-import-shopify-to-woocommerce' ), self::$process->get_items_left() ) ?>
                </p>
                <p>
					<?php printf( __( 'Please goto <a target="_blank" href="%s">Media</a> and view imported product images. If <strong>some images are imported repeatedly and no new images are imported</strong>, please <strong>1. Stop importing product</strong>, <strong>2.  <a class="s2w-cancel-download-images-button" href="%s">Cancel importing</a></strong> immediately and contact <strong>support@villatheme.com</strong> for help.', 's2w-import-shopify-to-woocommerce' ), admin_url( 'upload.php' ), add_query_arg( array( 's2w_cancel_download_image_for_import_csv' => '1', ), $_SERVER['REQUEST_URI'] ) ) ?>
                </p>
            </div>
			<?php
		} elseif ( ! self::$process->is_queue_empty() ) {
			?>
            <div class="updated">
                <p>
					<?php printf( esc_html__( 'S2W - Import Shopify to WooCommerce: %s images are in the queue.', 's2w-import-shopify-to-woocommerce' ), self::$process->get_items_left() ) ?>
                </p>
                <p>
					<?php _e( 'If your importing from CSV is still <strong>in progress</strong>, you can skip this message. It will automatically start importing images after all products are imported.', 's2w-import-shopify-to-woocommerce' ) ?>
                </p>
                <p>
					<?php printf( __( 'If your importing from CSV is <strong>completed or interrupted in the middle</strong>, you can <strong><a class="s2w-start-download-images-button" href="%s">Start importing</a></strong> to download images for imported products Or <strong><a class="s2w-empty-queue-images-button" href="%s">Empty queue</a></strong> if you don\'t need those images to be imported anymore.', 's2w-import-shopify-to-woocommerce' ), add_query_arg( array( 's2w_start_download_image_for_import_csv' => '1', ), $_SERVER['REQUEST_URI'] ), add_query_arg( array( 's2w_cancel_download_image_for_import_csv' => '1', ), $_SERVER['REQUEST_URI'] ) ) ?>
                </p>
            </div>
			<?php
		} else {
			$complete = false;
			if ( get_transient( 's2w_process_for_import_csv_complete' ) ) {
				delete_transient( 's2w_process_for_import_csv_complete' );
				$complete = true;
			}
			if ( get_transient( 's2w_background_processing_complete_for_import_csv' ) ) {
				delete_transient( 's2w_background_processing_complete_for_import_csv' );
				$complete = true;
			}
			if ( $complete ) {
				?>
                <div class="updated">
                    <p>
						<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: Product images are imported successfully.', 's2w-import-shopify-to-woocommerce' ) ?>
                    </p>
                </div>
				<?php
			}
		}
	}

	/**
	 * Background process that handles images of products imported via CSV
	 */
	public function plugins_loaded() {
		self::$process = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_For_Import_Csv();
		if ( isset( $_REQUEST['s2w_cancel_download_image_for_import_csv'] ) && $_REQUEST['s2w_cancel_download_image_for_import_csv'] ) {
			delete_transient( 's2w_background_processing_complete_for_import_csv' );
			self::$process->kill_process();
			wp_safe_redirect( esc_url_raw( remove_query_arg( 's2w_cancel_download_image_for_import_csv' ) ) );
			exit;
		} elseif ( isset( $_REQUEST['s2w_start_download_image_for_import_csv'] ) && $_REQUEST['s2w_start_download_image_for_import_csv'] ) {
			self::$process->dispatch();
			wp_safe_redirect( esc_url_raw( remove_query_arg( 's2w_start_download_image_for_import_csv' ) ) );
			exit;
		}
	}

	/**
	 * Add menu
	 */
	public function add_menu() {
		add_submenu_page(
			's2w-import-shopify-to-woocommerce',
			esc_html__( 'Import CSV', 's2w-import-shopify-to-woocommerce' ),
			esc_html__( 'Import CSV', 's2w-import-shopify-to-woocommerce' ), self::get_required_capability(),
			's2w-import-shopify-to-woocommerce-import-csv', array(
				$this,
				'import_csv_callback'
			)
		);
	}

	/**
	 * Handle uploaded CSV file and mapping fields
	 */
	public function import_csv() {
		global $pagenow;
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		if ( $pagenow === 'admin.php' && $page === 's2w-import-shopify-to-woocommerce-import-csv' ) {
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			$this->step                    = isset( $_REQUEST['step'] ) ? sanitize_text_field( $_REQUEST['step'] ) : '';
			$this->file_url                = isset( $_REQUEST['file_url'] ) ? urldecode_deep( $_REQUEST['file_url'] ) : '';
			self::$import_inventory_by_csv = isset( $_REQUEST['import_inventory_by_csv'] ) ? sanitize_text_field( $_REQUEST['import_inventory_by_csv'] ) : '';
			if ( $this->step === 'mapping' ) {
				if ( is_file( $this->file_url ) ) {
					if ( ( $handle = fopen( $this->file_url, "r" ) ) !== false ) {
						$this->header = fgetcsv( $handle, 0, "," );
						fclose( $handle );
						if ( ! count( $this->header ) ) {
							$this->step  = '';
							$this->error = esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' );
						}
					} else {
						$this->step  = '';
						$this->error = esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' );
					}
				} else {
					$this->step  = '';
					$this->error = esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' );
				}
			}

			if ( ! isset( $_POST['_s2w_import_shopify_to_woocommerce_import_nonce'] ) || ! wp_verify_nonce( $_POST['_s2w_import_shopify_to_woocommerce_import_nonce'], 's2w_import_shopify_to_woocommerce_import_action_nonce' ) ) {
				return;
			}
			if ( isset( $_POST['s2w_import_shopify_to_woocommerce_select_file'] ) ) {
				if ( ! isset( $_FILES['s2w_import_shopify_to_woocommerce_file'] ) ) {
					$error = new WP_Error( 's2w_import_shopify_to_woocommerce_csv_importer_upload_file_empty', __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.', 's2w-import-shopify-to-woocommerce' ) );
					wp_die( $error->get_error_messages() );
				} elseif ( ! empty( $_FILES['s2w_import_shopify_to_woocommerce_file']['error'] ) ) {
					$error = new WP_Error( 's2w_import_shopify_to_woocommerce_csv_importer_upload_file_error', __( 'File is error.', 's2w-import-shopify-to-woocommerce' ) );
					wp_die( $error->get_error_messages() );
				} else {
					$import    = $_FILES['s2w_import_shopify_to_woocommerce_file'];
					$overrides = array(
						'test_form' => false,
						'mimes'     => array(
							'csv' => 'text/csv',
						),
						'test_type' => false,
					);
					$upload    = wp_handle_upload( $import, $overrides );
					if ( isset( $upload['error'] ) ) {
						wp_die( $upload['error'] );
					}
					// Construct the object array.
					$object = array(
						'post_title'     => basename( $upload['file'] ),
						'post_content'   => $upload['url'],
						'post_mime_type' => $upload['type'],
						'guid'           => $upload['url'],
						'context'        => 'import',
						'post_status'    => 'private',
					);

					// Save the data.
					$id = wp_insert_attachment( $object, $upload['file'] );
					if ( is_wp_error( $id ) ) {
						wp_die( $id->get_error_messages() );
					}
					/**
					 * Schedule a cleanup for one day from now in case of failed import or missing wp_import_cleanup() call.
					 */
					wp_schedule_single_event( time() + DAY_IN_SECONDS, 's2w_import_shopify_to_woocommerce_importer_scheduled_cleanup', array( $id ) );
					wp_safe_redirect( add_query_arg( array(
						'step'                    => 'mapping',
						'file_url'                => urlencode( $upload['file'] ),
						'import_inventory_by_csv' => isset( $_POST['s2w_import_inventory_by_csv'] ) ? sanitize_text_field( $_POST['s2w_import_inventory_by_csv'] ) : '',
					) ) );
					exit();
				}
			} elseif ( isset( $_POST['s2w_import_shopify_to_woocommerce_import'] ) ) {
				self::$import_inventory_by_csv = isset( $_POST['s2w_import_inventory_by_csv'] ) ? sanitize_text_field( $_POST['s2w_import_inventory_by_csv'] ) : '';
				$this->step                    = 'import';
				$this->file_url                = isset( $_POST['s2w_import_shopify_to_woocommerce_file_url'] ) ? stripslashes( $_POST['s2w_import_shopify_to_woocommerce_file_url'] ) : '';
				$map_to                        = isset( $_POST['s2w_map_to'] ) ? array_map( 'sanitize_text_field', $_POST['s2w_map_to'] ) : array();
				if ( is_file( $this->file_url ) ) {
					if ( ( $file_handle = fopen( $this->file_url, "r" ) ) !== false ) {
						$header  = fgetcsv( $file_handle, 0, "," );
						$headers = self::get_column_headers( self::$import_inventory_by_csv );
						$index   = array();
						if ( self::$import_inventory_by_csv ) {
							if ( count( $header ) !== count( $map_to ) ) {
								wp_safe_redirect( add_query_arg( array( 's2w_error' => 1 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&step=mapping&file_url=' . urlencode( $this->file_url ) ) ) );
								exit();
							}
							foreach ( $map_to as $source_file_header_k => $source_file_header_v ) {
								if ( $source_file_header_v ) {
									if ( 'inventory_quantity' === $source_file_header_v ) {
										/*Inventory can be imported from multiple locations so it can be mapped from multiple column*/
										if ( ! isset( $index[ $source_file_header_v ] ) ) {
											$index[ $source_file_header_v ] = array();
										}
										$index[ $source_file_header_v ] = array_merge( $index[ $source_file_header_v ], array( $source_file_header_k ) );
									} else {
										$index[ $source_file_header_v ] = $source_file_header_k;
									}
								}
							}
							foreach ( $headers as $header_k => $header_v ) {
								if ( ! isset( $index[ $header_k ] ) ) {
									$index[ $header_k ] = - 1;
								}
							}
						} else {
							foreach ( $headers as $header_k => $header_v ) {
								$field_index = array_search( $map_to[ $header_k ], $header );
								if ( $field_index === false ) {
									$index[ $header_k ] = - 1;
								} else {
									$index[ $header_k ] = $field_index;
								}
							}
						}
						$required_fields = array_keys( self::get_require_fields( self::$import_inventory_by_csv ) );
						foreach ( $required_fields as $required_field ) {
							if ( 0 > $index[ $required_field ] ) {
								wp_safe_redirect( add_query_arg( array( 's2w_error' => 1 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&step=mapping&file_url=' . urlencode( $this->file_url ) ) ) );
								exit();
							}
						}

						if (
							(
								( 0 > $index['option2_name'] && - 1 < $index['option2_value'] ) ||
								( - 1 < $index['option2_name'] && 0 > $index['option2_value'] )
							) ||
							(
								( 0 > $index['option3_name'] && - 1 < $index['option3_value'] ) ||
								( - 1 < $index['option3_name'] && 0 > $index['option3_value'] )
							)
						) {
							wp_safe_redirect( add_query_arg( array( 's2w_error' => 2 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&step=mapping&file_url=' . urlencode( $this->file_url ) ) ) );
							exit();
						}
						$this->index = $index;
					} else {
						wp_safe_redirect( add_query_arg( array( 's2w_error' => 3 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&file_url=' . urlencode( $this->file_url ) ) ) );
						exit();
					}
				} else {
					wp_safe_redirect( add_query_arg( array( 's2w_error' => 4 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&file_url=' . urlencode( $this->file_url ) ) ) );
					exit();
				}
			}
		}
	}

	/**
	 * Find product ID by slug
	 *
	 * @param $slug
	 *
	 * @return int|string|WP_Post
	 */
	private static function get_woo_product_id_by_slug( $slug ) {
		$product_id = '';
		if ( $slug ) {
			$product_args = array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => '1',
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'post_name__in'  => array( $slug ),
			);

			$the_query = new WP_Query( $product_args );
			if ( $the_query->have_posts() ) {
				$product_id = $the_query->posts[0];
			}
		}

		return $product_id;
	}

	/**
	 * Import product after data is built
	 *
	 * @param $product_data
	 * @param $import_options
	 */
	public function import_product( $product_data, $import_options ) {
		if ( ! count( $product_data ) ) {
			return;
		}
		if ( empty( $product_data['handle'] ) ) {
			return;
		}
		$product_title     = isset( $product_data['title'] ) ? $product_data['title'] : '';
		$if_product_exists = $import_options['csv_if_product_exists'];
		$keep_slug         = $import_options['keep_slug'];
		$existing_id       = 0;
		if ( self::$import_inventory_by_csv ) {
			$existing_id = self::get_woo_product_id_by_slug( $product_data['handle'] );
			if ( $existing_id ) {
				if ( get_post_meta( $existing_id, '_shopify_product_id', true ) ) {
					/*Do not update if the product was imported via API*/
					self::log( sprintf( 'Skipped: [%s]%s', $existing_id, $product_title ) );

					return;
				}
			} else {
				self::log( sprintf( 'Product not found: %s', $product_title ? $product_title : $product_data['handle'] ) );

				return;
			}
		} else {
			if ( $if_product_exists !== 'import' ) {
				$existing_id = self::get_woo_product_id_by_slug( $product_data['handle'] );
				if ( $existing_id ) {
					if ( get_post_meta( $existing_id, '_shopify_product_id', true ) ) {
						/*Do not update if the product was imported via API*/
						self::log( sprintf( 'Skipped: [%s]%s', $existing_id, $product_title ) );

						return;
					}
					if ( 'update' !== $if_product_exists || ! $keep_slug ) {
						/*Do not update if the user does not choose*/
						self::log( sprintf( 'Skipped: [%s]%s', $existing_id, $product_title ) );

						return;
					}
				}
			}
		}
		wp_suspend_cache_invalidation( true );
		vi_s2w_set_time_limit();
		$options  = isset( $product_data['options'] ) ? $product_data['options'] : array();
		$variants = isset( $product_data['variants'] ) ? $product_data['variants'] : array();
		if ( self::$import_inventory_by_csv ) {
			/*Import inventories*/
			if ( is_array( $variants ) && count( $variants ) ) {
				$product = wc_get_product( $existing_id );
				if ( $product ) {
					$updated_inventory = false;
					if ( $product->is_type( 'variable' ) ) {
						$variations = $product->get_children();
						foreach ( $variations as $variation_id ) {
							$variation = wc_get_product( $variation_id );
							if ( $variation ) {
								foreach ( $variants as $variant ) {
									if ( $variant['inventory_quantity'] !== false && ! empty( $variant['sku'] ) && $variant['sku'] === $variation->get_sku( 'edit' ) ) {
										$variation->set_stock_quantity( $variant['inventory_quantity'] );
										$variation->save();
										$updated_inventory = true;
									}
								}
							}
						}
					} else {
						if ( count( $variants ) > 1 ) {
							foreach ( $variants as $variant ) {
								if ( $variant['inventory_quantity'] !== false && ! empty( $variant['sku'] ) && $variant['sku'] === $product->get_sku( 'edit' ) ) {
									$product->set_stock_quantity( $variant['inventory_quantity'] );
									$product->save();
									$updated_inventory = true;
									break;
								}
							}
						} else {
							if ( $variants[0]['inventory_quantity'] !== false ) {
								$product->set_stock_quantity( $variants[0]['inventory_quantity'] );
								$product->save();
								$updated_inventory = true;
							}
						}
					}
					if ( $updated_inventory ) {
						self::log( sprintf( 'Updated inventory: [%s]%s', $existing_id, get_the_title( $existing_id ) ) );
					} else {
						self::log( sprintf( 'Failed to update inventory, SKU not matched or stock not available: [%s]%s', $existing_id, get_the_title( $existing_id ) ) );
					}
				}
			}
		} else {
			/*Import new or update an existing product*/
			$download_images             = $import_options['download_images'];
			$disable_background_process  = $import_options['disable_background_process'];
			$download_description_images = $import_options['download_description_images'];
			$download_images_later       = $import_options['download_images_later'];
			$global_attributes           = $import_options['global_attributes'];
			$product_status              = $import_options['product_status'];
			$product_categories          = isset( $import_options['product_categories'] ) ? $import_options['product_categories'] : array();
			if ( ( is_array( $options ) && count( $options ) ) || ( is_array( $variants ) && count( $variants ) ) ) {
				$images_d   = array();
				$product_id = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::import_product( $options, $product_data, '', $global_attributes, $product_status, $keep_slug, $download_images, $product_categories, $images_d, '', $existing_id );
				if ( ! is_wp_error( $product_id ) ) {
					$dispatch    = false;
					$description = isset( $product_data['body_html'] ) ? html_entity_decode( $product_data['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
					self::handle_description_images( $description, $product_id, $download_description_images, $disable_background_process, $dispatch );
					if ( count( $images_d ) ) {
						if ( $disable_background_process ) {
							foreach ( $images_d as $images_d_k => $images_d_v ) {
								S2W_Error_Images_Table::insert( $product_id, implode( ',', $images_d_v['product_ids'] ), $images_d_v['src'], $images_d_v['alt'], intval( $images_d_v['set_gallery'] ) );
							}
						} else {
							$dispatch = true;
							foreach ( $images_d as $images_d_k => $images_d_v ) {
								self::$process->push_to_queue( $images_d_v );
							}
						}
					}
					if ( $dispatch ) {
						if ( $download_images_later ) {
							self::$process->save();
						} else {
							self::$process->save()->dispatch();
						}
					}
					if ( $existing_id ) {
						self::log( sprintf( 'Updated: [%s]%s', $product_id, get_the_title( $product_id ) ) );
					} else {
						self::log( sprintf( 'Imported: [%s]%s', $product_id, get_the_title( $product_id ) ) );
					}
				} else {
					self::log( sprintf( 'Error importing %s: %s', $product_title, $product_id->get_error_message() ) );
				}
			}
		}

		wp_suspend_cache_invalidation( false );
	}

	/**
	 * Ajax handler for CSV import
	 */
	public function import() {
		check_ajax_referer( 's2w_action_nonce', '_s2w_nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die();
		}
		ignore_user_abort( true );
		$file_url                      = isset( $_POST['file_url'] ) ? stripslashes( $_POST['file_url'] ) : '';
		$start                         = isset( $_POST['start'] ) ? absint( sanitize_text_field( $_POST['start'] ) ) : 0;
		$ftell                         = isset( $_POST['ftell'] ) ? absint( sanitize_text_field( $_POST['ftell'] ) ) : 0;
		$total                         = isset( $_POST['total'] ) ? absint( sanitize_text_field( $_POST['total'] ) ) : 0;
		$step                          = isset( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';
		$index                         = isset( $_POST['s2w_index'] ) ? wc_clean( $_POST['s2w_index'] ) : array();
		$products_per_request          = isset( $_POST['products_per_request'] ) ? absint( sanitize_text_field( $_POST['products_per_request'] ) ) : 1;
		self::$import_inventory_by_csv = isset( $_POST['import_inventory_by_csv'] ) ? sanitize_text_field( $_POST['import_inventory_by_csv'] ) : '';

		if ( is_file( $file_url ) ) {
			if ( ( $file_handle = fopen( $file_url, "r" ) ) !== false ) {
				$header = fgetcsv( $file_handle, 0, "," );
				unset( $header );
				$count = 0;
				if ( $step === 'check' ) {
					$count = 1;
					while ( ( $item = fgetcsv( $file_handle, 0, "," ) ) !== false ) {
						$count ++;
					}
					fclose( $file_handle );
					self::log( self::$import_inventory_by_csv ? '------- Start importing Inventories from the file: ' . $file_url : '------- Start importing Products from the file: ' . $file_url );
					wp_send_json( array(
						'status' => 'success',
						'total'  => $count,
					) );
				}
				$import_options = isset( $_POST['import_options'] ) ? wc_clean( $_POST['import_options'] ) : array();
				if ( ! self::$import_inventory_by_csv ) {
					if ( $index['image'] < 0 && $index['variant_image'] < 0 ) {
						$import_options['download_images'] = '';
					}
					if ( $index['body_html'] < 0 ) {
						$import_options['download_description_images'] = '';
					}
					$import_options['manage_stock']         = ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) ? true : false;
					$import_options['placeholder_image_id'] = s2w_get_placeholder_image();
					/*Save csv_if_product_exists and download_images_later options as they are only used for CSV import*/
					$params                          = self::$settings->get_params();
					$params['csv_if_product_exists'] = $import_options['csv_if_product_exists'];
					$params['download_images_later'] = $import_options['download_images_later'];
					update_option( 's2w_params', $params );
				}

				$products     = array();//store product handle
				$product_data = array();
				$ftell_2      = 0;
				if ( $ftell > 0 ) {
					/*Start importing from previous stage*/
					fseek( $file_handle, $ftell );
				} elseif ( $start > 1 ) {
					/*Seek for row to start*/
					for ( $i = 0; $i < $start; $i ++ ) {
						$buff = fgetcsv( $file_handle, 0, "," );
						unset( $buff );
					}
				}
				/*Loop through rows to build product data*/
				while ( ( $item = fgetcsv( $file_handle, 0, "," ) ) !== false ) {
					$count ++;
					$handle = $item[ $index['handle'] ];
					$start ++;
					if ( empty( $handle ) ) {
						continue;
					}
					$ftell_1 = ftell( $file_handle );
					vi_s2w_set_time_limit();
					if ( ! in_array( $handle, $products ) ) {
						/*Import previous product*/
						$this->import_product( $product_data, $import_options );
						if ( count( $products ) < $products_per_request ) {
							if ( ! self::$import_inventory_by_csv && empty( $item[ $index['title'] ] ) ) {
								$ftell_2 = $ftell_1;
								continue;
							}
							$products[]   = $handle;
							$product_data = array(
								'handle'   => $handle,
								'title'    => - 1 < $index['title'] ? $item[ $index['title'] ] : '',
								'variants' => array(),
								'options'  => array(),
								'images'   => array(),
							);
							self::build_variant_data( $index, $item, $product_data );
						} else {
							fclose( $file_handle );
							wp_send_json( array(
								'status'   => 'success',
								'products' => $product_data,
								'start'    => $start - 1,
								'ftell'    => $ftell_2,
								'percent'  => intval( 100 * ( $start ) / $total ),
							) );
						}
					} else {
						self::build_variant_data( $index, $item, $product_data, false );
					}
					unset( $item );
					$next_item = fgetcsv( $file_handle, 0, "," );
					if ( false === $next_item ) {
						/*Import previous product*/
						$this->import_product( $product_data, $import_options );
						if ( ! self::$import_inventory_by_csv && ! $import_options['disable_background_process'] && $import_options['download_images'] && $import_options['download_images_later'] ) {
							/*Maybe start importing images in the background*/
							self::$process->dispatch();
						}

						fclose( $file_handle );
						self::log( '------- Finish importing the file: ' . $file_url );
						wp_send_json( array(
							'status'  => 'finish',
							'start'   => $start,
							'ftell'   => $ftell_1,
							'percent' => intval( 100 * ( $start ) / $total ),
						) );
					} else {
						$count ++;
						$handle = $next_item[ $index['handle'] ];
						$start ++;
						if ( empty( $handle ) ) {
							continue;
						}
						$ftell_2 = ftell( $file_handle );
						if ( ! in_array( $handle, $products ) ) {
							/*Import previous product*/
							$this->import_product( $product_data, $import_options );
							if ( count( $products ) < $products_per_request ) {
								if ( ! self::$import_inventory_by_csv && empty( $next_item[ $index['title'] ] ) ) {
									continue;
								}
								$products[]   = $handle;
								$product_data = array(
									'handle'   => $handle,
									'title'    => - 1 < $index['title'] ? $next_item[ $index['title'] ] : '',
									'variants' => array(),
									'options'  => array(),
									'images'   => array(),
								);
								self::build_variant_data( $index, $next_item, $product_data );
							} else {
								fclose( $file_handle );
								wp_send_json( array(
									'status'   => 'success',
									'products' => $product_data,
									'start'    => $start - 1,
									'ftell'    => $ftell_1,
									'percent'  => intval( 100 * ( $start ) / $total ),
								) );
							}

						} else {
							self::build_variant_data( $index, $next_item, $product_data, false );
						}
						unset( $next_item );
					}
				}
				/*End of file, maybe import the last product*/
				$this->import_product( $product_data, $import_options );
				if ( ! self::$import_inventory_by_csv && ! $import_options['disable_background_process'] && $import_options['download_images'] && $import_options['download_images_later'] ) {
					/*Maybe start importing images in the background*/
					self::$process->dispatch();
				}
				fclose( $file_handle );
				self::log( '------- Finish importing the file: ' . $file_url );
				wp_send_json( array(
					'status'  => 'finish',
					'start'   => $start,
					'percent' => intval( 100 * ( $start ) / $total ),
				) );
			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' ),
					)
				);
			}
		} else {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' ),
				)
			);
		}
	}

	/**
	 * Build product data from each row
	 *
	 * @param $index
	 * @param $item
	 * @param $product_data
	 * @param bool $first
	 */
	private static function build_variant_data( $index, $item, &$product_data, $first = true ) {
		$variants = array(
			'sku' => - 1 < $index['sku'] ? $item[ $index['sku'] ] : '',
		);
		if ( self::$import_inventory_by_csv ) {
			/*In case of importing inventory, the inventory index is an array of indexes*/
			$inventory        = 0;
			$update_inventory = false;
			foreach ( $index['inventory_quantity'] as $inventory_index ) {
				if ( 'not stocked' !== $item[ $inventory_index ] ) {
					$inventory        += intval( $item[ $inventory_index ] );
					$update_inventory = true;
				}
			}

			$variants['inventory_quantity'] = $update_inventory ? $inventory : false;
		} else {
			/*Below data are only used for importing products*/
			$variants['inventory_quantity'] = - 1 < $index['inventory_quantity'] ? $item[ $index['inventory_quantity'] ] : false;
			$variants['price']              = $item[ $index['price'] ];
			$variants['compare_at_price']   = $item[ $index['compare_at_price'] ];
			$variants['image']              = - 1 < $index['variant_image'] ? $item[ $index['variant_image'] ] : '';
			$variants['inventory_policy']   = - 1 < $index['inventory_policy'] ? $item[ $index['inventory_policy'] ] : false;
			if ( - 1 < $index['barcode'] ) {
				$variants['barcode'] = $item[ $index['barcode'] ];
			}
			if ( - 1 < $index['image'] && ! empty( $item[ $index['image'] ] ) ) {
				$product_data['images'][] = array(
					'src' => $item[ $index['image'] ],
					'alt' => - 1 < $index['image_alt'] ? $item[ $index['image_alt'] ] : '',
				);
			}
		}
		if ( $first ) {
			/*First variant row of a product*/
			if ( ! self::$import_inventory_by_csv ) {
				/*Below data are only used for importing products*/
				if ( - 1 < $index['body_html'] ) {
					$product_data['body_html'] = $item[ $index['body_html'] ];
				}
				if ( - 1 < $index['tags'] ) {
					$product_data['tags'] = $item[ $index['tags'] ];
				}
				if ( - 1 < $index['type'] ) {
					$product_data['product_type'] = $item[ $index['type'] ];
				}
			}

			if ( - 1 < $index['option1_name'] && ! empty( $item[ $index['option1_name'] ] ) ) {
				$variants['option1']       = $item[ $index['option1_value'] ];
				$product_data['options'][] = array(
					'name'   => $item[ $index['option1_name'] ],
					'values' => array( $item[ $index['option1_value'] ] ),
				);
			}
			if ( - 1 < $index['option2_name'] && ! empty( $item[ $index['option2_name'] ] ) ) {
				$variants['option2']       = $item[ $index['option2_value'] ];
				$product_data['options'][] = array(
					'name'   => $item[ $index['option2_name'] ],
					'values' => array( $item[ $index['option2_value'] ] ),
				);
			}
			if ( - 1 < $index['option3_name'] && ! empty( $item[ $index['option3_name'] ] ) ) {
				$variants['option3']       = $item[ $index['option3_value'] ];
				$product_data['options'][] = array(
					'name'   => $item[ $index['option3_name'] ],
					'values' => array( $item[ $index['option3_value'] ] ),
				);
			}
		} else {
			if ( ! empty( $item[ $index['option1_value'] ] ) && ! empty( $product_data['options'][0]['values'] ) ) {
				$variants['option1'] = $item[ $index['option1_value'] ];
				if ( ! in_array( $item[ $index['option1_value'] ], $product_data['options'][0]['values'] ) ) {
					$product_data['options'][0]['values'][] = $item[ $index['option1_value'] ];
				}
			}
			if ( - 1 < $index['option2_value'] && ! empty( $item[ $index['option2_value'] ] ) && ! empty( $product_data['options'][1]['values'] ) ) {
				$variants['option2'] = $item[ $index['option2_value'] ];
				if ( ! in_array( $item[ $index['option2_value'] ], $product_data['options'][1]['values'] ) ) {
					$product_data['options'][1]['values'][] = $item[ $index['option2_value'] ];
				}
			}
			if ( - 1 < $index['option3_value'] && ! empty( $item[ $index['option3_value'] ] ) && ! empty( $product_data['options'][2]['values'] ) ) {
				$variants['option3'] = $item[ $index['option3_value'] ];
				if ( ! in_array( $item[ $index['option3_value'] ], $product_data['options'][2]['values'] ) ) {
					$product_data['options'][2]['values'][] = $item[ $index['option3_value'] ];
				}
			}
		}
		if (
			( - 1 < $index['option1_value'] && ! empty( $item[ $index['option1_value'] ] ) ) ||
			( - 1 < $index['option2_value'] && ! empty( $item[ $index['option2_value'] ] ) ) ||
			( - 1 < $index['option3_value'] && ! empty( $item[ $index['option3_value'] ] ) )
		) {
			$product_data['variants'][] = $variants;
		}
	}

	/**
	 * Enqueue scripts
	 */
	public function admin_enqueue_scripts() {
		S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::enqueue_3rd_library( array(
			'accordion',
			'menu',
			'tab',
			'sortable',
		), true );
		wp_enqueue_script( 's2w-import-shopify-to-woocommerce-import', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'import-csv.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-import', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'import-csv.css', '', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
		wp_localize_script( 's2w-import-shopify-to-woocommerce-import', 's2w_import_shopify_to_woocommerce_import_params', array(
			'url'                              => admin_url( 'admin-ajax.php' ),
			'step'                             => $this->step,
			'file_url'                         => $this->file_url,
			'_s2w_nonce'                       => wp_create_nonce( 's2w_action_nonce' ),
			's2w_index'                        => $this->index,
			'import_inventory_by_csv'          => self::$import_inventory_by_csv,
			'products_per_request'             => isset( $_POST['s2w_products_per_request'] ) ? sanitize_text_field( $_POST['s2w_products_per_request'] ) : '1',
			'custom_start'                     => isset( $_POST['s2w_custom_start'] ) ? sanitize_text_field( $_POST['s2w_custom_start'] ) : 1,
			'import_options'                   => array(
				'csv_if_product_exists'       => isset( $_POST['s2w_csv_if_product_exists'] ) ? sanitize_text_field( $_POST['s2w_csv_if_product_exists'] ) : '',
				'disable_background_process'  => isset( $_POST['s2w_disable_background_process'] ) ? sanitize_text_field( $_POST['s2w_disable_background_process'] ) : '',
				'download_description_images' => isset( $_POST['s2w_download_description_images'] ) ? sanitize_text_field( $_POST['s2w_download_description_images'] ) : '',
				'download_images'             => isset( $_POST['s2w_download_images'] ) ? sanitize_text_field( $_POST['s2w_download_images'] ) : '',
				'download_images_later'       => isset( $_POST['s2w_download_images_later'] ) ? sanitize_text_field( $_POST['s2w_download_images_later'] ) : '',
				'keep_slug'                   => isset( $_POST['s2w_keep_slug'] ) ? sanitize_text_field( $_POST['s2w_keep_slug'] ) : '',
				'global_attributes'           => isset( $_POST['s2w_global_attributes'] ) ? sanitize_text_field( $_POST['s2w_global_attributes'] ) : '',
				'product_status'              => isset( $_POST['s2w_product_status'] ) ? sanitize_text_field( $_POST['s2w_product_status'] ) : 'publish',
				'product_categories'          => isset( $_POST['s2w_product_categories'] ) ? array_map( 'sanitize_text_field', ( $_POST['s2w_product_categories'] ) ) : array(),
			),
			'required_fields'                  => self::get_require_fields(),
			'inventory_required_fields'        => self::get_require_fields( true ),
			'i18n_required_field'              => esc_html__( '{required_field} is required to map', 's2w-import-shopify-to-woocommerce' ),
			'i18n_required_fields'             => esc_html__( 'These fields are required to map: {required_fields}', 's2w-import-shopify-to-woocommerce' ),
			'i18n_option_2_mapping'            => esc_html__( 'Option2 Name & Option2 Value should both be mapped or not mapped', 's2w-import-shopify-to-woocommerce' ),
			'i18n_option_3_mapping'            => esc_html__( 'Option3 Name & Option3 Value should both be mapped or not mapped', 's2w-import-shopify-to-woocommerce' ),
			'i18n_progress_checking'           => esc_html__( 'Checking file...', 's2w-import-shopify-to-woocommerce' ),
			'i18n_progress_importing'          => esc_html__( 'Importing...', 's2w-import-shopify-to-woocommerce' ),
			'i18n_error_start_at_row'          => esc_html__( 'Error: The Start at row must be smaller than {max_row} for this file', 's2w-import-shopify-to-woocommerce' ),
			'i18n_error_no_data'               => esc_html__( 'Error: No data', 's2w-import-shopify-to-woocommerce' ),
			'i18n_error'                       => esc_html__( 'Error', 's2w-import-shopify-to-woocommerce' ),
			'i18n_error_message'               => esc_html__( 'Error: {error}', 's2w-import-shopify-to-woocommerce' ),
			'i18n_completed'                   => esc_html__( 'Import completed.', 's2w-import-shopify-to-woocommerce' ),
			'i18n_import_image_notice'         => esc_html__( 'Products images are being imported in the background.', 's2w-import-shopify-to-woocommerce' ),
			'i18n_category_search_placeholder' => esc_html__( 'Please fill in your category title', 's2w-import-shopify-to-woocommerce' ),
		) );
	}

	/**
	 * Required fields for importing products or inventory
	 *
	 * @param bool $import_inventory
	 *
	 * @return array
	 */
	private static function get_require_fields( $import_inventory = false ) {
		if ( $import_inventory ) {
			return array(
				'handle'             => 'Handle',
//				'option1_name'       => 'Option1 Name',
//				'option1_value'      => 'Option1 Value',
//				'option2_name'       => 'Option2 Name',
//				'option2_value'      => 'Option2 Value',
//				'option3_name'       => 'Option3 Name',
//				'option3_value'      => 'Option3 Value',
				'sku'                => 'SKU',
				'inventory_quantity' => 'Variant Inventory Qty',
			);
		} else {
			return array(
				'handle'           => 'Handle',
				'title'            => 'Title',
				'price'            => 'Variant Price',
				'compare_at_price' => 'Variant Compare At Price',
			);
		}
	}

	/**
	 * Import CSV page callback including 3 steps: start(choose file), mapping fields after file uploaded and import
	 */
	public function import_csv_callback() {
		?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Import Product From CSV file', 's2w-import-shopify-to-woocommerce' ); ?></h2>
			<?php
			$steps_state = array(
				'start'   => '',
				'mapping' => '',
				'import'  => '',
			);
			if ( $this->step === 'mapping' ) {
				$steps_state['start']   = '';
				$steps_state['mapping'] = 'active';
				$steps_state['import']  = 'disabled';
			} elseif ( $this->step === 'import' ) {
				$steps_state['start']   = '';
				$steps_state['mapping'] = '';
				$steps_state['import']  = 'active';
			} else {
				$steps_state['start']   = 'active';
				$steps_state['mapping'] = 'disabled';
				$steps_state['import']  = 'disabled';
			}
			?>
            <div class="vi-ui segment">
                <div class="vi-ui steps fluid">
                    <div class="step <?php echo esc_attr( $steps_state['start'] ) ?>">
                        <i class="upload icon"></i>
                        <div class="content">
                            <div class="title"><?php esc_html_e( 'Select file', 's2w-import-shopify-to-woocommerce' ); ?></div>
                        </div>
                    </div>
                    <div class="step <?php echo esc_attr( $steps_state['mapping'] ) ?>">
                        <i class="exchange icon"></i>
                        <div class="content">
                            <div class="title"><?php esc_html_e( 'Settings & Mapping', 's2w-import-shopify-to-woocommerce' ); ?></div>
                        </div>
                    </div>
                    <div class="step <?php echo esc_attr( $steps_state['import'] ) ?>">
                        <i class="refresh icon"></i>
                        <div class="content">
                            <div class="title"><?php esc_html_e( 'Import', 's2w-import-shopify-to-woocommerce' ); ?></div>
                        </div>
                    </div>
                </div>
				<?php
				if ( isset( $_REQUEST['s2w_error'] ) ) {
					$file_url = isset( $_REQUEST['file_url'] ) ? urldecode( $_REQUEST['file_url'] ) : '';
					?>
                    <div class="vi-ui negative message">
                        <div class="header">
							<?php
							switch ( $_REQUEST['s2w_error'] ) {
								case 1:
									esc_html_e( 'Please set mapping for all required fields', 's2w-import-shopify-to-woocommerce' );
									break;
								case 2:
									esc_html_e( 'Name & Value pair for Option2/Option3 should be mapped or should not be mapped together(eg: If Option2 Name is mapped, Option2 Value must be mapped, too)', 's2w-import-shopify-to-woocommerce' );
									break;
								case 3:
									if ( $file_url ) {
										_e( "Can not open file: <strong>{$file_url}</strong>", 's2w-import-shopify-to-woocommerce' );
									} else {
										esc_html_e( 'Can not open file', 's2w-import-shopify-to-woocommerce' );
									}
									break;
								default:
									if ( $file_url ) {
										_e( "File not exists: <strong>{$file_url}</strong>", 's2w-import-shopify-to-woocommerce' );
									} else {
										esc_html_e( 'File not exists', 's2w-import-shopify-to-woocommerce' );
									}
							}
							?>
                        </div>
                    </div>
					<?php
				}
				switch ( $this->step ) {
					case 'mapping':
						?>
                        <form class="<?php echo esc_attr( self::set( 'import-container-form' ) ) ?> vi-ui form"
                              method="post"
                              enctype="multipart/form-data"
                              action="<?php echo esc_url( remove_query_arg( array(
							      'step',
							      'file_url',
							      'import_inventory_by_csv',
							      's2w_error'
						      ) ) ) ?>">
							<?php
							wp_nonce_field( 's2w_import_shopify_to_woocommerce_import_action_nonce', '_s2w_import_shopify_to_woocommerce_import_nonce' );
							if ( $this->error ) {
								?>
                                <div class="error">
									<?php
									echo esc_html( $this->error );
									?>
                                </div>
								<?php
							}
							?>

                            <div class="vi-ui segment">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'products_per_request' ) ) ?>"><?php esc_html_e( 'Products per step', 's2w-import-shopify-to-woocommerce' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="number"
                                                   class="<?php echo esc_attr( self::set( 'products_per_request' ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'products_per_request' ) ) ?>"
                                                   name="<?php echo esc_attr( self::set( 'products_per_request', true ) ) ?>"
                                                   min="1"
                                                   value="<?php echo esc_attr( self::$settings->get_params( 'products_per_request' ) ) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'custom_start' ) ) ?>"><?php esc_html_e( 'Start at row', 's2w-import-shopify-to-woocommerce' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="number"
                                                   class="<?php echo esc_attr( self::set( 'custom_start' ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'custom_start' ) ) ?>"
                                                   name="<?php echo esc_attr( self::set( 'custom_start', true ) ) ?>"
                                                   min="2"
                                                   value="2">
                                            <p class="description"><?php esc_html_e( 'Only import products from this row on.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                        </td>
                                    </tr>
									<?php
									if ( ! self::$import_inventory_by_csv ) {
										?>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"><?php esc_html_e( 'Use global attributes', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div class="vi-ui toggle checkbox checked">
                                                    <input type="checkbox"
                                                           name="<?php echo esc_attr( self::set( 'global_attributes', true ) ) ?>"
                                                           id="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"
                                                           value="1" <?php checked( self::$settings->get_params( 'global_attributes' ), '1' ) ?>>
                                                    <label for="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"><?php esc_html_e( 'WC product filters plugin, Variations Swatch plugin... only work with global attributes.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"><?php esc_html_e( 'Import description images', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div class="vi-ui toggle checkbox checked">
                                                    <input type="checkbox"
                                                           name="<?php echo esc_attr( self::set( 'download_description_images', true ) ) ?>"
                                                           id="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"
                                                           value="1" <?php checked( self::$settings->get_params( 'download_description_images' ), '1' ) ?>>
                                                    <label for="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"><?php esc_html_e( 'Import images from product description in the background.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"><?php esc_html_e( 'Disable background processing', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div class="vi-ui toggle checkbox checked">
                                                    <input type="checkbox"
                                                           name="<?php echo esc_attr( self::set( 'disable_background_process', true ) ) ?>"
                                                           id="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"
                                                           value="1" <?php checked( self::$settings->get_params( 'disable_background_process' ), '1' ) ?>>
                                                    <label for="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"><?php _e( 'Product images and description images will be added to <a href="admin.php?page=s2w-import-shopify-to-woocommerce-error-images" target="_blank">Failed images</a> list so that you can go there to download all images with 1 click. This is recommended if your server is weak or if you usually have duplicated images issue.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"><?php esc_html_e( 'Import images', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div class="vi-ui toggle checkbox checked">
                                                    <input type="checkbox"
                                                           name="<?php echo esc_attr( self::set( 'download_images', true ) ) ?>"
                                                           id="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"
                                                           value="1" <?php checked( self::$settings->get_params( 'download_images' ), '1' ) ?>>
                                                    <label for="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"><?php esc_html_e( 'Product images will be imported in the background.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'download_images_later' ) ) ?>"><?php esc_html_e( 'Import images after importing products', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div class="vi-ui toggle checkbox checked">
                                                    <input type="checkbox"
                                                           name="<?php echo esc_attr( self::set( 'download_images_later', true ) ) ?>"
                                                           id="<?php echo esc_attr( self::set( 'download_images_later' ) ) ?>"
                                                           value="1" <?php checked( self::$settings->get_params( 'download_images_later' ), '1' ) ?>>
                                                    <label for="<?php echo esc_attr( self::set( 'download_images_later' ) ) ?>"><?php esc_html_e( 'Only start importing images after all products are imported', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                </div>
                                                <p class="description"><?php esc_html_e( '*It\'s faster than importing images while importing products.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"><?php esc_html_e( 'Keep product slug', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div class="vi-ui toggle checkbox checked">
                                                    <input type="checkbox"
                                                           name="<?php echo esc_attr( self::set( 'keep_slug', true ) ) ?>"
                                                           id="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"
                                                           value="1" <?php checked( self::$settings->get_params( 'keep_slug' ), '1' ) ?>>
                                                    <label for="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"><?php esc_html_e( 'If enabled, slug will be set from Shopify product Handle. Otherwise, slug will generated from product Title', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'csv_if_product_exists' ) ) ?>"><?php esc_html_e( 'If a product exists', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div>
                                                    <select class="vi-ui fluid dropdown"
                                                            id="<?php echo esc_attr( self::set( 'csv_if_product_exists' ) ) ?>"
                                                            name="<?php echo esc_attr( self::set( 'csv_if_product_exists', true ) ) ?>">
                                                        <option value="import" <?php selected( self::$settings->get_params( 'csv_if_product_exists' ), 'import' ) ?>><?php esc_html_e( 'Import normally', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                        <option value="skip" <?php selected( self::$settings->get_params( 'csv_if_product_exists' ), 'skip' ) ?>><?php esc_html_e( 'Skip', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                        <option value="update" <?php selected( self::$settings->get_params( 'csv_if_product_exists' ), 'update' ) ?>><?php esc_html_e( 'Update(slower)', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                    </select>
                                                </div>
                                                <p><?php esc_html_e( 'A product is checked for existence by its handle(slug)', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'product_status' ) ) ?>"><?php esc_html_e( 'Product status', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div>
                                                    <select class="vi-ui fluid dropdown"
                                                            id="<?php echo esc_attr( self::set( 'product_status' ) ) ?>"
                                                            name="<?php echo esc_attr( self::set( 'product_status', true ) ) ?>">
                                                        <option value="publish" <?php selected( self::$settings->get_params( 'product_status' ), 'publish' ) ?>><?php esc_html_e( 'Publish', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                        <option value="pending" <?php selected( self::$settings->get_params( 'product_status' ), 'pending' ) ?>><?php esc_html_e( 'Pending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                        <option value="draft" <?php selected( self::$settings->get_params( 'product_status' ), 'draft' ) ?>><?php esc_html_e( 'Draft', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                    </select>
                                                </div>
                                                <p><?php esc_html_e( 'Status of products after being successfully imported', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'product_categories' ) ) ?>"><?php esc_html_e( 'Product categories', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div>
                                                    <select class="search-category"
                                                            id="<?php echo esc_attr( self::set( 'product_categories' ) ) ?>"
                                                            name="<?php echo esc_attr( self::set( 'product_categories', true ) ) ?>[]"
                                                            multiple="multiple">
														<?php

														if ( is_array( self::$settings->get_params( 'product_categories' ) ) && count( self::$settings->get_params( 'product_categories' ) ) ) {
															foreach ( self::$settings->get_params( 'product_categories' ) as $category_id ) {
																$category = get_term( $category_id );
																if ( $category ) {
																	?>
                                                                    <option value="<?php echo esc_attr( $category_id ) ?>"
                                                                            selected><?php echo esc_html( $category->name ); ?></option>
																	<?php
																}
															}
														}
														?>
                                                    </select>
                                                </div>
                                                <p><?php esc_html_e( 'Choose categories you want to add imported products to', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
										<?php
									}
									?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="vi-ui segment">
                                <div class="vi-ui positive message">
									<?php
									if ( self::$import_inventory_by_csv ) {
										esc_html_e( 'Column mapping for importing inventory. "Variant Inventory Qty" can be mapped from multiple locations.', 's2w-import-shopify-to-woocommerce' );
									} else {
										esc_html_e( 'Column mapping for importing products.', 's2w-import-shopify-to-woocommerce' );
									}
									?>
                                </div>
                                <table class="vi-ui center aligned table <?php echo esc_attr( self::set( 'mapping-table' ) ) ?>">
                                    <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Column name', 's2w-import-shopify-to-woocommerce' ) ?></th>
                                        <th><?php esc_html_e( 'Map to field', 's2w-import-shopify-to-woocommerce' ) ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
									<?php
									$headers = self::get_column_headers( self::$import_inventory_by_csv );
									if ( self::$import_inventory_by_csv ) {
										/*Mapping fields for importing inventories, inventory can be mapped from multiple locations*/
										foreach ( $this->header as $source_file_header_k => $source_file_header_v ) {
											?>
                                            <tr>
                                                <td>
                                                    <label for="<?php echo esc_attr( self::set( "mapping-item-{$source_file_header_k}" ) ) ?>"><?php echo esc_html( $source_file_header_v ); ?></label>
                                                </td>
                                                <td>
                                                    <select id="<?php echo esc_attr( self::set( "mapping-item-{$source_file_header_k}" ) ) ?>"
                                                            class="vi-ui fluid search dropdown <?php echo esc_attr( self::set( 'map-to-field' ) ) ?>"
                                                            name="<?php echo self::set( 'map_to', true ) ?>[<?php echo esc_attr( $source_file_header_k ) ?>]">
                                                        <option value=""><?php esc_html_e( 'Do not import', 's2w-import-shopify-to-woocommerce' ) ?></option>
														<?php
														foreach ( $headers as $header_k => $header_v ) {
															?>
                                                            <option value="<?php echo esc_attr( $header_k ) ?>"<?php selected( $source_file_header_v, $header_v ) ?>><?php echo esc_html( $header_v ) ?></option>
															<?php
														}
														?>
                                                    </select>
                                                </td>
                                            </tr>
											<?php
										}
									} else {
										/*Mapping fields for importing products*/
										$required_fields = array_keys( self::get_require_fields() );
										$description     = array( 'barcode' => S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::get_barcode_sync_description() );
										foreach ( $headers as $header_k => $header_v ) {
											?>
                                            <tr>
                                                <td>
                                                    <select id="<?php echo esc_attr( self::set( $header_k ) ) ?>"
                                                            class="vi-ui fluid search dropdown"
                                                            name="<?php echo self::set( 'map_to', true ) ?>[<?php echo esc_attr( $header_k ) ?>]">
                                                        <option value=""><?php esc_html_e( 'Do not import', 's2w-import-shopify-to-woocommerce' ) ?></option>
														<?php
														foreach ( $this->header as $file_header ) {
															?>
                                                            <option value="<?php echo esc_attr( $file_header ) ?>"<?php selected( $header_v, $file_header ) ?>><?php echo esc_html( $file_header ) ?></option>
															<?php
														}
														?>
                                                    </select>
													<?php
													if ( ! empty( $description[ $header_k ] ) ) {
														?>
                                                        <p><?php echo wp_kses_post( $description[ $header_k ] ); ?></p>
														<?php
													}
													?>
                                                </td>
                                                <td>
													<?php
													$label = $header_v;
													if ( in_array( $header_k, $required_fields ) ) {
														$label .= esc_html__( '(*Required)', 's2w-import-shopify-to-woocommerce' );
													}
													?>
                                                    <label for="<?php echo esc_attr( self::set( $header_k ) ) ?>"><?php echo esc_html( $label ); ?></label>
                                                </td>
                                            </tr>
											<?php
										}
									}
									?>
                                    </tbody>
                                </table>
                            </div>
                            <input type="hidden" name="s2w_import_shopify_to_woocommerce_file_url"
                                   value="<?php echo esc_attr( stripslashes( $this->file_url ) ) ?>">
                            <input type="hidden" name="s2w_import_inventory_by_csv"
                                   value="<?php echo esc_attr( self::$import_inventory_by_csv ) ?>">
                            <p>
                                <input type="submit" name="s2w_import_shopify_to_woocommerce_import"
                                       class="vi-ui primary button <?php echo esc_attr( self::set( 'import-continue' ) ) ?>"
                                       value="<?php esc_attr_e( 'Import', 's2w-import-shopify-to-woocommerce' ); ?>">
                            </p>
                        </form>
						<?php
						break;
					case 'import':
						?>
                        <div>
                            <div class="vi-ui indicating progress standard <?php echo esc_attr( self::set( 'import-progress' ) ) ?>">
                                <div class="label"></div>
                                <div class="bar">
                                    <div class="progress"></div>
                                </div>
                            </div>
                            <div class="vi-ui positive message <?php echo esc_attr( self::set( array(
								'import-completed-message',
								'hidden'
							) ) ) ?>"><?php printf( __( 'To view log, please go to %s or %s and search for "s2w-import-csv".', 's2w-import-shopify-to-woocommerce' ), '<a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-logs' ) ) . '">' . esc_html__( 'Shopify to Woo/Logs', 's2w-import-shopify-to-woocommerce' ) . '</a>', '<a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs' ) ) . '">' . esc_html__( 'WooCommerce Status/Logs', 's2w-import-shopify-to-woocommerce' ) . '</a>' ) ?></div>
                        </div>
						<?php
						break;
					default:
						?>
                        <form class="<?php echo esc_attr( self::set( 'import-container-form' ) ) ?> vi-ui form"
                              method="post"
                              enctype="multipart/form-data">
							<?php
							wp_nonce_field( 's2w_import_shopify_to_woocommerce_import_action_nonce', '_s2w_import_shopify_to_woocommerce_import_nonce' );
							if ( $this->error ) {
								?>
                                <div class="error">
									<?php
									echo esc_html( $this->error );
									?>
                                </div>
								<?php
							}
							?>
                            <div class="<?php echo esc_attr( self::set( 'import-container' ) ) ?>">
                                <label for="<?php echo esc_attr( self::set( 'import-file' ) ) ?>"><?php esc_html_e( 'Select csv file to import', 's2w-import-shopify-to-woocommerce' ); ?></label>
                                <div>
                                    <input type="file" name="s2w_import_shopify_to_woocommerce_file"
                                           id="<?php echo esc_attr( self::set( 'import-file' ) ) ?>"
                                           class="<?php echo esc_attr( self::set( 'import-file' ) ) ?>"
                                           accept=".csv"
                                           required>
                                </div>
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'import_inventory_by_csv' ) ) ?>"><?php esc_html_e( 'Import inventory by CSV', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div class="vi-ui toggle checkbox checked">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr( self::set( 'import_inventory_by_csv', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'import_inventory_by_csv' ) ) ?>"
                                                       value="1" <?php checked( self::$settings->get_params( 'import_inventory_by_csv' ), '1' ) ?>>
                                                <label for="<?php echo esc_attr( self::set( 'import_inventory_by_csv' ) ) ?>"><?php esc_html_e( 'Enable this if this CSV file is an inventory exported file. This will only update inventory of existing products. Inventories from all Shopify locations will be accumulated and set to respective WooCommerce product.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                            <p class="description"><?php echo VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_kses_post( __( 'If you use more than 1 location for your Shopify store, you have to import products then use this option to import inventory. Learn more about <a target="_blank" href="https://help.shopify.com/en/manual/products/inventory/getting-started-with-inventory/inventory-csv">exporting inventory</a>.', 's2w-import-shopify-to-woocommerce' ) ) ?></p>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p><input type="submit" name="s2w_import_shopify_to_woocommerce_select_file"
                                      class="vi-ui primary button <?php echo esc_attr( self::set( 'import-continue' ) ) ?>"
                                      value="<?php esc_attr_e( 'Continue', 's2w-import-shopify-to-woocommerce' ); ?>">
                            </p>
                        </form>
					<?php
				}
				?>
            </div>
        </div>
		<?php
	}

	/**
	 * Standard headers of a Shopify exported products/inventories CSV file
	 *
	 * @param bool $import_inventory
	 *
	 * @return array
	 */
	private static function get_column_headers( $import_inventory = false ) {
		if ( $import_inventory ) {
			$headers = array(
				'handle'             => 'Handle',
				'title'              => 'Title',
				'sku'                => 'SKU',
				'inventory_quantity' => 'Variant Inventory Qty',
				'option1_name'       => 'Option1 Name',
				'option1_value'      => 'Option1 Value',
				'option2_name'       => 'Option2 Name',
				'option2_value'      => 'Option2 Value',
				'option3_name'       => 'Option3 Name',
				'option3_value'      => 'Option3 Value',
			);
		} else {
			$headers = array(
				'handle'             => 'Handle',
				'title'              => 'Title',
				'price'              => 'Variant Price',
				'compare_at_price'   => 'Variant Compare At Price',
				'option1_name'       => 'Option1 Name',
				'option1_value'      => 'Option1 Value',
				'option2_name'       => 'Option2 Name',
				'option2_value'      => 'Option2 Value',
				'option3_name'       => 'Option3 Name',
				'option3_value'      => 'Option3 Value',
				'sku'                => 'Variant SKU',
				'body_html'          => 'Body (HTML)',
				'type'               => 'Type',
				'tags'               => 'Tags',
//									'weight'=>'Variant Grams',
				'image'              => 'Image Src',
				'image_alt'          => 'Image Alt Text',
				'variant_image'      => 'Variant Image',
				'inventory_quantity' => 'Variant Inventory Qty',
				'inventory_policy'   => 'Variant Inventory Policy',
				'barcode'            => 'Variant Barcode',
			);
		}

		return $headers;
	}

	/**
	 * Capability required to view Import CSV page
	 *
	 * @return mixed|void
	 */
	private static function get_required_capability() {
		return apply_filters( 'vi_s2w_admin_sub_menu_capability', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_required_capability( 'import_csv' ), 's2w-import-shopify-to-woocommerce-import-csv' );
	}

	/**
	 * Look for all images from product description and import them based on import options
	 *
	 * @param $description
	 * @param $product_id
	 * @param $download_description_images
	 * @param $disable_background_process
	 * @param $dispatch
	 */
	private static function handle_description_images( $description, $product_id, $download_description_images, $disable_background_process, &$dispatch ) {
		if ( $description && $download_description_images && ! VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::use_external_image() ) {
			preg_match_all( '/src="([\s\S]*?)"/im', preg_replace( '/<script [\s\S]*?<\/script>/im', '', preg_replace( '/<iframe [\s\S]*?<\/iframe>/im', '', $description ) ), $matches );
			if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
				$description_images = array_unique( $matches[1] );
				if ( $disable_background_process ) {
					foreach ( $description_images as $description_image ) {
						S2W_Error_Images_Table::insert( $product_id, '', $description_image, '', 2, '' );
					}
				} else {
					$dispatch = true;
					foreach ( $description_images as $description_image ) {
						$images_data = array(
							'id'          => '',
							'src'         => $description_image,
							'alt'         => '',
							'parent_id'   => $product_id,
							'product_ids' => array(),
							'set_gallery' => 2,
						);
						self::$process->push_to_queue( $images_data );
					}
				}
			}
		}
	}

	/**
	 * Log
	 *
	 * @param $content
	 * @param string $level
	 */
	private static function log( $content, $level = 'info' ) {
		S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( $content, 'import-csv', $level );
	}
}