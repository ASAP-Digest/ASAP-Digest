<?php
/**
 * Simple admin page to test template creation
 * Access via: /wp-admin/admin.php?page=test-templates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user has admin permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Handle form submission
if (isset($_POST['create_templates']) && wp_verify_nonce($_POST['nonce'], 'asap_create_templates')) {
    try {
        $template_cpt = new \ASAPDigest\CPT\ASAP_Digest_Template_CPT();
        $template_cpt->force_create_default_templates();
        $message = '<div class="notice notice-success"><p>Default templates created successfully!</p></div>';
    } catch (Exception $e) {
        $message = '<div class="notice notice-error"><p>Error creating templates: ' . esc_html($e->getMessage()) . '</p></div>';
    }
}

// Check existing templates
$existing_templates = get_posts([
    'post_type' => 'asap_digest_template',
    'post_status' => 'publish',
    'numberposts' => -1
]);

?>
<div class="wrap">
    <h1>ASAP Digest Template Test</h1>
    
    <?php if (isset($message)) echo $message; ?>
    
    <div class="card">
        <h2>Current Templates</h2>
        <?php if (empty($existing_templates)): ?>
            <p><strong>No templates found!</strong> This might be why the digest creation page is failing.</p>
        <?php else: ?>
            <p>Found <?php echo count($existing_templates); ?> templates:</p>
            <ul>
                <?php foreach ($existing_templates as $template): ?>
                    <li>
                        <strong><?php echo esc_html($template->post_title); ?></strong> 
                        (ID: <?php echo $template->ID; ?>)
                        <br>
                        <small><?php echo esc_html($template->post_content); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Actions</h2>
        <form method="post">
            <?php wp_nonce_field('asap_create_templates', 'nonce'); ?>
            <p>
                <input type="submit" name="create_templates" class="button button-primary" 
                       value="Force Create Default Templates" 
                       onclick="return confirm('This will delete existing system templates and create new ones. Continue?');">
            </p>
            <p class="description">
                This will delete any existing system-created templates and create fresh default templates.
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2>API Test</h2>
        <p>
            <a href="<?php echo home_url('/test-api'); ?>" class="button" target="_blank">
                Test API Endpoints
            </a>
        </p>
        <p class="description">
            This will open a test page that tries to fetch layout templates via the REST API.
        </p>
    </div>
    
    <div class="card">
        <h2>Debug Info</h2>
        <p><strong>WordPress REST API Base:</strong> <?php echo rest_url('asap/v1/'); ?></p>
        <p><strong>Layout Templates Endpoint:</strong> <?php echo rest_url('asap/v1/digest-builder/layouts'); ?></p>
        <p><strong>Current User ID:</strong> <?php echo get_current_user_id(); ?></p>
        <p><strong>User Can Manage Options:</strong> <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></p>
    </div>
</div>

<style>
.card {
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.card h2 {
    margin-top: 0;
}

.card ul {
    margin-left: 20px;
}

.card li {
    margin-bottom: 10px;
}
</style> 