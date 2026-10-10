<?php
/**
 * Admin settings page template.
 *
 * @package QuickToolsForWooCommerce
 * @var array $settings Feature toggle values.
 * @var array $features Feature definitions.
 * @var int   $enabled  Count of enabled features.
 * @var int   $total    Total feature count.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use QTFW\Includes\Classes\Settings;
?>
<div class="wrap qtfw-settings">
	<div class="qtfw-settings__shell">
		<header class="qtfw-settings__hero">
			<div class="qtfw-settings__hero-copy">
				<p class="qtfw-settings__eyebrow"><?php esc_html_e( 'QuickTools for WooCommerce', 'quicktools-for-woocommerce' ); ?></p>
				<h1><?php esc_html_e( 'Control Center', 'quicktools-for-woocommerce' ); ?></h1>
				<p class="qtfw-settings__lede">
					<?php esc_html_e( 'Enable only the tools you need. Changes apply immediately after saving — no bloat on screens you turn off.', 'quicktools-for-woocommerce' ); ?>
				</p>
			</div>
			<div class="qtfw-settings__hero-stats" aria-label="<?php esc_attr_e( 'Feature status', 'quicktools-for-woocommerce' ); ?>">
				<div class="qtfw-settings__stat">
					<span class="qtfw-settings__stat-value"><?php echo esc_html( (string) $enabled ); ?>/<?php echo esc_html( (string) $total ); ?></span>
					<span class="qtfw-settings__stat-label"><?php esc_html_e( 'Features on', 'quicktools-for-woocommerce' ); ?></span>
				</div>
				<div class="qtfw-settings__stat">
					<span class="qtfw-settings__stat-value">v<?php echo esc_html( QTFW_VERSION ); ?></span>
					<span class="qtfw-settings__stat-label"><?php esc_html_e( 'Plugin version', 'quicktools-for-woocommerce' ); ?></span>
				</div>
			</div>
		</header>

		<?php settings_errors(); ?>

		<form method="post" action="options.php" class="qtfw-settings__form">
			<?php settings_fields( 'qtfw_settings_group' ); ?>

			<section class="qtfw-settings__section">
				<div class="qtfw-settings__section-head">
					<h2><?php esc_html_e( 'Features', 'quicktools-for-woocommerce' ); ?></h2>
					<p><?php esc_html_e( 'Toggle each module independently. Disabled features are not loaded.', 'quicktools-for-woocommerce' ); ?></p>
				</div>

				<div class="qtfw-settings__grid">
					<?php foreach ( $features as $key => $feature ) : ?>
						<?php
						$is_on      = ! empty( $settings[ $key ] );
						$field_id   = 'qtfw_feature_' . $key;
						$card_class = $is_on ? 'is-enabled' : 'is-disabled';
						?>
						<article class="qtfw-feature-card <?php echo esc_attr( $card_class ); ?>" data-qtfw-feature="<?php echo esc_attr( $key ); ?>">
							<div class="qtfw-feature-card__top">
								<span class="qtfw-feature-card__area"><?php echo esc_html( $feature['area'] ); ?></span>
								<label class="qtfw-switch" for="<?php echo esc_attr( $field_id ); ?>">
									<input
										type="checkbox"
										class="qtfw-switch__input"
										id="<?php echo esc_attr( $field_id ); ?>"
										name="<?php echo esc_attr( Settings::OPTION_KEY . '[' . $key . ']' ); ?>"
										value="1"
										<?php checked( $is_on, true ); ?>
									/>
									<span class="qtfw-switch__track" aria-hidden="true">
										<span class="qtfw-switch__thumb"></span>
									</span>
									<span class="screen-reader-text">
										<?php
										printf(
											/* translators: %s: feature title */
											esc_html__( 'Enable %s', 'quicktools-for-woocommerce' ),
											esc_html( $feature['title'] )
										);
										?>
									</span>
								</label>
							</div>
							<h3 class="qtfw-feature-card__title">
								<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $feature['title'] ); ?></label>
							</h3>
							<p class="qtfw-feature-card__desc"><?php echo esc_html( $feature['description'] ); ?></p>
							<p class="qtfw-feature-card__status">
								<span class="qtfw-feature-card__status-dot" aria-hidden="true"></span>
								<span
									class="qtfw-feature-card__status-text"
									data-on-label="<?php esc_attr_e( 'Enabled', 'quicktools-for-woocommerce' ); ?>"
									data-off-label="<?php esc_attr_e( 'Disabled', 'quicktools-for-woocommerce' ); ?>"
								>
									<?php echo $is_on ? esc_html__( 'Enabled', 'quicktools-for-woocommerce' ) : esc_html__( 'Disabled', 'quicktools-for-woocommerce' ); ?>
								</span>
							</p>
						</article>
					<?php endforeach; ?>
				</div>
			</section>

			<footer class="qtfw-settings__footer">
				<div class="qtfw-settings__footer-note">
					<?php esc_html_e( 'Only users with WooCommerce manage permission can change these settings.', 'quicktools-for-woocommerce' ); ?>
				</div>
				<?php
				submit_button(
					__( 'Save changes', 'quicktools-for-woocommerce' ),
					'primary qtfw-settings__save',
					'submit',
					false
				);
				?>
			</footer>
		</form>
	</div>
</div>
