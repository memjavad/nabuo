<?php
/**
 * PHPUnit tests for the Comments Moderation class.
 *
 * @package ArabPsychology\NabooDatabase\Tests\Admin
 */

use PHPUnit\Framework\TestCase;
use ArabPsychology\NabooDatabase\Admin\Comments_Moderation;

require_once __DIR__ . "/mock-core-functions.php";
class Test_Comments_Moderation extends TestCase {

    private $moderation;

    protected function setUp(): void {
        parent::setUp();

        // Setup mock environment
        $_POST = [];
        global $wpdb;
        $wpdb = new class {
            public $prefix = 'wp_';
            public $queries = [];
            public function query($query) {
                $this->queries[] = $query;
                return true;
            }
            public function prepare($query, ...$args) {
                if (count($args) === 1 && is_array($args[0])) {
                    $args = $args[0];
                }
                $prepared = $query;
                foreach ($args as $arg) {
                    $prepared = preg_replace('/%d/', (int)$arg, $prepared, 1);
                }
                return $prepared;
            }
            public function get_results($query) { return []; }
            public function get_charset_collate() { return ""; }
            public function get_var($query) { return null; }
        };

        // Reset global mock states
        $GLOBALS['mock_verify_nonce_result'] = true;
        $GLOBALS['mock_current_user_can'] = true;

        // Require the class to test
        require_once dirname(__DIR__, 3) . '/includes/admin/class-comments-moderation.php';

        $this->moderation = new Comments_Moderation('plugin-name', '1.0.0');
    }

    public function test_missing_nonce() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('wp_die: Security check failed.');

        // $_POST is empty
        $this->moderation->handle_action();
    }

    public function test_invalid_nonce() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('wp_die: Security check failed.');

        $GLOBALS['mock_verify_nonce_result'] = false;
        $_POST['naboo_comment_action_nonce'] = 'invalid';

        $this->moderation->handle_action();
    }

    public function test_unauthorized_user() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('wp_die: Unauthorized.');

        $GLOBALS['mock_current_user_can'] = false;
        $_POST['naboo_comment_action_nonce'] = 'valid';

        $this->moderation->handle_action();
    }

    public function test_no_selection() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('wp_safe_redirect: http://example.com/wp-admin/admin.php?page=naboo-comments-moderation&tab=pending&notice=none_selected');

        $_POST['naboo_comment_action_nonce'] = 'valid';
        $_POST['current_tab'] = 'pending';

        $this->moderation->handle_action();
    }

    public function test_approve_single_comment() {
        global $wpdb;

        $_POST['naboo_comment_action_nonce'] = 'valid';
        $_POST['naboo_action'] = 'approve';
        $_POST['comment_id'] = 42;
        $_POST['current_tab'] = 'pending';

        try {
            $this->moderation->handle_action();
            $this->fail('Expected Exception for wp_safe_redirect was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('wp_safe_redirect: http://example.com/wp-admin/admin.php?page=naboo-comments-moderation&tab=pending&notice=approved', $e->getMessage());
        }

        $this->assertCount(1, $wpdb->queries);
        $this->assertEquals("UPDATE `wp_naboo_comments` SET status = 'approved' WHERE id IN (42)", $wpdb->queries[0]);
    }

    public function test_reject_multiple_comments() {
        global $wpdb;

        $_POST['naboo_comment_action_nonce'] = 'valid';
        $_POST['naboo_action'] = 'reject';
        $_POST['bulk_ids'] = [10, 20, 30];
        $_POST['current_tab'] = 'pending';

        try {
            $this->moderation->handle_action();
            $this->fail('Expected Exception for wp_safe_redirect was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('wp_safe_redirect: http://example.com/wp-admin/admin.php?page=naboo-comments-moderation&tab=pending&notice=rejected', $e->getMessage());
        }

        $this->assertCount(1, $wpdb->queries);
        $this->assertEquals("UPDATE `wp_naboo_comments` SET status = 'rejected' WHERE id IN (10,20,30)", $wpdb->queries[0]);
    }

    public function test_spam_comments() {
        global $wpdb;

        $_POST['naboo_comment_action_nonce'] = 'valid';
        $_POST['naboo_action'] = 'spam';
        $_POST['bulk_ids'] = [99];
        $_POST['current_tab'] = 'pending';

        try {
            $this->moderation->handle_action();
            $this->fail('Expected Exception for wp_safe_redirect was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('wp_safe_redirect: http://example.com/wp-admin/admin.php?page=naboo-comments-moderation&tab=pending&notice=spam', $e->getMessage());
        }

        $this->assertCount(1, $wpdb->queries);
        $this->assertEquals("UPDATE `wp_naboo_comments` SET status = 'spam' WHERE id IN (99)", $wpdb->queries[0]);
    }

    public function test_delete_comments() {
        global $wpdb;

        $_POST['naboo_comment_action_nonce'] = 'valid';
        $_POST['naboo_action'] = 'delete';
        $_POST['bulk_ids'] = [5, 6];
        $_POST['current_tab'] = 'pending';

        try {
            $this->moderation->handle_action();
            $this->fail('Expected Exception for wp_safe_redirect was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('wp_safe_redirect: http://example.com/wp-admin/admin.php?page=naboo-comments-moderation&tab=pending&notice=deleted', $e->getMessage());
        }

        $this->assertCount(2, $wpdb->queries);
        $this->assertEquals("DELETE FROM `wp_naboo_comments` WHERE parent_id IN (5,6)", $wpdb->queries[0]);
        $this->assertEquals("DELETE FROM `wp_naboo_comments` WHERE id IN (5,6)", $wpdb->queries[1]);
    }
}
