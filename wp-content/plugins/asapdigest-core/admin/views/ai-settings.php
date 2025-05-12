<?php
/**
 * @file-marker ASAP_Digest_AI_Settings
 * @location /wp-content/plugins/asapdigest-core/admin/views/ai-settings.php
 */

if (!defined('ABSPATH')) {
    exit;
}

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
    <form method="post" action="options.php">
        <?php settings_fields('asap_ai_settings'); ?>
        <?php $opts = get_option('asap_ai_settings', []); ?>
            <table class="form-table">
                <tr>
                <th scope="row"><label for="asap_ai_provider">Provider</label></th>
                <td>
                    <select name="asap_ai_settings[provider]" id="asap_ai_provider">
                        <option value="openai" <?php selected($opts['provider'] ?? '', 'openai'); ?>>OpenAI</option>
                        <option value="anthropic" <?php selected($opts['provider'] ?? '', 'anthropic'); ?>>Anthropic</option>
                        <!-- Add more providers as needed -->
                    </select>
                    </td>
                </tr>
                <tr>
                <th scope="row"><label for="asap_ai_api_key">API Key</label></th>
                    <td>
                    <input type="password" name="asap_ai_settings[api_key]" id="asap_ai_api_key" value="<?php echo esc_attr($opts['api_key'] ?? ''); ?>" size="40" autocomplete="off" />
                    <button type="button" class="button" onclick="asapTestAIConnection()">Test Connection</button>
                    <span id="ai-test-result"></span>
                    </td>
                </tr>
                <tr>
                <th scope="row">Enable AI Provider</th>
                    <td>
                    <input type="checkbox" name="asap_ai_settings[enabled]" value="1" <?php checked($opts['enabled'] ?? false, true); ?> />
                    </td>
                </tr>
            </table>
        <?php submit_button('Save AI Settings'); ?>
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

<script>
jQuery(document).ready(function($) {
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
    
    function testAIFeature(feature) {
        const text = $('#asap-ai-test-text').val();
        if (!text) {
            alert('Please enter some text to test.');
            return;
        }
        
        $('#asap-ai-test-output').html('<p>Processing, please wait...</p>');
        $('#asap-ai-test-results').show();
        
        $.ajax({
            url: '/wp-json/asap/v1/ai/' + feature,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ text: text }),
            success: function(response) {
                let output = '';
                
                if (feature === 'summarize' && response.summary) {
                    output = '<p><strong>Summary:</strong> ' + response.summary + '</p>';
                } else if (feature === 'entities' && response.entities) {
                    output = '<p><strong>Entities:</strong></p><ul>';
                    response.entities.forEach(function(entity) {
                        output += '<li>' + entity.entity + ' (' + entity.type + ', confidence: ' + (entity.confidence * 100).toFixed(1) + '%)</li>';
                    });
                    output += '</ul>';
                } else if (feature === 'keywords' && response.keywords) {
                    output = '<p><strong>Keywords:</strong></p><ul>';
                    response.keywords.forEach(function(keyword) {
                        output += '<li>' + keyword.keyword + ' (score: ' + (keyword.score * 100).toFixed(1) + '%)</li>';
                    });
                    output += '</ul>';
                } else {
                    output = '<p>No results returned.</p>';
                }
                
                $('#asap-ai-test-output').html(output);
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {}
                
                $('#asap-ai-test-output').html('<p class="error">Error: ' + errorMessage + '</p>');
            }
        });
    }
});

function asapTestAIConnection() {
    var provider = document.getElementById('asap_ai_provider').value;
    var apiKey = document.getElementById('asap_ai_api_key').value;
    var nonce = typeof asapDigestAdmin !== 'undefined' ? asapDigestAdmin.nonce : '';
    var resultSpan = document.getElementById('ai-test-result');
    resultSpan.textContent = 'Testing...';
    var data = new FormData();
    data.append('action', 'asap_test_ai_connection');
    data.append('provider', provider);
    data.append('api_key', apiKey);
    data.append('nonce', nonce);

    // Log the data being sent
    console.log('Testing AI Connection with data:', {
        action: data.get('action'),
        provider: data.get('provider'),
        apiKeyLength: data.get('api_key') ? data.get('api_key').length : 0, // Log length, not the key
        nonce: data.get('nonce')
    });

    fetch(ajaxurl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
    })
    .then(function(response) { return response.json(); })
    .then(function(json) {
        if (json.success) {
            resultSpan.textContent = json.data.message || 'Connection successful!';
            resultSpan.style.color = 'green';
        } else {
            resultSpan.textContent = json.data && json.data.message ? json.data.message : 'Connection failed.';
            resultSpan.style.color = 'red';
        }
    })
    .catch(function(err) {
        resultSpan.textContent = 'AJAX error: ' + err;
        resultSpan.style.color = 'red';
    });
}
</script> 