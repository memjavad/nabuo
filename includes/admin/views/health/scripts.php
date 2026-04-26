<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<script>
jQuery(document).ready(function($) {
$('#save-health-settings').on('click', function() {
var btn = $(this);
var autoOptimize = $('#naboo-auto-optimize').is(':checked') ? 1 : 0;

btn.prop('disabled', true).text('Saving...');

$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'naboo_health_optimize',
maintenance_action: 'save_settings',
auto_optimize: autoOptimize,
nonce: '<?php echo wp_create_nonce( 'naboo_health_optimize' ); ?>'
},
success: function(response) {
btn.prop('disabled', false).text('Update Automation Settings');
if (response.success) {
}
}
});
});

$('#run-health-scan').on('click', function() {
$('#health-idle-message').hide();
$('#health-loading').show();

$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'naboo_health_check',
nonce: '<?php echo wp_create_nonce( 'naboo_health_check' ); ?>'
},
success: function(response) {
$('#health-loading').hide();
if (response.success) {
$('#health-results').html(response.data.html).show();
}
}
});
});

$(document).on('click', '.run-maintenance-action, #optimize-all-btn', function() {
var btn = $(this);
var action = btn.data('action');
var originalText = btn.text();

btn.prop('disabled', true).text('...');

$.ajax({
url: ajaxurl,
type: 'POST',
data: {
action: 'naboo_health_optimize',
maintenance_action: action,
nonce: '<?php echo wp_create_nonce( 'naboo_health_optimize' ); ?>'
},
success: function(response) {
btn.prop('disabled', false).text(originalText);
if (response.success) {
if (action === 'all') {
$('#run-health-scan').trigger('click');
} else {
btn.css('background', 'var(--naboo-success)').css('color', 'white');
setTimeout(function(){
btn.css('background', '').css('color', '');
}, 2000);
}
}
}
});
});
});
</script>
