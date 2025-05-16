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
                            data-key-field="asap_ai_huggingface_key">
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
            </table>
            
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
        const apiKey = $('#' + keyField).val();
        const resultSpan = $('#' + provider + '-test-result');
        
        if (!apiKey) {
            resultSpan.html('<span style="color:red;">Please enter an API key first</span>');
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