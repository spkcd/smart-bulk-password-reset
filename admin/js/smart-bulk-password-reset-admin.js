(function( $ ) {
	'use strict';

	$(function() {

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

        // Dummy data for preview/test
        var dummyData = {
            '{user_name}': 'John Doe',
            '{user_email}': 'john.doe@example.com',
            '{new_password}': 'password123'
        };

        // Function to get content from TinyMCE editor
        function getEditorContent() {
            if (typeof tinymce !== 'undefined' && tinymce.get(emailBodyEditorId)) {
                return tinymce.get(emailBodyEditorId).getContent();
            } else {
                // Fallback for Text view or if TinyMCE isn't fully loaded
                return $('#' + emailBodyEditorId).val();
            }
        }

        // Function to replace placeholders
        function replacePlaceholders(content) {
            var replacedContent = content;
            $.each(dummyData, function(placeholder, value) {
                // Use a RegExp for global replacement
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
                    // Ensure the editor is visually updated
                    editor.fire('change');
                } else {
                     // Fallback if editor not ready
                    $('#' + emailBodyEditorId).val(content);
                }
            } else {
                $('#' + emailBodyEditorId).val(content);
            }
        }

		// Function to load users via AJAX
		function loadUsers(role) {
			userListContainer.html('<p class="sbpr-loading">Loading users...</p>');
            selectAllCheckbox.prop('checked', false); // Uncheck select all

			$.ajax({
				url: sbpr_admin_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'sbpr_get_users_by_role',
					role: role,
					nonce: sbpr_admin_ajax.nonce
				},
				success: function(response) {
					userListContainer.empty(); // Clear previous content
					if (response.success) {
						if (response.data.message) {
							// Handle "No users found" message
							userListContainer.html('<p class="sbpr-no-users">' + response.data.message + '</p>');
                            selectAllCheckbox.prop('disabled', true);
						} else if (response.data.length > 0) {
							// Populate user list
							$.each(response.data, function(index, user) {
								var userItem = '<label class="sbpr-user-item">' +
								               '<input type="checkbox" name="sbpr_users[]" value="' + user.ID + '"> ' +
								               user.user_login + ' (' + user.user_email + ')' +
								               '</label>';
								userListContainer.append(userItem);
							});
                            selectAllCheckbox.prop('disabled', false);
						} else {
                            // Should not happen if message is handled, but just in case
                            userListContainer.html('<p class="sbpr-no-users">No users found for this role.</p>');
                             selectAllCheckbox.prop('disabled', true);
                        }
					} else {
						userListContainer.html('<p class="sbpr-error">Error loading users: ' + (response.data || 'Unknown error') + '</p>');
                        selectAllCheckbox.prop('disabled', true);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					userListContainer.html('<p class="sbpr-error">AJAX Error: ' + textStatus + ' - ' + errorThrown + '</p>');
                    selectAllCheckbox.prop('disabled', true);
				}
			});
		}

		// Event listener for role selection change
		roleSelect.on('change', function() {
			var selectedRole = $(this).val();
			if (selectedRole) {
				loadUsers(selectedRole);
			} else {
				userListContainer.html('<p>Please select a role first.</p>');
                selectAllCheckbox.prop('checked', false);
                selectAllCheckbox.prop('disabled', true);
			}
		});

        // Event listener for "Select All" checkbox
        selectAllCheckbox.on('change', function() {
            var isChecked = $(this).prop('checked');
            userListContainer.find('input[type="checkbox"]').prop('checked', isChecked);
        });

        // Optional: Uncheck "Select All" if any individual checkbox is unchecked
        userListContainer.on('change', 'input[type="checkbox"]', function() {
            if (!$(this).prop('checked')) {
                selectAllCheckbox.prop('checked', false);
            } else {
                // Check if all are checked now
                if (userListContainer.find('input[type="checkbox"]:not(:checked)').length === 0) {
                    selectAllCheckbox.prop('checked', true);
                }
            }
        });

        // Initial state for select all checkbox
        selectAllCheckbox.prop('disabled', true);

        // Initialize TinyMCE - wp_enqueue_editor() should handle this,
        // but sometimes explicit initialization is needed depending on theme/plugin conflicts.
        // If the editor doesn't load, uncommenting the relevant parts might help.
        // $(document).trigger('wp-before-tinymce-init', { editorId: 'sbpr_email_body' });
        // if (typeof tinymce !== 'undefined') {
        //     tinymce.init(window.tinyMCEPreInit.mceInit.sbpr_email_body);
        // }
        // if (typeof quicktags !== 'undefined') {
        //     quicktags(window.tinyMCEPreInit.qtInit.sbpr_email_body);
        // }

        // Event listener for Preview Email button
        previewButton.on('click', function() {
            var emailBody = getEditorContent();
            var previewHtml = replacePlaceholders(emailBody);
            previewContent.html(previewHtml);
            previewArea.show();
        });

        // Event listener for Send Test Email button
        testEmailButton.on('click', function() {
            var testEmail = testEmailInput.val().trim();
            var emailSubject = emailSubjectInput.val().trim();
            var emailBody = getEditorContent();

            if (!testEmail) {
                // Use placeholder (current user's email) if input is empty
                testEmail = testEmailInput.attr('placeholder');
            }

            if (!emailSubject || !emailBody) {
                testEmailStatus.text('Subject and Body cannot be empty.').css('color', 'red').show();
                return;
            }

            // Basic email validation
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
                    body: emailBody, // Send the raw template
                    nonce: sbpr_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        testEmailStatus.text(response.data).css('color', 'green').show();
                    } else {
                        testEmailStatus.text('Error: ' + (response.data || 'Unknown error')).css('color', 'red').show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    testEmailStatus.text('AJAX Error: ' + textStatus + ' - ' + errorThrown).css('color', 'red').show();
                },
                complete: function() {
                    testEmailButton.prop('disabled', false);
                    // Optionally hide status after a few seconds
                    setTimeout(function() { testEmailStatus.fadeOut(); }, 5000);
                }
            });
        });

        // --- Template Management ---

        // Function to show template status messages
        function showTemplateStatus(message, isError) {
            templateStatus.text(message).css('color', isError ? 'red' : 'green').show();
            setTimeout(function() { templateStatus.fadeOut(); }, 4000);
        }

        // Function to update UI state based on template selection
        function updateTemplateUI(selectedId) {
             templateNameInput.val('').hide(); // Hide name input by default
             if (selectedId && selectedId !== 'default') {
                // Existing template selected
                updateTemplateButton.show();
                deleteTemplateButton.show();
                saveTemplateButton.text('Save as New Template').removeClass('button-primary'); // Change save button appearance
            } else {
                // '-- Select --' or '-- Default --' selected
                updateTemplateButton.hide();
                deleteTemplateButton.hide();
                saveTemplateButton.text('Save as New Template').addClass('button-primary'); // Reset save button
            }
        }

        // Event listener for template selection change
        templateSelect.on('change', function() {
            var selectedTemplateId = $(this).val();
            previewArea.hide(); // Hide preview when changing template
            updateTemplateUI(selectedTemplateId); // Update button visibility

            if (!selectedTemplateId) {
                // '-- Select --' chosen, maybe clear fields or load default? For now, do nothing.
                // emailSubjectInput.val('');
                // setEditorContent('');
                return;
            }

             // Load template content via AJAX
            $.ajax({
                url: sbpr_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sbpr_load_template',
                    template_id: selectedTemplateId,
                    nonce: sbpr_admin_ajax.nonce
                },
                dataType: 'json', // Expect JSON response
                success: function(response) {
                    if (response.success) {
                        emailSubjectInput.val(response.data.subject);
                        setEditorContent(response.data.body);
                        showTemplateStatus('Template loaded.', false);
                    } else {
                        showTemplateStatus('Error loading template: ' + (response.data || 'Unknown error'), true);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                     showTemplateStatus('AJAX Error loading template: ' + textStatus, true);
                }
            });
        });

        // Event listener for "Save as New Template" button
        saveTemplateButton.on('click', function() {
            templateNameInput.show().focus(); // Show the name input

            // If name input is visible and has value, proceed with saving
            var templateName = templateNameInput.val().trim();
            if (templateNameInput.is(':visible') && templateName) {
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
                            // Add new template to dropdown and select it
                            templateSelect.append($('<option>', {
                                value: response.data.id,
                                text: response.data.name
                            }));
                            templateSelect.val(response.data.id);
                            updateTemplateUI(response.data.id); // Update buttons for the newly saved template
                            showTemplateStatus(response.data.message, false);
                            templateNameInput.val('').hide(); // Hide name input again
                        } else {
                            showTemplateStatus('Error saving template: ' + (response.data || 'Unknown error'), true);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showTemplateStatus('AJAX Error saving template: ' + textStatus, true);
                    },
                    complete: function() {
                         saveTemplateButton.prop('disabled', false);
                    }
                });

            } else if (templateNameInput.is(':visible') && !templateName) {
                 showTemplateStatus('Please enter a name for the new template.', true);
            }
            // Else: First click just showed the input, do nothing more yet.
        });

         // Event listener for "Update Template" button
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
                        showTemplateStatus('Error updating template: ' + (response.data || 'Unknown error'), true);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showTemplateStatus('AJAX Error updating template: ' + textStatus, true);
                },
                 complete: function() {
                     updateTemplateButton.prop('disabled', false);
                }
            });
        });

        // Event listener for "Delete Template" button
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
                            templateSelect.val(''); // Reset dropdown
                            updateTemplateUI(''); // Reset UI state
                            emailSubjectInput.val(''); // Clear fields
                            setEditorContent('');
                            showTemplateStatus(response.data, false);
                        } else {
                            showTemplateStatus('Error deleting template: ' + (response.data || 'Unknown error'), true);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showTemplateStatus('AJAX Error deleting template: ' + textStatus, true);
                    },
                    complete: function() {
                         deleteTemplateButton.prop('disabled', false);
                    }
                });
            }
        });

        // Initial UI state for templates
        updateTemplateUI(templateSelect.val());


	});

})( jQuery );
