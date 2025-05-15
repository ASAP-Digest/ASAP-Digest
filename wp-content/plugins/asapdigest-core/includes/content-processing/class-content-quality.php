<?php
/**
 * Content Quality Class
 *
 * Provides detailed quality assessment and scoring for content items
 * based on configurable rules and metrics.
 *
 * @package ASAP_Digest
 * @subpackage Content_Processing
 * @since 2.2.0
 * @file-marker ASAP_Digest_Content_Quality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content Quality class
 *
 * @since 2.2.0
 */
class ASAP_Digest_Content_Quality {

    /**
     * Content data to analyze
     *
     * @var array
     */
    private $content_data = [];

    /**
     * Quality rules configuration
     *
     * @var array
     */
    private $rules = [];

    /**
     * Quality assessment results
     *
     * @var array
     */
    private $assessment = [];

    /**
     * Constructor
     *
     * @param array $content_data Optional content data to analyze
     */
    public function __construct($content_data = []) {
        $this->content_data = $content_data;
        $this->rules = $this->get_default_rules();
        $this->assessment = [
            'score' => 0,
            'max_score' => 0,
            'percentage' => 0,
            'category' => 'poor',
            'metrics' => [],
            'suggestions' => [],
        ];
    }

    /**
     * Set content data for analysis
     *
     * @param array $content_data Content data to analyze
     * @return ASAP_Digest_Content_Quality
     */
    public function set_content_data($content_data) {
        $this->content_data = $content_data;
        return $this;
    }

    /**
     * Get default quality assessment rules
     *
     * @return array Default rules configuration
     */
    public function get_default_rules() {
        return [
            // Content Completeness Metrics
            'completeness' => [
                'weight' => 30, // 30% of total score
                'rules' => [
                    'has_title' => [
                        'points' => 10,
                        'description' => 'Content has a title',
                    ],
                    'title_length' => [
                        'points' => 5,
                        'min' => 5,
                        'max' => 100,
                        'description' => 'Title has appropriate length (5-100 chars)',
                    ],
                    'has_content' => [
                        'points' => 10,
                        'description' => 'Content has main body text',
                    ],
                    'content_length' => [
                        'points' => 10,
                        'min' => 150,
                        'description' => 'Content has sufficient length (150+ chars)',
                    ],
                    'has_summary' => [
                        'points' => 5,
                        'description' => 'Content has a summary',
                    ],
                    'has_source_url' => [
                        'points' => 5,
                        'description' => 'Content has a source URL',
                    ],
                    'has_publish_date' => [
                        'points' => 5,
                        'description' => 'Content has a publish date',
                    ],
                ],
            ],
            
            // Content Readability Metrics
            'readability' => [
                'weight' => 20, // 20% of total score
                'rules' => [
                    'sentence_structure' => [
                        'points' => 10,
                        'description' => 'Contains well-formed sentences',
                    ],
                    'paragraph_structure' => [
                        'points' => 10,
                        'description' => 'Contains well-formed paragraphs',
                    ],
                    'reading_level' => [
                        'points' => 10,
                        'description' => 'Appropriate reading level',
                    ],
                    'formatting' => [
                        'points' => 10,
                        'description' => 'Proper HTML formatting (headings, lists)',
                    ],
                ],
            ],
            
            // Content Relevance Metrics
            'relevance' => [
                'weight' => 25, // 25% of total score
                'rules' => [
                    'title_matches_content' => [
                        'points' => 10,
                        'description' => 'Title accurately reflects content',
                    ],
                    'keyword_presence' => [
                        'points' => 10,
                        'description' => 'Contains relevant keywords',
                    ],
                    'topic_coherence' => [
                        'points' => 10,
                        'description' => 'Content stays on topic',
                    ],
                ],
            ],
            
            // Content Freshness Metrics
            'freshness' => [
                'weight' => 15, // 15% of total score
                'rules' => [
                    'recency' => [
                        'points' => 20,
                        'days' => [
                            1 => 20,    // 1 day old: 20 points
                            7 => 15,    // 1 week old: 15 points
                            30 => 10,   // 1 month old: 10 points
                            90 => 5,    // 3 months old: 5 points
                            365 => 2,   // 1 year old: 2 points
                            0 => 0,     // older: 0 points
                        ],
                        'description' => 'Content is recent',
                    ],
                ],
            ],
            
            // Media & Enrichment Metrics
            'enrichment' => [
                'weight' => 10, // 10% of total score
                'rules' => [
                    'has_images' => [
                        'points' => 10,
                        'description' => 'Contains images',
                    ],
                    'has_links' => [
                        'points' => 5,
                        'description' => 'Contains outbound links',
                    ],
                    'has_structured_data' => [
                        'points' => 5,
                        'description' => 'Contains structured data (tables, etc.)',
                    ],
                ],
            ],
        ];
    }

    /**
     * Run quality assessment on content
     *
     * @return array Assessment results
     */
    public function assess() {
        // Reset assessment
        $this->assessment = [
            'score' => 0,
            'max_score' => 0,
            'percentage' => 0,
            'category' => 'poor',
            'metrics' => [],
            'suggestions' => [],
        ];
        
        // Process each metric category
        foreach ($this->rules as $category => $config) {
            $this->assessment['metrics'][$category] = [
                'weight' => $config['weight'],
                'score' => 0,
                'max_score' => 0,
                'percentage' => 0,
                'rules' => [],
            ];
            
            // Process each rule in the category
            foreach ($config['rules'] as $rule_id => $rule_config) {
                $rule_result = $this->evaluate_rule($category, $rule_id, $rule_config);
                $this->assessment['metrics'][$category]['rules'][$rule_id] = $rule_result;
                
                // Update category score
                $this->assessment['metrics'][$category]['score'] += $rule_result['score'];
                $this->assessment['metrics'][$category]['max_score'] += $rule_result['max_score'];
            }
            
            // Calculate category percentage
            if ($this->assessment['metrics'][$category]['max_score'] > 0) {
                $this->assessment['metrics'][$category]['percentage'] = round(
                    ($this->assessment['metrics'][$category]['score'] / $this->assessment['metrics'][$category]['max_score']) * 100
                );
            }
            
            // Add weighted score to total
            $category_weight = $config['weight'] / 100;
            $category_score_contribution = $this->assessment['metrics'][$category]['percentage'] * $category_weight;
            $this->assessment['score'] += $category_score_contribution;
        }
        
        // Ensure score is within 0-100 range
        $this->assessment['score'] = max(0, min(100, round($this->assessment['score'])));
        
        // Determine quality category
        $this->assessment['category'] = $this->get_quality_category($this->assessment['score']);
        
        // Generate improvement suggestions
        $this->assessment['suggestions'] = $this->generate_suggestions();
        
        return $this->assessment;
    }

    /**
     * Evaluate a specific quality rule
     *
     * @param string $category Rule category
     * @param string $rule_id Rule identifier
     * @param array $rule_config Rule configuration
     * @return array Rule evaluation result
     */
    private function evaluate_rule($category, $rule_id, $rule_config) {
        $result = [
            'name' => $rule_config['description'],
            'score' => 0,
            'max_score' => $rule_config['points'],
            'passed' => false,
            'details' => '',
        ];
        
        // Handle different rule types
        switch ($rule_id) {
            // Completeness rules
            case 'has_title':
                $result['passed'] = !empty($this->content_data['title']);
                break;
                
            case 'title_length':
                $title_length = isset($this->content_data['title']) ? mb_strlen($this->content_data['title']) : 0;
                $result['passed'] = ($title_length >= $rule_config['min'] && $title_length <= $rule_config['max']);
                $result['details'] = sprintf('Title length: %d characters', $title_length);
                break;
                
            case 'has_content':
                $result['passed'] = !empty($this->content_data['content']);
                break;
                
            case 'content_length':
                $content_text = isset($this->content_data['content']) ? wp_strip_all_tags($this->content_data['content']) : '';
                $content_length = mb_strlen($content_text);
                $result['passed'] = ($content_length >= $rule_config['min']);
                $result['details'] = sprintf('Content length: %d characters', $content_length);
                break;
                
            case 'has_summary':
                $result['passed'] = !empty($this->content_data['summary']);
                break;
                
            case 'has_source_url':
                $result['passed'] = !empty($this->content_data['source_url']);
                break;
                
            case 'has_publish_date':
                $result['passed'] = !empty($this->content_data['publish_date']);
                break;
                
            // Readability rules
            case 'sentence_structure':
                $result['score'] = $this->evaluate_sentence_structure();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            case 'paragraph_structure':
                $result['score'] = $this->evaluate_paragraph_structure();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            case 'reading_level':
                $result['score'] = $this->evaluate_reading_level();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            case 'formatting':
                $result['score'] = $this->evaluate_formatting();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            // Relevance rules
            case 'title_matches_content':
                $result['score'] = $this->evaluate_title_content_match();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            case 'keyword_presence':
                $result['score'] = $this->evaluate_keyword_presence();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            case 'topic_coherence':
                $result['score'] = $this->evaluate_topic_coherence();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            // Freshness rules
            case 'recency':
                $result['score'] = $this->evaluate_recency($rule_config['days']);
                $result['passed'] = ($result['score'] > 0);
                break;
                
            // Enrichment rules
            case 'has_images':
                $result['score'] = $this->evaluate_has_images();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            case 'has_links':
                $result['score'] = $this->evaluate_has_links();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            case 'has_structured_data':
                $result['score'] = $this->evaluate_has_structured_data();
                $result['passed'] = ($result['score'] > 0);
                break;
                
            default:
                // Unknown rule, assign zero points
                $result['details'] = 'Unknown rule';
                break;
        }
        
        // For simple pass/fail rules
        if ($result['passed'] && $result['score'] === 0) {
            $result['score'] = $result['max_score'];
        }
        
        return $result;
    }

    /**
     * Evaluate sentence structure quality
     *
     * @return int Score points
     */
    private function evaluate_sentence_structure() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = wp_strip_all_tags($this->content_data['content']);
        
        // Simple approach: sentences should generally end with punctuation
        // and not be excessively long
        $sentences = preg_split('/[.!?]+(?=\s|$)/u', $content, -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($sentences)) {
            return 0;
        }
        
        $valid_sentences = 0;
        $total_sentences = count($sentences);
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            $length = mb_strlen($sentence);
            
            // Sentences should be between 5 and 200 characters
            if ($length >= 5 && $length <= 200) {
                $valid_sentences++;
            }
        }
        
        $sentence_ratio = $valid_sentences / $total_sentences;
        
        // Scale from 0-10 based on the ratio of valid sentences
        return min(10, max(0, round($sentence_ratio * 10)));
    }

    /**
     * Evaluate paragraph structure quality
     *
     * @return int Score points
     */
    private function evaluate_paragraph_structure() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = $this->content_data['content'];
        
        // Look for paragraph breaks (<p>, <br>, \n\n, etc.)
        $has_paragraphs = false;
        
        if (strpos($content, '</p>') !== false) {
            // Has HTML paragraphs
            $has_paragraphs = true;
        } elseif (strpos($content, "\n\n") !== false) {
            // Has line breaks as paragraphs
            $has_paragraphs = true;
        } elseif (strpos($content, '<br') !== false) {
            // Has <br> tags as paragraph breaks
            $has_paragraphs = true;
        }
        
        // Count paragraphs
        $paragraph_count = 0;
        
        if (preg_match_all('/<p[^>]*>/i', $content, $matches)) {
            $paragraph_count = count($matches[0]);
        } else {
            // Count double line breaks
            $paragraph_count = substr_count($content, "\n\n") + 1;
        }
        
        // Score based on paragraph count and presence
        if (!$has_paragraphs) {
            return 0; // No paragraph structure
        } elseif ($paragraph_count === 1) {
            return 2; // Single paragraph
        } elseif ($paragraph_count <= 3) {
            return 5; // Few paragraphs
        } elseif ($paragraph_count <= 10) {
            return 8; // Decent number of paragraphs
        } else {
            return 10; // Lots of paragraphs
        }
    }

    /**
     * Evaluate content reading level
     *
     * @return int Score points
     */
    private function evaluate_reading_level() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = wp_strip_all_tags($this->content_data['content']);
        
        // Simplified reading level estimation
        // Based on average sentence length and word length
        
        // Count sentences
        $sentences = preg_split('/[.!?]+(?=\s|$)/u', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        
        if ($sentence_count === 0) {
            return 0;
        }
        
        // Count words
        $words = preg_split('/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY);
        $word_count = count($words);
        
        if ($word_count === 0) {
            return 0;
        }
        
        // Calculate averages
        $avg_sentence_length = $word_count / $sentence_count;
        
        // Calculate average word length
        $total_word_length = 0;
        foreach ($words as $word) {
            $total_word_length += mb_strlen($word);
        }
        $avg_word_length = $total_word_length / $word_count;
        
        // Score based on averages (penalize extremes)
        $sentence_length_score = 0;
        if ($avg_sentence_length < 5) {
            $sentence_length_score = 2; // Too short
        } elseif ($avg_sentence_length < 10) {
            $sentence_length_score = 3; // Somewhat short
        } elseif ($avg_sentence_length <= 25) {
            $sentence_length_score = 5; // Good range
        } elseif ($avg_sentence_length <= 35) {
            $sentence_length_score = 3; // Somewhat long
        } else {
            $sentence_length_score = 2; // Too long
        }
        
        $word_length_score = 0;
        if ($avg_word_length < 3) {
            $word_length_score = 2; // Too simple
        } elseif ($avg_word_length <= 6) {
            $word_length_score = 5; // Good range
        } else {
            $word_length_score = 2; // Too complex
        }
        
        // Combine scores
        return $sentence_length_score + $word_length_score;
    }

    /**
     * Evaluate HTML formatting quality
     *
     * @return int Score points
     */
    private function evaluate_formatting() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = $this->content_data['content'];
        
        $score = 0;
        
        // Check for headings
        if (preg_match('/<h[1-6][^>]*>/i', $content)) {
            $score += 3;
        }
        
        // Check for lists
        if (preg_match('/<[ou]l[^>]*>/i', $content)) {
            $score += 3;
        }
        
        // Check for emphasis
        if (preg_match('/<(strong|em|b|i)[^>]*>/i', $content)) {
            $score += 2;
        }
        
        // Check for images with alt text
        if (preg_match('/<img[^>]*alt=["\'][^"\']+["\'][^>]*>/i', $content)) {
            $score += 2;
        }
        
        return min(10, $score);
    }

    /**
     * Evaluate if title matches content
     *
     * @return int Score points
     */
    private function evaluate_title_content_match() {
        if (empty($this->content_data['title']) || empty($this->content_data['content'])) {
            return 0;
        }
        
        $title = strtolower(wp_strip_all_tags($this->content_data['title']));
        $content = strtolower(wp_strip_all_tags($this->content_data['content']));
        
        // Get significant words from title (not stopwords)
        $stopwords = [
            'a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'was', 'were',
            'this', 'that', 'these', 'those', 'in', 'on', 'at', 'by', 'for',
            'with', 'about', 'of', 'to', 'from', 'as', 'like', 'how', 'when',
            'where', 'why', 'what', 'who', 'which', 'not', 'no', 'yes',
        ];
        
        $title_words = explode(' ', $title);
        $significant_words = [];
        
        foreach ($title_words as $word) {
            if (strlen($word) > 3 && !in_array($word, $stopwords)) {
                $significant_words[] = $word;
            }
        }
        
        if (empty($significant_words)) {
            return 5; // No significant words to check
        }
        
        $word_matches = 0;
        
        foreach ($significant_words as $word) {
            if (strpos($content, $word) !== false) {
                $word_matches++;
            }
        }
        
        $match_ratio = $word_matches / count($significant_words);
        
        // Scale score based on match ratio
        return min(10, max(0, round($match_ratio * 10)));
    }

    /**
     * Evaluate keyword presence based on content type
     *
     * @return int Score points
     */
    private function evaluate_keyword_presence() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = strtolower(wp_strip_all_tags($this->content_data['content']));
        $type = strtolower($this->content_data['type'] ?? 'article');
        
        // Expected keywords based on content type
        $keywords = [];
        
        switch ($type) {
            case 'article':
                $keywords = ['analysis', 'report', 'study', 'research', 'findings', 'according'];
                break;
                
            case 'blog':
                $keywords = ['opinion', 'perspective', 'thoughts', 'view', 'commentary', 'today'];
                break;
                
            case 'news':
                $keywords = ['announced', 'reported', 'released', 'statement', 'today', 'yesterday'];
                break;
                
            case 'podcast':
                $keywords = ['episode', 'host', 'guest', 'interview', 'discussed', 'conversation'];
                break;
                
            case 'video':
                $keywords = ['watch', 'viewing', 'seen', 'footage', 'captured', 'recorded'];
                break;
                
            default:
                return 5; // Unknown type, give medium score
        }
        
        $matches = 0;
        
        foreach ($keywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $matches++;
            }
        }
        
        // Score based on keyword matches
        if ($matches == 0) {
            return 2; // No expected keywords
        } elseif ($matches == 1) {
            return 4; // One keyword
        } elseif ($matches == 2) {
            return 6; // Two keywords
        } elseif ($matches == 3) {
            return 8; // Three keywords
        } else {
            return 10; // Four or more keywords
        }
    }

    /**
     * Evaluate topic coherence
     *
     * @return int Score points
     */
    private function evaluate_topic_coherence() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = wp_strip_all_tags($this->content_data['content']);
        $paragraphs = preg_split('/\n\s*\n|\r\n\s*\r\n|\r\s*\r/', $content);
        
        if (count($paragraphs) < 2) {
            return 5; // Not enough paragraphs to evaluate coherence
        }
        
        // Extract frequent terms for each paragraph
        $paragraph_terms = [];
        
        foreach ($paragraphs as $paragraph) {
            $words = preg_split('/\s+/', strtolower($paragraph), -1, PREG_SPLIT_NO_EMPTY);
            $filtered_words = [];
            
            // Remove stopwords
            $stopwords = ['a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 'this', 'that', 'these', 'those', 'in', 'on', 'at', 'by', 'for', 'with', 'about', 'of', 'to', 'from'];
            
            foreach ($words as $word) {
                if (strlen($word) > 3 && !in_array($word, $stopwords)) {
                    $filtered_words[] = $word;
                }
            }
            
            // Count term frequencies
            $term_counts = array_count_values($filtered_words);
            arsort($term_counts);
            
            // Take top 3 terms
            $paragraph_terms[] = array_slice(array_keys($term_counts), 0, 3);
        }
        
        // Check term overlap between consecutive paragraphs
        $coherence_score = 0;
        $comparisons = 0;
        
        for ($i = 0; $i < count($paragraph_terms) - 1; $i++) {
            $current_terms = $paragraph_terms[$i];
            $next_terms = $paragraph_terms[$i + 1];
            
            // Skip if either paragraph has no significant terms
            if (empty($current_terms) || empty($next_terms)) {
                continue;
            }
            
            $overlap = array_intersect($current_terms, $next_terms);
            $coherence_score += count($overlap);
            $comparisons++;
        }
        
        if ($comparisons === 0) {
            return 5; // Not enough comparisons
        }
        
        $average_coherence = $coherence_score / $comparisons;
        
        // Score based on average coherence
        if ($average_coherence >= 2) {
            return 10; // Very coherent
        } elseif ($average_coherence >= 1) {
            return 8; // Good coherence
        } elseif ($average_coherence >= 0.5) {
            return 6; // Moderate coherence
        } elseif ($average_coherence > 0) {
            return 4; // Low coherence
        } else {
            return 2; // No coherence
        }
    }

    /**
     * Evaluate content recency
     *
     * @param array $day_points Mapping of days to points
     * @return int Score points
     */
    private function evaluate_recency($day_points) {
        if (empty($this->content_data['publish_date'])) {
            return 0; // No publish date
        }
        
        $publish_timestamp = strtotime($this->content_data['publish_date']);
        
        if ($publish_timestamp === false) {
            return 0; // Invalid date format
        }
        
        // Calculate age in days
        $now = time();
        $age_days = floor(($now - $publish_timestamp) / (60 * 60 * 24));
        
        // Determine points based on age
        $points = 0;
        
        // Sort day thresholds in descending order
        $thresholds = array_keys($day_points);
        rsort($thresholds);
        
        foreach ($thresholds as $days) {
            if ($age_days <= $days || $days === 0) {
                $points = $day_points[$days];
            }
        }
        
        return $points;
    }

    /**
     * Evaluate presence of images
     *
     * @return int Score points
     */
    private function evaluate_has_images() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = $this->content_data['content'];
        
        // Count image tags
        $image_count = preg_match_all('/<img[^>]+>/i', $content, $matches);
        
        // Check if extra data contains images
        $has_extra_images = false;
        if (!empty($this->content_data['extra']) && is_array($this->content_data['extra'])) {
            $extra = $this->content_data['extra'];
            
            if (isset($extra['images']) && !empty($extra['images'])) {
                $has_extra_images = true;
            } elseif (isset($extra['media']) && !empty($extra['media'])) {
                foreach ($extra['media'] as $media) {
                    if (isset($media['type']) && $media['type'] === 'image') {
                        $has_extra_images = true;
                        break;
                    }
                }
            }
        }
        
        // Score based on image count
        if ($image_count >= 3 || $has_extra_images) {
            return 10; // Multiple images
        } elseif ($image_count > 0) {
            return 5; // At least one image
        } else {
            return 0; // No images
        }
    }

    /**
     * Evaluate presence of links
     *
     * @return int Score points
     */
    private function evaluate_has_links() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = $this->content_data['content'];
        
        // Count link tags
        $link_count = preg_match_all('/<a[^>]+href=["\'][^"\']+["\'][^>]*>/i', $content, $matches);
        
        // Score based on link count
        if ($link_count >= 3) {
            return 5; // Multiple links
        } elseif ($link_count > 0) {
            return 3; // At least one link
        } else {
            return 0; // No links
        }
    }

    /**
     * Evaluate presence of structured data
     *
     * @return int Score points
     */
    private function evaluate_has_structured_data() {
        if (empty($this->content_data['content'])) {
            return 0;
        }
        
        $content = $this->content_data['content'];
        
        $score = 0;
        
        // Check for tables
        if (strpos($content, '<table') !== false) {
            $score += 3;
        }
        
        // Check for schema.org markup
        if (strpos($content, 'itemtype="http://schema.org/') !== false || 
            strpos($content, 'itemtype="https://schema.org/') !== false) {
            $score += 2;
        }
        
        // Check for JSON-LD
        if (strpos($content, '<script type="application/ld+json">') !== false) {
            $score += 3;
        }
        
        // Check for structured data in extra
        if (!empty($this->content_data['extra']) && is_array($this->content_data['extra'])) {
            $extra = $this->content_data['extra'];
            
            if (isset($extra['structured_data']) && !empty($extra['structured_data'])) {
                $score += 3;
            }
        }
        
        return min(5, $score);
    }

    /**
     * Generate quality improvement suggestions
     *
     * @return array Improvement suggestions
     */
    private function generate_suggestions() {
        $suggestions = [];
        
        // Add suggestions based on metrics with low scores
        foreach ($this->assessment['metrics'] as $category => $metric) {
            if ($metric['percentage'] < 50) {
                // Add category-level suggestion
                switch ($category) {
                    case 'completeness':
                        $suggestions[] = 'Improve content completeness by adding missing required fields and more detail.';
                        break;
                        
                    case 'readability':
                        $suggestions[] = 'Improve readability with better sentence structure, paragraphs, and formatting.';
                        break;
                        
                    case 'relevance':
                        $suggestions[] = 'Enhance content relevance by ensuring title accurately reflects content and including key topic terms.';
                        break;
                        
                    case 'freshness':
                        $suggestions[] = 'Update content or ensure proper publish date is set.';
                        break;
                        
                    case 'enrichment':
                        $suggestions[] = 'Enrich content with images, links, or other media elements.';
                        break;
                }
                
                // Add specific rule-level suggestions
                foreach ($metric['rules'] as $rule_id => $rule_result) {
                    if (!$rule_result['passed']) {
                        switch ($rule_id) {
                            case 'title_length':
                                $suggestions[] = 'Ensure title is between 5-100 characters for optimal display and SEO.';
                                break;
                                
                            case 'content_length':
                                $suggestions[] = 'Add more text content to meet the minimum length requirement (150+ characters).';
                                break;
                                
                            case 'has_summary':
                                $suggestions[] = 'Add a summary to improve content discoverability and reader engagement.';
                                break;
                                
                            case 'has_source_url':
                                $suggestions[] = 'Include the original source URL for proper attribution.';
                                break;
                                
                            case 'has_publish_date':
                                $suggestions[] = 'Add publish date to establish content timeline and relevance.';
                                break;
                                
                            case 'sentence_structure':
                                $suggestions[] = 'Improve sentence structure with varied lengths and proper punctuation.';
                                break;
                                
                            case 'paragraph_structure':
                                $suggestions[] = 'Break content into multiple paragraphs for better readability.';
                                break;
                                
                            case 'formatting':
                                $suggestions[] = 'Add HTML formatting like headings, lists, and emphasis to improve readability.';
                                break;
                                
                            case 'has_images':
                                $suggestions[] = 'Add images to enhance visual appeal and engagement.';
                                break;
                                
                            case 'has_links':
                                $suggestions[] = 'Include relevant links to provide additional context and resources.';
                                break;
                        }
                    }
                }
            }
        }
        
        // Remove duplicates and limit to top 5 suggestions
        $suggestions = array_unique($suggestions);
        $suggestions = array_slice($suggestions, 0, 5);
        
        return $suggestions;
    }

    /**
     * Get quality category based on score
     *
     * @param int $score Quality score
     * @return string Quality category
     */
    private function get_quality_category($score) {
        if ($score >= 90) {
            return 'excellent';
        } elseif ($score >= 70) {
            return 'good';
        } elseif ($score >= 50) {
            return 'average';
        } elseif ($score >= 30) {
            return 'poor';
        } else {
            return 'very_poor';
        }
    }

    /**
     * Get quality assessment results
     *
     * @return array Quality assessment results
     */
    public function get_assessment() {
        return $this->assessment;
    }

    /**
     * Get the calculated quality score
     *
     * @return int Quality score (0-100)
     */
    public function get_score() {
        return $this->assessment['score'];
    }

    /**
     * Check if content passes minimum quality threshold
     *
     * @param int $threshold Minimum quality threshold (default: use ASAP_QUALITY_SCORE_MINIMUM constant)
     * @return bool True if content passes minimum quality threshold
     */
    public function passes_quality_threshold($threshold = null) {
        if ($threshold === null) {
            if (defined('ASAP_QUALITY_SCORE_MINIMUM')) {
                $threshold = ASAP_QUALITY_SCORE_MINIMUM;
            } else {
                $threshold = 40; // Default if constant not defined
            }
        }
        
        return $this->assessment['score'] >= $threshold;
    }

    /**
     * Calculate quality score
     *
     * @param string $content Content to score
     * @param array $options Options for scoring
     * @return array Quality score data
     */
    public function calculate_quality_score($content, $options = []) {
        // Use ContentQualityCalculator if available
        if (class_exists('ASAPDigest\\Core\\ContentProcessing\\ContentQualityCalculator')) {
            $calculator = new \ASAPDigest\Core\ContentProcessing\ContentQualityCalculator();
            return $calculator->calculate_score($content, $options);
        }
        
        // Legacy fallback - basic quality scoring
        $score = $this->calculate_quality_score_legacy($content);
        return [
            'overall' => $score,
            'coherence' => $score,
            'clarity' => $score,
            'accuracy' => $score,
            'relevance' => $score,
            'engagement' => $score,
            'pass' => $score >= 6.0,
            'recommendations' => ['Consider using the ContentQualityCalculator for more detailed analysis.']
        ];
    }
    
    /**
     * Legacy quality scoring implementation
     *
     * @param string $content Content to analyze
     * @return float Quality score (0-10)
     */
    private function calculate_quality_score_legacy($content) {
        if (empty($content)) {
            return 0;
        }
        
        $score = 5.0; // Start with a base score
        
        // Basic readability
        $avg_sentence_length = $this->calculate_avg_sentence_length($content);
        if ($avg_sentence_length > 10 && $avg_sentence_length < 25) {
            $score += 1;
        }
        
        // Content length
        $word_count = str_word_count(strip_tags($content));
        if ($word_count > 300) {
            $score += 1;
        }
        
        // Basic structure check (paragraphs, headings)
        if (preg_match_all('/<p>/', $content, $matches) > 3) {
            $score += 0.5;
        }
        
        if (preg_match_all('/<h[2-6]/', $content, $matches) > 0) {
            $score += 0.5;
        }
        
        // Basic language check (avoid simple banned words)
        $banned_words = ['very', 'really', 'actually', 'basically', 'literally'];
        $banned_count = 0;
        foreach ($banned_words as $word) {
            $banned_count += substr_count(strtolower($content), " $word ");
        }
        
        if ($banned_count < 3) {
            $score += 0.5;
        }
        
        // Grammar red flags (very basic)
        $grammar_flags = ['should of', 'could of', 'would of', 'alot', 'seperate'];
        $grammar_issues = 0;
        foreach ($grammar_flags as $flag) {
            $grammar_issues += substr_count(strtolower($content), $flag);
        }
        
        if ($grammar_issues == 0) {
            $score += 0.5;
        }
        
        // Ensure score is between 0-10
        return min(10, max(0, $score));
    }

    /**
     * Calculate average sentence length
     *
     * @param string $content Content to analyze
     * @return float Average sentence length in words
     */
    private function calculate_avg_sentence_length($content) {
        $text = strip_tags($content);
        
        // Split by sentence-ending punctuation
        $sentences = preg_split('/[.!?]+/', $text);
        
        // Remove empty sentences
        $sentences = array_filter($sentences, function($sentence) {
            return trim($sentence) !== '';
        });
        
        if (empty($sentences)) {
            return 0;
        }
        
        $total_words = 0;
        foreach ($sentences as $sentence) {
            $total_words += str_word_count(trim($sentence));
        }
        
        return $total_words / count($sentences);
    }
} 