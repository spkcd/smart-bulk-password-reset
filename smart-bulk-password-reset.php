<?php
/**
 * Plugin Name:       Smart Bulk Password Reset
 * Plugin URI:        https://example.com/plugins/smart-bulk-password-reset/
 * Description:       Allows administrators to bulk reset passwords for users based on their role, send custom email notifications, manage templates, and log resets.
 * Version:           1.0.0
 * Author:            SPARKWEB Studio
 * Author URI:        https://sparkwebstudio.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smart-bulk-password-reset
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants
 */
define( 'SBPR_VERSION', '1.1.0' );
define( 'SBPR_PLUGIN_FILE', __FILE__ ); // Use this for plugin_basename

// =========================================================================
// Class: Smart_Bulk_Password_Reset_Loader
// =========================================================================
/**
 * Register all actions and filters for the plugin.
 */
class Smart_Bulk_Password_Reset_Loader {
	protected $actions;
	protected $filters;

	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args
		);
		return $hooks;
	}

	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}

// =========================================================================
// Class: Smart_Bulk_Password_Reset_Admin
// =========================================================================
/**
 * The admin-specific functionality of the plugin.
 */
class Smart_Bulk_Password_Reset_Admin {
	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/** Register the admin menu page */
	public function add_plugin_admin_menu() {
		add_management_page(
			__( 'Smart Bulk Password Reset', 'smart-bulk-password-reset' ),
			__( 'Password Reset Tool', 'smart-bulk-password-reset' ),
			'manage_options', // Capability check
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/** Add settings link */
	public function add_action_links( $links ) {
		if ( ! current_user_can( 'manage_options' ) ) { // Ensure only admins see this
            return $links;
        }
		$settings_link = array(
			'<a href="' . admin_url( 'tools.php?page=' . $this->plugin_name ) . '">' . __( 'Settings', 'smart-bulk-password-reset' ) . '</a>',
		);
		return array_merge( $settings_link, $links );
	}

	/** Render the admin page */
	public function display_plugin_admin_page() {
        // Security check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'smart-bulk-password-reset' ) );
        }
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors( 'sbpr_messages' ); ?>

			<form id="sbpr_main_form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sbpr_send_reset_emails">
				<?php wp_nonce_field( 'sbpr_send_reset_action', 'sbpr_send_reset_nonce' ); // Nonce for form submission ?>

				<h2 class="title"><?php _e( 'User Selection', 'smart-bulk-password-reset' ); ?></h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="sbpr_user_role"><?php _e( 'Select User Role', 'smart-bulk-password-reset' ); ?></label>
							</th>
							<td>
								<select name="sbpr_user_role" id="sbpr_user_role">
									<option value=""><?php _e( '-- Select Role --', 'smart-bulk-password-reset' ); ?></option>
									<?php
									global $wp_roles;
									// Ensure $wp_roles is loaded
									if ( ! isset( $wp_roles ) ) {
                                        $wp_roles = new WP_Roles();
                                    }
									foreach ( $wp_roles->get_names() as $role_value => $role_name ) :
										echo '<option value="' . esc_attr( $role_value ) . '">' . esc_html( $role_name ) . '</option>';
									endforeach;
									?>
								</select>
								<p class="description"><?php _e( 'Select a role to load users.', 'smart-bulk-password-reset' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Select Users', 'smart-bulk-password-reset' ); ?></th>
							<td>
								<div id="sbpr_user_list_container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff;">
									<p><?php _e( 'Please select a role first.', 'smart-bulk-password-reset' ); ?></p>
								</div>
								 <p><label><input type="checkbox" id="sbpr_select_all_users"> <?php _e( 'Select/Deselect All', 'smart-bulk-password-reset' ); ?></label></p>
							</td>
						</tr>
					</tbody>
				</table>

				<hr>
				<h2 class="title"><?php _e( 'Email Template', 'smart-bulk-password-reset' ); ?></h2>

				<table class="form-table">
					 <tbody>
						<tr>
							<th scope="row">
								<label for="sbpr_template_select"><?php _e( 'Load Template', 'smart-bulk-password-reset' ); ?></label>
							</th>
							<td>
								<select id="sbpr_template_select" name="sbpr_template_select">
									<option value=""><?php _e( '-- Select a Template --', 'smart-bulk-password-reset' ); ?></option>
									<?php
									$templates = get_option( 'sbpr_email_templates', array() );
									foreach ( $templates as $id => $template ) :
										if ( is_array( $template ) && isset( $template['name'] ) ) :
									?>
										<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $template['name'] ); ?></option>
									<?php
										endif;
									endforeach;
									?>
									 <option value="default"><?php _e( '-- Default Template --', 'smart-bulk-password-reset' ); ?></option>
								</select>
								<button type="button" id="sbpr_delete_template_button" class="button button-link-delete" style="margin-left: 10px; display: none;"><?php _e( 'Delete Selected Template', 'smart-bulk-password-reset' ); ?></button>
								<p class="description"><?php _e( 'Select a saved template to load its subject and body.', 'smart-bulk-password-reset' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="sbpr_email_subject"><?php _e( 'Email Subject', 'smart-bulk-password-reset' ); ?></label>
							</th>
							<td>
								<input type="text" name="sbpr_email_subject" id="sbpr_email_subject" class="regular-text" value="<?php echo esc_attr( get_option('sbpr_default_subject', 'Your New Password for {site_title}') ); ?>">
								 <p class="description"><?php _e( 'Enter the subject for the password reset email.', 'smart-bulk-password-reset' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="sbpr_email_body"><?php _e( 'Email Body', 'smart-bulk-password-reset' ); ?></label>
							</th>
							<td>
								<?php
								$default_content = sprintf(
									__( "<p>Hi {user_name},</p>\n<p>Your password for %s has been reset.</p>\n<p>Your new password is: <strong>{new_password}</strong></p>\n<p>You can log in here: %s</p>\n<p>We recommend changing this password after logging in.</p>", 'smart-bulk-password-reset' ),
									get_bloginfo( 'name' ),
									wp_login_url()
								);
								$content = get_option( 'sbpr_default_email_body', $default_content );
								$editor_id = 'sbpr_email_body';
								$settings = array(
									'textarea_name' => 'sbpr_email_body',
									'media_buttons' => true,
									'textarea_rows' => 15,
									'tinymce'       => array(
										'toolbar1' => 'formatselect | bold italic underline strikethrough | forecolor backcolor | bullist numlist | blockquote | alignleft aligncenter alignright | link unlink | wp_adv',
										'toolbar2' => 'strikethrough hr | pastetext removeformat | charmap | outdent indent | undo redo | code',
									),
									'quicktags'     => true,
								);
								wp_editor( $content, $editor_id, $settings );
								?>
								<p class="description">
									<?php _e( 'Edit the email content. Available placeholders are listed below.', 'smart-bulk-password-reset' ); ?>
								</p>
								<div style="background-color: #f0f0f0; border: 1px solid #e0e0e0; padding: 10px 15px; margin-top: 5px; margin-bottom: 15px; border-radius: 4px;">
									<strong><?php _e( 'Available placeholders you can use:', 'smart-bulk-password-reset' ); ?></strong>
									<ul style="margin-top: 5px; margin-bottom: 0; padding-left: 20px;">
										<li><code>{user_name}</code> = <?php _e( 'User’s full name', 'smart-bulk-password-reset' ); ?></li>
										<li><code>{user_email}</code> = <?php _e( 'User’s email address', 'smart-bulk-password-reset' ); ?></li>
										<li><code>{username}</code> = <?php _e( 'User’s login name', 'smart-bulk-password-reset' ); ?></li>
										<li><code>{new_password}</code> = <?php _e( 'Newly generated password', 'smart-bulk-password-reset' ); ?></li>
										<li><code>{login_url}</code> = <?php _e( 'Login link to the user portal', 'smart-bulk-password-reset' ); ?></li>
									</ul>
								</div>
								<div id="sbpr_template_actions">
									 <input type="text" id="sbpr_template_name" placeholder="<?php esc_attr_e( 'Enter Template Name', 'smart-bulk-password-reset' ); ?>" style="margin-right: 5px; display: none;">
									 <button type="button" id="sbpr_save_template_button" class="button button-primary"><?php _e( 'Save as New Template', 'smart-bulk-password-reset' ); ?></button>
									 <button type="button" id="sbpr_update_template_button" class="button" style="display: none;"><?php _e( 'Update Selected Template', 'smart-bulk-password-reset' ); ?></button>
									 <span id="sbpr_template_status" style="display: none; margin-left: 10px;"></span>
								</div>
								 <p style="margin-top: 15px;">
									<button type="button" id="sbpr_preview_email_button" class="button"><?php _e( 'Preview Email', 'smart-bulk-password-reset' ); ?></button>
								</p>
								<div id="sbpr_email_preview_area" style="border: 1px dashed #ccc; padding: 15px; margin-top: 10px; display: none; background-color: #f9f9f9;">
									<h4><?php _e( 'Email Preview', 'smart-bulk-password-reset' ); ?></h4>
									<div id="sbpr_email_preview_content"></div>
								</div>
							</td>
						</tr>
						 <tr>
							<th scope="row">
								<label for="sbpr_test_email_address"><?php _e( 'Send Test Email To', 'smart-bulk-password-reset' ); ?></label>
							</th>
							<td>
								<input type="email" id="sbpr_test_email_address" class="regular-text" placeholder="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
								<button type="button" id="sbpr_send_test_email_button" class="button"><?php _e( 'Send Test Email', 'smart-bulk-password-reset' ); ?></button>
								<p class="description"><?php _e( 'Enter an email address to send a test email using the current subject and body with dummy data.', 'smart-bulk-password-reset' ); ?></p>
								<span id="sbpr_test_email_status" style="display: none; margin-left: 10px;"></span>
							</td>
						</tr>
					</tbody>
				</table>

				<hr>
				<?php submit_button( __( 'Send Reset Emails', 'smart-bulk-password-reset' ) ); ?>

			</form>
		</div>
		<?php
	}

	/** Enqueue styles */
	public function enqueue_styles( $hook_suffix ) {
		$expected_hook = 'tools_page_' . $this->plugin_name;
		if ( $hook_suffix == $expected_hook ) {
			$css = "
            #sbpr_user_list_container {
                min-height: 50px; /* Ensure it has some height even when empty */
                max-height: 200px;
                overflow-y: auto;
                border: 1px solid #ccd0d4;
                padding: 10px;
                background: #fff;
            }
            #sbpr_user_list_container .sbpr-user-item {
                display: block;
                margin-bottom: 5px;
            }
            #sbpr_user_list_container .sbpr-loading,
            #sbpr_user_list_container .sbpr-no-users {
                color: #666;
                font-style: italic;
            }
            #sbpr_template_status, #sbpr_test_email_status {
                display: inline-block;
                margin-left: 10px;
            }
            #sbpr_email_preview_area {
                border: 1px dashed #ccc;
                padding: 15px;
                margin-top: 10px;
                background-color: #f9f9f9;
            }
            ";
			wp_register_style( $this->plugin_name . '-admin-styles', false );
            wp_enqueue_style( $this->plugin_name . '-admin-styles' );
            wp_add_inline_style( $this->plugin_name . '-admin-styles', $css );
		}
	}

	/** Enqueue scripts */
	public function enqueue_scripts( $hook_suffix ) {
		$expected_hook = 'tools_page_' . $this->plugin_name;
		if ( $hook_suffix == $expected_hook ) {
            // Enqueue dependencies
            wp_enqueue_script('jquery');
            wp_enqueue_editor(); // Handles TinyMCE and Quicktags

            // Register a dummy script handle for inline script + localization
            wp_register_script( $this->plugin_name . '-admin-script', false, array('jquery', 'wp-tinymce', 'quicktags'), $this->version, true );
            wp_enqueue_script( $this->plugin_name . '-admin-script' );

            // Localize data for the script
			wp_localize_script( $this->plugin_name . '-admin-script', 'sbpr_admin_ajax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'sbpr_admin_nonce' ) // Nonce for AJAX
			));

            // Add the inline script
            $js = <<<JS
(function( $ ) {
	'use strict';

	$(function() { // Use $(function() shorthand for $(document).ready()

		var userListContainer = $('#sbpr_user_list_container');
		var roleSelect = $('#sbpr_user_role');
		var selectAllCheckbox = $('#sbpr_select_all_users');
        var previewButton = $('#sbpr_preview_email_button');
        var previewArea = $('#sbpr_email_preview_area');
        var previewContent = $('#sbpr_email_preview_content');
        var testEmailButton = $('#sbpr_send_test_email_button');
        var testEmailInput = $('#sbpr_test_email_address');
        var testEmailStatus = $('#sbpr_test_email_status');
        var emailSubjectInput = $('#sbpr_email_subject');
        var emailBodyEditorId = 'sbpr_email_body'; // TinyMCE editor ID
        var templateSelect = $('#sbpr_template_select');
        var templateNameInput = $('#sbpr_template_name');
        var saveTemplateButton = $('#sbpr_save_template_button');
        var updateTemplateButton = $('#sbpr_update_template_button');
        var deleteTemplateButton = $('#sbpr_delete_template_button');
        var templateStatus = $('#sbpr_template_status');

        // Dummy data for preview/test (should match PHP's smartbpr_preview_placeholders)
        var dummyData = {
            '{user_name}': 'John Doe',
            '{user_email}': 'john@example.com',
            '{username}': 'johndoe',
            '{new_password}': 'Test@1234',
            '{login_url}': 'https://crosscultural.kcdev.site/my-account/'
            // Note: {site_title} is handled server-side if needed, not typically in JS preview
        };

        // Function to get content from TinyMCE editor
        function getEditorContent() {
            if (typeof tinymce !== 'undefined' && tinymce.get(emailBodyEditorId)) {
                var editor = tinymce.get(emailBodyEditorId);
                 // Check if editor is initialized and not hidden (i.e., Visual tab is active)
                if (editor && !editor.isHidden()) {
                    return editor.getContent();
                }
            }
            // Fallback for Text view or if TinyMCE isn't fully loaded/visible
            return $('#' + emailBodyEditorId).val();
        }

        // Function to replace placeholders
        function replacePlaceholders(content) {
            var replacedContent = content;
            $.each(dummyData, function(placeholder, value) {
                var regex = new RegExp(placeholder.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'g');
                replacedContent = replacedContent.replace(regex, value);
            });
            return replacedContent;
        }

        // Function to set TinyMCE editor content
        function setEditorContent(content) {
            if (typeof tinymce !== 'undefined') {
                var editor = tinymce.get(emailBodyEditorId);
                if (editor) {
                    editor.setContent(content);
                    editor.fire('change'); // Trigger change event for potential integrations
                } else {
                    $('#' + emailBodyEditorId).val(content); // Fallback
                }
            } else {
                $('#' + emailBodyEditorId).val(content); // Fallback
            }
             // Also update the underlying textarea for Quicktags (Text view)
            $('#' + emailBodyEditorId).val(content);
        }

		// Function to load users via AJAX
		function loadUsers(role) {
			userListContainer.html('<p class="sbpr-loading">Loading users...</p>');
            selectAllCheckbox.prop('checked', false).prop('disabled', true);

			$.ajax({
				url: sbpr_admin_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'sbpr_get_users_by_role',
					role: role,
					nonce: sbpr_admin_ajax.nonce
				},
                dataType: 'json',
				success: function(response) {
					userListContainer.empty();
					if (response.success) {
						if (response.data.message || response.data.length === 0) {
							userListContainer.html('<p class="sbpr-no-users">' + (response.data.message || 'No users found for this role.') + '</p>');
                            selectAllCheckbox.prop('disabled', true);
						} else {
							$.each(response.data, function(index, user) {
								var userItem = '<label class="sbpr-user-item">' +
								               '<input type="checkbox" name="sbpr_users[]" value="' + user.ID + '"> ' +
								               esc_html(user.user_login) + ' (' + esc_html(user.user_email) + ')' +
								               '</label>';
								userListContainer.append(userItem);
							});
                            selectAllCheckbox.prop('disabled', false);
						}
					} else {
						userListContainer.html('<p class="sbpr-error">Error loading users: ' + esc_html(response.data || 'Unknown error') + '</p>');
                        selectAllCheckbox.prop('disabled', true);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					userListContainer.html('<p class="sbpr-error">AJAX Error: ' + esc_html(textStatus) + ' - ' + esc_html(errorThrown) + '</p>');
                    selectAllCheckbox.prop('disabled', true);
				}
			});
		}

        // Basic HTML escaping function for JS
        function esc_html(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

		// Event listener for role selection change
		roleSelect.on('change', function() {
			var selectedRole = $(this).val();
			if (selectedRole) {
				loadUsers(selectedRole);
			} else {
				userListContainer.html('<p>Please select a role first.</p>');
                selectAllCheckbox.prop('checked', false).prop('disabled', true);
			}
		});

        // Event listener for "Select All" checkbox
        selectAllCheckbox.on('change', function() {
            var isChecked = $(this).prop('checked');
            userListContainer.find('input[type="checkbox"]').prop('checked', isChecked);
        });

        // Uncheck "Select All" if any individual checkbox is unchecked
        userListContainer.on('change', 'input[type="checkbox"]', function() {
            if (!$(this).prop('checked')) {
                selectAllCheckbox.prop('checked', false);
            } else {
                if (userListContainer.find('input[type="checkbox"]:not(:checked)').length === 0) {
                    selectAllCheckbox.prop('checked', true);
                }
            }
        });

        // Initial state for select all checkbox
        selectAllCheckbox.prop('disabled', true);

        // Event listener for Preview Email button
        previewButton.on('click', function() {
            var emailBody = getEditorContent();
            var previewHtml = replacePlaceholders(emailBody);
            previewContent.html(previewHtml); // Note: This assumes the content is safe HTML (it comes from TinyMCE or wp_kses_post)
            previewArea.show();
        });

        // Event listener for Send Test Email button
        testEmailButton.on('click', function() {
            var testEmail = testEmailInput.val().trim();
            var emailSubject = emailSubjectInput.val().trim();
            var emailBody = getEditorContent();

            if (!testEmail) {
                testEmail = testEmailInput.attr('placeholder');
            }

            if (!emailSubject || !emailBody) {
                testEmailStatus.text('Subject and Body cannot be empty.').css('color', 'red').show();
                return;
            }

            var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!emailPattern.test(testEmail)) {
                 testEmailStatus.text('Invalid email address.').css('color', 'red').show();
                 return;
            }

            testEmailStatus.text('Sending...').css('color', 'blue').show();
            testEmailButton.prop('disabled', true);

            $.ajax({
                url: sbpr_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sbpr_send_test_email',
                    test_email: testEmail,
                    subject: emailSubject,
                    body: emailBody,
                    nonce: sbpr_admin_ajax.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        testEmailStatus.text(response.data).css('color', 'green').show();
                    } else {
                        testEmailStatus.text('Error: ' + esc_html(response.data || 'Unknown error')).css('color', 'red').show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    testEmailStatus.text('AJAX Error: ' + esc_html(textStatus) + ' - ' + esc_html(errorThrown)).css('color', 'red').show();
                },
                complete: function() {
                    testEmailButton.prop('disabled', false);
                    setTimeout(function() { testEmailStatus.fadeOut(); }, 5000);
                }
            });
        });

        // --- Template Management ---

        function showTemplateStatus(message, isError) {
            templateStatus.text(message).css('color', isError ? 'red' : 'green').show();
            setTimeout(function() { templateStatus.fadeOut(); }, 4000);
        }

        function updateTemplateUI(selectedId) {
             templateNameInput.val('').hide();
             if (selectedId && selectedId !== 'default') {
                updateTemplateButton.show();
                deleteTemplateButton.show();
                saveTemplateButton.text('Save as New Template').removeClass('button-primary');
            } else {
                updateTemplateButton.hide();
                deleteTemplateButton.hide();
                saveTemplateButton.text('Save as New Template').addClass('button-primary');
            }
        }

        templateSelect.on('change', function() {
            var selectedTemplateId = $(this).val();
            previewArea.hide();
            updateTemplateUI(selectedTemplateId);

            if (!selectedTemplateId) return;

            $.ajax({
                url: sbpr_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sbpr_load_template',
                    template_id: selectedTemplateId,
                    nonce: sbpr_admin_ajax.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        emailSubjectInput.val(response.data.subject);
                        setEditorContent(response.data.body);
                        showTemplateStatus('Template loaded.', false);
                    } else {
                        showTemplateStatus('Error loading template: ' + esc_html(response.data || 'Unknown error'), true);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                     showTemplateStatus('AJAX Error loading template: ' + esc_html(textStatus), true);
                }
            });
        });

        saveTemplateButton.on('click', function() {
            // First click shows the input if hidden
            if (!templateNameInput.is(':visible')) {
                 templateNameInput.show().focus();
                 return; // Wait for name input on next click
            }

            var templateName = templateNameInput.val().trim();
            if (templateName) {
                var emailSubject = emailSubjectInput.val().trim();
                var emailBody = getEditorContent();

                if (!emailSubject || !emailBody) {
                    showTemplateStatus('Subject and Body cannot be empty.', true);
                    return;
                }

                saveTemplateButton.prop('disabled', true);
                templateStatus.text('Saving...').css('color', 'blue').show();

                $.ajax({
                    url: sbpr_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sbpr_save_template',
                        name: templateName,
                        subject: emailSubject,
                        body: emailBody,
                        nonce: sbpr_admin_ajax.nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            templateSelect.append($('<option>', {
                                value: response.data.id,
                                text: esc_html(response.data.name) // Escape name
                            }));
                            templateSelect.val(response.data.id);
                            updateTemplateUI(response.data.id);
                            showTemplateStatus(response.data.message, false);
                            templateNameInput.val('').hide();
                        } else {
                            showTemplateStatus('Error saving template: ' + esc_html(response.data || 'Unknown error'), true);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showTemplateStatus('AJAX Error saving template: ' + esc_html(textStatus), true);
                    },
                    complete: function() {
                         saveTemplateButton.prop('disabled', false);
                    }
                });

            } else {
                 showTemplateStatus('Please enter a name for the new template.', true);
                 templateNameInput.focus();
            }
        });

        updateTemplateButton.on('click', function() {
            var selectedTemplateId = templateSelect.val();
            var emailSubject = emailSubjectInput.val().trim();
            var emailBody = getEditorContent();

            if (!selectedTemplateId || selectedTemplateId === 'default') {
                showTemplateStatus('Please select a saved template to update.', true);
                return;
            }
             if (!emailSubject || !emailBody) {
                showTemplateStatus('Subject and Body cannot be empty.', true);
                return;
            }

            updateTemplateButton.prop('disabled', true);
            templateStatus.text('Updating...').css('color', 'blue').show();

            $.ajax({
                url: sbpr_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sbpr_update_template',
                    template_id: selectedTemplateId,
                    subject: emailSubject,
                    body: emailBody,
                    nonce: sbpr_admin_ajax.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showTemplateStatus(response.data, false);
                    } else {
                        showTemplateStatus('Error updating template: ' + esc_html(response.data || 'Unknown error'), true);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showTemplateStatus('AJAX Error updating template: ' + esc_html(textStatus), true);
                },
                 complete: function() {
                     updateTemplateButton.prop('disabled', false);
                }
            });
        });

        deleteTemplateButton.on('click', function() {
            var selectedTemplateId = templateSelect.val();
            var selectedTemplateName = templateSelect.find('option:selected').text();

            if (!selectedTemplateId || selectedTemplateId === 'default') {
                showTemplateStatus('Please select a saved template to delete.', true);
                return;
            }

            if (confirm('Are you sure you want to delete the template "' + selectedTemplateName + '"?')) {
                deleteTemplateButton.prop('disabled', true);
                templateStatus.text('Deleting...').css('color', 'blue').show();

                 $.ajax({
                    url: sbpr_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sbpr_delete_template',
                        template_id: selectedTemplateId,
                        nonce: sbpr_admin_ajax.nonce
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            templateSelect.find('option[value="' + selectedTemplateId + '"]').remove();
                            templateSelect.val('');
                            updateTemplateUI('');
                            emailSubjectInput.val('');
                            setEditorContent('');
                            showTemplateStatus(response.data, false);
                        } else {
                            showTemplateStatus('Error deleting template: ' + esc_html(response.data || 'Unknown error'), true);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showTemplateStatus('AJAX Error deleting template: ' + esc_html(textStatus), true);
                    },
                    complete: function() {
                         deleteTemplateButton.prop('disabled', false);
                    }
                });
            }
        });

        // Initial UI state for templates
        updateTemplateUI(templateSelect.val());

	}); // End document ready

})( jQuery );
JS;
            wp_add_inline_script( $this->plugin_name . '-admin-script', $js );
		}
	}

	/** AJAX: Get users by role */
	public function ajax_get_users_by_role() {
		check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied.' ); }

		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		if ( empty( $role ) ) { wp_send_json_error( 'Role not specified.' ); }

		$users = get_users( array( 'role' => $role, 'fields' => array( 'ID', 'user_login', 'user_email' ) ) );
		if ( empty( $users ) ) { wp_send_json_success( array( 'message' => 'No users found for this role.' ) ); }
        else { wp_send_json_success( $users ); }
	}

	/** Handle main form submission */
	public function handle_send_reset_emails() {
		// Verify nonce first
        if ( ! isset( $_POST['sbpr_send_reset_nonce'] ) || ! wp_verify_nonce( $_POST['sbpr_send_reset_nonce'], 'sbpr_send_reset_action' ) ) {
			wp_die( 'Security check failed (Nonce verification failed).' );
		}
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to perform this action.' );
		}

		$user_ids = isset( $_POST['sbpr_users'] ) ? array_map( 'intval', $_POST['sbpr_users'] ) : array();
		$subject = isset( $_POST['sbpr_email_subject'] ) ? sanitize_text_field( $_POST['sbpr_email_subject'] ) : '';
		$message_template = isset( $_POST['sbpr_email_body'] ) ? wp_kses_post( stripslashes( $_POST['sbpr_email_body'] ) ) : '';

		if ( empty( $user_ids ) ) {
			add_settings_error( 'sbpr_messages', 'sbpr_no_users', 'No users selected.', 'error' );
			wp_redirect( admin_url( 'tools.php?page=' . $this->plugin_name ) ); exit;
		}
		if ( empty( $subject ) || empty( $message_template ) ) {
			add_settings_error( 'sbpr_messages', 'sbpr_empty_fields', 'Email subject and body cannot be empty.', 'error' );
			wp_redirect( admin_url( 'tools.php?page=' . $this->plugin_name ) ); exit;
		}

		$sent_count = 0;
        $log_entries = array();
		$headers = array('Content-Type: text/html; charset=UTF-8');
        $current_time = current_time( 'mysql' );

		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) continue;

			$new_password = wp_generate_password( 12, true, true );

            // Replace placeholders in subject and message
            $processed_subject = smartbpr_replace_placeholders( $subject, $user, $new_password );
            $processed_message = smartbpr_replace_placeholders( $message_template, $user, $new_password );

			$mail_sent = wp_mail( $user->user_email, $processed_subject, $processed_message, $headers );

			if ( $mail_sent ) {
			    wp_set_password( $new_password, $user_id );
				$sent_count++;
                $log_entries[] = array(
                    'timestamp' => $current_time,
                    'username'  => $user->user_login,
                    'email'     => $user->user_email,
                    'password'  => $new_password
                );
			}
		}

        // CSV Logging
        if ( ! empty( $log_entries ) ) {
            $upload_dir = wp_upload_dir();
            if ( ! $upload_dir['error'] ) {
                $log_dir = $upload_dir['basedir'] . '/password-reset-logs';
                $log_file_path = $log_dir . '/password_reset_log_' . date('Y-m-d') . '.csv';

                if ( ! file_exists( $log_dir ) ) {
                    wp_mkdir_p( $log_dir );
                    if ( ! file_exists( $log_dir . '/index.php' ) ) @file_put_contents( $log_dir . '/index.php', '<?php // Silence is golden.' );
                    if ( ! file_exists( $log_dir . '/.htaccess' ) ) @file_put_contents( $log_dir . '/.htaccess', "Options -Indexes\ndeny from all" );
                }

                $write_header = ! file_exists( $log_file_path ) || filesize( $log_file_path ) === 0;
                $log_handle = @fopen( $log_file_path, 'a' );

                if ( $log_handle ) {
                    if ( $write_header ) fputcsv( $log_handle, array( 'Timestamp', 'Username', 'Email', 'New Password' ) );
                    foreach ( $log_entries as $entry ) fputcsv( $log_handle, array_values($entry) ); // Use array_values for safety
                    @fclose( $log_handle );
                } else {
                    add_settings_error('sbpr_messages', 'sbpr_log_error', 'Warning: Could not open log file for writing: ' . esc_html($log_file_path), 'warning');
                }
            } else {
                 add_settings_error('sbpr_messages', 'sbpr_upload_dir_error', 'Error: Could not determine upload directory. Logging failed. ' . esc_html($upload_dir['error']), 'error');
            }
        }

		// Add admin notice
		$notice_message = sprintf( 'Successfully sent %d password reset emails.', $sent_count );
        if (!empty($log_entries)) $notice_message .= ' Log file updated.';
        add_settings_error('sbpr_messages', 'sbpr_emails_sent', $notice_message, 'success');

		// Redirect back
		wp_redirect( admin_url( 'tools.php?page=' . $this->plugin_name ) );
		exit;
	}

    /** AJAX: Send test email */
	public function ajax_send_test_email() {
		check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied.' ); }

        $to_email = isset( $_POST['test_email'] ) ? sanitize_email( $_POST['test_email'] ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
		$message_template = isset( $_POST['body'] ) ? wp_kses_post( stripslashes( $_POST['body'] ) ) : '';

        if ( ! is_email( $to_email ) ) { wp_send_json_error( 'Invalid email address provided.' ); }
        if ( empty( $subject ) || empty( $message_template ) ) { wp_send_json_error( 'Email subject and body cannot be empty.' ); }

        // Use the preview function for consistent placeholder replacement with dummy data
        $processed_subject = smartbpr_preview_placeholders( $subject );
        $processed_message = smartbpr_preview_placeholders( $message_template );

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $mail_sent = wp_mail( $to_email, $processed_subject, $processed_message, $headers );

        if ( $mail_sent ) { wp_send_json_success( 'Test email sent successfully to ' . $to_email ); }
        else { wp_send_json_error( 'Failed to send test email.' ); } // Keep error simple for JS
    }

    /** Helper: Get templates */
    private function get_templates() { return get_option( 'sbpr_email_templates', array() ); }
    /** Helper: Save templates */
    private function save_templates( $templates ) { update_option( 'sbpr_email_templates', $templates ); }

    /** AJAX: Load template */
    public function ajax_load_template() {
        check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied.' ); }

        $template_id = isset( $_POST['template_id'] ) ? sanitize_key( $_POST['template_id'] ) : '';
        if ( empty( $template_id ) ) { wp_send_json_error( 'No template ID provided.' ); }

        if ( $template_id === 'default' ) {
             $default_subject = get_option('sbpr_default_subject', 'Your New Password for {site_title}');
             $default_content = sprintf( __( "<p>Hi {user_name},</p>\n<p>Your password for %s has been reset.</p>\n<p>Your new password is: <strong>{new_password}</strong></p>\n<p>You can log in here: %s</p>\n<p>We recommend changing this password after logging in.</p>", 'smart-bulk-password-reset' ), get_bloginfo( 'name' ), wp_login_url() );
             $default_body = get_option( 'sbpr_default_email_body', $default_content );
             wp_send_json_success( array( 'subject' => $default_subject, 'body' => $default_body ) );
        }

        $templates = $this->get_templates();
        if ( isset( $templates[ $template_id ] ) ) { wp_send_json_success( $templates[ $template_id ] ); }
        else { wp_send_json_error( 'Template not found.' ); }
    }

    /** AJAX: Save template */
    public function ajax_save_template() {
        check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied.' ); }

        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
        $body = isset( $_POST['body'] ) ? wp_kses_post( stripslashes( $_POST['body'] ) ) : '';

        if ( empty( $name ) || empty( $subject ) || empty( $body ) ) { wp_send_json_error( 'Template name, subject, and body are required.' ); }

        $templates = $this->get_templates();
        $new_id = uniqid( 'sbpr_' );
        $templates[ $new_id ] = array( 'name' => $name, 'subject' => $subject, 'body' => $body );
        $this->save_templates( $templates );
        wp_send_json_success( array( 'id' => $new_id, 'name' => $name, 'message' => 'Template saved successfully.' ) );
    }

    /** AJAX: Update template */
    public function ajax_update_template() {
        check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied.' ); }

        $template_id = isset( $_POST['template_id'] ) ? sanitize_key( $_POST['template_id'] ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
        $body = isset( $_POST['body'] ) ? wp_kses_post( stripslashes( $_POST['body'] ) ) : '';

        if ( empty( $template_id ) || empty( $subject ) || empty( $body ) ) { wp_send_json_error( 'Template ID, subject, and body are required.' ); }

        $templates = $this->get_templates();
        if ( ! isset( $templates[ $template_id ] ) ) { wp_send_json_error( 'Template not found.' ); }

        $templates[ $template_id ]['subject'] = $subject;
        $templates[ $template_id ]['body'] = $body;
        $this->save_templates( $templates );
        wp_send_json_success( 'Template updated successfully.' );
    }

     /** AJAX: Delete template */
    public function ajax_delete_template() {
        check_ajax_referer( 'sbpr_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied.' ); }

        $template_id = isset( $_POST['template_id'] ) ? sanitize_key( $_POST['template_id'] ) : '';
        if ( empty( $template_id ) ) { wp_send_json_error( 'No template ID provided.' ); }

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


// =========================================================================
// Class: Smart_Bulk_Password_Reset (Core Plugin Class)
// =========================================================================
/**
 * The core plugin class.
 */
class Smart_Bulk_Password_Reset {
	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->version = SBPR_VERSION;
		$this->plugin_name = 'smart-bulk-password-reset';
		$this->load_dependencies(); // Instantiates loader
		$this->define_admin_hooks();
	}

	private function load_dependencies() {
		// Classes are defined above, just instantiate loader
		$this->loader = new Smart_Bulk_Password_Reset_Loader();
	}

	private function define_admin_hooks() {
		// Instantiate admin class
		$plugin_admin = new Smart_Bulk_Password_Reset_Admin( $this->get_plugin_name(), $this->get_version() );

		// Add menu item
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		// Add Settings link to plugin page - Use SBPR_PLUGIN_FILE defined at the top
        $plugin_basename = plugin_basename( SBPR_PLUGIN_FILE );
        $this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );

		// Enqueue admin scripts and styles (methods now handle inlining)
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// AJAX Hooks
		$this->loader->add_action( 'wp_ajax_sbpr_get_users_by_role', $plugin_admin, 'ajax_get_users_by_role' );
		$this->loader->add_action( 'wp_ajax_sbpr_send_test_email', $plugin_admin, 'ajax_send_test_email' );
        $this->loader->add_action( 'wp_ajax_sbpr_load_template', $plugin_admin, 'ajax_load_template' );
        $this->loader->add_action( 'wp_ajax_sbpr_save_template', $plugin_admin, 'ajax_save_template' );
        $this->loader->add_action( 'wp_ajax_sbpr_update_template', $plugin_admin, 'ajax_update_template' );
        $this->loader->add_action( 'wp_ajax_sbpr_delete_template', $plugin_admin, 'ajax_delete_template' );

        // Form submission hook
		$this->loader->add_action( 'admin_post_sbpr_send_reset_emails', $plugin_admin, 'handle_send_reset_emails' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() { return $this->plugin_name; }
	public function get_loader() { return $this->loader; }
	public function get_version() { return $this->version; }
}

// =========================================================================
// Plugin Execution
// =========================================================================
/**
 * Begins execution of the plugin.
 */
function run_smart_bulk_password_reset() {
	$plugin = new Smart_Bulk_Password_Reset();
	$plugin->run();
}
run_smart_bulk_password_reset();

// =========================================================================
// Placeholder Replacement Function
// =========================================================================
/**
 * Replaces placeholders in email content.
 *
 * @param string $content The content (subject or body) containing placeholders.
 * @param WP_User $user The user object.
 * @param string $new_password The newly generated password.
 * @return string Content with placeholders replaced.
 */
function smartbpr_replace_placeholders( $content, $user, $new_password ) {
    if ( ! $user instanceof WP_User ) {
        return $content; // Return original content if user object is invalid
    }

    $login_url = 'https://crosscultural.kcdev.site/my-account/'; // Fixed login URL

    $placeholders = array(
        '{user_name}'   => $user->display_name,
        '{user_email}'  => $user->user_email,
        '{username}'    => $user->user_login,
        '{new_password}'=> $new_password,
        '{login_url}'   => $login_url,
    );

    // Add site title placeholder for backward compatibility if used in subject/body
    $placeholders['{site_title}'] = get_bloginfo( 'name' );

    // Perform the replacement
    foreach ( $placeholders as $placeholder => $value ) {
        $content = str_replace( $placeholder, $value, $content );
    }

    return $content;
}

// =========================================================================
// Preview Placeholder Replacement Function
// =========================================================================
/**
 * Replaces placeholders in content using dummy data for preview/test purposes.
 *
 * @param string $content The content (subject or body) containing placeholders.
 * @return string Content with placeholders replaced with dummy values.
 */
function smartbpr_preview_placeholders( $content ) {
    $login_url = 'https://crosscultural.kcdev.site/my-account/'; // Fixed login URL

    $dummy_placeholders = array(
        '{user_name}'   => 'John Doe',
        '{user_email}'  => 'john@example.com',
        '{username}'    => 'johndoe',
        '{new_password}'=> 'Test@1234',
        '{login_url}'   => $login_url,
    );

    // Add site title placeholder for backward compatibility if used in subject/body
    $dummy_placeholders['{site_title}'] = get_bloginfo( 'name' );

    // Perform the replacement
    foreach ( $dummy_placeholders as $placeholder => $value ) {
        $content = str_replace( $placeholder, $value, $content );
    }

    return $content;
}
