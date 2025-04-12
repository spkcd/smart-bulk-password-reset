<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Smart_Bulk_Password_Reset
 * @subpackage Smart_Bulk_Password_Reset/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    Smart_Bulk_Password_Reset
 * @subpackage Smart_Bulk_Password_Reset/admin
 * @author     Cline <your-email@example.com>
 */
class Smart_Bulk_Password_Reset_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_management_page(
			__( 'Smart Bulk Password Reset', 'smart-bulk-password-reset' ), // Page title
			__( 'Password Reset Tool', 'smart-bulk-password-reset' ),    // Menu title
			'manage_options', // Capability required
			$this->plugin_name, // Menu slug
			array( $this, 'display_plugin_admin_page' ) // Function to display the page
		);
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'tools.php?page=' . $this->plugin_name ) . '">' . __( 'Settings', 'smart-bulk-password-reset' ) . '</a>',
		);
		return array_merge( $settings_link, $links );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/smart-bulk-password-reset-admin-display.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_styles( $hook_suffix ) {
		// Only load on our plugin's page (tools_page_smart-bulk-password-reset)
        $expected_hook = 'tools_page_' . $this->plugin_name;
        if ( $hook_suffix == $expected_hook ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/smart-bulk-password-reset-admin.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
     * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
        // Only load on our plugin's page
        $expected_hook = 'tools_page_' . $this->plugin_name;
		if ( $hook_suffix == $expected_hook ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/smart-bulk-password-reset-admin.js', array( 'jquery', 'wp-tinymce' ), $this->version, true ); // Ensure 'wp-tinymce' is a dependency

            // Localize script for AJAX
            wp_localize_script( $this->plugin_name, 'sbpr_admin_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'sbpr_admin_nonce' )
            ));

            // Ensure TinyMCE is initialized for our editor
            wp_enqueue_editor();
		}
	}

	/**
	 * AJAX handler to get users based on selected role.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_users_by_role() {
		check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );

		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';

		if ( empty( $role ) ) {
			wp_send_json_error( 'Role not specified.' );
		}

		$users = get_users( array( 'role' => $role, 'fields' => array( 'ID', 'user_login', 'user_email' ) ) );

		if ( empty( $users ) ) {
			wp_send_json_success( array( 'message' => 'No users found for this role.' ) );
		} else {
			wp_send_json_success( $users );
		}
	}

	/**
	 * Handle the form submission for sending password reset emails.
	 *
	 * @since 1.0.0
	 */
	public function handle_send_reset_emails() {
		// Verify nonce
		if ( ! isset( $_POST['sbpr_send_reset_nonce'] ) || ! wp_verify_nonce( $_POST['sbpr_send_reset_nonce'], 'sbpr_send_reset_action' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to perform this action.' );
		}

		$user_ids = isset( $_POST['sbpr_users'] ) ? array_map( 'intval', $_POST['sbpr_users'] ) : array();
		$subject = isset( $_POST['sbpr_email_subject'] ) ? sanitize_text_field( $_POST['sbpr_email_subject'] ) : '';
		// Use wp_kses_post for the email body to allow HTML but sanitize it
		$message_template = isset( $_POST['sbpr_email_body'] ) ? wp_kses_post( stripslashes( $_POST['sbpr_email_body'] ) ) : '';

		if ( empty( $user_ids ) ) {
			add_settings_error( 'sbpr_messages', 'sbpr_no_users', 'No users selected.', 'error' );
			wp_redirect( admin_url( 'tools.php?page=' . $this->plugin_name ) );
			exit;
		}

		if ( empty( $subject ) || empty( $message_template ) ) {
			add_settings_error( 'sbpr_messages', 'sbpr_empty_fields', 'Email subject and body cannot be empty.', 'error' );
			wp_redirect( admin_url( 'tools.php?page=' . $this->plugin_name ) );
			exit;
		}

		$sent_count = 0;
        $log_entries = array(); // Array to hold data for logging
		$headers = array('Content-Type: text/html; charset=UTF-8');
        $current_time = current_time( 'mysql' ); // Get WordPress current time for timestamp

		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue; // Skip if user doesn't exist
			}

			// Generate a new secure password
			$new_password = wp_generate_password( 12, true, true );

            // Prepare email content FIRST (before setting password, in case mail fails)
			$message = str_replace(
				array( '{user_name}', '{user_email}', '{new_password}' ),
				array( $user->user_login, $user->user_email, $new_password ),
				$message_template
			);

			// Send the email
			$mail_sent = wp_mail( $user->user_email, $subject, $message, $headers );

			// Only if email is sent successfully, update password and prepare log entry
			if ( $mail_sent ) {
                // Update the user's password
			    wp_set_password( $new_password, $user_id );
				$sent_count++;
                // Add data to log array
                $log_entries[] = array(
                    'timestamp' => $current_time,
                    'username'  => $user->user_login,
                    'email'     => $user->user_email,
                    'password'  => $new_password // Log the actual password sent
                );
			}
            // If mail fails, password is not set, and nothing is logged for this user.
		}

        // --- CSV Logging ---
        if ( ! empty( $log_entries ) ) {
            $upload_dir = wp_upload_dir();
            // Check if basedir is writable, though wp_upload_dir usually handles this.
            if ( ! $upload_dir['error'] ) {
                $log_dir = $upload_dir['basedir'] . '/password-reset-logs';
                $log_file_path = $log_dir . '/password_reset_log_' . date('Y-m-d') . '.csv';

                // Ensure log directory exists and is protected
                if ( ! file_exists( $log_dir ) ) {
                    wp_mkdir_p( $log_dir );
                    // Add index.php and .htaccess for security
                    if ( ! file_exists( $log_dir . '/index.php' ) ) {
                        @file_put_contents( $log_dir . '/index.php', '<?php // Silence is golden.' );
                    }
                    if ( ! file_exists( $log_dir . '/.htaccess' ) ) {
                        @file_put_contents( $log_dir . '/.htaccess', "Options -Indexes\ndeny from all" ); // More robust .htaccess
                    }
                }

                // Check if header needs to be written
                $write_header = ! file_exists( $log_file_path ) || filesize( $log_file_path ) === 0;

                // Open file for appending
                $log_handle = @fopen( $log_file_path, 'a' );

                if ( $log_handle ) {
                    // Write header if needed
                    if ( $write_header ) {
                        fputcsv( $log_handle, array( 'Timestamp', 'Username', 'Email', 'New Password' ) );
                    }

                    // Write log data from our array
                    foreach ( $log_entries as $entry ) {
                        fputcsv( $log_handle, array(
                            $entry['timestamp'],
                            $entry['username'],
                            $entry['email'],
                            $entry['password'] // Write the actual password
                        ) );
                    }

                    @fclose( $log_handle );

                } else {
                    // Add error notice if log file cannot be opened
                    add_settings_error(
                        'sbpr_messages',
                        'sbpr_log_error',
                        'Warning: Could not open log file for writing: ' . esc_html($log_file_path), // Escaped path
                        'warning'
                    );
                }
            } else {
                 // Error getting upload directory
                 add_settings_error(
                    'sbpr_messages',
                    'sbpr_upload_dir_error',
                    'Error: Could not determine upload directory. Logging failed. ' . esc_html($upload_dir['error']),
                    'error'
                );
            }
        }
         // --- End CSV Logging ---


		// Add admin notice (updated message)
		$notice_message = sprintf( 'Successfully sent %d password reset emails.', $sent_count );
        if (!empty($log_entries)) {
            $notice_message .= ' Log file updated.';
        }
        add_settings_error(
			'sbpr_messages',
			'sbpr_emails_sent',
            $notice_message,
			'success'
		);


		// Redirect back to the settings page
		wp_redirect( admin_url( 'tools.php?page=' . $this->plugin_name ) );
		exit;
	}

    /**
	 * AJAX handler to send a test email.
	 *
	 * @since 1.0.0
	 */
	public function ajax_send_test_email() {
		check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );

        // Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.' );
		}

        $to_email = isset( $_POST['test_email'] ) ? sanitize_email( $_POST['test_email'] ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
        // Use wp_kses_post for the email body to allow HTML but sanitize it
		$message_template = isset( $_POST['body'] ) ? wp_kses_post( stripslashes( $_POST['body'] ) ) : '';

        if ( ! is_email( $to_email ) ) {
            wp_send_json_error( 'Invalid email address provided.' );
        }

        if ( empty( $subject ) || empty( $message_template ) ) {
			wp_send_json_error( 'Email subject and body cannot be empty.' );
		}

        // Use dummy data for placeholders
        $dummy_user_name = 'John Doe';
        $dummy_user_email = 'john.doe@example.com';
        $dummy_password = 'password123';

        // Prepare email content
        $message = str_replace(
            array( '{user_name}', '{user_email}', '{new_password}' ),
            array( $dummy_user_name, $dummy_user_email, $dummy_password ),
            $message_template
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send the email
        $mail_sent = wp_mail( $to_email, $subject, $message, $headers );

        if ( $mail_sent ) {
            wp_send_json_success( 'Test email sent successfully to ' . $to_email );
        } else {
            global $ts_mail_errors; // Attempt to get more detailed error info if available
            $error_message = 'Failed to send test email.';
            if (!empty($ts_mail_errors)) {
                 $error_message .= ' Error: ' . implode(', ', $ts_mail_errors);
            }
            wp_send_json_error( $error_message );
        }
    }

    /** Helper function to get templates */
    private function get_templates() {
        return get_option( 'sbpr_email_templates', array() );
    }

    /** Helper function to save templates */
    private function save_templates( $templates ) {
        update_option( 'sbpr_email_templates', $templates );
    }

    /**
	 * AJAX handler to load a specific email template.
	 *
	 * @since 1.0.0
	 */
    public function ajax_load_template() {
        check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.' );
		}

        $template_id = isset( $_POST['template_id'] ) ? sanitize_key( $_POST['template_id'] ) : '';

        if ( empty( $template_id ) ) {
            wp_send_json_error( 'No template ID provided.' );
        }

        // Handle default template request
        if ( $template_id === 'default' ) {
             $default_subject = get_option('sbpr_default_subject', 'Your New Password for {site_title}');
             $default_content = sprintf(
                __( "<p>Hi {user_name},</p>\n<p>Your password for %s has been reset.</p>\n<p>Your new password is: <strong>{new_password}</strong></p>\n<p>You can log in here: %s</p>\n<p>We recommend changing this password after logging in.</p>", 'smart-bulk-password-reset' ),
                get_bloginfo( 'name' ),
                wp_login_url()
            );
             $default_body = get_option( 'sbpr_default_email_body', $default_content );
             wp_send_json_success( array( 'subject' => $default_subject, 'body' => $default_body ) );
        }


        $templates = $this->get_templates();

        if ( isset( $templates[ $template_id ] ) ) {
            wp_send_json_success( $templates[ $template_id ] );
        } else {
            wp_send_json_error( 'Template not found.' );
        }
    }

    /**
	 * AJAX handler to save a new email template.
	 *
	 * @since 1.0.0
	 */
    public function ajax_save_template() {
        check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.' );
		}

        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
        $body = isset( $_POST['body'] ) ? wp_kses_post( stripslashes( $_POST['body'] ) ) : '';

        if ( empty( $name ) || empty( $subject ) || empty( $body ) ) {
            wp_send_json_error( 'Template name, subject, and body are required.' );
        }

        $templates = $this->get_templates();
        $new_id = uniqid( 'sbpr_' ); // Generate a unique ID

        $templates[ $new_id ] = array(
            'name'    => $name,
            'subject' => $subject,
            'body'    => $body,
        );

        $this->save_templates( $templates );

        wp_send_json_success( array( 'id' => $new_id, 'name' => $name, 'message' => 'Template saved successfully.' ) );
    }

    /**
	 * AJAX handler to update an existing email template.
	 *
	 * @since 1.0.0
	 */
    public function ajax_update_template() {
        check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.' );
		}

        $template_id = isset( $_POST['template_id'] ) ? sanitize_key( $_POST['template_id'] ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
        $body = isset( $_POST['body'] ) ? wp_kses_post( stripslashes( $_POST['body'] ) ) : '';

        if ( empty( $template_id ) || empty( $subject ) || empty( $body ) ) {
            wp_send_json_error( 'Template ID, subject, and body are required.' );
        }

        $templates = $this->get_templates();

        if ( ! isset( $templates[ $template_id ] ) ) {
             wp_send_json_error( 'Template not found.' );
        }

        // Update existing template (name remains the same)
        $templates[ $template_id ]['subject'] = $subject;
        $templates[ $template_id ]['body'] = $body;

        $this->save_templates( $templates );

        wp_send_json_success( 'Template updated successfully.' );
    }

     /**
	 * AJAX handler to delete an email template.
	 *
	 * @since 1.0.0
	 */
    public function ajax_delete_template() {
        check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.' );
		}

        $template_id = isset( $_POST['template_id'] ) ? sanitize_key( $_POST['template_id'] ) : '';

        if ( empty( $template_id ) ) {
            wp_send_json_error( 'No template ID provided.' );
        }

        $templates = $this->get_templates();

        if ( isset( $templates[ $template_id ] ) ) {
            unset( $templates[ $template_id ] );
            $this->save_templates( $templates );
            wp_send_json_success( 'Template deleted successfully.' );
        } else {
            wp_send_json_error( 'Template not found or already deleted.' );
        }
    }
}
