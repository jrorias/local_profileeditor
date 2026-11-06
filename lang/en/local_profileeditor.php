<?php
// This file is part of Moodle - http://moodle.org/

/**
 * English strings.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Profile field editor';
$string['changelog'] = 'Change log';
$string['profileeditor:view'] = 'View the profile field editor';
$string['profileeditor:edit'] = 'Edit user profile fields and import updates';
$string['profileeditor:viewlog'] = 'View the profile field change log';
$string['corefields'] = 'User details';

// Navigation.
$string['nav_editor'] = 'Edit users';
$string['nav_import'] = 'Import updates';
$string['nav_guide'] = 'Template & guide';
$string['nav_log'] = 'Change log';
$string['nav_columns'] = 'Columns';
$string['nav_branding'] = 'Branding';
$string['editor_subtitle'] = 'Click a cell to edit. Every change is saved straight away and logged.';

// Settings.
$string['settings'] = 'Settings';
$string['settings_pages'] = 'Arrange, show/hide and lock columns on the <a href="{$a->columns}">Columns</a> page. Change the organisation name, colours, fonts and sizes on the <a href="{$a->branding}">Branding</a> page.';
$string['settings_template'] = 'Update template';
$string['settings_import'] = 'Import';
$string['defaultperpage'] = 'Users per page';
$string['defaultperpage_desc'] = 'Default number of users shown per page in the editor.';
$string['templateformat'] = 'Default template format';
$string['templateformat_desc'] = 'Excel is recommended: branded, with drop-down lists for menu fields and an instructions sheet. CSV can also be used with the LMS Upload users tool.';
$string['format_xlsx'] = 'Excel (.xlsx)';
$string['format_csv'] = 'CSV (.csv)';
$string['templatesort'] = 'Sort template rows by';
$string['templatesort_desc'] = 'Order of the users in the downloaded template.';
$string['templatesort_name'] = 'Last name, first name';
$string['templatesortdir'] = 'Sort direction';
$string['templatedomains'] = 'Only include these email domains (optional)';
$string['templatedomains_desc'] = 'One domain per line, for example <code>example.com</code>. Leave empty to include every user. When filled in, rows are grouped by domain in this order.';
$string['templateexcluded'] = 'Leave these accounts out (optional)';
$string['templateexcluded_desc'] = 'One email address or username per line, for example system or test accounts. Leave empty to leave nobody out.';
$string['matchby_desc'] = 'How rows in an imported file are matched to users. Automatic uses the username column if the file has one, otherwise the email column.';

// Editor page.
$string['search'] = 'Search';
$string['searchplaceholder'] = 'Name, email, username or any field value';
$string['filterby'] = 'Filter by field';
$string['anyfield'] = '— Any field —';
$string['contains'] = 'contains';
$string['emptyonly'] = 'Only users where this field is empty';
$string['showsuspended'] = 'Include suspended users';
$string['perpage'] = 'Per page';
$string['all'] = 'All';
$string['apply'] = 'Apply';
$string['reset'] = 'Reset';
$string['downloadcsv'] = 'Download this view (CSV)';
$string['viewlog'] = 'View change log';
$string['usersfound'] = '{$a} users';
$string['nousers'] = 'No users match these filters.';
$string['clicktoedit'] = '<strong>Tip:</strong> click a cell to edit. <kbd>Enter</kbd> saves and moves down, <kbd>Tab</kbd> saves and moves right, <kbd>Esc</kbd> cancels. Arrow keys move between cells.';
$string['readonlyhint'] = 'read-only';
$string['bulkedit'] = 'Bulk edit';
$string['bulkscope_selected'] = 'Ticked users on this page';
$string['bulkscope_all'] = 'All {$a} users matching the filters';
$string['set'] = 'set';
$string['to'] = 'to';
$string['bulkapply'] = 'Apply';
$string['bulkconfirm'] = 'Set "{$a->field}" for {$a->count} users?';
$string['bulknoneselected'] = 'Tick at least one user first, or choose "All users matching the filters".';
$string['bulkdone'] = '{$a->updated} users updated, {$a->failed} failed.';
$string['bulkworking'] = 'Updating… please keep this page open.';
$string['selectall'] = 'Select all on this page';
$string['saved'] = 'Saved';

// Template.
$string['template_heading'] = 'Update many users with Excel';
$string['template_summary'] = ' — download, fill in, import';
$string['template_step1'] = 'Download the template. It already contains the current data of every user.';
$string['template_step2'] = 'Change only the cells that need updating. Leave the rest as they are.';
$string['template_step3'] = 'Upload it on <a href="{$a}">Import updates</a>. You see every change before anything is saved.';
$string['template_fields'] = 'Columns to include';
$string['template_selectall'] = 'Select all / none';
$string['template_suspended'] = 'Include suspended users';
$string['template_download_xlsx'] = 'Download Excel template';
$string['template_download_csv'] = 'Download CSV';
$string['template_fullguide'] = 'Step-by-step guide';
$string['template_choosefields'] = 'Choose columns';
$string['template_title'] = 'User update template';
$string['template_sheet_users'] = 'Users';
$string['template_sheet_help'] = 'How to use';
$string['template_invalidtitle'] = 'Not an allowed value';
$string['template_invalidmenu'] = 'Choose one of the values in the drop-down list.';
$string['template_help1'] = 'Change only the cells that need updating in the Users sheet. Unchanged cells are ignored.';
$string['template_help2'] = 'Do not change the username or email columns: they identify the user. Grey columns are read-only.';
$string['template_help3'] = 'Do not rename or move the first two rows. You can delete columns you do not need, and rows of users you are not changing.';
$string['template_help4'] = 'Dates as YYYY-MM-DD (for example 2026-03-18). Drop-down columns only accept the listed values.';
$string['template_help5'] = 'Save the file and upload it on the Import updates page. Check the preview, then apply.';

// Import.
$string['import_subtitle'] = 'Upload the filled-in template. Nothing is saved until you confirm the preview.';
$string['import_step_upload'] = 'Upload the file';
$string['import_choosefile'] = 'Click to choose a file or drop it here (.xlsx or .csv)';
$string['import_maxsize'] = 'Maximum size: {$a}';
$string['import_matchby'] = 'Find users by';
$string['import_matchby_auto'] = 'Automatic (username, otherwise email)';
$string['import_matchby_username'] = 'Username';
$string['import_matchby_email'] = 'Email address';
$string['import_matchby_idnumber'] = 'ID number';
$string['import_blank'] = 'Empty cells';
$string['import_blank_skip'] = 'Leave the current value (recommended)';
$string['import_blank_clear'] = 'Clear the current value';
$string['import_previewbtn'] = 'Check file and preview changes';
$string['import_safe'] = 'Only cells that are different from the LMS are changed. Every change is written to the change log.';
$string['import_how'] = 'How it works';
$string['import_how1'] = 'Download the Excel template (it has everyone\'s current data).';
$string['import_how2'] = 'Edit the cells that need to change, then save.';
$string['import_how3'] = 'Upload it here and check the preview: you see the old and new value of every change, and any problems.';
$string['import_how4'] = 'Click Apply. Hundreds of users are updated in one go.';
$string['import_nofile'] = 'Please choose a file to upload.';
$string['import_badtype'] = 'Please upload an Excel (.xlsx) or CSV (.csv) file.';
$string['import_unreadable'] = 'The file could not be read: {$a}';
$string['import_noheader'] = 'No header row found. The file needs a username or email column (row 1 or 2 of the template).';
$string['import_nomatchcol'] = 'The file has no "{$a}" column to find users by.';
$string['import_usernotfound'] = 'User not found.';
$string['import_ambiguous'] = 'More than one user has this {$a}. Use username instead.';
$string['import_duplicaterow'] = 'This user is already on row {$a}. Only the first row is used.';
$string['import_expired'] = 'This preview has expired or was already applied. Please upload the file again.';
$string['import_preview'] = 'Preview: {$a}';
$string['import_previewintro'] = 'Users are matched by: {$a}. Nothing has been saved yet.';
$string['import_s_rows'] = 'rows in file';
$string['import_s_matched'] = 'users found';
$string['import_s_users'] = 'users to update';
$string['import_s_changes'] = 'values to change';
$string['import_s_unchanged'] = 'users unchanged';
$string['import_s_errors'] = 'problems';
$string['import_columns'] = 'Columns in the file';
$string['import_col_match'] = 'used to find users';
$string['import_col_identity'] = 'identity, not changed';
$string['import_col_update'] = 'will update';
$string['import_col_readonly'] = 'read-only, ignored';
$string['import_col_unknown'] = 'not recognised, ignored';
$string['import_apply'] = 'Apply {$a->changes} changes to {$a->users} users';
$string['import_nothing'] = 'Nothing to change: the file matches the current data.';
$string['import_errors'] = 'Problems ({$a})';
$string['import_errorsnote'] = 'These rows or cells are skipped. Everything else can still be applied.';
$string['import_changes'] = 'Changes ({$a})';
$string['import_changesmore'] = 'Showing the first {$a} changes. All of them will be applied.';
$string['import_row'] = 'Row';
$string['import_who'] = 'User';
$string['import_value'] = 'Value in file';
$string['import_problem'] = 'Problem';
$string['import_applying'] = 'Applying changes…';
$string['import_progress'] = '{$a->done} of {$a->total} users';
$string['import_r_users'] = 'users updated';
$string['import_r_fields'] = 'values changed';
$string['import_r_errors'] = 'problems';
$string['import_viewlog'] = 'View these changes in the change log';
$string['import_another'] = 'Import another file';

// Columns page.
$string['columns_subtitle'] = 'Arrange, show and lock the columns of the editor and the template.';
$string['columns_intro'] = 'Drag rows (or use the arrows) to change the column order. Profile fields you <a href="{$a}">add later</a> appear at the end automatically, shown and editable, so no update is needed.';
$string['columns_field'] = 'Field';
$string['columns_category'] = 'Category';
$string['columns_type'] = 'Type';
$string['columns_type_core'] = 'user detail';
$string['columns_show'] = 'Show';
$string['columns_editable'] = 'Editable';
$string['columns_move'] = 'Move';
$string['columns_drag'] = 'Drag to move';
$string['columns_resetorder'] = 'Reset order';
$string['columns_orderreset'] = 'Column order reset to the default.';

// Branding page.
$string['branding_subtitle'] = 'Organisation name, colours, fonts and sizes for these pages and the Excel template.';
$string['branding_presets'] = 'Start from a colour theme';
$string['branding_presets_desc'] = 'A theme fills in the colours below. You can then change any of them. Nothing is saved until you click Save changes.';
$string['preset_classic'] = 'Classic blue';
$string['preset_aquagrey'] = 'Aqua & grey';
$string['preset_navygold'] = 'Navy & gold';
$string['preset_forest'] = 'Forest';
$string['preset_plum'] = 'Plum';
$string['preset_charcoal'] = 'Charcoal & orange';
$string['branding_identity'] = 'Name';
$string['branding_orgname'] = 'Organisation name';
$string['branding_orgname_desc'] = 'Shown at the top of these pages, in the template and in file names. Leave empty to use the site name.';
$string['branding_tagline'] = 'Tagline (optional)';
$string['branding_showlogo'] = 'Show the site logo';
$string['branding_colours'] = 'Colours';
$string['branding_primary'] = 'Primary (buttons, links)';
$string['branding_primarytext'] = 'Text on primary';
$string['branding_accent'] = 'Accent';
$string['branding_headerbg'] = 'Header background';
$string['branding_headertext'] = 'Header text';
$string['branding_tableheadbg'] = 'Table header background';
$string['branding_tableheadtext'] = 'Table header text';
$string['branding_text'] = 'Text';
$string['branding_muted'] = 'Secondary text';
$string['branding_border'] = 'Borders';
$string['branding_rowhover'] = 'Cell highlight';
$string['branding_background'] = 'Card background';
$string['branding_fonts'] = 'Fonts';
$string['branding_font'] = 'Text font';
$string['branding_headingfont'] = 'Heading font';
$string['branding_samefont'] = 'Same as text';
$string['branding_customfont'] = 'Custom font name';
$string['branding_customfont_desc'] = 'Used when "Custom…" is chosen. The font must be installed or loaded with the URL below.';
$string['branding_fonturl'] = 'Font stylesheet URL (optional)';
$string['branding_fonturl_desc'] = 'An https:// link to a font stylesheet, for example from Google Fonts or the organisation\'s own font host.';
$string['branding_sizes'] = 'Sizes';
$string['branding_basesize'] = 'Text size';
$string['branding_tablesize'] = 'Table text size';
$string['branding_headingsize'] = 'Heading size';
$string['branding_radius'] = 'Corner roundness';
$string['branding_density'] = 'Table spacing';
$string['branding_density_comfortable'] = 'Comfortable';
$string['branding_density_compact'] = 'Compact (more rows on screen)';
$string['branding_reset'] = 'Reset to default';
$string['branding_resetdone'] = 'Branding reset to the default.';
$string['branding_preview'] = 'Preview';
$string['branding_samplelink'] = 'Sample link';
$string['branding_samplemanager'] = 'Line manager';
$string['branding_share'] = 'Copy to another site';
$string['branding_share_desc'] = 'Download these settings as a file and import them on another LMS.';
$string['branding_export'] = 'Download branding file';
$string['branding_import'] = 'Import branding file';
$string['branding_imported'] = 'Branding imported.';
$string['branding_badfile'] = 'That is not a branding file.';

// Errors.
$string['error_generic'] = '{$a}';
$string['error_invalidfield'] = 'This field cannot be edited.';
$string['error_required'] = 'This field cannot be empty.';
$string['error_invalidemail'] = 'Invalid email address.';
$string['error_duplicateemail'] = 'Another user already has this email address.';
$string['error_invaliddate'] = 'Invalid date. Use the format YYYY-MM-DD.';
$string['error_invalidoption'] = 'This value is not one of the allowed options.';
$string['error_toolong'] = 'Value is too long (maximum {$a} characters).';
$string['error_siteadmin'] = 'Only site administrators can edit a site administrator.';

// Change log.
$string['log_subtitle'] = 'Who changed what, when — from the editor, bulk edit or file import.';
$string['log_time'] = 'Time';
$string['log_user'] = 'User';
$string['log_field'] = 'Field';
$string['log_old'] = 'Old value';
$string['log_new'] = 'New value';
$string['log_by'] = 'Changed by';
$string['log_source'] = 'How';
$string['log_src_all'] = 'All';
$string['log_src_editor'] = 'Editor';
$string['log_src_bulk'] = 'Bulk edit';
$string['log_src_import'] = 'File import';
$string['log_src_mobile'] = 'Mobile app';
$string['log_empty'] = 'No changes yet.';
$string['log_showall'] = 'Show all users';
$string['backtoeditor'] = 'Back to editor';

// Guide.
$string['guide'] = 'Template & guide';
$string['guide_subtitle'] = 'Update many users at once with the Excel template.';
$string['guide_download_heading'] = '1. Download the template';
$string['guide_download_desc'] = 'The template is created when you click download, so it always has the current data ({$a} active users). It contains username, email and the profile fields you choose, in the same order as the editor.';
$string['guide_domains'] = 'Only users with these email domains are included: {$a}';
$string['guide_excludedcount'] = '{$a} accounts are left out (see Settings).';
$string['guide_columns'] = '2. What is in the file';
$string['guide_col_column'] = 'Column';
$string['guide_col_field'] = 'Field';
$string['guide_col_format'] = 'What to enter';
$string['guide_fmt_username'] = 'Do not change. Used to find the user.';
$string['guide_fmt_email'] = 'Leave as it is. Email is the sign-in identity; it is only changed if the email column is set as editable.';
$string['guide_fmt_readonly'] = 'Read-only. Changes in this column are ignored.';
$string['guide_fmt_text'] = 'Free text. Use exactly the same spelling for the same value (for example line manager names).';
$string['guide_fmt_menu'] = 'Must be exactly one of:';
$string['guide_fmt_checkbox'] = '1 = Yes, 0 = No (Yes / No also accepted)';
$string['guide_fmt_date'] = 'Date as YYYY-MM-DD, for example 2026-03-18 (DD/MM/YYYY also accepted)';
$string['guide_steps'] = '3. Update and import (recommended)';
$string['guide_step1'] = 'Open the downloaded Excel file. Edit only the cells that need to change.';
$string['guide_step2'] = 'Keep the username and email columns and the first two rows. You can delete other columns and rows you do not need.';
$string['guide_step3'] = 'Save the file (keep it as .xlsx).';
$string['guide_step4'] = 'Upload it on <a href="{$a}">Import updates</a>. The preview shows every change (old → new) and any problems.';
$string['guide_step5'] = 'Click Apply. All changes are saved in one go and recorded in the change log.';
$string['guide_alt'] = 'Alternative: LMS Upload users';
$string['guide_alt_desc'] = 'The CSV download also works with <a href="{$a}">Site administration &gt; Users &gt; Upload users</a>. Use these settings on its preview page. Changes made there do not appear in this change log.';
$string['guide_setting'] = 'Setting';
$string['guide_value'] = 'Choose';
$string['guide_set_uutype'] = 'Upload type';
$string['guide_val_uutype'] = 'Update existing users only';
$string['guide_set_uuupdatetype'] = 'Existing user details';
$string['guide_val_uuupdatetype'] = 'Override with file';
$string['guide_set_uupasswordold'] = 'Existing user password';
$string['guide_val_uupasswordold'] = 'No changes';
$string['guide_set_uuallowrenames'] = 'Allow renames';
$string['guide_val_uuallowrenames'] = 'No';
$string['guide_set_uustandardusernames'] = 'Standardise usernames';
$string['guide_val_uustandardusernames'] = 'No';
$string['guide_set_uubulk'] = 'Select for bulk user actions';
$string['guide_val_uubulk'] = 'No';
$string['guide_cautions'] = 'Good to know';
$string['guide_caution1'] = 'Download a fresh template right before editing. An old file could undo changes made since.';
$string['guide_caution2'] = 'Keep the original download as a backup. Importing it again puts the old values back.';
$string['guide_caution3'] = 'Empty cells are ignored by default, so you cannot clear a value by accident.';
$string['guide_caution4'] = 'Drop-down columns only accept their exact values. Anything else is reported in the preview and skipped.';
$string['guide_caution5'] = 'For a few quick fixes, editing directly in the table is faster.';

// Mobile app.
$string['mobile_menutitle'] = 'Profile field editor';
$string['mobile_editprofilefields'] = 'Edit profile fields';
$string['mobile_nav_users'] = 'Users';
$string['mobile_nav_bulk'] = 'Bulk update';
$string['mobile_nav_log'] = 'Recent changes';
$string['mobile_found'] = 'Users found:';
$string['mobile_webonly'] = 'Excel template, file import, columns and branding are on the website.';
$string['mobile_savedmsg'] = 'Changes saved';
$string['mobile_savenote'] = 'Only changed values are saved. Every change is logged.';
$string['mobile_saveproblems'] = 'Some values were not saved: {$a}';
$string['mobile_bulk_find'] = 'Find the users';
$string['mobile_bulk_count'] = 'Find users';
$string['mobile_bulk_matching'] = 'active users match.';
$string['mobile_bulk_set'] = 'Set a value for all of them';
$string['mobile_newvalue'] = 'New value';
$string['mobile_bulk_confirm'] = 'Update all the matching users? This cannot be undone from the app.';
$string['mobile_bulk_done'] = 'Users updated';
$string['mobile_bulk_apply'] = 'Update all matching users';
$string['mobile_bulknofilter'] = 'Choose a filter first, so that not every user is changed.';

// Privacy.
$string['privacy:metadata:log'] = 'A log of profile field changes made with the profile field editor.';
$string['privacy:metadata:log:userid'] = 'The user whose profile was changed.';
$string['privacy:metadata:log:fieldlabel'] = 'The field that was changed.';
$string['privacy:metadata:log:oldvalue'] = 'The value before the change.';
$string['privacy:metadata:log:newvalue'] = 'The value after the change.';
$string['privacy:metadata:log:changedby'] = 'The user who made the change.';
$string['privacy:metadata:log:timecreated'] = 'When the change was made.';
