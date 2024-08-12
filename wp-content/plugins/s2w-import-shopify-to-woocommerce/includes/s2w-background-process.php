<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main task is to import product images. To perform another task, override the task method
 *
 * Class S2W_Background_Process
 */
abstract class S2W_Background_Process extends WP_Background_Process {
	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$product_id       = isset( $item['parent_id'] ) ? $item['parent_id'] : '';//ID of Woo product
		$set_gallery      = isset( $item['set_gallery'] ) ? $item['set_gallery'] : '';
		$product_ids      = isset( $item['product_ids'] ) ? $item['product_ids'] : array();//Woo product ID + list of Woo variation IDs
		$src              = isset( $item['src'] ) ? $item['src'] : '';
		$id               = isset( $item['id'] ) ? $item['id'] : '';//Shopify image ID
		$shopify_image_id = $id;//This is the real ID from Shopify
		$alt              = isset( $item['alt'] ) ? $item['alt'] : '';
		try {
			if ( $set_gallery == 2 && VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::use_external_image() ) {
				return false;
			}
			if ( $product_id && $src ) {
				$post = get_post( $product_id );
				if ( $post && $post->post_type === 'product' ) {
					vi_s2w_set_time_limit();
					$thumb_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::download_image( $id, $src, $product_id, array( 'gif' ) );
					if ( $thumb_id && ! is_wp_error( $thumb_id ) ) {
						if ( $id ) {
							/*Save shopify image ID*/
							update_post_meta( $thumb_id, '_s2w_shopify_image_id', $id );
						}
						if ( $set_gallery == 2 ) {
							/*This image is from product description => replace original image src in product description with the new one*/
							if ( $id ) {
								$downloaded_url = wp_get_attachment_url( $thumb_id );
								$description    = html_entity_decode( $post->post_content, ENT_QUOTES | ENT_XML1, 'UTF-8' );
								$description    = preg_replace( '/[^"]{0,}' . preg_quote( $id, '/' ) . '[^"]{0,}/U', $downloaded_url, $description );
								$description    = str_replace( $src, $downloaded_url, $description );
								wp_update_post( array( 'ID' => $product_id, 'post_content' => $description ) );
							}
						} else {
							if ( $alt ) {
								/*Save alt if any*/
								update_post_meta( $thumb_id, '_wp_attachment_image_alt', $alt );
							}
							if ( count( $product_ids ) ) {
								/*Set feature image for product/variations*/
								foreach ( $product_ids as $v_id ) {
									if ( in_array( get_post_type( $v_id ), array(
										'product',
										'product_variation'
									) ) ) {
										update_post_meta( $v_id, '_thumbnail_id', $thumb_id );
									}
								}
							}

							if ( 1 == $set_gallery ) {
								/*This image is from gallery => add it to gallery of Woo product*/
								$gallery = get_post_meta( $product_id, '_product_image_gallery', true );
								if ( $gallery ) {
									$gallery_array = explode( ',', $gallery );
								} else {
									$gallery_array = array();
								}
								$gallery_array[] = $thumb_id;
								update_post_meta( $product_id, '_product_image_gallery', implode( ',', array_unique( $gallery_array ) ) );
							}
						}
					} else {
						/*If unable to import image, add it to failed images*/
						S2W_Error_Images_Table::insert( $product_id, implode( ',', $product_ids ), $src, $alt, intval( $set_gallery ), $shopify_image_id );
						if ( is_wp_error( $thumb_id ) ) {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Background product images import: ' . $thumb_id->get_error_code() . ' - ' . $thumb_id->get_error_message() );
						}
					}
				}
			}

		} catch ( Error $e ) {
			/*If unable to import image, add it to failed images*/
			S2W_Error_Images_Table::insert( $product_id, implode( ',', $product_ids ), $src, $alt, intval( $set_gallery ), $shopify_image_id );
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Background product images import: ' . $e->getMessage() );

			return false;
		} catch ( Exception $e ) {
			/*If unable to import image, add it to failed images*/
			S2W_Error_Images_Table::insert( $product_id, implode( ',', $product_ids ), $src, $alt, intval( $set_gallery ), $shopify_image_id );
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Background product images import: ' . $e->getMessage() );

			return false;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function is_downloading() {
		return $this->is_process_running();
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		if ( ! $this->is_process_running() && $this->is_queue_empty() ) {
			set_transient( $this->action . '_complete', time() );
		}
		// Show notice to user or perform some other arbitrary task...
		parent::complete();
	}

	/**
	 * Delete all batches.
	 *
	 * @return S2W_Background_Process
	 */
	public function delete_all_batches() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $key ) ); // @codingStandardsIgnoreLine.

		return $this;
	}

	/**
	 * Kill process.
	 *
	 * Stop processing queue items, clear cronjob and delete all batches.
	 */
	public function kill_process() {
		if ( ! $this->is_queue_empty() ) {
			$this->delete_all_batches();
			wp_clear_scheduled_hook( $this->cron_hook_identifier );
		}
	}

	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	public function is_queue_empty() {
		return parent::is_queue_empty();
	}

	/**
	 * Return all batches
	 *
	 * @return array|object|null
	 */
	public function get_all_batches() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$column} LIKE %s", $key ), ARRAY_A ); // @codingStandardsIgnoreLine.;
	}

	/**
	 * Return number of items which are currently in the queue
	 *
	 * @return int|void
	 */
	public function get_items_left() {
		$batches    = $this->get_all_batches();
		$items_left = 0;
		if ( $batches ) {
			foreach ( $batches as $batch ) {
				if ( ! empty( $batch['option_value'] ) ) {
					$items_left += count( maybe_unserialize( $batch['option_value'] ) );
				}
			}
		}

		return $items_left;
	}

	/**
	 * @return mixed|string
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * Considered as late if cron is triggered more than 300s late
	 *
	 * @return bool
	 */
	public function is_cron_late() {
		$is_late = false;
		$cron    = $this->get_identifier() . '_cron';
		$next    = wp_next_scheduled( $cron );
		if ( $next ) {
			$late = $next - time();
			if ( $late < - 300 ) {
				$is_late = true;
			}
		}

		return $is_late;
	}
}