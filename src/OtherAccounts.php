<?php
/**
 * @package Daan\EDD\BetterAccount
 * @author  Daan van den Bergh
 * @url https://daan.dev
 * @license MIT
 */

namespace Daan\EDD\BetterAccount;

/**
 * Customers who bought with different email addresses over the years (e.g. john@company.com and sales@company.com)
 * sometimes can't find their orders. This tells them about their other accounts with the same email domain, with a
 * username hint, so they can log in with that one.
 */
class OtherAccounts {
	private $text_domain = 'edd-better-account';

	/**
	 * @var Tabs
	 */
	private $tabs;

	/**
	 * At most this many hints are shown.
	 */
	const MAX = 3;

	/**
	 * Email providers anyone can sign up with: customers with the same domain aren't related.
	 */
	const PUBLIC_DOMAINS = [
		'a1.net',
		'alice.it',
		'aol.com',
		'aon.at',
		'arcor.de',
		'berlin.de',
		'bluewin.ch',
		'chello.at',
		'email.de',
		'euronet.de',
		'ewe.net',
		'ewetel.net',
		'free.fr',
		'freenet.de',
		'gmail.com',
		'gmx.at',
		'gmx.ch',
		'gmx.com',
		'gmx.de',
		'gmx.fr',
		'gmx.net',
		'googlemail.com',
		'hetnet.nl',
		'hey.com',
		'home.nl',
		'hotmail.co.uk',
		'hotmail.com',
		'hotmail.de',
		'hotmail.es',
		'hotmail.fr',
		'hotmail.it',
		'icloud.com',
		'kpnmail.nl',
		'laposte.net',
		'libero.it',
		'live.co.uk',
		'live.com',
		'live.de',
		'live.fr',
		'live.it',
		'live.nl',
		'mac.com',
		'magenta.de',
		'mail.com',
		'mail.de',
		'mail.ru',
		'mailbox.org',
		'me.com',
		'msn.com',
		'o2.pl',
		'onet.pl',
		'online.de',
		'orange.fr',
		'orange.nl',
		'outlook.com',
		'outlook.de',
		'outlook.fr',
		'passmail.com',
		'planet.nl',
		'pm.me',
		'posteo.de',
		'proton.me',
		'protonmail.com',
		'qq.com',
		'riseup.net',
		'seznam.cz',
		'sfr.fr',
		'skynet.be',
		't-online.de',
		'telenet.be',
		'telfort.nl',
		'tiscali.it',
		'tuta.io',
		'tutanota.com',
		'vodafone.de',
		'wanadoo.fr',
		'web.de',
		'wp.pl',
		'xs4all.nl',
		'yahoo.co.uk',
		'yahoo.com',
		'yahoo.de',
		'yahoo.es',
		'yahoo.fr',
		'yahoo.it',
		'yandex.com',
		'yandex.ru',
		'ymail.com',
		'ziggo.nl',
		'zoho.com',
	];

	/**
	 * Build class.
	 *
	 * @param Tabs $tabs
	 */
	public function __construct( $tabs ) {
		$this->tabs = $tabs;
	}

	/**
	 * A notice with a username hint for each other account of the current user's email domain.
	 *
	 * @return string HTML, empty if there are no other accounts.
	 */
	public function get_notice() {
		$user   = wp_get_current_user();
		$domain = $this->get_domain( $user->user_email );

		if ( ! $user->exists() || ! $domain || $this->is_public_domain( $domain ) ) {
			return '';
		}

		$hints = array_map( [ $this, 'get_hint' ], $this->get_other_emails( $user, $domain ) );

		if ( ! $hints ) {
			return '';
		}

		$link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( wp_logout_url( $this->get_login_url() ) ),
			esc_html( _n( 'log into the other account', 'log into one of the other accounts', count( $hints ), $this->text_domain ) )
		);

		$message = sprintf(
			/* translators: 1: the other account(s), e.g. joh***doe@company.com, 2: link to log out and log into the other account(s) */
			_n(
				'A similar account (%1$s) was found. If you have trouble finding your orders, license keys and/or files under this account, %2$s.',
				'Similar accounts (%1$s) were found. If you have trouble finding your orders, license keys and/or files under this account, %2$s.',
				count( $hints ),
				$this->text_domain
			),
			implode( ', ', array_map( fn( $hint ) => '<strong>' . esc_html( $hint ) . '</strong>', $hints ) ),
			$link
		);

		return '<div class="edd-alert edd-alert-info eba-other-accounts"><p>' . $message . '</p></div>';
	}

	/**
	 * Where customers log in after logging out: EDD's login page (Settings > General > Pages > Login Page), or else the
	 * first tab (e.g. the profile page, which shows a login form to logged out visitors), or else the login URL.
	 *
	 * @return string
	 */
	private function get_login_url() {
		$url = function_exists( 'edd_get_login_page_uri' ) ? edd_get_login_page_uri() : false;

		if ( ! $url ) {
			$first = $this->tabs->get_items()[ 0 ] ?? null;
			$url   = $first && strpos( $first->url, 'action=logout' ) === false ? $first->url : '';
		}

		/**
		 * Where customers are sent after logging out to log into their other account. A URL on another host (e.g. an SSO
		 * login page) must be allowed with the allowed_redirect_hosts filter, or WordPress redirects to the admin instead.
		 *
		 * @param string $url
		 */
		return apply_filters( 'edd_better_account_other_account_login_url', $url ?: wp_login_url() );
	}

	/**
	 * Email addresses of other customers with orders, with this domain, who can log in.
	 *
	 * @param \WP_User $user
	 * @param string   $domain
	 *
	 * @return string[]
	 */
	private function get_other_emails( $user, $domain ) {
		if ( ! function_exists( 'edd_get_customers' ) ) {
			return [];
		}

		$emails = [];
		$batch  = 50;
		$offset = 0;

		// The search also matches e.g. @company.com.au, and not every match has orders, so look further until enough are found.
		do {
			$customers = edd_get_customers(
				[
					'search'          => '@' . $domain,
					'search_columns'  => [ 'email' ],
					'user_id__not_in' => [ 0, $user->ID ],
					'number'          => $batch,
					'offset'          => $offset,
					'orderby'         => 'date_created',
					'order'           => 'DESC',
				]
			);

			foreach ( $customers as $customer ) {
				$other = get_userdata( $customer->user_id );

				if ( ! $other || $customer->purchase_count < 1 || $this->get_domain( $other->user_email ) !== $domain ) {
					continue;
				}

				$emails[ strtolower( $other->user_email ) ] = $other->user_email;

				if ( count( $emails ) >= self::MAX ) {
					break 2;
				}
			}

			$offset += $batch;
		} while ( count( $customers ) === $batch );

		return array_values( $emails );
	}

	/**
	 * Only the first and last 3 characters before the @ are shown, e.g. joh***doe@company.com. Shorter names show less.
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	private function get_hint( $email ) {
		[ $name, $domain ] = explode( '@', $email, 2 );

		$length = mb_strlen( $name );
		$shown  = $length > 6 ? 3 : ( $length > 2 ? 1 : 0 );
		$hint   = $shown ? mb_substr( $name, 0, $shown ) . '***' . mb_substr( $name, -$shown ) : mb_substr( $name, 0, 1 ) . '***';

		return $hint . '@' . $domain;
	}

	/**
	 * @param string $email
	 *
	 * @return string Lowercase, empty if it's not an email address.
	 */
	private function get_domain( $email ) {
		$at = strrpos( (string) $email, '@' );

		return $at === false ? '' : strtolower( substr( $email, $at + 1 ) );
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function is_public_domain( $domain ) {
		/**
		 * Email domains for which no other accounts are looked up, because anyone can sign up with them.
		 *
		 * @param string[] $domains
		 */
		$domains = apply_filters( 'edd_better_account_public_email_domains', self::PUBLIC_DOMAINS );

		return in_array( $domain, $domains, true );
	}
}
