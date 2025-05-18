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
    
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('AI settings updated.', 'asapdigest-core') . '</p></div>';
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

// Get the plugin folder constants
$plugin_dir = dirname(dirname(__DIR__));

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

// Get list of verified models
$verified_models = get_option('asap_ai_verified_huggingface_models', array());
$failed_models = get_option('asap_ai_failed_huggingface_models', array());
?>

<div class="wrap asap-central-command">
    <h1><?php echo esc_html__('ASAP Digest AI Settings', 'asapdigest-core'); ?></h1>
    
    <?php 
    // Display any admin notices
    settings_errors('asap_messages');
    
    // Hidden nonce field that can be accessed by jQuery - only needed for backward compatibility
    wp_nonce_field('asap_digest_content_nonce', 'asap_test_connection_nonce'); 
    ?>
    
    <div class="asap-dashboard-grid">
        <form method="post" action="" class="asap-settings__form">
            <?php wp_nonce_field('asap_ai_settings', 'asap_ai_nonce'); ?>
            
            <div class="asap-settings__card">
                <div class="asap-settings__card-header">
                    <h2><?php echo esc_html__('API Keys', 'asapdigest-core'); ?></h2>
                </div>
                <div class="asap-settings__card-content">
                    <p class="description"><?php echo esc_html__('Enter your AI provider API keys below. Each provider requires a separate API key.', 'asapdigest-core'); ?></p>
                    
                    <table class="form-table">
                        <!-- OpenAI API Key -->
                        <tr>
                            <th scope="row"><label for="asap_ai_openai_key"><?php echo esc_html__('OpenAI API Key', 'asapdigest-core'); ?></label></th>
                            <td>
                                <div class="asap-settings__field-group">
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
                                        <?php echo esc_html__('Test Connection', 'asapdigest-core'); ?>
                                    </button>
                                    <span id="openai-test-result" class="test-result-indicator"></span>
                                </div>
                                <p class="description"><?php printf(__('Get your API key from <a href="%s" target="_blank">OpenAI Dashboard</a>', 'asapdigest-core'), 'https://platform.openai.com/api-keys'); ?></p>
                            </td>
                        </tr>
                        
                        <!-- Anthropic API Key -->
                        <tr>
                            <th scope="row"><label for="asap_ai_anthropic_key"><?php echo esc_html__('Anthropic API Key', 'asapdigest-core'); ?></label></th>
                            <td>
                                <div class="asap-settings__field-group">
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
                                        <?php echo esc_html__('Test Connection', 'asapdigest-core'); ?>
                                    </button>
                                    <span id="anthropic-test-result" class="test-result-indicator"></span>
                                </div>
                                <p class="description"><?php printf(__('Get your API key from <a href="%s" target="_blank">Anthropic Console</a>', 'asapdigest-core'), 'https://console.anthropic.com/keys'); ?></p>
                            </td>
                        </tr>
                        
                        <!-- Hugging Face API Key -->
                        <tr>
                            <th scope="row"><label for="asap_ai_huggingface_key"><?php echo esc_html__('Hugging Face API Key', 'asapdigest-core'); ?></label></th>
                            <td>
                                <div class="asap-settings__field-group">
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
                                        <?php echo esc_html__('Test Connection', 'asapdigest-core'); ?>
                                    </button>
                                    <span id="huggingface-test-result" class="test-result-indicator"></span>
                                </div>
                                <p class="description"><?php printf(__('Get your API key from <a href="%s" target="_blank">Hugging Face Token Settings</a>', 'asapdigest-core'), 'https://huggingface.co/settings/tokens'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div><!-- .asap-settings__card -->
            
            <div class="asap-settings__card">
                <div class="asap-settings__card-header">
                    <h2><?php echo esc_html__('Model Configuration', 'asapdigest-core'); ?></h2>
                </div>
                <div class="asap-settings__card-content">
                    <p class="description"><?php echo esc_html__('Select the specific models to use for each provider.', 'asapdigest-core'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="asap_ai_openai_model"><?php echo esc_html__('OpenAI Model', 'asapdigest-core'); ?></label></th>
                            <td>
                                <select name="asap_ai_openai_model" id="asap_ai_openai_model" class="regular-text">
                                    <?php foreach ($openai_models as $value => $label): ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($openai_model, $value); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="asap_ai_anthropic_model"><?php echo esc_html__('Anthropic Model', 'asapdigest-core'); ?></label></th>
                            <td>
                                <select name="asap_ai_anthropic_model" id="asap_ai_anthropic_model" class="regular-text">
                                    <?php foreach ($anthropic_models as $value => $label): ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($anthropic_model, $value); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="asap_ai_huggingface_model"><?php echo esc_html__('Hugging Face Model', 'asapdigest-core'); ?></label></th>
                            <td>
                                <select name="asap_ai_huggingface_model" id="asap_ai_huggingface_model" class="regular-text">
                                    <?php foreach ($all_huggingface_models as $value => $label): ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($huggingface_model, $value); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php echo esc_html__('The Hugging Face API requires a model name. For testing, use \'distilbert-base-uncased\' which is a general-purpose model.', 'asapdigest-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div><!-- .asap-settings__card -->
            
            <div class="asap-settings__card">
                <div class="asap-settings__card-header">
                    <h2><?php echo esc_html__('Provider Preferences', 'asapdigest-core'); ?></h2>
                </div>
                <div class="asap-settings__card-content">
                    <p class="description"><?php echo esc_html__('Configure which provider to use for each task.', 'asapdigest-core'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="asap_ai_default_provider"><?php echo esc_html__('Default Provider', 'asapdigest-core'); ?></label></th>
                            <td>
                                <select name="asap_ai_default_provider" id="asap_ai_default_provider" class="regular-text">
                                    <option value=""><?php echo esc_html__('-- Select Default Provider --', 'asapdigest-core'); ?></option>
                                    <?php foreach ($providers as $value => $label): ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($default_provider, $value); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php echo esc_html__('The default provider to use when no task-specific provider is set.', 'asapdigest-core'); ?></p>
                            </td>
                        </tr>
                        
                        <?php foreach ($tasks as $task => $label): ?>
                            <tr>
                                <th scope="row"><label for="task_provider_<?php echo esc_attr($task); ?>"><?php echo esc_html($label); ?></label></th>
                                <td>
                                    <select name="task_provider[<?php echo esc_attr($task); ?>]" id="task_provider_<?php echo esc_attr($task); ?>" class="regular-text">
                                        <option value=""><?php echo esc_html__('-- Use Default Provider --', 'asapdigest-core'); ?></option>
                                        <?php foreach ($providers as $value => $provider_label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($task_preferences[$task] ?? '', $value); ?>><?php echo esc_html($provider_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div><!-- .asap-settings__card -->
            
            <div class="asap-settings__card">
                <div class="asap-settings__card-header">
                    <h2><?php echo esc_html__('Test Integration', 'asapdigest-core'); ?></h2>
                </div>
                <div class="asap-settings__card-content">
                    <p class="description"><?php echo esc_html__('Test your configured providers with a sample text.', 'asapdigest-core'); ?></p>
                    
                    <div class="asap-settings__test-area">
                        <textarea id="asap-ai-test-text" rows="4" placeholder="<?php echo esc_attr__('Enter some text to test AI features...', 'asapdigest-core'); ?>" class="large-text"></textarea>
                        
                        <div class="asap-settings__test-controls">
                            <button type="button" class="button" id="asap-ai-test-summarize"><?php echo esc_html__('Test Summary', 'asapdigest-core'); ?></button>
                            <button type="button" class="button" id="asap-ai-test-entities"><?php echo esc_html__('Test Entity Extraction', 'asapdigest-core'); ?></button>
                            <button type="button" class="button" id="asap-ai-test-keywords"><?php echo esc_html__('Test Keywords', 'asapdigest-core'); ?></button>
                        </div>
                        
                        <div id="asap-ai-test-results" style="display:none;">
                            <h3><?php echo esc_html__('Test Results', 'asapdigest-core'); ?></h3>
                            <div id="asap-ai-test-output"></div>
                        </div>
                    </div>
                </div>
            </div><!-- .asap-settings__card -->
            
            <div class="asap-settings__card">
                <div class="asap-settings__card-header">
                    <h2><?php echo esc_html__('Hugging Face Models Management', 'asapdigest-core'); ?></h2>
                </div>
                <div class="asap-settings__card-content">
                    <p class="description"><?php echo esc_html__('Add, edit, or remove custom Hugging Face models. Models should be verified before use. Failed models may cause errors and should be removed.', 'asapdigest-core'); ?></p>
                    
                    <!-- Bulk Actions -->
                    <div class="asap-settings__bulk-actions">
                        <button id="hf-verify-all-models" class="button"><?php echo esc_html__('Verify All Models', 'asapdigest-core'); ?></button>
                        <button id="hf-remove-failed-models" class="button"><?php echo esc_html__('Remove Failed Models', 'asapdigest-core'); ?></button>
                        <span id="hf-bulk-action-result"></span>
                    </div>
                    
                    <!-- Add New Model Form -->
                    <div class="asap-settings__add-model">
                        <h3><?php echo esc_html__('Add New Model', 'asapdigest-core'); ?></h3>
                        
                        <div class="asap-settings__add-options">
                            <label>
                                <input type="checkbox" id="hf-verify-before-add" checked> 
                                <?php echo esc_html__('Verify model before adding', 'asapdigest-core'); ?>
                            </label>
                        </div>
                        
                        <form id="hf-add-model-form" class="asap-settings__model-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="hf_model_id"><?php echo esc_html__('Model ID', 'asapdigest-core'); ?></label></th>
                                    <td>
                                        <input type="text" id="hf_model_id" name="hf_model_id" class="regular-text" 
                                            placeholder="<?php echo esc_attr__('e.g., mistralai/Mistral-7B-Instruct-v0.2', 'asapdigest-core'); ?>" required>
                                        <p class="description">
                                            <?php echo esc_html__('The model ID from Hugging Face (e.g., organization/model-name)', 'asapdigest-core'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="hf_model_label"><?php echo esc_html__('Display Name', 'asapdigest-core'); ?></label></th>
                                    <td>
                                        <input type="text" id="hf_model_label" name="hf_model_label" class="regular-text" 
                                            placeholder="<?php echo esc_attr__('e.g., Mistral 7B Instruct', 'asapdigest-core'); ?>" required>
                                        <p class="description">
                                            <?php echo esc_html__('A human-readable name for the model', 'asapdigest-core'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <div class="asap-settings__form-actions">
                                <button type="submit" class="button button-primary"><?php echo esc_html__('Add Model', 'asapdigest-core'); ?></button>
                                <button type="button" id="hf-test-new-model" class="button"><?php echo esc_html__('Test Model', 'asapdigest-core'); ?></button>
                                <span id="hf-test-new-result" class="test-result-indicator"></span>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Recommended Models Section -->
                    <div class="asap-settings__recommended-models">
                        <h3><?php echo esc_html__('Recommended Models', 'asapdigest-core'); ?></h3>
                        <p><?php echo esc_html__('These models are known to work well with the Hugging Face Inference API.', 'asapdigest-core'); ?></p>
                        
                        <div class="asap-settings__recommended-models-grid">
                            <?php 
                            // Example recommended models - for demonstration purposes
                            $recommended_models = [
                                'mistralai/Mistral-7B-Instruct-v0.2' => [
                                    'name' => 'Mistral 7B Instruct',
                                    'description' => 'A powerful 7B parameter instruction-tuned model for text generation.',
                                    'tasks' => 'Text Generation, Chat',
                                    'verified' => in_array('mistralai/Mistral-7B-Instruct-v0.2', $verified_models),
                                    'failed' => in_array('mistralai/Mistral-7B-Instruct-v0.2', $failed_models),
                                ],
                                'meta-llama/Llama-2-7b-chat-hf' => [
                                    'name' => 'Llama 2 7B Chat',
                                    'description' => 'Meta\'s Llama 2 model optimized for dialogue and conversation.',
                                    'tasks' => 'Chat, Text Generation',
                                    'verified' => in_array('meta-llama/Llama-2-7b-chat-hf', $verified_models),
                                    'failed' => in_array('meta-llama/Llama-2-7b-chat-hf', $failed_models),
                                ],
                                'facebook/bart-large-cnn' => [
                                    'name' => 'BART Large CNN',
                                    'description' => 'Specialized model for summarization trained on news articles.',
                                    'tasks' => 'Summarization',
                                    'verified' => in_array('facebook/bart-large-cnn', $verified_models),
                                    'failed' => in_array('facebook/bart-large-cnn', $failed_models),
                                ],
                                'microsoft/deberta-v3-base' => [
                                    'name' => 'DeBERTa v3 Base',
                                    'description' => 'Microsoft\'s improved BERT model with disentangled attention.',
                                    'tasks' => 'Classification, NLU',
                                    'verified' => in_array('microsoft/deberta-v3-base', $verified_models),
                                    'failed' => in_array('microsoft/deberta-v3-base', $failed_models),
                                ],
                            ];
                            
                            // Check if we should use the file or our examples
                            $recommended_models_file = plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/views/hf-models-recommended.php';
                            if (file_exists($recommended_models_file)) {
                                include_once($recommended_models_file);
                            } else {
                                // Display our example models
                                foreach ($recommended_models as $model_id => $model) {
                                    $model_class = $model['verified'] ? 'model-verified' : ($model['failed'] ? 'model-failed' : '');
                                    ?>
                                    <div class="hf-recommended-model-card <?php echo esc_attr($model_class); ?>">
                                        <h4><?php echo esc_html($model['name']); ?></h4>
                                        <div class="model-id"><?php echo esc_html($model_id); ?></div>
                                        <div class="model-description"><?php echo esc_html($model['description']); ?></div>
                                        <div class="model-meta">
                                            <strong><?php echo esc_html__('Tasks:', 'asapdigest-core'); ?></strong> <?php echo esc_html($model['tasks']); ?>
                                        </div>
                                        <div class="model-actions">
                                            <button type="button" class="button button-small hf-test-model" data-model="<?php echo esc_attr($model_id); ?>"><?php echo esc_html__('Test', 'asapdigest-core'); ?></button>
                                            <button type="button" class="button button-small hf-add-recommended" data-model="<?php echo esc_attr($model_id); ?>" data-name="<?php echo esc_attr($model['name']); ?>"><?php echo esc_html__('Add', 'asapdigest-core'); ?></button>
                                            <span class="test-result-indicator"></span>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Custom Models List -->
                    <div class="asap-settings__custom-models">
                        <h3><?php echo esc_html__('Custom Models', 'asapdigest-core'); ?></h3>
                        <p><?php echo esc_html__('These are the models you\'ve added for use with your API key.', 'asapdigest-core'); ?></p>
                        
                        <table class="widefat asap-settings__models-table">
                            <thead>
                                <tr>
                                    <th class="model-id"><?php echo esc_html__('Model ID', 'asapdigest-core'); ?></th>
                                    <th class="model-name"><?php echo esc_html__('Display Name', 'asapdigest-core'); ?></th>
                                    <th class="model-status"><?php echo esc_html__('Status', 'asapdigest-core'); ?></th>
                                    <th class="actions"><?php echo esc_html__('Actions', 'asapdigest-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="hf-models-list">
                                <?php
                                // Display custom models
                                if (!empty($custom_hf_models)) {
                                    foreach ($custom_hf_models as $model_id => $model_label) {
                                        $is_verified = in_array($model_id, $verified_models);
                                        $is_failed = in_array($model_id, $failed_models);
                                        
                                        $model_class = $is_verified ? 'model-verified' : ($is_failed ? 'model-failed' : 'model-unverified');
                                        $status_html = $is_verified ? 
                                            '<span class="status-verified">✓ ' . esc_html__('Verified', 'asapdigest-core') . '</span>' : 
                                            ($is_failed ? 
                                                '<span class="status-failed">✗ ' . esc_html__('Failed', 'asapdigest-core') . '</span>' : 
                                                '<span class="status-unverified">⚠ ' . esc_html__('Unverified', 'asapdigest-core') . '</span>');
                                        
                                        echo '<tr data-model-id="' . esc_attr($model_id) . '" class="' . esc_attr($model_class) . '">';
                                        echo '<td class="model-id">' . esc_html($model_id) . '</td>';
                                        echo '<td class="model-name">' . esc_html($model_label) . '</td>';
                                        echo '<td class="model-status">' . $status_html . '</td>';
                                        echo '<td class="actions">';
                                        echo '<div class="asap-settings__action-buttons">';
                                        echo '<button type="button" class="button button-small hf-test-model" data-model="' . esc_attr($model_id) . '">' . esc_html__('Test', 'asapdigest-core') . '</button> ';
                                        echo '<button type="button" class="button button-small hf-edit-model" data-model="' . esc_attr($model_id) . '" data-name="' . esc_attr($model_label) . '">' . esc_html__('Edit', 'asapdigest-core') . '</button> ';
                                        echo '<button type="button" class="button button-small hf-delete-model" data-model="' . esc_attr($model_id) . '">' . esc_html__('Delete', 'asapdigest-core') . '</button>';
                                        echo '<span class="test-result-indicator"></span>';
                                        echo '</div>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr class="no-items"><td colspan="4">' . esc_html__('No custom models added yet. Add a model above or select from recommended models.', 'asapdigest-core') . '</td></tr>';
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
                                    <th scope="row"><label for="edit_model_id"><?php echo esc_html__('Model ID', 'asapdigest-core'); ?></label></th>
                                    <td>
                                        <input type="text" id="edit_model_id" name="edit_model_id" class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="edit_model_label"><?php echo esc_html__('Display Name', 'asapdigest-core'); ?></label></th>
                                    <td>
                                        <input type="text" id="edit_model_label" name="edit_model_label" class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="edit_verify_after_update"><?php echo esc_html__('Verify After Update', 'asapdigest-core'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="edit_verify_after_update" name="edit_verify_after_update" checked>
                                            <?php echo esc_html__('Test this model after updating (if ID changed)', 'asapdigest-core'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
            </div><!-- .asap-settings__card -->
            
            <p class="submit">
                <input type="submit" name="asap_ai_submit" class="button button-primary" value="<?php echo esc_attr__('Save AI Settings', 'asapdigest-core'); ?>">
            </p>
        </form>
    </div><!-- .asap-dashboard-grid -->
</div><!-- .wrap -->

<!-- Debug section - keep for now -->
<?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
<div class="wrap" style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 20px;">
    <h2><?php echo esc_html__('Debug: Manual AJAX Test', 'asapdigest-core'); ?></h2>
    <p><?php echo esc_html__('This section is for debugging the AI connection test functionality.', 'asapdigest-core'); ?></p>
    
    <button id="manual-ajax-test" class="button button-secondary"><?php echo esc_html__('Send Manual AJAX Test', 'asapdigest-core'); ?></button>
    <span id="manual-test-result" style="margin-left: 10px; font-weight: bold;"></span>
    
    <div class="notice notice-info" style="margin-top: 15px;">
        <p><strong><?php echo esc_html__('Debug Information:', 'asapdigest-core'); ?></strong></p>
        <ul>
            <li><?php echo esc_html__('AJAX URL:', 'asapdigest-core'); ?> <code><?php echo esc_html(admin_url('admin-ajax.php')); ?></code></li>
            <li><?php echo esc_html__('Nonce Action:', 'asapdigest-core'); ?> <code>asap_digest_content_nonce</code></li>
            <li><?php echo esc_html__('Nonce Value:', 'asapdigest-core'); ?> <code><?php echo esc_html(wp_create_nonce('asap_digest_content_nonce')); ?></code></li>
            <li><?php echo esc_html__('Test for wp_ajax_asap_test_ai_connection:', 'asapdigest-core'); ?> <?php echo has_action('wp_ajax_asap_test_ai_connection') ? '<span style="color:green">✓ Registered</span>' : '<span style="color:red">✗ Not Registered</span>'; ?></li>
        </ul>
    </div>
</div>
<?php endif; ?>

<style>
/**
 * ASAP Digest Admin UI Component Styles
 *
 * Following WordPress Admin UI Component Style Protocol
 * Using BEM methodology for CSS structure
 */

/* Dashboard grid layout - matches usage-analytics */
.asap-dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-top: 20px;
}
    
/* Basic container for settings page */
.asap-settings__container {
    width: 100%;
    margin: 0;
}

/* Card component - follows WP admin patterns */
.asap-settings__card {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
    position: relative;
    display: flex;
    flex-direction: column;
}

.asap-settings__card-header {
    border-bottom: 1px solid #c3c4c7;
    padding: 12px 16px;
    background: #f6f7f7;
}

.asap-settings__card-header h2 {
    margin: 0;
    font-size: 14px;
    line-height: 1.4;
    font-weight: 600;
}

.asap-settings__card-content {
    padding: 16px;
    flex: 1;
}

/* Field Groups - provides horizontal layouts */
.asap-settings__field-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.asap-settings__field-group input {
    flex: 1;
    min-width: 300px;
}

.asap-settings__field-group .button {
    flex-shrink: 0;
}

/* Test area styles */
.asap-settings__test-area {
    background: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}

.asap-settings__test-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 10px 0;
}

#asap-ai-test-results {
    background: #fff;
    padding: 15px;
    border: 1px solid #ddd;
    margin-top: 15px;
    border-radius: 4px;
}

/* Hugging Face Models Management Styles */
.asap-settings__bulk-actions {
    background: #f9f9f9;
    padding: 10px 15px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

/* Card components for add, recommended, and custom models */
.asap-settings__add-model,
.asap-settings__recommended-models,
.asap-settings__custom-models {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    width: 100%;
    box-sizing: border-box;
    display: block; /* Ensure proper containment of child elements */
}

.asap-settings__add-options {
    margin-bottom: 10px;
}

.asap-settings__form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.asap-settings__models-table {
    border-collapse: collapse;
    width: 100%;
}

.asap-settings__models-table th,
.asap-settings__models-table td {
    padding: 8px 10px;
    text-align: left;
}

.asap-settings__models-table .model-id {
    width: 35%;
}

.asap-settings__models-table .model-name {
    width: 25%;
}

.asap-settings__models-table .model-status {
    width: 15%;
}

.asap-settings__models-table .actions {
    width: 25%;
}

.asap-settings__action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-items: center;
}

/* Recommended model grid layout - fixes display issues */
.asap-settings__recommended-models-grid {
    /* display: grid; */
    /* grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); */
    gap: 20px; 
    margin-top: 15px;
    width: 100%;
    box-sizing: border-box;
}

/* Recommended model card styling - optimized for grid layout */
.hf-recommended-model-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    margin: 0;
    height: auto;
    min-height: 200px;
    box-sizing: border-box;
    position: relative;
    transition: all 0.2s ease;
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
    margin-top: auto;
    padding-top: 10px;
}

.hf-recommended-model-card .test-result-indicator {
    margin-left: auto;
}

/* Status indicators with verification styles */
.hf-recommended-model-card.model-verified {
    background-color: rgba(70, 180, 80, 0.05);
    border-color: rgba(70, 180, 80, 0.3);
}

.hf-recommended-model-card.model-failed {
    background-color: rgba(220, 50, 50, 0.05);
    border-color: rgba(220, 50, 50, 0.3);
}

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

/* Row status highlighting */
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

/* Dialog styles for model editing */
.ui-dialog {
    padding: 0;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
}

.ui-dialog .ui-dialog-titlebar {
    background: #2271b1;
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 0;
    padding: 10px 15px;
}

.ui-dialog .ui-dialog-title {
    font-size: 14px;
}

.ui-dialog .ui-dialog-titlebar-close {
    color: #fff;
    background: transparent;
    border: none;
    right: 10px;
}

.ui-dialog .ui-dialog-content {
    padding: 15px;
}

.ui-dialog .ui-dialog-buttonpane {
    border-top: 1px solid #ddd;
    margin-top: 0;
    background: #f9f9f9;
    padding: 10px;
}

.ui-dialog .ui-dialog-buttonpane .ui-button {
    margin: 0.5em 0.4em 0.5em 0;
    padding: 5px 15px;
}

/* Form highlight animation for recommended model addition */
@keyframes highlightForm {
    0% { box-shadow: 0 0 0 0 rgba(34, 113, 177, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(34, 113, 177, 0); }
    100% { box-shadow: 0 0 0 0 rgba(34, 113, 177, 0); }
}

#hf-add-model-form.highlight {
    animation: highlightForm 1.5s ease;
    border-color: #2271b1;
}

/* Better form styling */
#hf-add-model-form {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

/* Better spacing for section headers */
.asap-settings__card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
    color: #2271b1;
}

/* Improve description text */
.description {
    color: #646970;
    font-style: italic;
    margin-bottom: 15px;
}

/* Table styling improvements */
.form-table th {
    padding: 15px 10px 15px 0;
    width: 200px;
}

.asap-settings__card-content .form-table {
    margin-top: 0;
}

/* Make submit button more prominent */
.submit .button-primary {
    padding: 6px 20px;
    height: auto;
    font-size: 14px;
}

/* Responsive Adjustments */
@media screen and (max-width: 782px) {
    .asap-settings__field-group input {
        min-width: 200px;
    }
    
    .asap-settings__recommended-models-grid {
        grid-template-columns: 1fr;
    }
    
    .asap-settings__action-buttons {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .asap-settings__action-buttons .button {
        width: 100%;
        margin-bottom: 5px;
    }
}

/* Fix for recommended models container - add specific styling for the class used in hf-models-recommended.php */
.hf-recommended-models-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    width: 100%;
    margin-top: 15px;
}
</style>

<?php
// Add JavaScript for the AI test functionality and model management
add_action('admin_footer', function() {
?>
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
    
    // Handle adding a recommended model
    $(document).on('click', '.hf-add-recommended', function() {
        const modelId = $(this).data('model');
        const modelName = $(this).data('name');
        
        // Set the form values for adding a new model
        $('#hf_model_id').val(modelId);
        $('#hf_model_label').val(modelName);
        
        // Scroll to the form
        $('html, body').animate({
            scrollTop: $('#hf-add-model-form').offset().top - 50
        }, 500);
        
        // Highlight the form
        $('#hf-add-model-form').addClass('highlight');
        setTimeout(function() {
            $('#hf-add-model-form').removeClass('highlight');
        }, 1500);
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
<?php
});
?> 