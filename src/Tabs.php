<?php
/**
 * @package Daan\EDD\BetterAccount
 * @author  Daan van den Bergh
 * @url https://daan.dev
 * @license MIT
 */

namespace Daan\EDD\BetterAccount;

/**
 * The tabs: the top level items of the menu assigned to the Account Tabs menu location.
 */
class Tabs {
	const LOCATION = 'edd-better-account';

	private $text_domain = 'edd-better-account';

	/**
	 * @var \WP_Post[]|null Top level menu items.
	 */
	private $items;

	/**
	 * Top level items of the menu assigned to our location.
	 *
	 * @return \WP_Post[]
	 */
	public function get_items() {
		if ( $this->items !== null ) {
			return $this->items;
		}

		$this->items = [];
		$locations   = get_nav_menu_locations();

		if ( empty( $locations[ self::LOCATION ] ) ) {
			return $this->items;
		}

		$items = wp_get_nav_menu_items( $locations[ self::LOCATION ], [ 'update_post_term_cache' => false ] );

		foreach ( $items ?: [] as $item ) {
			if ( (int) $item->menu_item_parent === 0 ) {
				$this->items[] = $item;
			}
		}

		return $this->items;
	}

	/**
	 * The tab for a page: the page itself, or else its closest ancestor, e.g. a subpage of Account belongs to the Account tab.
	 *
	 * @param int $page_id
	 *
	 * @return \WP_Post|null
	 */
	public function get_tab_for_page( $page_id ) {
		if ( ! $page_id ) {
			return null;
		}

		foreach ( array_merge( [ $page_id ], get_post_ancestors( $page_id ) ) as $id ) {
			foreach ( $this->get_items() as $item ) {
				if ( $item->type === 'post_type' && (int) $item->object_id === (int) $id ) {
					return $item;
				}
			}
		}

		return null;
	}

	/**
	 * The tab of the page that lists the subscriptions, i.e. contains EDD Recurring's [edd_subscriptions] shortcode or
	 * Subscriptions block.
	 *
	 * @return \WP_Post|null
	 */
	public function get_subscriptions_tab() {
		$tab = null;

		foreach ( $this->get_items() as $item ) {
			if ( $item->type !== 'post_type' ) {
				continue;
			}

			$page = get_post( $item->object_id );

			if ( $page && ( has_shortcode( $page->post_content, 'edd_subscriptions' ) || has_block( 'edd/subscriptions', $page ) ) ) {
				$tab = $item;

				break;
			}
		}

		/**
		 * The tab to show as current on EDD Recurring's Update Payment Method page.
		 *
		 * @param \WP_Post|null $tab   Menu item.
		 * @param \WP_Post[]    $items Top level menu items.
		 */
		return apply_filters( 'edd_better_account_subscriptions_tab', $tab, $this->get_items() );
	}

	/**
	 * @param \WP_Post $current The tab to show as current.
	 *
	 * @return string
	 */
	public function render( $current ) {
		ob_start();
		?>
		<nav class="eba-tabs" aria-label="<?php esc_attr_e( 'Account', $this->text_domain ); ?>">
			<button type="button" class="eba-tabs__toggle" aria-expanded="false" aria-controls="eba-tabs-list">
				<span><?php echo esc_html( $current->title ); ?></span>
				<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
					<path d="M5.293 9.707l6 6c0.391 0.391 1.024 0.391 1.414 0l6-6c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z"></path>
				</svg>
			</button>
			<ul id="eba-tabs-list" class="eba-tabs__list">
				<?php foreach ( $this->get_items() as $item ) : ?>
					<?php
					$is_current = (int) $item->ID === (int) $current->ID;
					$classes    = array_map( 'sanitize_html_class', array_filter( (array) $item->classes ) );

					if ( $is_current ) {
						$classes[] = 'is-current';
					}
					?>
					<li class="eba-tabs__item <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
						<a href="<?php echo esc_url( $this->get_url( $item ) ); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?><?php echo $item->target ? ' target="' . esc_attr( $item->target ) . '"' : ''; ?>><?php echo esc_html( $item->title ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php

		return ob_get_clean();
	}

	/**
	 * A link to wp-login.php?action=logout without a nonce asks "Do you really want to log out?", so use a proper logout
	 * URL instead.
	 *
	 * @param \WP_Post $item
	 *
	 * @return string
	 */
	private function get_url( $item ) {
		if ( strpos( $item->url, 'action=logout' ) !== false ) {
			return wp_logout_url( home_url() );
		}

		return $item->url;
	}
}
