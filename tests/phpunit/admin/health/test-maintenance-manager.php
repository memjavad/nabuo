<?php
/**
 * Tests for Maintenance_Manager class.
 *
 * @package ArabPsychology\NabooDatabase\Tests\Admin\Health
 */

namespace ArabPsychology\NabooDatabase\Tests\Admin\Health;

use WP_UnitTestCase;
use ArabPsychology\NabooDatabase\Admin\Health\Maintenance_Manager;

class Test_Maintenance_Manager extends WP_UnitTestCase {

    private $manager;

    public function set_up() {
        parent::set_up();
        $this->manager = new Maintenance_Manager();
    }

    public function test_fix_plugin_crons_removes_old_crons() {
        $now = time();
        $crons = [
            $now - 700 => ['some_old_hook' => []],
            $now - 500 => ['some_new_hook' => []],
        ];
        _set_cron_array($crons);

        $this->manager->fix_plugin_crons();

        $updated_crons = _get_cron_array();

        $this->assertIsArray($updated_crons, "Cron array should be preserved");
        $this->assertArrayNotHasKey($now - 700, $updated_crons, "Old cron should be removed");
        $this->assertArrayHasKey($now - 500, $updated_crons, "New cron should be kept");
    }

    public function test_fix_plugin_crons_schedules_missing_events() {
        update_option('naboo_remote_auto_sync', 1);
        update_option('naboo_full_auto_import', 1);
        update_option('naboo_background_ai_delay', '120'); // <= 300 so interval 'naboo_5min'

        // Clear them first to ensure they aren't scheduled
        wp_clear_scheduled_hook('naboo_remote_auto_sync_event');
        wp_clear_scheduled_hook('naboo_weekly_sitemap_cron');
        wp_clear_scheduled_hook('naboo_queue_cleanup');
        wp_clear_scheduled_hook('naboo_full_auto_import_event');
        wp_clear_scheduled_hook('naboo_background_ai_process_draft_event');

        $this->manager->fix_plugin_crons();

        $this->assertTrue(wp_next_scheduled('naboo_remote_auto_sync_event') !== false, "Remote auto sync should be scheduled");

        $this->assertTrue(wp_next_scheduled('naboo_weekly_sitemap_cron') !== false, "Sitemap cron should be scheduled");

        $this->assertTrue(wp_next_scheduled('naboo_queue_cleanup') !== false, "Queue cleanup should be scheduled");

        $this->assertTrue(wp_next_scheduled('naboo_full_auto_import_event') !== false, "Full auto import should be scheduled");

        $this->assertTrue(wp_next_scheduled('naboo_background_ai_process_draft_event') !== false, "AI process draft should be scheduled");
    }

    public function test_fix_plugin_crons_does_not_schedule_if_already_scheduled() {
        update_option('naboo_remote_auto_sync', 1);
        update_option('naboo_full_auto_import', 1);

        // Clear them first
        wp_clear_scheduled_hook('naboo_remote_auto_sync_event');
        wp_clear_scheduled_hook('naboo_weekly_sitemap_cron');
        wp_clear_scheduled_hook('naboo_queue_cleanup');
        wp_clear_scheduled_hook('naboo_full_auto_import_event');

        // Pre-schedule at a specific known time
        $scheduled_time = time() + 3600;
        wp_schedule_event($scheduled_time, 'hourly', 'naboo_remote_auto_sync_event');
        wp_schedule_event($scheduled_time, 'weekly', 'naboo_weekly_sitemap_cron');
        wp_schedule_event($scheduled_time, 'daily', 'naboo_queue_cleanup');
        wp_schedule_event($scheduled_time, 'hourly', 'naboo_full_auto_import_event');

        $this->manager->fix_plugin_crons();

        $this->assertEquals($scheduled_time, wp_next_scheduled('naboo_remote_auto_sync_event'), "Should not overwrite remote auto sync");
        $this->assertEquals($scheduled_time, wp_next_scheduled('naboo_weekly_sitemap_cron'), "Should not overwrite sitemap cron");
        $this->assertEquals($scheduled_time, wp_next_scheduled('naboo_queue_cleanup'), "Should not overwrite queue cleanup");
        $this->assertEquals($scheduled_time, wp_next_scheduled('naboo_full_auto_import_event'), "Should not overwrite full auto import");
    }
}
