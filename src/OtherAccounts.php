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
	 * At most this many hints are shown.
	 */
	const MAX = 3;

	/**
	 * Email providers anyone can sign up with: customers with the same domain aren't related.
	 */
	const PUBLIC_DOMAINS = [
		'aol.com',
		'gmail.com',
		'googlemail.com',
		'gmx.com',
		'gmx.de',
		'gmx.net',
		'hey.com',
		'hotmail.com',
		'hotmail.co.uk',
		'icloud.com',
		'live.com',
		'live.nl',
		'mac.com',
		'mail.com',
		'mail.ru',
		'me.com',
		'msn.com',
		'outlook.com',
		'proton.me',
		'protonmail.com',
		'qq.com',
		'web.de',
		'yahoo.com',
		'yahoo.co.uk',
		'yandex.com',
		'yandex.ru',
		'zoho.com',
		'kpnmail.nl',
		'ziggo.nl',
		'hetnet.nl',
		'planet.nl',
		'home.nl',
		'xs4all.nl',
		'telenet.be',
		'skynet.be',
		'orange.fr',
		'free.fr',
		'laposte.net',
		't-online.de',
		'libero.it',
	];

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

		$message = sprintf(
			/* translators: 1: email domain, e.g. @company.com, 2: username hint(s), e.g. joh***doe@company.com */
			_n(
				'Another account ending with %1$s was found. If you can\'t find your orders under this account, try logging in with the other account. Username hint: %2$s.',
				'Other accounts ending with %1$s were found. If you can\'t find your orders under this account, try logging in with one of the other accounts. Username hints: %2$s.',
				count( $hints ),
				$this->text_domain
			),
			'<strong>@' . esc_html( $domain ) . '</strong>',
			implode( ', ', array_map( fn( $hint ) => '<strong>' . esc_html( $hint ) . '</strong>', $hints ) )
		);

		return '<div class="edd-alert edd-alert-info eba-other-accounts"><p>' . $message . '</p></div>';
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

		$customers = edd_get_customers(
			[
				'search'          => '@' . $domain,
				'search_columns'  => [ 'email' ],
				'user_id__not_in' => [ 0, $user->ID ],
				'number'          => 50,
				'orderby'         => 'date_created',
				'order'           => 'DESC',
			]
		);

		$emails = [];

		foreach ( $customers as $customer ) {
			$other = get_userdata( $customer->user_id );

			if ( ! $other || $customer->purchase_count < 1 || $this->get_domain( $other->user_email ) !== $domain ) {
				continue;
			}

			$emails[ strtolower( $other->user_email ) ] = $other->user_email;

			if ( count( $emails ) >= self::MAX ) {
				break;
			}
		}

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
