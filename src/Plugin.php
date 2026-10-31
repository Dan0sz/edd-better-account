<?php
/**
 * @package Daan\EDD\BetterAccount
 * @author  Daan van den Bergh
 * @url https://daan.dev
 * @license MIT
 */

namespace Daan\EDD\BetterAccount;

class Plugin {
	private $text_domain = 'edd-better-account';

	/**
	 * @var Tabs
	 */
	private $tabs;

	/**
	 * @var \WP_Post|null The current tab, if the tabs are shown on this page.
	 */
	private $current;

	/**
	 * @var bool Whether the tabs were already added to the content.
	 */
	private $rendered = false;

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->tabs = new Tabs();

		$this->init();
	}

	/**
	 * Action & Filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'after_setup_theme', [ $this, 'register_menu_location' ] );
		add_action( 'wp', [ $this, 'set_current_tab' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'body_class', [ $this, 'add_body_class' ] );

		// After blocks (9) and shortcodes (11) are rendered.
		add_filter( 'the_content', [ $this, 'add_tabs' ], 99 );

		// After EDD Recurring's Update Payment Method page template (10).
		add_filter( 'template_include', [ $this, 'maybe_replace_update_page_template' ], 20 );
	}

	/**
	 * @return void
	 */
	public function register_menu_location() {
		register_nav_menu( Tabs::LOCATION, __( 'Account Tabs', $this->text_domain ) );
	}

	/**
	 * Tabs are shown to logged in customers, on pages in the menu (and their subpages) and on EDD Recurring's Update
	 * Payment Method page.
	 *
	 * @return void
	 */
	public function set_current_tab() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( $this->is_update_page() ) {
			$this->current = $this->tabs->get_subscriptions_tab();

			return;
		}

		if ( is_page() ) {
			$this->current = $this->tabs->get_tab_for_page( get_queried_object_id() );
		}
	}

	/**
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! $this->current ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'edd-better-account',
			EDD_BETTER_ACCOUNT_PLUGIN_URL . "assets/css/better-account$suffix.css",
			[],
			filemtime( EDD_BETTER_ACCOUNT_PLUGIN_DIR . "assets/css/better-account$suffix.css" )
		);

		wp_enqueue_script(
			'edd-better-account',
			EDD_BETTER_ACCOUNT_PLUGIN_URL . "assets/js/better-account$suffix.js",
			[],
			filemtime( EDD_BETTER_ACCOUNT_PLUGIN_DIR . "assets/js/better-account$suffix.js" ),
			true
		);
	}

	/**
	 * @param array $classes
	 *
	 * @return array
	 */
	public function add_body_class( $classes ) {
		if ( $this->current ) {
			$classes[] = 'has-account-tabs';
		}

		return $classes;
	}

	/**
	 * Puts the tabs next to the page's content.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function add_tabs( $content ) {
		if ( ! $this->current || $this->rendered || ! in_the_loop() || ! is_main_query() || get_the_ID() !== get_queried_object_id() ) {
			return $content;
		}

		$this->rendered = true;

		return $this->wrap( $content );
	}

	/**
	 * EDD Recurring's Update Payment Method page doesn't use the_content, so it gets its own template with the tabs.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function maybe_replace_update_page_template( $template ) {
		if ( ! $this->current || ! $this->is_update_page() || basename( $template ) !== 'subscription-update-default.php' ) {
			return $template;
		}

		return EDD_BETTER_ACCOUNT_PLUGIN_DIR . 'templates/subscription-update.php';
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function wrap( $content ) {
		return '<div class="eba">' . $this->tabs->render( $this->current ) . '<div class="eba__content">' . $content . '</div></div>';
	}

	/**
	 * @see \EDD\Recurring\Subscriptions\Update\Page::add_custom_rewrite_rule()
	 *
	 * @return bool
	 */
	private function is_update_page() {
		return get_query_var( 'edd_recurring_update' ) && get_query_var( 'pagename' ) === 'subscription-update';
	}
}
