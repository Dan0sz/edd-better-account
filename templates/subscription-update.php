<?php
/**
 * EDD Recurring's Update Payment Method page, with the account tabs.
 *
 * @see edd-recurring/templates/subscription-update-default.php
 */

defined( 'ABSPATH' ) || exit;

get_header();

$token = sanitize_text_field( get_query_var( 'edd_recurring_update' ) );
$page  = new \EDD\Recurring\Subscriptions\Update\Page();

ob_start();
?>
<div class="edd-recurring__update--page">
	<header class="edd-recurring__update--header">
		<h1><?php esc_html_e( 'Update Payment Method', 'edd-recurring' ); ?></h1>
	</header>

	<div class="edd-recurring__update--content">
		<?php $page->render_update_content( $token ); ?>
	</div>
</div>
<?php
$content = ob_get_clean();
?>
<div class="eba-page">
	<?php echo daan_load_edd_better_account()->wrap( $content ); ?>
</div>
<?php
get_footer();
