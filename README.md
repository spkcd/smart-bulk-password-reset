# Smart Bulk Password Reset WordPress Plugin

A WordPress plugin that allows administrators to bulk reset passwords for users based on their role, send customized HTML email notifications using templates, and log the reset events.

## Features

*   Adds a "Password Reset Tool" page under the "Tools" menu in the WordPress admin area.
*   Select users by role (e.g., Subscriber, Customer, Administrator).
*   Individually select or deselect users within the chosen role.
*   Customize the password reset email subject and body using a WYSIWYG editor (TinyMCE) with Visual/Text toggle.
*   Use placeholders in the email: `{user_name}`, `{user_email}`, `{new_password}`.
*   Preview the email with dummy data before sending.
*   Send a test email to a specified address.
*   Save, load, update, and delete multiple email templates.
*   Generates secure passwords for selected users.
*   Sends HTML emails using `wp_mail()`.
*   Logs successfully processed resets (Timestamp, Username, Email, New Password) to a daily CSV file in `/wp-content/uploads/password-reset-logs/`.
*   Restricted access to Administrators (`manage_options` capability).
*   Uses nonces for security on form submissions and AJAX actions.

## Installation

1.  Download the `smart-bulk-password-reset.php` file.
2.  **Option A (Upload):**
    *   Zip the `smart-bulk-password-reset.php` file.
    *   In your WordPress admin dashboard, go to `Plugins` > `Add New`.
    *   Click `Upload Plugin`.
    *   Choose the zipped file and click `Install Now`.
    *   Activate the plugin.
3.  **Option B (Manual):**
    *   Create a directory named `smart-bulk-password-reset` inside your `/wp-content/plugins/` directory.
    *   Place the `smart-bulk-password-reset.php` file inside this new directory.
    *   In your WordPress admin dashboard, go to `Plugins` > `Installed Plugins`.
    *   Find "Smart Bulk Password Reset" and click `Activate`.

*(Note: While the development process created `includes` and `admin` directories, only the single `smart-bulk-password-reset.php` file is required for the plugin to function after the consolidation step.)*

## Usage

1.  Navigate to `Tools` > `Password Reset Tool` in your WordPress admin dashboard.
2.  **Select Users:**
    *   Choose a user role from the "Select User Role" dropdown. The list of users in that role will load below.
    *   Check the boxes next to the users whose passwords you want to reset. You can use the "Select/Deselect All" checkbox.
3.  **Prepare Email:**
    *   **Load Template (Optional):** Select a previously saved template from the "Load Template" dropdown to populate the subject and body. Select "-- Default Template --" to load the default message.
    *   **Subject:** Edit the "Email Subject" field.
    *   **Body:** Edit the "Email Body" using the WYSIWYG editor. Use the Visual/Text tabs to switch modes. You can use the placeholders `{user_name}`, `{user_email}`, and `{new_password}` which will be replaced for each user. Use the "Add Media" button to insert images.
    *   **Preview:** Click "Preview Email" to see how the email will look with dummy data below the editor.
    *   **Test:** Enter an email address in the "Send Test Email To" field and click "Send Test Email" to send the current subject/body with dummy data to that address.
4.  **Manage Templates (Optional):**
    *   **Save New:** Enter a name in the "Enter Template Name" field (appears after clicking "Save as New Template" once) and click "Save as New Template" again to save the current subject and body.
    *   **Update:** Select a saved template from the dropdown, make changes to the subject/body, and click "Update Selected Template".
    *   **Delete:** Select a saved template from the dropdown and click "Delete Selected Template". Confirm the deletion.
5.  **Send Resets:** Once users are selected and the email is ready, click the "Send Reset Emails" button.
6.  A confirmation notice will appear indicating how many emails were sent successfully.

## Logging

*   Successful password resets are logged daily.
*   Log files are stored in `/wp-content/uploads/password-reset-logs/`.
*   The filename format is `password_reset_log_YYYY-MM-DD.csv`.
*   Each row contains: `Timestamp`, `Username`, `Email`, `New Password`.
*   The log directory is protected from direct web access via an `.htaccess` file (on Apache servers).

## License

This plugin is licensed under the GPL v2 or later. See the `LICENSE` file for more details.
