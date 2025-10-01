<?php
/**
 * Sidebar template for Access Defender settings page
 *
 * @package AccessDefender
 * @since 1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Retrieve Gravatar profile data.
 *
 * This function fetches and returns Gravatar profile data in JSON format
 * for a specific hash. If an error occurs during the API request, it logs
 * the error message and returns false.
 *
 * @return mixed The Gravatar profile data as an object if successful, false otherwise.
 */
function get_gravatar_data() {
	$hash    = '038022ec95fb7284e079b88c50216047';
	$api_url = "https://en.gravatar.com/{$hash}.json";

	$response = wp_remote_get( $api_url );
	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body );

	if ( ! empty( $data->entry[0] ) ) {
		return $data->entry[0];
	}

	return false;
}

// Get and cache profile data.
$profile = get_transient( 'gravatar_profile_data' );
if ( false === $profile ) {
	$profile = get_gravatar_data();
	if ( $profile ) {
		set_transient( 'gravatar_profile_data', $profile, HOUR_IN_SECONDS );
	}
}
?>

<div class="access-defender-about">
	<?php if ( $profile ) : ?>
		<div class="sidebar-section developer-profile">
			<h3><?php esc_html_e( 'Developer Profile', 'access-defender' ); ?></h3>

			<!-- Profile Image -->
			<div class="profile-image">
				<a href="<?php echo esc_url( $profile->accounts[0]->url ); ?>" target="_blank" rel="noopener noreferrer">
					<img src="<?php echo esc_url( $profile->thumbnailUrl ); ?>?s=200" 
						alt="<?php echo esc_attr( $profile->displayName ); ?>">
				</a>
			</div>

			<!-- Profile Name -->
			<h3 class="developer-name"><?php echo esc_html( $profile->displayName ); ?></h3>

			<!-- Location -->
			<?php if ( ! empty( $profile->currentLocation ) ) : ?>
				<div class="profile-location">
					<span class="dashicons dashicons-location"></span>
					<?php echo esc_html( $profile->currentLocation ); ?>
				</div>
			<?php endif; ?>

			<!-- Social Links -->
			<div class="social-links">
				<?php
				// First display the API-based accounts.
				if ( ! empty( $profile->accounts ) ) :
					foreach ( $profile->accounts as $account ) :
						$icon_html = '';
						if ( isset( $account->url ) ) {
							$parsed_url  = wp_parse_url( $account->url );
							$site_domain = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';

							if ( 'huzaifa.im' === $site_domain ) {
								$icon_html = '<span class="dashicons dashicons-admin-site-alt3"></span>';
							} elseif ( ! empty( $account->iconUrl ) ) {
								$icon_html = sprintf(
									'<img src="%s" alt="%s" class="social-icon">',
									esc_url( $account->iconUrl ),
									esc_attr( $account->name )
								);
							} else {
								$icon_html = '<span class="dashicons dashicons-admin-links"></span>';
							}
							?>
							<a href="<?php echo esc_url( $account->url ); ?>" 
								class="social-link" 
								target="_blank"
								rel="noopener noreferrer" 
								title="<?php echo esc_attr( $account->display ); ?>">
								<?php
								echo wp_kses(
									$icon_html,
									array(
										'span' => array(
											'class' => array(),
										),
										'img'  => array(
											'src'   => array(),
											'alt'   => array(),
											'class' => array(),
										),
									)
								);
								?>
							</a>
							<?php
						}
					endforeach;
				endif;
				?>

				<!-- Manually added WordPress profile -->
				<a href="https://profiles.wordpress.org/huzaifaalmesbah" 
					class="social-link" 
					target="_blank"
					rel="noopener noreferrer" 
					title="<?php echo esc_attr__( 'Huzaifa Al Mesbah', 'access-defender' ); ?>">
					<span class="wordpress-icon dashicons dashicons-wordpress"></span>
				</a>
			</div>
		</div>

		<!-- Quick Links Section -->
		<div class="sidebar-section">
			<h3><?php esc_html_e( 'Quick Links', 'access-defender' ); ?></h3>
			<div class="quick-links">
				<a href="https://wordpress.org/plugins/access-defender/#description" 
					class="quick-link" 
					target="_blank" 
					rel="noopener noreferrer">
					<span class="dashicons dashicons-book"></span>
					<?php esc_html_e( 'Documentation', 'access-defender' ); ?>
				</a>
				<a href="https://wordpress.org/support/plugin/access-defender/" 
					class="quick-link" 
					target="_blank" 
					rel="noopener noreferrer">
					<span class="dashicons dashicons-editor-help"></span>
					<?php esc_html_e( 'Support', 'access-defender' ); ?>
				</a>
				<a href="https://wordpress.org/support/plugin/access-defender/reviews/" 
					class="quick-link" 
					target="_blank" 
					rel="noopener noreferrer">
					<span class="dashicons dashicons-star-filled"></span>
					<?php esc_html_e( 'Rate Plugin', 'access-defender' ); ?>
				</a>
			</div>
		</div>
	<?php endif; ?>
</div>