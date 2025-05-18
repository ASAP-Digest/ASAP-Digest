<?php
/**
 * @file-marker ASAP_Digest_Hugging_Face_Recommended_Models
 * @location /wp-content/plugins/asapdigest-core/admin/views/hf-models-recommended.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Recommended Hugging Face Models</h1>
    
    <p>This page lists Hugging Face models that are known to work reliably with the Inference API. These models have been tested and verified to be compatible with our system.</p>
    
    <div id="hf-models-tabs" class="hf-models-tabs">
        <ul class="nav-tab-wrapper">
            <li><a href="#tab-general" class="nav-tab nav-tab-active">General Purpose</a></li>
            <li><a href="#tab-summarization" class="nav-tab">Summarization</a></li>
            <li><a href="#tab-text-generation" class="nav-tab">Text Generation</a></li>
            <li><a href="#tab-classification" class="nav-tab">Classification</a></li>
            <li><a href="#tab-entity" class="nav-tab">Entity Extraction</a></li>
            <li><a href="#tab-keywords" class="nav-tab">Keyword Generation</a></li>
        </ul>
        
        <div id="tab-general" class="tab-content">
            <h2>General Purpose Models</h2>
            <p>These models can be used for various tasks and are generally reliable with the Inference API.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Model ID</th>
                        <th>Description</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>google-t5/t5-base</td>
                        <td>Versatile text-to-text model that can handle summarization, translation, and other tasks.</td>
                        <td>220M</td>
                        <td>
                            <button class="button add-model" data-model="google-t5/t5-base" data-name="T5 Base - General Purpose">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>google-t5/t5-small</td>
                        <td>Smaller version of T5, good for resource-constrained environments.</td>
                        <td>60M</td>
                        <td>
                            <button class="button add-model" data-model="google-t5/t5-small" data-name="T5 Small - General Purpose">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>google-t5/t5-large</td>
                        <td>Larger version of T5 with better performance on complex tasks.</td>
                        <td>770M</td>
                        <td>
                            <button class="button add-model" data-model="google-t5/t5-large" data-name="T5 Large - General Purpose">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>google/flan-t5-base</td>
                        <td>Instruction-tuned T5 model with improved performance on many tasks.</td>
                        <td>250M</td>
                        <td>
                            <button class="button add-model" data-model="google/flan-t5-base" data-name="Flan-T5 Base - Instruction Tuned">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>distilbert-base-uncased</td>
                        <td>Lightweight BERT model that works well for many classification tasks.</td>
                        <td>66M</td>
                        <td>
                            <button class="button add-model" data-model="distilbert-base-uncased" data-name="DistilBERT - General Purpose">Add to My Models</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div id="tab-summarization" class="tab-content" style="display:none;">
            <h2>Summarization Models</h2>
            <p>These models are specialized for text summarization tasks.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Model ID</th>
                        <th>Description</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>facebook/bart-large-cnn</td>
                        <td>BART model fine-tuned on CNN/Daily Mail dataset for news summarization.</td>
                        <td>400M</td>
                        <td>
                            <button class="button add-model" data-model="facebook/bart-large-cnn" data-name="BART Large CNN - Summarization">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>google-t5/t5-small-sum</td>
                        <td>T5 model fine-tuned specifically for summarization tasks.</td>
                        <td>60M</td>
                        <td>
                            <button class="button add-model" data-model="google-t5/t5-small" data-name="T5 Small - Summarization">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>sshleifer/distilbart-cnn-12-6</td>
                        <td>Distilled version of BART fine-tuned for summarization, smaller but still effective.</td>
                        <td>306M</td>
                        <td>
                            <button class="button add-model" data-model="sshleifer/distilbart-cnn-12-6" data-name="DistilBART CNN - Summarization">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>philschmid/bart-large-cnn-samsum</td>
                        <td>BART model fine-tuned on the SAMSum dataset for dialogue summarization.</td>
                        <td>400M</td>
                        <td>
                            <button class="button add-model" data-model="philschmid/bart-large-cnn-samsum" data-name="BART Large - Dialog Summarization">Add to My Models</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div id="tab-text-generation" class="tab-content" style="display:none;">
            <h2>Text Generation Models</h2>
            <p>These models are designed for text generation tasks.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Model ID</th>
                        <th>Description</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>google/flan-t5-base</td>
                        <td>Instruction-tuned T5 model that performs well across many generation tasks.</td>
                        <td>250M</td>
                        <td>
                            <button class="button add-model" data-model="google/flan-t5-base" data-name="Flan-T5 Base - Text Generation">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>google/flan-t5-large</td>
                        <td>Larger version of Flan-T5, better performance but requires more resources.</td>
                        <td>770M</td>
                        <td>
                            <button class="button add-model" data-model="google/flan-t5-large" data-name="Flan-T5 Large - Text Generation">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>gpt2</td>
                        <td>Classic GPT-2 model for text generation, reliable with the Inference API.</td>
                        <td>124M</td>
                        <td>
                            <button class="button add-model" data-model="gpt2" data-name="GPT-2 - Text Generation">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>EleutherAI/gpt-neo-125m</td>
                        <td>Smaller version of GPT-Neo, an open-source alternative to GPT-3.</td>
                        <td>125M</td>
                        <td>
                            <button class="button add-model" data-model="EleutherAI/gpt-neo-125m" data-name="GPT-Neo 125M - Text Generation">Add to My Models</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div id="tab-classification" class="tab-content" style="display:none;">
            <h2>Classification Models</h2>
            <p>These models are best suited for classification tasks.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Model ID</th>
                        <th>Description</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>facebook/bart-large-mnli</td>
                        <td>BART model fine-tuned on MNLI for natural language inference and classification.</td>
                        <td>400M</td>
                        <td>
                            <button class="button add-model" data-model="facebook/bart-large-mnli" data-name="BART Large MNLI - Classification">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>distilbert-base-uncased-finetuned-sst-2-english</td>
                        <td>DistilBERT model fine-tuned for sentiment analysis on SST-2 dataset.</td>
                        <td>66M</td>
                        <td>
                            <button class="button add-model" data-model="distilbert-base-uncased-finetuned-sst-2-english" data-name="DistilBERT - Sentiment Analysis">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>cross-encoder/ms-marco-MiniLM-L-6-v2</td>
                        <td>Cross-encoder model for semantic textual similarity and relevance ranking.</td>
                        <td>80M</td>
                        <td>
                            <button class="button add-model" data-model="cross-encoder/ms-marco-MiniLM-L-6-v2" data-name="MiniLM - Text Similarity">Add to My Models</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div id="tab-entity" class="tab-content" style="display:none;">
            <h2>Entity Extraction Models</h2>
            <p>These models are specialized for named entity recognition tasks.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Model ID</th>
                        <th>Description</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>dbmdz/bert-large-cased-finetuned-conll03-english</td>
                        <td>BERT model fine-tuned on CoNLL-2003 for named entity recognition.</td>
                        <td>340M</td>
                        <td>
                            <button class="button add-model" data-model="dbmdz/bert-large-cased-finetuned-conll03-english" data-name="BERT Large - Entity Extraction (CoNLL)">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>dslim/bert-base-NER</td>
                        <td>BERT model fine-tuned for named entity recognition, more lightweight.</td>
                        <td>110M</td>
                        <td>
                            <button class="button add-model" data-model="dslim/bert-base-NER" data-name="BERT Base - Named Entity Recognition">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>Jean-Baptiste/roberta-large-ner-english</td>
                        <td>RoBERTa model fine-tuned for named entity recognition with good performance.</td>
                        <td>355M</td>
                        <td>
                            <button class="button add-model" data-model="Jean-Baptiste/roberta-large-ner-english" data-name="RoBERTa Large - NER English">Add to My Models</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div id="tab-keywords" class="tab-content" style="display:none;">
            <h2>Keyword Generation Models</h2>
            <p>These models are good for extracting keywords from text.</p>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Model ID</th>
                        <th>Description</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>yanekyuk/bert-uncased-keyword-extractor</td>
                        <td>BERT model fine-tuned specifically for keyword extraction tasks.</td>
                        <td>110M</td>
                        <td>
                            <button class="button add-model" data-model="yanekyuk/bert-uncased-keyword-extractor" data-name="BERT - Keyword Extractor">Add to My Models</button>
                        </td>
                    </tr>
                    <tr>
                        <td>ml6team/keyphrase-extraction-distilbert-inspec</td>
                        <td>DistilBERT model fine-tuned on the Inspec dataset for keyphrase extraction.</td>
                        <td>66M</td>
                        <td>
                            <button class="button add-model" data-model="ml6team/keyphrase-extraction-distilbert-inspec" data-name="DistilBERT - Keyphrase Extraction">Add to My Models</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.hf-models-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('.hf-models-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show content
        var target = $(this).attr('href');
        $('.tab-content').hide();
        $(target).show();
    });
    
    // Add model button
    $('.add-model').on('click', function() {
        const modelId = $(this).data('model');
        const modelName = $(this).data('name');
        
        // Add to custom models
        window.parent.jQuery('#hf_model_id').val(modelId);
        window.parent.jQuery('#hf_model_label').val(modelName);
        
        // Close modal if in one
        if (window.parent.tb_remove) {
            window.parent.tb_remove();
        }
    });
});
</script>

<style>
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
</style> 