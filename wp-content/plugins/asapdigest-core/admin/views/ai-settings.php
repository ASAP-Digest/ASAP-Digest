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
?>

<div class="wrap">
    <h1>AI Settings</h1>
    
    <p>Configure your AI providers and settings for content enhancement features.</p>
    
    <form method="post" action="">
        <?php wp_nonce_field('asap_ai_settings', 'asap_ai_nonce'); ?>
        
        <div class="asap-settings-container">
            <h2>Provider API Keys</h2>
            <p>Enter your API keys for the providers you want to use.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="asap_ai_openai_key">OpenAI API Key</label></th>
                    <td>
                        <input type="password" name="asap_ai_openai_key" id="asap_ai_openai_key" value="<?php echo esc_attr($openai_key); ?>" class="regular-text">
                        <p class="description">Your OpenAI API key for GPT models.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="asap_ai_huggingface_key">Hugging Face API Key</label></th>
                    <td>
                        <input type="password" name="asap_ai_huggingface_key" id="asap_ai_huggingface_key" value="<?php echo esc_attr($huggingface_key); ?>" class="regular-text">
                        <p class="description">Your Hugging Face API key for transformer models.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="asap_ai_anthropic_key">Anthropic API Key</label></th>
                    <td>
                        <input type="password" name="asap_ai_anthropic_key" id="asap_ai_anthropic_key" value="<?php echo esc_attr($anthropic_key); ?>" class="regular-text">
                        <p class="description">Your Anthropic API key for Claude models.</p>
                    </td>
                </tr>
            </table>
            
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
    </form>
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
</script> 