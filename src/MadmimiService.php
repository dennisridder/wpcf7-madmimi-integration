<?php

namespace DennisRidder\Integrations\WPCF7;

class MadMimi_Service extends \WPCF7_Service {

	private static $instance;
    protected $email = false;
    protected $api_key = false;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		$this->email = \WPCF7::get_option( 'madmimi_email' );
		$this->api_key = \WPCF7::get_option( 'madmimi_api_key' );
	}

	public function get_title() {
		return __( 'MadMimi', 'dennisridder' );
	}

	public function is_active() {

        $email = $this->get_email();
        $api_key = $this->get_api_key();

		return $email && $api_key;
	}

	public function get_api() {
		return new \MadMimi( $this->get_email(), $this->get_api_key() );
	}

	public function get_categories() {
		return array( 'email' );
	}

	public function icon() {
	}

	public function link() {
		echo wpcf7_link(
			'https://madmimi.com',
			'madmimi.com'
		);
	}

	public function get_email() {
		return $this->email;
    }
    
	public function get_api_key() {
		return $this->api_key;
	}

	protected function log( $url, $request, $response ) {
		wpcf7_log_remote_request( $url, $request, $response );
	}

	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wpcf7-integration', false );
		$url = add_query_arg( array( 'service' => 'madmimi' ), $url );

		if ( ! empty( $args) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	protected function save_data() {
		\WPCF7::update_option( 'madmimi_email', $this->email );
		\WPCF7::update_option( 'madmimi_api_key', $this->api_key );
	}

	protected function reset_data() {
		$this->email = false;
		$this->api_key = false;
		$this->save_data();
	}

	public function load( $action = '' ) {
		if ( 'setup' == $action and 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wpcf7-madmimi-setup' );

			if ( ! empty( $_POST['reset'] ) ) {
				$this->reset_data();
				$redirect_to = $this->menu_page_url( 'action=setup' );
			} else {
				$email = isset( $_POST['email'] ) ? trim( $_POST['email'] ) : '';
				$api_key = isset( $_POST['api_key'] ) ? trim( $_POST['api_key'] ) : '';

				if ( $email and $api_key ) {
					$this->email = $email;
					$this->api_key = $api_key;
					$this->save_data();

					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );
				} else {
					$redirect_to = $this->menu_page_url( array(
						'action' => 'setup',
						'message' => 'invalid',
					) );
				}
			}

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	public function admin_notice( $message = '' ) {
		if ( 'invalid' == $message ) {
			echo sprintf(
				'<div class="error notice notice-error is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "ERROR", 'contact-form-7' ) ),
				esc_html( __( "Invalid API settings.", 'contact-form-7' ) ) );
		}

		if ( 'success' == $message ) {
			echo sprintf( '<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( __( 'Settings saved.', 'contact-form-7' ) ) );
		}
	}

	public function display( $action = '' ) {
		echo '<p>' . sprintf(
			esc_html( __( 'This madmimi integration lets you subscribe users to your newsletter. For more information, please visit %s.', 'contact-form-7' ) ),
			wpcf7_link(
				__( 'https://madmimi.com/', 'contact-form-7' ),
				__( 'madmimi.com', 'contact-form-7' )
			)
		) . '</p>';

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( "MadMimi is active on this site.", 'contact-form-7' ) )
			);
		}

		if ( 'setup' == $action ) {
			$this->display_setup();
		} else {
			echo sprintf(
				'<p><a href="%1$s" class="button">%2$s</a></p>',
				esc_url( $this->menu_page_url( 'action=setup' ) ),
				esc_html( __( 'Setup Integration', 'contact-form-7' ) )
			);
		}
	}

	private function display_setup() {
		$email 		= $this->is_active() ? $this->get_email() : '';
		$api_key 	= $this->is_active() ? $this->get_api_key() : '';

?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
<?php wp_nonce_field( 'wpcf7-madmimi-setup' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="email"><?php echo esc_html( __( 'Email', 'contact-form-7' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( $email );
			echo sprintf(
				'<input type="hidden" value="%1$s" id="email" name="email" />',
				esc_attr( $email )
			);
		} else {
			echo sprintf(
				'<input type="email" aria-required="true" value="%1$s" id="email" name="email" class="regular-text code" placeholder="your@email.com" />',
				esc_attr( $email )
			);
		}
	?></td>
</tr>
<tr>
	<th scope="row"><label for="api_key"><?php echo esc_html( __( 'API key', 'contact-form-7' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( wpcf7_mask_password( $api_key ) );
			echo sprintf(
				'<input type="hidden" value="%1$s" id="api_key" name="api_key" />',
				esc_attr( $api_key )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%1$s" id="api_key" name="api_key" class="regular-text code" />',
				esc_attr( $api_key )
			);
		}
	?></td>
</tr>
</tbody>
</table>
<?php
		if ( $this->is_active() ) {
			if ( $this->get_email() && $this->get_api_key() ) {
				submit_button(
					_x( 'Remove Keys', 'API keys', 'contact-form-7' ),
					'small', 'reset'
				);
			}
		} else {
			submit_button( __( 'Save Changes', 'contact-form-7' ) );
		}
?>
</form>
<?php
	}
}
