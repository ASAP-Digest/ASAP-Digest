<?php
/**
 * @file-marker ASAP_Digest_Hugging_Face_Recommended_Models
 * @location /wp-content/plugins/asapdigest-core/admin/views/hf-models-recommended.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define recommended models
$recommended_models = array(
    array(
        'id' => 'mistralai/Mistral-7B-Instruct-v0.2',
        'name' => 'Mistral 7B Instruct v0.2',
        'description' => 'Powerful and fast instruction-tuned model for general purpose tasks. Excellent balance of performance and speed.',
        'task' => 'text-generation',
        'size' => '7B parameters',
    ),
    array(
        'id' => 'meta-llama/Llama-2-7b-chat-hf',
        'name' => 'Llama 2 7B Chat',
        'description' => 'Meta\'s chat-optimized model. Good for conversational AI and general text generation tasks.',
        'task' => 'text-generation',
        'size' => '7B parameters',
    ),
    array(
        'id' => 'sentence-transformers/all-MiniLM-L6-v2',
        'name' => 'MiniLM L6 v2 (Embeddings)',
        'description' => 'Fast and efficient model for generating embeddings. Perfect for semantic search applications.',
        'task' => 'feature-extraction',
        'size' => '80MB',
    ),
    array(
        'id' => 'facebook/bart-large-cnn',
        'name' => 'BART Large CNN',
        'description' => 'Optimized for summarization tasks. Works well with news articles and longer content.',
        'task' => 'summarization',
        'size' => '400MB',
    ),
    array(
        'id' => 'google/flan-t5-large',
        'name' => 'Flan-T5 Large',
        'description' => 'Google\'s T5 model fine-tuned with instructions. Great for text-to-text generation tasks.',
        'task' => 'text2text-generation',
        'size' => '780MB',
    ),
    array(
        'id' => 'microsoft/phi-2',
        'name' => 'Phi-2',
        'description' => 'Small but powerful model from Microsoft. Excellent performance for its size.',
        'task' => 'text-generation',
        'size' => '2.7B parameters',
    ),
    array(
        'id' => 'HuggingFaceH4/zephyr-7b-beta',
        'name' => 'Zephyr 7B Beta',
        'description' => 'Fine-tuned on high-quality human feedback data. Good for general purpose tasks.',
        'task' => 'text-generation',
        'size' => '7B parameters',
    ),
    array(
        'id' => 'tiiuae/falcon-7b-instruct',
        'name' => 'Falcon 7B Instruct',
        'description' => 'Instruction-tuned model that performs well on various tasks.',
        'task' => 'text-generation',
        'size' => '7B parameters',
    ),
    array(
        'id' => 'google/gemma-7b-it',
        'name' => 'Gemma 7B IT',
        'description' => 'Google\'s instruction-tuned version of the Gemma model. High-quality, diverse responses.',
        'task' => 'text-generation', 
        'size' => '7B parameters',
    ),
    array(
        'id' => 'nateraw/bert-base-uncased-emotion',
        'name' => 'BERT Emotion Classifier',
        'description' => 'Classifies text into emotions (joy, sadness, anger, fear, love, surprise).',
        'task' => 'text-classification',
        'size' => 'Base',
    ),
    array(
        'id' => 'sshleifer/distilbart-cnn-12-6',
        'name' => 'DistilBART CNN',
        'description' => 'Lightweight version of BART for summarization tasks. Faster than the full model.',
        'task' => 'summarization',
        'size' => 'Small',
    ),
);

// Custom nonce for AJAX requests
$nonce = wp_create_nonce('asap_ai_admin_nonce');

// Store nonce in global JS variable (if not already added)
?>
<script>
    // Ensure asapDigestAdmin exists
    if (typeof asapDigestAdmin === 'undefined') {
        var asapDigestAdmin = {};
    }
    // Set nonce if not already set
    if (typeof asapDigestAdmin.nonce === 'undefined') {
        asapDigestAdmin.nonce = '<?php echo $nonce; ?>';
    }
</script>

<?php
// Display recommended models in cards
echo '<div class="hf-recommended-models-container">';
foreach ($recommended_models as $model) :
    // Check if model is already added to custom models
    $is_added = isset($custom_huggingface_models[$model['id']]);
    
    // Get verification status
    $verified_models = get_option('asap_ai_verified_huggingface_models', array());
    $failed_models = get_option('asap_ai_failed_huggingface_models', array());
    $is_verified = in_array($model['id'], $verified_models);
    $is_failed = in_array($model['id'], $failed_models);
    
    // Determine status class and text
    $status_class = $is_verified ? 'status-verified' : ($is_failed ? 'status-failed' : 'status-unverified');
    $status_text = $is_verified ? '✓ Verified' : ($is_failed ? '✗ Failed' : '⚠ Unverified');
?>
<div class="hf-recommended-model-card" data-model-id="<?php echo esc_attr($model['id']); ?>">
    <h4><?php echo esc_html($model['name']); ?></h4>
    <div class="model-id"><?php echo esc_html($model['id']); ?></div>
    <div class="model-description"><?php echo esc_html($model['description']); ?></div>
    
    <div class="model-meta">
        <span class="model-task"><?php echo esc_html(ucfirst($model['task'])); ?></span> • 
        <span class="model-size"><?php echo esc_html($model['size']); ?></span>
        <?php if ($is_added || $is_verified || $is_failed): ?>
            • <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
        <?php endif; ?>
    </div>
    
    <div class="model-actions">
        <?php if ($is_added): ?>
            <button type="button" class="button button-small hf-test-model" data-model="<?php echo esc_attr($model['id']); ?>">Test</button>
            <button type="button" class="button button-small hf-model-in-use" disabled>Already Added</button>
        <?php else: ?>
            <button type="button" class="button button-small hf-quick-add-model" 
                    data-model="<?php echo esc_attr($model['id']); ?>" 
                    data-name="<?php echo esc_attr($model['name']); ?>">
                Add Model
            </button>
            <button type="button" class="button button-small hf-test-recommended" data-model="<?php echo esc_attr($model['id']); ?>">Test</button>
        <?php endif; ?>
        <span class="test-result-indicator"></span>
    </div>
</div>
<?php endforeach; ?>
<?php
// Close the container div properly
echo '</div>';
?>

<script>
    // Handler for quick-add buttons
    jQuery(document).ready(function($) {
        $('.hf-quick-add-model').on('click', function() {
            var button = $(this);
            var modelId = button.data('model');
            var modelName = button.data('name');
            var resultIndicator = button.closest('.hf-recommended-model-card').find('.test-result-indicator');
            
            // First test the model
            var apiKey = $('#asap_ai_huggingface_key').val();
            
            if (!apiKey) {
                resultIndicator.html('<span class="error">⚠️ Please enter your Hugging Face API key first</span>');
                return;
            }
            
            // Show loading indicator
            resultIndicator.html('<span class="test-loading"></span> Testing before adding...');
            button.prop('disabled', true);
            
            // Use the global nonce from asapDigestAdmin
            var nonceValue = asapDigestAdmin.nonce;
            
            // Test the model
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asap_test_ai_connection',
                    provider: 'huggingface',
                    api_key: apiKey,
                    model: modelId,
                    nonce: nonceValue
                },
                success: function(response) {
                    if (response.success) {
                        // Model works, add it
                        resultIndicator.html('<span class="success">✓ Model verified, adding...</span>');
                        addModelToCustomList(modelId, modelName, true);
                    } else {
                        // Model verification failed
                        var errorMsg = response.data.message || 'Connection failed';
                        resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                        
                        // Ask if user wants to add anyway
                        if (confirm('Model verification failed: ' + errorMsg + '. Add this model anyway?')) {
                            addModelToCustomList(modelId, modelName, false);
                        } else {
                            button.prop('disabled', false);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = 'Connection failed';
                    
                    try {
                        var jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                            errorMsg = jsonResponse.data.message;
                        }
                    } catch (e) {
                        if (xhr.responseText && xhr.responseText.length < 100) {
                            errorMsg = xhr.responseText;
                        }
                    }
                    
                    resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                    
                    // Ask if user wants to add anyway
                    if (confirm('Model verification failed: ' + errorMsg + '. Add this model anyway?')) {
                        addModelToCustomList(modelId, modelName, false);
                    } else {
                        button.prop('disabled', false);
                    }
                }
            });
            
            // Function to add model to custom list
            function addModelToCustomList(modelId, modelName, isVerified) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'asap_save_custom_hf_models',
                        operation: 'add',
                        model_id: modelId,
                        model_label: modelName,
                        nonce: nonceValue
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update UI to show model is added
                            var statusHtml = isVerified ? 
                                '<span class="status-verified">✓ Verified</span>' : 
                                '<span class="status-unverified">⚠ Unverified</span>';
                            
                            var rowClass = isVerified ? 'model-verified' : 'model-unverified';
                            
                            // Add to models table
                            var newRow = $('<tr data-model-id="' + modelId + '" class="' + rowClass + '">' +
                                          '<td class="model-id">' + modelId + '</td>' +
                                          '<td class="model-name">' + modelName + '</td>' +
                                          '<td class="model-status">' + statusHtml + '</td>' +
                                          '<td class="actions">' +
                                            '<button type="button" class="button button-small hf-test-model" data-model="' + modelId + '">Test</button> ' +
                                            '<button type="button" class="button button-small hf-edit-model" data-model="' + modelId + '" data-name="' + modelName + '">Edit</button> ' +
                                            '<button type="button" class="button button-small hf-delete-model" data-model="' + modelId + '">Delete</button>' +
                                            '<span class="test-result-indicator"></span>' +
                                          '</td>' +
                                        '</tr>');
                            
                            // Remove the "no items" row if it exists
                            $('#hf-models-list .no-items').remove();
                            
                            // Add the new row
                            $('#hf-models-list').append(newRow);
                            
                            // Update dropdown
                            $('#asap_ai_huggingface_model option[value="' + modelId + '"]').remove();
                            var option = new Option(modelName, modelId);
                            $('#asap_ai_huggingface_model').append(option);
                            
                            // Update verification status
                            if (isVerified) {
                                updateModelVerificationStatus(modelId, true);
                            }
                            
                            // Update recommended model card
                            button.parent().html('<button type="button" class="button button-small hf-test-model" data-model="' + modelId + '">Test</button> ' +
                                               '<button type="button" class="button button-small hf-model-in-use" disabled>Already Added</button>');
                            
                            resultIndicator.html('<span class="success">✓ Added successfully</span>');
                            setTimeout(function() {
                                resultIndicator.html('');
                            }, 3000);
                            
                            // Show success notice
                            showAdminNotice('success', 'Model added successfully!');
                        } else {
                            resultIndicator.html('<span class="error">✗ Error adding model</span>');
                            button.prop('disabled', false);
                            showAdminNotice('error', 'Error adding model: ' + (response.data ? response.data.message : 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        resultIndicator.html('<span class="error">✗ Error adding model</span>');
                        button.prop('disabled', false);
                        showAdminNotice('error', 'An error occurred while saving the model: ' + error);
                        console.error('Error saving model:', {xhr, status, error});
                    }
                });
            }
        });
        
        // Handler for test recommended model button
        $('.hf-test-recommended').on('click', function() {
            var button = $(this);
            var modelId = button.data('model');
            var apiKey = $('#asap_ai_huggingface_key').val();
            var resultIndicator = button.closest('.hf-recommended-model-card').find('.test-result-indicator');
            
            if (!apiKey) {
                resultIndicator.html('<span class="error">⚠️ Please enter your Hugging Face API key first</span>');
                return;
            }
            
            // Show loading indicator
            resultIndicator.html('<span class="test-loading"></span> Testing... <span class="test-timer">0</span>s');
            
            // Start timer
            let seconds = 0;
            const timer = setInterval(function() {
                seconds++;
                resultIndicator.find('.test-timer').text(seconds);
            }, 1000);
            
            // Use the global nonce from asapDigestAdmin
            var nonceValue = asapDigestAdmin.nonce;
            
            // Test the model
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asap_test_ai_connection',
                    provider: 'huggingface',
                    api_key: apiKey,
                    model: modelId,
                    nonce: nonceValue
                },
                success: function(response) {
                    clearInterval(timer);
                    if (response.success) {
                        resultIndicator.html('<span class="success">✓ ' + response.data.message + '</span>');
                        
                        // Hide success message after 3 seconds
                        setTimeout(function() {
                            resultIndicator.html('');
                        }, 3000);
                    } else {
                        var errorMsg = response.data.message || 'Connection failed';
                        resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    clearInterval(timer);
                    console.error('Test model error:', {xhr, status, error});
                    var errorMsg = 'Connection failed';
                    
                    try {
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMsg = xhr.responseJSON.data.message;
                        } else {
                            var jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                                errorMsg = jsonResponse.data.message;
                            }
                        }
                    } catch (e) {
                        if (xhr.responseText && xhr.responseText.length < 100) {
                            errorMsg = xhr.responseText;
                        }
                    }
                    
                    resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                }
            });
        });
    });
</script>

<style>
/* CSS Grid Layout Implementation
 * Following MDN Grid Layout guidelines: https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_grid_layout
 */

/* First, reset any conflicting styles that might be inherited */
.hf-recommended-models-container {
    /* Remove any display properties that might conflict with grid */
    display: grid;
    /* Define a proper grid template with responsive columns */
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    /* Set appropriate gap between grid items */
    gap: 20px;
    /* Ensure the container takes full width */
    width: 100%;
    /* Reset any potentially conflicting properties */
    flex-direction: initial;
    flex-wrap: initial;
    padding: 0;
    margin: 0;
}

/* Style individual cards to ensure consistent layout */
.hf-recommended-model-card {
    /* Use a grid for internal layout instead of flexbox */
    display: grid;
    /* Set up the internal grid layout with areas */
    grid-template-areas:
        "header"
        "model-id"
        "description"
        "meta"
        "actions";
    grid-template-rows: auto auto 1fr auto auto;
    /* Standard card styling */
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    /* Ensure consistent card height behavior */
    height: 100%;
    min-height: 200px;
    /* Box sizing to include padding in dimensions */
    box-sizing: border-box;
}

/* Card content area styling */
.hf-recommended-model-card h4 {
    grid-area: header;
    margin-top: 0;
    margin-bottom: 8px;
}

.hf-recommended-model-card .model-id {
    grid-area: model-id;
    font-family: monospace;
    font-size: 12px;
    color: #777;
    margin-bottom: 8px;
    word-break: break-all;
}

.hf-recommended-model-card .model-description {
    grid-area: description;
    font-size: 13px;
    margin-bottom: 8px;
}

.hf-recommended-model-card .model-meta {
    grid-area: meta;
    font-size: 12px;
    color: #666;
    padding-top: 8px;
    margin: 8px 0;
}

.hf-recommended-model-card .model-actions {
    grid-area: actions;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: auto;
    padding-top: 10px;
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

/* Additional styles needed for the model tabs */
.hf-models-tabs {
    margin-top: 20px;
}

.tab-content {
    padding: 20px;
    background: #fff;
    border: 1px solid #ccc;
    border-top: none;
}

.nav-tab-wrapper {
    margin-bottom: 0;
}

.nav-tab {
    cursor: pointer;
}

.striped tbody tr:nth-child(odd) {
    background-color: #f9f9f9;
}

.add-model {
    background-color: #0073aa;
    color: white;
    border-color: #0073aa;
}

.add-model:hover {
    background-color: #005177;
    color: white;
    border-color: #005177;
}

/* Add status class styling for the cards */
.hf-recommended-model-card.model-verified {
    background-color: rgba(70, 180, 80, 0.05);
    border-color: rgba(70, 180, 80, 0.3);
}

.hf-recommended-model-card.model-failed {
    background-color: rgba(220, 50, 50, 0.05);
    border-color: rgba(220, 50, 50, 0.3);
}
</style> 