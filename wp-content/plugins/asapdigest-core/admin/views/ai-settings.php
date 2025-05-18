<?php
/**
 * @file-marker ASAP_Digest_AI_Settings
 * @location /wp-content/plugins/asapdigest-core/admin/views/ai-settings.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue and localize admin script for AJAX
wp_enqueue_script('asapdigest-admin', plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/js/admin.js', array('jquery'), ASAP_DIGEST_VERSION, true);
wp_localize_script('asapdigest-admin', 'asapDigestAdmin', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('asap_digest_content_nonce'),
    'restNonce' => wp_create_nonce('wp_rest'),
    'i18n' => array(
        'testing' => __('Testing...', 'asapdigest-core'),
        'success' => __('Connection successful!', 'asapdigest-core'),
        'error' => __('Connection failed: ', 'asapdigest-core')
    )
));

// Get plugin instance
$plugin = AsapDigest\Core\ASAP_Digest_Core::get_instance();

// Process form submission
if (isset($_POST['asap_ai_submit'])) {
    check_admin_referer('asap_ai_settings', 'asap_ai_nonce');
    
    // Update API keys
    update_option('asap_ai_openai_key', sanitize_text_field($_POST['asap_ai_openai_key'] ?? ''));
    update_option('asap_ai_huggingface_key', sanitize_text_field($_POST['asap_ai_huggingface_key'] ?? ''));
    update_option('asap_ai_anthropic_key', sanitize_text_field($_POST['asap_ai_anthropic_key'] ?? ''));
    
    // Update models
    update_option('asap_ai_openai_model', sanitize_text_field($_POST['asap_ai_openai_model'] ?? 'gpt-3.5-turbo'));
    update_option('asap_ai_anthropic_model', sanitize_text_field($_POST['asap_ai_anthropic_model'] ?? 'claude-2'));
    update_option('asap_ai_huggingface_model', sanitize_text_field($_POST['asap_ai_huggingface_model'] ?? 'distilbert-base-uncased'));
    
    // Update provider preferences
    $task_preferences = [];
    if (!empty($_POST['task_provider'])) {
        foreach ($_POST['task_provider'] as $task => $provider) {
            $task_preferences[sanitize_text_field($task)] = sanitize_text_field($provider);
        }
    }
    
    update_option('asap_ai_config', [
        'default_provider' => sanitize_text_field($_POST['asap_ai_default_provider'] ?? ''),
        'task_preferences' => $task_preferences,
    ]);
    
    echo '<div class="notice notice-success"><p>AI settings updated.</p></div>';
}

// Get current settings
$openai_key = get_option('asap_ai_openai_key', '');
$huggingface_key = get_option('asap_ai_huggingface_key', '');
$anthropic_key = get_option('asap_ai_anthropic_key', '');
$openai_model = get_option('asap_ai_openai_model', 'gpt-3.5-turbo');
$anthropic_model = get_option('asap_ai_anthropic_model', 'claude-2');
$huggingface_model = get_option('asap_ai_huggingface_model', 'distilbert-base-uncased');
$ai_config = get_option('asap_ai_config', []);
$default_provider = $ai_config['default_provider'] ?? '';
$task_preferences = $ai_config['task_preferences'] ?? [];

// Available provider options
$providers = [
    'openai' => 'OpenAI',
    'huggingface' => 'Hugging Face',
    'anthropic' => 'Anthropic',
    'google' => 'Google',
    'openrouter' => 'OpenRouter',
    'fireworks' => 'Fireworks',
    'groq' => 'Groq',
];

// Available tasks
$tasks = [
    'summarize' => 'Content Summarization',
    'extract_entities' => 'Entity Extraction',
    'classify' => 'Content Classification',
    'generate_keywords' => 'Keyword Generation',
    'process_image' => 'Image Analysis',
];

// OpenAI models
$openai_models = [
    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    'gpt-4' => 'GPT-4',
    'gpt-4-turbo' => 'GPT-4 Turbo',
];

// Anthropic models
$anthropic_models = [
    'claude-2' => 'Claude 2',
    'claude-3-opus' => 'Claude 3 Opus',
    'claude-3-sonnet' => 'Claude 3 Sonnet',
    'claude-3-haiku' => 'Claude 3 Haiku',
];

// Default Hugging Face models
$huggingface_models = [
    'distilbert-base-uncased' => 'DistilBERT (Base, Uncased) - General Purpose',
    'facebook/bart-large-cnn' => 'BART (Facebook/CNN) - Summarization',
    'dbmdz/bert-large-cased-finetuned-conll03-english' => 'BERT (CONLL03) - Entity Extraction',
    'ml6team/keyphrase-extraction-distilbert-inspec' => 'DistilBERT (Keyphrase) - Keyword Extraction',
    'gpt2' => 'GPT-2 - Text Generation',
];

// Get custom Hugging Face models and merge with default models
$custom_hf_models = get_option('asap_ai_custom_huggingface_models', []);
$all_huggingface_models = array_merge($huggingface_models, $custom_hf_models);

// Ensure the settings group is registered to avoid 'not in the allowed options list' error
add_action('admin_init', function() {
    register_setting('asap_ai_settings', 'asap_ai_settings');
});
?>

<div class="wrap">
    <h1>ASAP Digest AI Settings</h1>
    
    <div class="asap-settings-container">
        <!-- Settings form for API keys and model selection -->
        <form method="post" action="options.php">
            <?php 
            settings_fields('asap_ai_settings_group');
            do_settings_sections('asap_ai_settings_group');
            
            // Get the plugin folder constants
            $plugin_dir = dirname(dirname(__DIR__));

            // Get the API keys and other settings
            $openai_key = get_option('asap_ai_openai_key', '');
            $huggingface_key = get_option('asap_ai_huggingface_key', '');
            $selected_provider = get_option('asap_ai_provider', 'openai');
            $openai_model = get_option('asap_ai_openai_model', 'gpt-3.5-turbo');
            $huggingface_model = get_option('asap_ai_huggingface_model', 'mistralai/Mistral-7B-Instruct-v0.2');
            
            // Get custom OpenAI models
            $custom_openai_models = get_option('asap_ai_custom_openai_models', array());
            
            // Get custom Hugging Face models
            $custom_huggingface_models = get_option('asap_ai_custom_huggingface_models', array());
            
            // Combine with default models
            $all_openai_models = array(
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                'gpt-4' => 'GPT-4',
                'gpt-4-turbo' => 'GPT-4 Turbo',
            );
            
            $all_huggingface_models = array(
                'mistralai/Mistral-7B-Instruct-v0.2' => 'Mistral 7B Instruct',
                'meta-llama/Llama-2-7b-chat-hf' => 'Llama 2 7B Chat',
            );
            
            // Add custom models to the lists
            $all_openai_models = array_merge($all_openai_models, $custom_openai_models);
            $all_huggingface_models = array_merge($all_huggingface_models, $custom_huggingface_models);
            ?>
            
            <h2>AI Provider Configuration</h2>
            <p>Configure your AI providers and settings for content enhancement features.</p>
            
            <h3>API Keys</h3>
            <p class="description">Enter your AI provider API keys below. Each provider requires a separate API key.</p>
            
            <table class="form-table">
                <!-- OpenAI API Key -->
                <tr>
                    <th scope="row"><label for="asap_ai_openai_key">OpenAI API Key</label></th>
                    <td>
                        <input type="password" 
                               name="asap_ai_openai_key" 
                               id="asap_ai_openai_key" 
                               value="<?php echo esc_attr($openai_key); ?>" 
                               class="regular-text" 
                               autocomplete="off" />
                        <button type="button" 
                                class="button asap-test-provider-button" 
                                data-provider="openai" 
                                data-key-field="asap_ai_openai_key">
                            Test Connection
                        </button>
                        <span id="openai-test-result" class="test-result-indicator"></span>
                        <p class="description">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a></p>
                    </td>
                </tr>
                
                <!-- Anthropic API Key -->
                <tr>
                    <th scope="row"><label for="asap_ai_anthropic_key">Anthropic API Key</label></th>
                    <td>
                        <input type="password" 
                               name="asap_ai_anthropic_key" 
                               id="asap_ai_anthropic_key" 
                               value="<?php echo esc_attr($anthropic_key); ?>" 
                               class="regular-text" 
                               autocomplete="off" />
                        <button type="button" 
                                class="button asap-test-provider-button" 
                                data-provider="anthropic" 
                                data-key-field="asap_ai_anthropic_key">
                            Test Connection
                        </button>
                        <span id="anthropic-test-result" class="test-result-indicator"></span>
                        <p class="description">Get your API key from <a href="https://console.anthropic.com/keys" target="_blank">Anthropic Console</a></p>
                    </td>
                </tr>
                
                <!-- Hugging Face API Key -->
                <tr>
                    <th scope="row"><label for="asap_ai_huggingface_key">Hugging Face API Key</label></th>
                    <td>
                        <input type="password" 
                               name="asap_ai_huggingface_key" 
                               id="asap_ai_huggingface_key" 
                               value="<?php echo esc_attr($huggingface_key); ?>" 
                               class="regular-text" 
                               autocomplete="off" />
                        <button type="button" 
                                class="button asap-test-provider-button" 
                                data-provider="huggingface" 
                                data-key-field="asap_ai_huggingface_key"
                                data-model-field="asap_ai_huggingface_model">
                            Test Connection
                        </button>
                        <span id="huggingface-test-result" class="test-result-indicator"></span>
                        <p class="description">Get your API key from <a href="https://huggingface.co/settings/tokens" target="_blank">Hugging Face Token Settings</a></p>
                    </td>
                </tr>
            </table>
            
            <?php 
            // Hidden nonce field that can be accessed by jQuery - only needed for backward compatibility
            wp_nonce_field('asap_digest_content_nonce', 'asap_test_connection_nonce'); 
            ?>
            
            <input type="submit" name="asap_ai_submit" class="button button-primary" value="Save API Settings">
        </form>
            
        <h2>Model Configuration</h2>
        <p>Select the specific models to use for each provider.</p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="asap_ai_openai_model">OpenAI Model</label></th>
                <td>
                    <select name="asap_ai_openai_model" id="asap_ai_openai_model">
                        <?php foreach ($openai_models as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($openai_model, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="asap_ai_anthropic_model">Anthropic Model</label></th>
                <td>
                    <select name="asap_ai_anthropic_model" id="asap_ai_anthropic_model">
                        <?php foreach ($anthropic_models as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($anthropic_model, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="asap_ai_huggingface_model">Hugging Face Model</label></th>
                <td>
                    <select name="asap_ai_huggingface_model" id="asap_ai_huggingface_model">
                        <?php foreach ($all_huggingface_models as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($huggingface_model, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">The Hugging Face API <strong>requires</strong> a model name. For testing, use 'distilbert-base-uncased' which is a general-purpose model.</p>
                </td>
            </tr>
        </table>
        
        <!-- Hugging Face Custom Models Management Section -->
        <h2>Hugging Face Models Management</h2>
        <p>Add, edit, or remove custom Hugging Face models. Models should be verified before use. Failed models may cause errors and should be removed.</p>
        
        <div class="hf-models-management">
            <!-- Bulk Actions -->
            <div class="hf-model-bulk-actions">
                <button id="hf-verify-all-models" class="button">Verify All Models</button>
                <button id="hf-remove-failed-models" class="button">Remove Failed Models</button>
                <span id="hf-bulk-action-result"></span>
            </div>
            
            <!-- Add New Model Form -->
            <div class="hf-add-model-section">
                <div class="hf-add-options">
                    <label><input type="checkbox" id="hf-verify-before-add" checked> Verify model before adding</label>
                </div>
                
                <h3>Add New Model</h3>
                <form id="hf-add-model-form" class="hf-model-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="hf_model_id">Model ID</label></th>
                            <td>
                                <input type="text" id="hf_model_id" name="hf_model_id" class="regular-text" placeholder="e.g., mistralai/Mistral-7B-Instruct-v0.2" required>
                                <p class="description">The model ID from Hugging Face (e.g., organization/model-name)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="hf_model_label">Display Name</label></th>
                            <td>
                                <input type="text" id="hf_model_label" name="hf_model_label" class="regular-text" placeholder="e.g., Mistral 7B Instruct" required>
                                <p class="description">A human-readable name for the model</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="hf-form-actions">
                        <button type="submit" class="button button-primary">Add Model</button>
                        <button type="button" id="hf-test-new-model" class="button">Test Model</button>
                        <span id="hf-test-new-result" class="test-result-indicator"></span>
                    </div>
                </form>
            </div>
            
            <!-- Recommended Models Section -->
            <div class="hf-recommended-models-section">
                <h3>Recommended Models</h3>
                <p>These models are known to work well with the Hugging Face Inference API.</p>
                <div class="hf-recommended-models-container">
                    <?php 
                    // Include the recommended models file if it exists
                    $recommended_models_file = plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/views/hf-models-recommended.php';
                    if (file_exists($recommended_models_file)) {
                        include_once($recommended_models_file);
                    } else {
                        echo '<p>Recommended models file not found.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Custom Models List -->
            <div class="hf-custom-models-section">
                <h3>Custom Models</h3>
                <p>These are the models you've added for use with your API key.</p>
                <table class="widefat hf-models-table">
                    <thead>
                        <tr>
                            <th class="model-id">Model ID</th>
                            <th class="model-name">Display Name</th>
                            <th class="model-status">Status</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="hf-models-list">
                        <?php
                        // Display custom models
                        if (!empty($custom_hf_models)) {
                            // Get list of verified models
                            $verified_models = get_option('asap_ai_verified_huggingface_models', array());
                            $failed_models = get_option('asap_ai_failed_huggingface_models', array());
                            
                            foreach ($custom_hf_models as $model_id => $model_label) {
                                $is_verified = in_array($model_id, $verified_models);
                                $is_failed = in_array($model_id, $failed_models);
                                
                                $model_class = $is_verified ? 'model-verified' : ($is_failed ? 'model-failed' : 'model-unverified');
                                $status_html = $is_verified ? 
                                    '<span class="status-verified">✓ Verified</span>' : 
                                    ($is_failed ? 
                                        '<span class="status-failed">✗ Failed</span>' : 
                                        '<span class="status-unverified">⚠ Unverified</span>');
                                
                                echo '<tr data-model-id="' . esc_attr($model_id) . '" class="' . esc_attr($model_class) . '">';
                                echo '<td class="model-id">' . esc_html($model_id) . '</td>';
                                echo '<td class="model-name">' . esc_html($model_label) . '</td>';
                                echo '<td class="model-status">' . $status_html . '</td>';
                                echo '<td class="actions">';
                                echo '<button type="button" class="button button-small hf-test-model" data-model="' . esc_attr($model_id) . '">Test</button> ';
                                echo '<button type="button" class="button button-small hf-edit-model" data-model="' . esc_attr($model_id) . '" data-name="' . esc_attr($model_label) . '">Edit</button> ';
                                echo '<button type="button" class="button button-small hf-delete-model" data-model="' . esc_attr($model_id) . '">Delete</button>';
                                echo '<span class="test-result-indicator"></span>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr class="no-items"><td colspan="4">No custom models added yet. Add a model above or select from recommended models.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Edit Model Dialog (hidden) -->
            <div id="hf-edit-model-dialog" style="display:none;">
                <form id="hf-edit-model-form">
                    <input type="hidden" id="edit_original_model_id" name="edit_original_model_id">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="edit_model_id">Model ID</label></th>
                            <td>
                                <input type="text" id="edit_model_id" name="edit_model_id" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit_model_label">Display Name</label></th>
                            <td>
                                <input type="text" id="edit_model_label" name="edit_model_label" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit_verify_after_update">Verify After Update</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="edit_verify_after_update" name="edit_verify_after_update" checked>
                                    Test this model after updating (if ID changed)
                                </label>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
        
        <h2>Provider Preferences</h2>
        <p>Configure which provider to use for each task.</p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="asap_ai_default_provider">Default Provider</label></th>
                <td>
                    <select name="asap_ai_default_provider" id="asap_ai_default_provider">
                        <option value="">-- Select Default Provider --</option>
                        <?php foreach ($providers as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($default_provider, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">The default provider to use when no task-specific provider is set.</p>
                </td>
            </tr>
            
            <?php foreach ($tasks as $task => $label): ?>
                <tr>
                    <th scope="row"><label for="task_provider_<?php echo esc_attr($task); ?>"><?php echo esc_html($label); ?></label></th>
                    <td>
                        <select name="task_provider[<?php echo esc_attr($task); ?>]" id="task_provider_<?php echo esc_attr($task); ?>">
                            <option value="">-- Use Default Provider --</option>
                            <?php foreach ($providers as $value => $provider_label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($task_preferences[$task] ?? '', $value); ?>><?php echo esc_html($provider_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Test Integration</h2>
        <p>Test your configured providers with a sample text.</p>
        
        <div class="asap-ai-test-area">
            <textarea id="asap-ai-test-text" rows="4" placeholder="Enter some text to test AI features..." class="large-text"></textarea>
            
            <div class="asap-ai-test-controls">
                <button type="button" class="button" id="asap-ai-test-summarize">Test Summary</button>
                <button type="button" class="button" id="asap-ai-test-entities">Test Entity Extraction</button>
                <button type="button" class="button" id="asap-ai-test-keywords">Test Keywords</button>
            </div>
            
            <div id="asap-ai-test-results" style="display:none;">
                <h3>Test Results</h3>
                <div id="asap-ai-test-output"></div>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="asap_ai_submit" class="button button-primary" value="Save AI Settings">
        </p>
    </div>
</div>

<style>
    /* General Styles */
    .asap-settings-container {
        max-width: 900px;
    }
    
    .asap-ai-test-area {
        background: #f9f9f9;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .asap-ai-test-controls {
        margin: 10px 0;
    }
    
    .asap-ai-test-controls .button {
        margin-right: 10px;
    }
    
    #asap-ai-test-results {
        background: #fff;
        padding: 15px;
        border: 1px solid #ddd;
        margin-top: 15px;
    }

    /* Hugging Face Models Management Styles */
    .hf-models-management {
        margin-top: 20px;
    }
    
    .hf-model-bulk-actions {
        margin-bottom: 15px;
        background: #f9f9f9;
        padding: 10px;
        border-radius: 4px;
        display: flex;
        align-items: center;
    }
    
    .hf-model-bulk-actions button {
        margin-right: 8px;
    }
    
    #hf-bulk-action-result {
        margin-left: 10px;
    }
    
    .hf-add-model-section, 
    .hf-custom-models-section,
    .hf-recommended-models-section {
        background: #fff;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .hf-add-options {
        margin-bottom: 10px;
    }
    
    .hf-form-actions {
        margin-top: 15px;
    }
    
    .hf-form-actions button {
        margin-right: 8px;
    }
    
    .hf-models-table {
        border-collapse: collapse;
        width: 100%;
    }
    
    .hf-models-table th, 
    .hf-models-table td {
        padding: 8px;
        text-align: left;
    }
    
    .hf-models-table .model-id {
        width: 35%;
    }
    
    .hf-models-table .model-name {
        width: 25%;
    }
    
    .hf-models-table .model-status {
        width: 15%;
    }
    
    .hf-models-table .actions {
        width: 25%;
    }
    
    /* Status indicators */
    .status-verified {
        color: #46b450;
        font-weight: 600;
    }
    
    .status-failed {
        color: #dc3232;
        font-weight: 600;
    }
    
    .status-unverified {
        color: #ffb900;
        font-weight: 600;
    }
    
    /* Row highlighting */
    .model-verified {
        background-color: rgba(70, 180, 80, 0.05);
    }
    
    .model-failed {
        background-color: rgba(220, 50, 50, 0.05);
    }
    
    /* Test loading animation */
    .test-loading {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(0, 0, 0, 0.1);
        border-left-color: #0073aa;
        border-radius: 50%;
        animation: test-loading-animation 1s linear infinite;
        vertical-align: middle;
    }
    
    @keyframes test-loading-animation {
        to { transform: rotate(360deg); }
    }
    
    /* Dialog styles */
    .ui-dialog {
        padding: 0;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        border-radius: 4px;
        overflow: hidden;
    }
    
    .ui-dialog .ui-dialog-titlebar {
        background: #0073aa;
        color: #fff;
        font-weight: bold;
        border: none;
        border-radius: 0;
    }
    
    .ui-dialog .ui-dialog-titlebar-close {
        color: #fff;
    }
    
    .ui-dialog .ui-dialog-content {
        padding: 15px;
    }
    
    .ui-dialog .ui-dialog-buttonpane {
        border-top: 1px solid #ddd;
        margin-top: 0;
        background: #f9f9f9;
    }
    
    .ui-dialog .ui-dialog-buttonpane .ui-button {
        margin: 0.5em 0.4em 0.5em 0;
    }
    
    /* Recommended models section */
    .hf-recommended-models-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
        margin-top: 15px;
    }
    
    .hf-recommended-model-card {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        transition: all 0.2s ease;
    }
    
    .hf-recommended-model-card.model-verified {
        background-color: rgba(70, 180, 80, 0.05);
        border-color: rgba(70, 180, 80, 0.3);
    }
    
    .hf-recommended-model-card.model-failed {
        background-color: rgba(220, 50, 50, 0.05);
        border-color: rgba(220, 50, 50, 0.3);
    }
    
    .hf-recommended-model-card h4 {
        margin-top: 0;
        margin-bottom: 8px;
    }
    
    .hf-recommended-model-card .model-id {
        font-family: monospace;
        font-size: 12px;
        color: #777;
        margin-bottom: 8px;
        word-break: break-all;
    }
    
    .hf-recommended-model-card .model-description {
        flex-grow: 1;
        margin-bottom: 15px;
        font-size: 13px;
    }
    
    .hf-recommended-model-card .model-meta {
        font-size: 12px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .hf-recommended-model-card .model-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .hf-recommended-model-card .test-result-indicator {
        margin-left: auto;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Provider connection test handlers
    $('.asap-test-provider-button').on('click', function() {
        const provider = $(this).data('provider');
        const keyField = $(this).data('key-field');
        const modelField = $(this).data('model-field');
        const apiKey = $('#' + keyField).val();
        const resultSpan = $('#' + provider + '-test-result');
        
        // Additional data for the AJAX request (model for Hugging Face)
        const additionalData = {};
        
        // If this is a Hugging Face test and we have a model field
        if (provider === 'huggingface' && modelField) {
            additionalData.model = $('#' + modelField).val();
        }
        
        if (!apiKey) {
            resultSpan.html('<span style="color:red;">Please enter an API key first</span>');
            return;
        }
        
        // For Hugging Face, ensure model is selected
        if (provider === 'huggingface' && (!additionalData.model || additionalData.model === '')) {
            resultSpan.html('<span style="color:red;">Please select a model</span>');
            return;
        }
        
        // Show loading indicator with timer
        let seconds = 0;
        resultSpan.html('<span class="test-loading"></span> Testing... <span class="test-timer">0</span>s');
        
        const timer = setInterval(function() {
            seconds++;
            resultSpan.find('.test-timer').text(seconds);
        }, 1000);
        
        // Use the global nonce from asapDigestAdmin
        const nonceValue = asapDigestAdmin.nonce;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_test_ai_connection',
                provider: provider,
                api_key: apiKey,
                model: additionalData.model, // Add model for Hugging Face
                nonce: nonceValue
            },
            timeout: 30000, // 30 second timeout
            success: function(response) {
                clearInterval(timer);
                console.log(provider + ' test response:', response);
                
                if (response.success) {
                    resultSpan.html('<span style="color:green;">✓ Connected successfully</span>');
                } else {
                    resultSpan.html('<span style="color:red;">✗ ' + (response.data ? response.data.message : 'Unknown error') + '</span>');
                }
            },
            error: function(xhr, status, error) {
                clearInterval(timer);
                console.error(provider + ' test error:', {xhr, status, error});
                let errorMessage = error;
                if (status === 'timeout') {
                    errorMessage = 'Connection timed out after 30 seconds';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    try {
                        const resp = JSON.parse(xhr.responseText);
                        if (resp.data && resp.data.message) {
                            errorMessage = resp.data.message;
                        }
                    } catch (e) {}
                }
                resultSpan.html('<span style="color:red;">✗ ' + errorMessage + '</span>');
            }
        });
    });
    
    // Test button click handlers
    $('#asap-ai-test-summarize').on('click', function() {
        testAIFeature('summarize');
    });
    
    $('#asap-ai-test-entities').on('click', function() {
        testAIFeature('entities');
    });
    
    $('#asap-ai-test-keywords').on('click', function() {
        testAIFeature('keywords');
    });
    
    // Direct AJAX test handler
    $('#manual-ajax-test').on('click', function() {
        var resultSpan = $('#manual-test-result');
        resultSpan.html('<span class="test-loading"></span> Sending direct AJAX request...');
        
        // Use the global nonce from asapDigestAdmin
        const nonceValue = asapDigestAdmin.nonce;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_test_ai_connection',
                provider: 'anthropic',
                api_key: $('#asap_ai_anthropic_key').val(),
                nonce: nonceValue
            },
            success: function(response) {
                console.log('Manual test response:', response);
                if (response.success) {
                    resultSpan.html('<span style="color:green;">✓ ' + response.data.message + '</span>');
                } else {
                    resultSpan.html('<span style="color:red;">✗ ' + (response.data ? response.data.message : 'Unknown error') + '</span>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Manual test error:', {xhr, status, error});
                resultSpan.html('<span style="color:red;">✗ AJAX Error: ' + error + '</span>');
            }
        });
    });
    
    function testAIFeature(feature) {
        const text = $('#asap-ai-test-text').val();
        if (!text) {
            alert('Please enter some text to test.');
            return;
        }
        
        $('#asap-ai-test-output').html('<p><span class="test-loading"></span> Processing, please wait... <span id="request-timer">0</span> seconds</p>');
        $('#asap-ai-test-results').show();
        
        // Add timer to indicate how long the request is taking
        let seconds = 0;
        const timer = setInterval(function() {
            seconds++;
            $('#request-timer').text(seconds);
        }, 1000);
        
        $.ajax({
            url: '/wp-json/asap/v1/ai/' + feature,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ text: text }),
            timeout: 60000, // 60 seconds timeout
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', asapDigestAdmin.restNonce);
            },
            success: function(response) {
                clearInterval(timer);
                let output = '';
                
                console.log('Test response:', feature, response);
                
                if (feature === 'summarize') {
                    if (response.summary) {
                    output = '<p><strong>Summary:</strong> ' + response.summary + '</p>';
                    } else {
                        output = '<p><strong>Summary:</strong> ' + JSON.stringify(response) + '</p>';
                    }
                } else if (feature === 'entities') {
                    if (response.entities && Array.isArray(response.entities)) {
                    output = '<p><strong>Entities:</strong></p><ul>';
                    response.entities.forEach(function(entity) {
                        output += '<li>' + entity.entity + ' (' + entity.type + ', confidence: ' + (entity.confidence * 100).toFixed(1) + '%)</li>';
                    });
                    output += '</ul>';
                    } else {
                        output = '<p><strong>Entities:</strong> ' + JSON.stringify(response) + '</p>';
                    }
                } else if (feature === 'keywords') {
                    if (response.keywords && Array.isArray(response.keywords)) {
                    output = '<p><strong>Keywords:</strong></p><ul>';
                    response.keywords.forEach(function(keyword) {
                        output += '<li>' + keyword.keyword + ' (score: ' + (keyword.score * 100).toFixed(1) + '%)</li>';
                    });
                    output += '</ul>';
                } else {
                        output = '<p><strong>Keywords:</strong> ' + JSON.stringify(response) + '</p>';
                    }
                } else {
                    output = '<p>No results returned.</p><pre>' + JSON.stringify(response, null, 2) + '</pre>';
                }
                
                $('#asap-ai-test-output').html(output);
            },
            error: function(xhr, status, error) {
                clearInterval(timer);
                let errorMessage = 'An error occurred.';
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out after 60 seconds. The AI service might be experiencing delays.';
                } else {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                    } catch (e) {
                        // Use the error parameter if available
                        if (error) {
                            errorMessage = error;
                        }
                    }
                }
                
                console.error('AJAX error:', { status, error, xhr });
                $('#asap-ai-test-output').html('<p class="error">Error: ' + errorMessage + '</p>');
            }
        });
    }
});
</script>

<!-- Manual AJAX Test Section -->
<div class="wrap" style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 20px;">
    <h2>Debug: Manual AJAX Test</h2>
    <p>This section is for debugging the AI connection test functionality.</p>
    
    <button id="manual-ajax-test" class="button button-secondary">Send Manual AJAX Test</button>
    <span id="manual-test-result" style="margin-left: 10px; font-weight: bold;"></span>
    
    <div class="notice notice-info" style="margin-top: 15px;">
        <p><strong>Debug Information:</strong></p>
        <ul>
            <li>AJAX URL: <code><?php echo esc_html(admin_url('admin-ajax.php')); ?></code></li>
            <li>Nonce Action: <code>asap_digest_content_nonce</code></li>
            <li>Nonce Value: <code><?php echo esc_html(wp_create_nonce('asap_digest_content_nonce')); ?></code></li>
            <li>Test for wp_ajax_asap_test_ai_connection: <?php echo has_action('wp_ajax_asap_test_ai_connection') ? '<span style="color:green">✓ Registered</span>' : '<span style="color:red">✗ Not Registered</span>'; ?></li>
        </ul>
    </div>
</div>

<?php
// Add JavaScript for the custom models management
add_action('admin_footer', function() {
    // No need to add additional JavaScript as we've already handled this in admin.js
});
?> 