<?php
/**
 * @package Daan\EDD\BetterAccount
 * @author  Daan van den Bergh
 * @url https://daan.dev
 * @license MIT
 */

namespace Daan\EDD\BetterAccount;

/**
 * The login page as logged out visitors see it: the login form on a card, with what's in their account next to it.
 */
class Login {
	private $text_domain = 'edd-better-account';

	/**
	 * @var Tabs
	 */
	private $tabs;

	/**
	 * @var string The login form (with its error messages), once it's rendered in the page's content.
	 */
	private $form = '';

	/**
	 * Build class.
	 *
	 * @param Tabs $tabs
	 */
	public function __construct( $tabs ) {
		$this->tabs = $tabs;

		// EDD's login form, e.g. [edd_login], or the profile editor for logged out visitors.
		add_filter( 'edd_login_form', [ $this, 'set_form' ], 99 );

		// EDD's Login block.
		add_filter( 'render_block_edd/login', [ $this, 'set_form' ], 99 );
	}

	/**
	 * Whether this is the login page (which is in the menu, or EDD's login page), for a logged out visitor.
	 *
	 * @return bool
	 */
	public function is_login_page() {
		if ( is_user_logged_in() || ! is_page() || ! $this->tabs->get_items() ) {
			return false;
		}

		return url_to_postid( $this->tabs->get_login_url() ) === get_queried_object_id();
	}

	/**
	 * @param string $form
	 *
	 * @return string
	 */
	public function set_form( $form ) {
		if ( in_the_loop() && is_main_query() && get_the_ID() === get_queried_object_id() ) {
			$this->form = $form;
		}

		return $form;
	}

	/**
	 * @param string $content
	 *
	 * @return string Unchanged if it doesn't contain a login form.
	 */
	public function wrap( $content ) {
		if ( ! $this->form ) {
			return $content;
		}

		/**
		 * Whether to hide the login page's other content (e.g. an introduction to the profile page) from logged out visitors.
		 *
		 * @param bool $hide
		 * @param int  $page_id
		 */
		if ( apply_filters( 'edd_better_account_login_hide_content', true, get_queried_object_id() ) ) {
			$content = $this->form;
		}

		return '<div class="eba-login"><div class="eba-login__card">' . $this->get_notice() . $content . '</div>' . $this->get_list() . '</div>';
	}

	/**
	 * Why they need to log in, if they were sent here from another account page.
	 *
	 * @see Plugin::maybe_redirect_to_login()
	 *
	 * @return string
	 */
	private function get_notice() {
		if ( empty( $_GET[ 'redirect_to' ] ) || ! is_string( $_GET[ 'redirect_to' ] ) ) {
			return '';
		}

		$page_id = url_to_postid( esc_url_raw( wp_unslash( $_GET[ 'redirect_to' ] ) ) );
		$tab     = $page_id !== get_queried_object_id() ? $this->tabs->get_tab_for_page( $page_id ) : null;

		if ( ! $tab ) {
			return '';
		}

		$message = $this->is_danger( $tab )
			/* translators: %s: the account page they wanted to open, e.g. Delete Account */
			? __( 'Log in to continue to %s.', $this->text_domain )
			/* translators: %s: the account page they wanted to open, e.g. Your Orders */
			: __( 'Log in to view %s.', $this->text_domain );

		return '<p class="eba-login__notice">' . sprintf( esc_html( $message ), '<strong>' . esc_html( $tab->title ) . '</strong>' ) . '</p>';
	}

	/**
	 * The tabs they can open after logging in, except the login page itself, logging out and tabs like Delete Account.
	 *
	 * @return string
	 */
	private function get_list() {
		$items = '';

		foreach ( $this->tabs->get_items() as $item ) {
			$is_login_page = $item->type === 'post_type' && (int) $item->object_id === get_queried_object_id();

			if ( $is_login_page || $this->is_danger( $item ) || strpos( $item->url, 'action=logout' ) !== false ) {
				continue;
			}

			$items .= '<li>' . esc_html( $item->title ) . '</li>';
		}

		if ( ! $items ) {
			return '';
		}

		return '<div class="eba-login__list"><h2>' . esc_html__( 'In your account', $this->text_domain ) . '</h2><ul>' . $items . '</ul></div>';
	}

	/**
	 * @param \WP_Post $item
	 *
	 * @return bool
	 */
	private function is_danger( $item ) {
		return in_array( 'is-danger', (array) $item->classes, true );
	}
}
