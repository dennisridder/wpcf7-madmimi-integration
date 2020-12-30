<?php

namespace DennisRidder\Integrations\WPCF7;

class Connector {
    protected $service;
    protected $defaults = [
        'list'              => '',
        'firstname_field'   => '',
        'lastname_field'    => '',
        'email_field'       => '',
    ];

    /**
     * @param MadMimi $api
     * @param \Wpcf7_Service $service
     */
    public function __construct( $service ) {

        $this->service = $service;

        add_action( 'wpcf7_init', array( $this, 'register_service' ) );

        if ( ! $this->service->is_active() ) {
            return;
        }

        add_filter( 'wpcf7_editor_panels', array( $this, 'editor_panels' ) );
        add_filter( 'wpcf7_contact_form_properties', array( $this, 'add_properties_array_key' ), 10, 2 );
        
        add_action( 'wpcf7_save_contact_form', array( $this, 'save_contact_form' ), 10, 3 );
        add_action( 'wpcf7_before_send_mail', array( $this, 'subscribe_to_list' ), 10, 3 );
    }

    public function editor_panels( $panels ) {

        $panels[ 'madmimi-panel' ] = [
            'title'     => 'MadMimi',
            'callback'  => [ $this, 'panel' ],
        ];

        return $panels;
    }

    public function panel( $contact_form ) {

        $listsXml = $this->service->get_api()->Lists();
        $lists = simplexml_load_string( $listsXml );

        $config = $contact_form->get_properties();
        $fields = $contact_form->collect_mail_tags();
        $props = wp_parse_args( $config['madmimi'], $this->defaults );

        ?>

        <h2>MadMimi</h2>

        <p>Hint: To disable Contact Form 7 e-mails after submission, add <span style="font-family: Courier; display: inline-block; padding: 3px 5px; background: #fff; box-shadow: 1px 1px 1px #ddd;">skip_mail: on</span> to the <a href="#additional-settings-panel-tab">Additional settings</a>.</p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="madmimi-list"><?php _e('List', 'dennisridder'); ?></label></th>
                    <td>
                        <select id="madmimi-list" name="wpcf7-madmimi[list]" data-config-field="madmimi.list">
                            <option value=""><?php _e( '-- Select a list --', 'dennisridder' ); ?></option>
                            <?php foreach( $lists as $list ) : $attributes = $list->attributes(); ?>
                            <option value="<?php echo $attributes->id; ?>" <?php selected( $attributes->id, $props['list'] ); ?>><?php esc_attr_e( $attributes->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="madmimi-firstname-field"><?php _e('First name field', 'dennisridder'); ?></label></th>
                    <td>
                        <select id="madmimi-firstname-field" name="wpcf7-madmimi[firstname_field]" data-config-field="madmimi.firstname_field">
                            <option value=""><?php _e( '-- Select a field --', 'dennisridder' ); ?></option>
                            <?php foreach( $fields as $field ) : ?>
                            <option value="<?php echo $field; ?>" <?php selected( $field, $props['firstname_field'] ); ?>><?php esc_attr_e( $field ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="madmimi-lastname-field"><?php _e('Last name field', 'dennisridder'); ?></label></th>
                    <td>
                        <select id="madmimi-lastname-field" name="wpcf7-madmimi[lastname_field]" data-config-field="madmimi.lastname_field">
                            <option value=""><?php _e( '-- Select a field --', 'dennisridder' ); ?></option>
                            <?php foreach( $fields as $field ) : ?>
                            <option value="<?php echo $field; ?>" <?php selected( $field, $props['lastname_field'] ); ?>><?php esc_attr_e( $field ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('If you are using one field with a full name, we suggest to use the `first name` field only.', 'dennisridder'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="madmimi-email-field"><?php _e('E-mail field', 'dennisridder'); ?></label></th>
                    <td>
                        <select id="madmimi-email-field" name="wpcf7-madmimi[email_field]" data-config-field="madmimi.email_field">
                            <option value=""><?php _e( '-- Select a field --', 'dennisridder' ); ?></option>
                            <?php foreach( $fields as $field ) : ?>
                            <option value="<?php echo $field; ?>" <?php selected( $field, $props['email_field'] ); ?>><?php esc_attr_e( $field ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <?php /* <tr>
                    <th><label for="madmimi-phone-field"><?php _e('Phone field', 'dennisridder'); ?></label></th>
                    <td>
                        <select id="madmimi-phone-field" name="wpcf7-madmimi[phone_field]" data-config-field="madmimi.phone_field">
                            <option value=""><?php _e( '-- Select a field --', 'dennisridder' ); ?></option>
                            <?php foreach( $fields as $field ) : ?>
                            <option value="<?php echo $field; ?>" <?php selected( $field, $props['phone_field'] ); ?>><?php esc_attr_e( $field ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr> */ ?>
            </tbody>
        </table>

        <div>
        </div>
    <?php
    }

    public function save_contact_form( $contact_form, $args, $context ) {

        if ( $context == 'save' ) {

            $post_id = $contact_form->id();

            $props = array_filter( $args['wpcf7-madmimi'] );
            $props = array_intersect_key( $props, $this->defaults );

            update_post_meta( $post_id, '_madmimi', $props );
        }

        return $contact_form;
    }

    public function add_properties_array_key( $properties, \WPCF7_ContactForm $form ) {

        $properties['madmimi'] = get_post_meta( $form->id(), '_madmimi', true );

        return $properties;
    }

    public function subscribe_to_list( $contact_form, $abort, $submission ) {

        $config = $contact_form->get_properties();
        $config = $config['madmimi'];

        if ( ! $this->service->is_active() ) {
            return;
        }

        // List id required
        if ( ! isset( $config['list'] ) || $config['list'] == '' ) {
            return;
        }

        // Email field required
        if ( ! isset( $config['email_field'] ) || $config['email_field'] == '' ) {
            return;
        }

        $user = array(
			'email'		=> sanitize_email( $_POST[ $config['email_field'] ] ),
			'firstName'	=> ! empty( $config['firstname_field'] ) ? esc_html( $_POST[ $config['firstname_field'] ] ) : '',
			'lastName'	=> ! empty( $config['lastname_field'] ) ? esc_html( $_POST[ $config['lastname_field'] ] ) : '',
			'add_list'	=> $config['list'],
		);

		$response = $this->service->get_api()->AddUser( $user );
    }

    public function register_service() {

        $integration = \WPCF7_Integration::get_instance();
        $integration->add_category( 'email', __( 'MadMimi', 'dennisridder' ) );
        $integration->add_service( 'madmimi', $this->service );
    }
}
