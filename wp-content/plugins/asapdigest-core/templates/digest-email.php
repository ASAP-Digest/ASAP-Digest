<?php
/**
 * ASAP Digest Email Template
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 * 
 * Variables available:
 * - $posts: Array of WP_Post objects
 * - $settings: Array of digest settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get site info
$site_name = get_bloginfo('name');
$site_url = get_bloginfo('url');

// Get date range
$frequency = $settings['frequency'];
$date_format = get_option('date_format');
$end_date = current_time($date_format);
$start_date = date($date_format, strtotime('-1 ' . $frequency));

?>
<!DOCTYPE html>
<html lang="<?php echo get_bloginfo('language'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($site_name); ?> - <?php echo esc_html__('Digest', 'asap-digest'); ?></title>
    <style type="text/css">
        /* Reset styles */
        body { margin: 0; padding: 0; min-width: 100%; width: 100% !important; height: 100% !important; }
        body, table, td, div, p, a { -webkit-font-smoothing: antialiased; text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; line-height: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse !important; border-spacing: 0; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        
        /* Basic styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: #333333;
            background-color: #f5f5f5;
        }
        
        /* Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        
        /* Header */
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a1a1a;
        }
        
        .header p {
            margin: 10px 0 0;
            color: #666666;
            font-size: 14px;
        }
        
        /* Post list */
        .post-list {
            padding: 20px 0;
        }
        
        .post-item {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .post-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .post-title {
            margin: 0 0 10px;
            font-size: 20px;
        }
        
        .post-title a {
            color: #0073aa;
            text-decoration: none;
        }
        
        .post-meta {
            margin: 0 0 10px;
            font-size: 14px;
            color: #666666;
        }
        
        .post-excerpt {
            margin: 0;
            color: #333333;
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 2px solid #f0f0f0;
            font-size: 14px;
            color: #666666;
        }
        
        .footer a {
            color: #0073aa;
            text-decoration: none;
        }
        
        /* Responsive */
        @media only screen and (max-width: 620px) {
            .container {
                width: 100% !important;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .post-title {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo esc_html($site_name); ?> - <?php echo esc_html__('Digest', 'asap-digest'); ?></h1>
            <p>
                <?php
                printf(
                    esc_html__('Posts from %s to %s', 'asap-digest'),
                    esc_html($start_date),
                    esc_html($end_date)
                );
                ?>
            </p>
        </div>

        <div class="post-list">
            <?php foreach ($posts as $post) : ?>
                <?php
                setup_postdata($post);
                $categories = get_the_category($post->ID);
                $category_names = array_map(function($cat) {
                    return $cat->name;
                }, $categories);
                ?>
                <div class="post-item">
                    <h2 class="post-title">
                        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                            <?php echo esc_html(get_the_title($post->ID)); ?>
                        </a>
                    </h2>
                    <div class="post-meta">
                        <?php
                        printf(
                            esc_html__('Posted on %s in %s', 'asap-digest'),
                            esc_html(get_the_date('', $post->ID)),
                            esc_html(implode(', ', $category_names))
                        );
                        ?>
                    </div>
                    <div class="post-excerpt">
                        <?php echo wp_trim_words(get_the_excerpt($post->ID), 30); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php wp_reset_postdata(); ?>
        </div>

        <div class="footer">
            <p>
                <?php
                printf(
                    esc_html__('You received this email because you are subscribed to %s digests.', 'asap-digest'),
                    esc_html($site_name)
                );
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url($site_url); ?>">
                    <?php esc_html_e('Visit our website', 'asap-digest'); ?>
                </a>
            </p>
        </div>
    </div>
</body>
</html> 