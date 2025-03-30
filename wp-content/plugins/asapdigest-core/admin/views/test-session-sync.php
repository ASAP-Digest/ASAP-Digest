<?php
/**
 * Admin view for session sync testing
 * 
 * @package ASAPDigest_Core
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Run tests if requested
$test_results = null;
if (isset($_POST['run_tests']) && check_admin_referer('asap_session_sync_tests')) {
    require_once(plugin_dir_path(__FILE__) . '../../tests/test-session-sync.php');
    $test_results = asap_run_session_sync_tests();
}
?>

<div class="wrap">
    <h1>Better Auth Session Sync Tests</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('asap_session_sync_tests'); ?>
        <p>
            <input type="submit" name="run_tests" class="button button-primary" value="Run Tests">
        </p>
    </form>

    <?php if ($test_results): ?>
        <div class="card">
            <h2>Test Results</h2>
            
            <?php foreach ($test_results as $category => $tests): ?>
                <h3><?php echo esc_html(ucwords(str_replace('_', ' ', $category))); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Test</th>
                            <th>Status</th>
                            <th>Error (if any)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test): ?>
                            <tr>
                                <td><?php echo esc_html($test['test']); ?></td>
                                <td>
                                    <?php if ($test['passed']): ?>
                                        <span style="color: green;">✓ Passed</span>
                                    <?php else: ?>
                                        <span style="color: red;">✗ Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($test['error'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div> 