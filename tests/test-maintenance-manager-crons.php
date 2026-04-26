<?php
namespace {
    require_once __DIR__ . '/mock-wp-crons.php';
    require_once __DIR__ . '/../includes/admin/health/class-maintenance-manager.php';

    use ArabPsychology\NabooDatabase\Admin\Health\Maintenance_Manager;

    function reset_mocks() {
        $GLOBALS['mock_crons'] = [];
        $GLOBALS['mock_options'] = [];
        $GLOBALS['mock_scheduled'] = [];
        $GLOBALS['mock_actions_called'] = [];
        $GLOBALS['mock_cron_array_saved'] = null;
    }

    ini_set('assert.exception', 1);

    function test_fix_plugin_crons_removes_old_crons() {
        reset_mocks();

        $now = time();
        $GLOBALS['mock_crons'] = [
            $now - 700 => ['some_old_hook' => []],
            $now - 500 => ['some_new_hook' => []],
        ];

        $manager = new Maintenance_Manager();
        $manager->fix_plugin_crons();

        assert($GLOBALS['mock_cron_array_saved'] !== null, "Cron array should be saved when modified");
        assert(!isset($GLOBALS['mock_cron_array_saved'][$now - 700]), "Old cron should be removed");
        assert(isset($GLOBALS['mock_cron_array_saved'][$now - 500]), "New cron should be kept");
        echo "PASS: test_fix_plugin_crons_removes_old_crons\n";
    }

    function test_fix_plugin_crons_schedules_missing_events() {
        reset_mocks();

        $GLOBALS['mock_options']['naboo_remote_auto_sync'] = 1;
        $GLOBALS['mock_options']['naboo_full_auto_import'] = 1;
        $GLOBALS['mock_options']['naboo_background_ai_delay'] = '120'; // <= 300 so interval 'naboo_5min'

        $manager = new Maintenance_Manager();
        $manager->fix_plugin_crons();

        assert(isset($GLOBALS['mock_scheduled']['naboo_remote_auto_sync_event']), "Remote auto sync should be scheduled");
        assert($GLOBALS['mock_scheduled']['naboo_remote_auto_sync_event'] === 'hourly', "Remote auto sync interval should be hourly");

        assert(isset($GLOBALS['mock_scheduled']['naboo_weekly_sitemap_cron']), "Sitemap cron should be scheduled");
        assert($GLOBALS['mock_scheduled']['naboo_weekly_sitemap_cron'] === 'weekly', "Sitemap interval should be weekly");

        assert(isset($GLOBALS['mock_scheduled']['naboo_queue_cleanup']), "Queue cleanup should be scheduled");
        assert($GLOBALS['mock_scheduled']['naboo_queue_cleanup'] === 'daily', "Queue cleanup interval should be daily");

        assert(isset($GLOBALS['mock_scheduled']['naboo_full_auto_import_event']), "Full auto import should be scheduled");
        assert($GLOBALS['mock_scheduled']['naboo_full_auto_import_event'] === 'naboo_5min', "Full auto import interval should be naboo_5min");

        assert(isset($GLOBALS['mock_scheduled']['naboo_background_ai_process_draft_event']), "AI process draft should be scheduled");
        assert($GLOBALS['mock_scheduled']['naboo_background_ai_process_draft_event'] === 'naboo_5min', "AI process draft interval should be naboo_5min");

        echo "PASS: test_fix_plugin_crons_schedules_missing_events\n";
    }

    function test_fix_plugin_crons_does_not_schedule_if_already_scheduled() {
        reset_mocks();

        $GLOBALS['mock_options']['naboo_remote_auto_sync'] = 1;
        $GLOBALS['mock_options']['naboo_full_auto_import'] = 1;

        // Pre-schedule
        $GLOBALS['mock_scheduled']['naboo_remote_auto_sync_event'] = 'already_set';
        $GLOBALS['mock_scheduled']['naboo_weekly_sitemap_cron'] = 'already_set';
        $GLOBALS['mock_scheduled']['naboo_queue_cleanup'] = 'already_set';
        $GLOBALS['mock_scheduled']['naboo_full_auto_import_event'] = 'already_set';

        $manager = new Maintenance_Manager();
        $manager->fix_plugin_crons();

        // They should remain 'already_set'
        assert($GLOBALS['mock_scheduled']['naboo_remote_auto_sync_event'] === 'already_set', "Should not overwrite remote auto sync");
        assert($GLOBALS['mock_scheduled']['naboo_weekly_sitemap_cron'] === 'already_set', "Should not overwrite sitemap cron");
        assert($GLOBALS['mock_scheduled']['naboo_queue_cleanup'] === 'already_set', "Should not overwrite queue cleanup");
        assert($GLOBALS['mock_scheduled']['naboo_full_auto_import_event'] === 'already_set', "Should not overwrite full auto import");

        echo "PASS: test_fix_plugin_crons_does_not_schedule_if_already_scheduled\n";
    }

    echo "Running Maintenance_Manager tests...\n";
    test_fix_plugin_crons_removes_old_crons();
    test_fix_plugin_crons_schedules_missing_events();
    test_fix_plugin_crons_does_not_schedule_if_already_scheduled();
    echo "All Maintenance_Manager tests passed.\n";
}
