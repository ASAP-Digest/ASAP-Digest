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
    <h1>AI Settings</h1>
    
    <p>Configure your AI providers and settings for content enhancement features.</p>
    
    <h2>AI Provider Configuration</h2>
    <form method="post" action="<?php echo admin_url('admin.php?page=asap-ai-settings'); ?>" name="asap_ai_config_form">
        <?php wp_nonce_field('asap_ai_settings', 'asap_ai_nonce'); ?>
        
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
        
                    <?php wp_nonce_field('asap_digest_content_nonce', 'asap_test_connection_nonce'); ?>
                    <script>
                    // Make the nonce accessible globally for the test connection function
                    window.asapTestConnectionNonce = document.getElementById('asap_test_connection_nonce').value;
                    </script>
                
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
            <p>Add, edit, or remove custom Hugging Face models. Use this section to manage models that work with your API key.</p>
            
            <div class="hf-models-management">
                <!-- Add New Model Form -->
                <div class="hf-add-model-section">
                    <h3>Add New Model</h3>
                    <form id="hf-add-model-form" class="hf-model-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="hf_model_id">Model ID</label></th>
                                <td>
                                    <input type="text" id="hf_model_id" name="hf_model_id" class="regular-text" placeholder="e.g., distilbert-base-uncased" required>
                                    <p class="description">The Hugging Face model ID (e.g., 'distilbert-base-uncased' or 'facebook/bart-large-cnn')</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hf_model_label">Display Name</label></th>
                                <td>
                                    <input type="text" id="hf_model_label" name="hf_model_label" class="regular-text" placeholder="e.g., DistilBERT - General Purpose" required>
                                    <p class="description">A human-readable name for this model</p>
                                </td>
                            </tr>
                            <tr>
                                <th></th>
                                <td>
                                    <button type="button" id="hf-test-new-model" class="button button-secondary">Test Model</button>
                                    <button type="button" id="hf-browse-models" class="button button-secondary">Browse Recommended Models</button>
                                    <span id="hf-test-new-result" class="test-result-indicator"></span>
                                </td>
                            </tr>
                            <tr>
                                <th></th>
                                <td>
                                    <button type="submit" class="button button-primary">Add Model</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <!-- Existing Models Table -->
                <div class="hf-models-list-section">
                    <h3>Existing Models</h3>
                    <?php 
                    // Get existing custom models if any
                    $custom_hf_models = get_option('asap_ai_custom_huggingface_models', []);
                    ?>
                    <table class="widefat hf-models-table">
                        <thead>
                            <tr>
                                <th>Model ID</th>
                                <th>Display Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="hf-models-list">
                            <?php if (!empty($custom_hf_models)) : ?>
                                <?php foreach ($custom_hf_models as $model_id => $model_name) : ?>
                                    <tr data-model-id="<?php echo esc_attr($model_id); ?>">
                                        <td class="model-id"><?php echo esc_html($model_id); ?></td>
                                        <td class="model-name"><?php echo esc_html($model_name); ?></td>
                                        <td class="actions">
                                            <button type="button" class="button button-small hf-test-model" data-model="<?php echo esc_attr($model_id); ?>">Test</button>
                                            <button type="button" class="button button-small hf-edit-model" data-model="<?php echo esc_attr($model_id); ?>" data-name="<?php echo esc_attr($model_name); ?>">Edit</button>
                                            <button type="button" class="button button-small hf-delete-model" data-model="<?php echo esc_attr($model_id); ?>">Delete</button>
                                            <span class="test-result-indicator"></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-items">
                                    <td colspan="3">No custom models added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Edit Model Dialog (Hidden by default) -->
                <div id="hf-edit-model-dialog" style="display:none;">
                    <form id="hf-edit-model-form">
                        <input type="hidden" id="edit_original_model_id" name="edit_original_model_id">
                        <div class="form-field">
                            <label for="edit_model_id">Model ID:</label>
                            <input type="text" id="edit_model_id" name="edit_model_id" class="regular-text" required>
                        </div>
                        <div class="form-field">
                            <label for="edit_model_label">Display Name:</label>
                            <input type="text" id="edit_model_label" name="edit_model_label" class="regular-text" required>
                        </div>
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

<style>
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
</style>

<style>
.test-result-indicator {
    margin-left: 10px;
    font-weight: bold;
    display: inline-block;
    min-width: 100px;
}
.test-loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    margin-right: 5px;
    border: 2px solid rgba(0,0,0,0.2);
    border-radius: 50%;
    border-top-color: #0073aa;
    animation: asap-spinner 0.8s linear infinite;
}
@keyframes asap-spinner {
    to { transform: rotate(360deg); }
}
.asap-ai-test-area .error {
    color: #d63638;
    font-weight: bold;
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
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_test_ai_connection',
                provider: provider,
                api_key: apiKey,
                model: additionalData.model, // Add model for Hugging Face
                nonce: $('#asap_test_connection_nonce').val()
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
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_test_ai_connection',
                provider: 'anthropic',
                api_key: $('#asap_ai_anthropic_key').val(),
                nonce: $('#asap_test_connection_nonce').val()
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
?>
<script>
jQuery(document).ready(function($) {
    // Function to save custom models
    function saveCustomModels(models, callback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_save_custom_hf_models',
                models: models,
                nonce: $('#asap_test_connection_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    if (callback) callback(true);
                } else {
                    alert('Error saving models: ' + (response.data ? response.data.message : 'Unknown error'));
                    if (callback) callback(false);
                }
            },
            error: function() {
                alert('Server error while saving models.');
                if (callback) callback(false);
            }
        });
    }
    
    // Function to get current custom models
    function getCurrentModels() {
        let models = {};
        $('#hf-models-list tr:not(.no-items)').each(function() {
            const modelId = $(this).data('model-id');
            const modelName = $(this).find('.model-name').text();
            if (modelId) {
                models[modelId] = modelName;
            }
        });
        return models;
    }
    
    // Test a Hugging Face model
    function testHuggingFaceModel(modelId, resultElement) {
        const apiKey = $('#asap_ai_huggingface_key').val();
        
        if (!apiKey) {
            resultElement.html('<span style="color:red;">Please enter an API key first</span>');
            return;
        }
        
        if (!modelId) {
            resultElement.html('<span style="color:red;">Model ID is required</span>');
            return;
        }
        
        // Show loading indicator with timer
        let seconds = 0;
        resultElement.html('<span class="test-loading"></span> Testing... <span class="test-timer">0</span>s');
        
        const timer = setInterval(function() {
            seconds++;
            resultElement.find('.test-timer').text(seconds);
        }, 1000);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_test_ai_connection',
                provider: 'huggingface',
                api_key: apiKey,
                model: modelId,
                nonce: $('#asap_test_connection_nonce').val()
            },
            timeout: 30000,
            success: function(response) {
                clearInterval(timer);
                
                if (response.success) {
                    resultElement.html('<span style="color:green;">✓ Model works!</span>');
                } else {
                    resultElement.html('<span style="color:red;">✗ ' + (response.data ? response.data.message : 'Unknown error') + '</span>');
                }
            },
            error: function(xhr, status, error) {
                clearInterval(timer);
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
                resultElement.html('<span style="color:red;">✗ ' + errorMessage + '</span>');
            }
        });
    }
    
    // Add new model form submission
    $('#hf-add-model-form').on('submit', function(e) {
        e.preventDefault();
        
        const modelId = $('#hf_model_id').val().trim();
        const modelLabel = $('#hf_model_label').val().trim();
        
        if (!modelId || !modelLabel) {
            alert('Both Model ID and Display Name are required.');
            return;
        }
        
        // Get current models
        let models = getCurrentModels();
        
        // Check if model ID already exists
        if (models[modelId]) {
            alert('A model with this ID already exists. Please use a different ID or edit the existing model.');
            return;
        }
        
        // Add new model
        models[modelId] = modelLabel;
        
        // Save updated models
        saveCustomModels(models, function(success) {
            if (success) {
                // Add row to the table
                const newRow = $('<tr data-model-id="' + modelId + '">' +
                    '<td class="model-id">' + modelId + '</td>' +
                    '<td class="model-name">' + modelLabel + '</td>' +
                    '<td class="actions">' +
                    '<button type="button" class="button button-small hf-test-model" data-model="' + modelId + '">Test</button> ' +
                    '<button type="button" class="button button-small hf-edit-model" data-model="' + modelId + '" data-name="' + modelLabel + '">Edit</button> ' +
                    '<button type="button" class="button button-small hf-delete-model" data-model="' + modelId + '">Delete</button>' +
                    '<span class="test-result-indicator"></span>' +
                    '</td>' +
                    '</tr>');
                
                // Remove "no items" row if present
                $('#hf-models-list tr.no-items').remove();
                
                // Add new row
                $('#hf-models-list').append(newRow);
                
                // Add to dropdown
                const option = new Option(modelLabel, modelId);
                $('#asap_ai_huggingface_model').append(option);
                
                // Clear form
                $('#hf_model_id').val('');
                $('#hf_model_label').val('');
                $('#hf-test-new-result').html('');
                
                // Show success message
                alert('Model added successfully!');
            }
        });
    });
    
    // Test new model button
    $('#hf-test-new-model').on('click', function() {
        const modelId = $('#hf_model_id').val().trim();
        if (!modelId) {
            $('#hf-test-new-result').html('<span style="color:red;">Please enter a Model ID first</span>');
            return;
        }
        
        testHuggingFaceModel(modelId, $('#hf-test-new-result'));
    });
    
    // Test existing model button
    $(document).on('click', '.hf-test-model', function() {
        const modelId = $(this).data('model');
        const resultSpan = $(this).siblings('.test-result-indicator');
        
        testHuggingFaceModel(modelId, resultSpan);
    });
    
    // Edit model button
    $(document).on('click', '.hf-edit-model', function() {
        const modelId = $(this).data('model');
        const modelName = $(this).data('name');
        
        // Populate edit form
        $('#edit_original_model_id').val(modelId);
        $('#edit_model_id').val(modelId);
        $('#edit_model_label').val(modelName);
        
        // Show dialog
        $('#hf-edit-model-dialog').show();
    });
    
    // Update model button
    $('#hf-update-model').on('click', function() {
        const originalModelId = $('#edit_original_model_id').val();
        const newModelId = $('#edit_model_id').val().trim();
        const newModelLabel = $('#edit_model_label').val().trim();
        
        if (!newModelId || !newModelLabel) {
            alert('Both Model ID and Display Name are required.');
            return;
        }
        
        // Get current models
        let models = getCurrentModels();
        
        // Check if new model ID already exists (unless it's the same as original)
        if (newModelId !== originalModelId && models[newModelId]) {
            alert('A model with this ID already exists. Please use a different ID.');
            return;
        }
        
        // Remove original model
        delete models[originalModelId];
        
        // Add updated model
        models[newModelId] = newModelLabel;
        
        // Save updated models
        saveCustomModels(models, function(success) {
            if (success) {
                // Find the row to update
                const row = $('#hf-models-list tr[data-model-id="' + originalModelId + '"]');
                
                // Update row
                row.attr('data-model-id', newModelId);
                row.find('.model-id').text(newModelId);
                row.find('.model-name').text(newModelLabel);
                row.find('.hf-test-model').attr('data-model', newModelId);
                row.find('.hf-edit-model').attr('data-model', newModelId).attr('data-name', newModelLabel);
                row.find('.hf-delete-model').attr('data-model', newModelId);
                
                // Update in dropdown
                const dropdownOption = $('#asap_ai_huggingface_model option[value="' + originalModelId + '"]');
                if (dropdownOption.length) {
                    dropdownOption.val(newModelId).text(newModelLabel);
                } else {
                    // Add if not exists
                    const option = new Option(newModelLabel, newModelId);
                    $('#asap_ai_huggingface_model').append(option);
                }
                
                // Hide dialog
                $('#hf-edit-model-dialog').hide();
                
                // Show success message
                alert('Model updated successfully!');
            }
        });
    });
    
    // Cancel edit button
    $('#hf-cancel-edit').on('click', function() {
        $('#hf-edit-model-dialog').hide();
    });
    
    // Delete model button
    $(document).on('click', '.hf-delete-model', function() {
        if (!confirm('Are you sure you want to delete this model?')) {
            return;
        }
        
        const modelId = $(this).data('model');
        const row = $(this).closest('tr');
        
        // Get current models
        let models = getCurrentModels();
        
        // Remove model
        delete models[modelId];
        
        // Save updated models
        saveCustomModels(models, function(success) {
            if (success) {
                // Remove row
                row.remove();
                
                // Add "no items" row if this was the last model
                if ($('#hf-models-list tr').length === 0) {
                    $('#hf-models-list').append('<tr class="no-items"><td colspan="3">No custom models added yet.</td></tr>');
                }
                
                // Remove from dropdown
                $('#asap_ai_huggingface_model option[value="' + modelId + '"]').remove();
                
                // Show success message
                alert('Model deleted successfully!');
            }
        });
    });
});
</script>

<style>
.hf-models-management {
    margin-top: 20px;
}
.hf-add-model-section {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.hf-models-table {
    margin-top: 10px;
}
.hf-models-table .actions {
    white-space: nowrap;
}
.hf-models-table .button-small {
    margin-right: 5px;
}
#hf-edit-model-dialog {
    position: fixed;
    z-index: 100;
    background: white;
    border: 1px solid #ccc;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
    padding: 20px;
    border-radius: 4px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 400px;
    max-width: 90%;
}
#hf-edit-model-dialog .form-field {
    margin-bottom: 15px;
}
#hf-edit-model-dialog label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
#hf-edit-model-dialog input {
    width: 100%;
    margin-bottom: 5px;
}
#hf-edit-model-dialog .dialog-buttons {
    margin-top: 15px;
    text-align: right;
}
#hf-edit-model-dialog .dialog-buttons button {
    margin-left: 10px;
}
</style>
<?php
});
?> 