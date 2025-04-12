<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Smart_Bulk_Password_Reset
 * @subpackage Smart_Bulk_Password_Reset/admin/partials
 */
?>

<div class="wrap">

    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php settings_errors( 'sbpr_messages' ); ?>

    <form id="sbpr_main_form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="sbpr_send_reset_emails">
        <?php wp_nonce_field( 'sbpr_send_reset_action', 'sbpr_send_reset_nonce' ); ?>

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
                            'media_buttons' => true, // Enable media buttons for image embedding
                            'textarea_rows' => 15,
                            'tinymce'       => array(
                                // Ensure common formatting, link, and code view buttons are present
                                'toolbar1' => 'formatselect | bold italic underline strikethrough | forecolor backcolor | bullist numlist | blockquote | alignleft aligncenter alignright | link unlink | wp_adv',
                                'toolbar2' => 'strikethrough hr | forecolor backcolor | pastetext removeformat | charmap | outdent indent | undo redo | code', // Added 'code' button
                            ),
                            'quicktags'     => true, // Enable Text view
                        );
                        wp_editor( $content, $editor_id, $settings );
                        ?>
                        <p class="description">
                            <?php _e( 'Edit the email content. Available placeholders: <code>{user_name}</code>, <code>{user_email}</code>, <code>{new_password}</code>.', 'smart-bulk-password-reset' ); ?>
                        </p>
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
