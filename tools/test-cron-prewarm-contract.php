<?php

declare(strict_types=1);

require 'C:/xampp/htdocs/wordpress/wp-load.php';

function assert_cron_contract($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function cron_event_count($hook)
{
    $count = 0;
    foreach (_get_cron_array() as $timestamp => $hooks) {
        if (isset($hooks[$hook]) && is_array($hooks[$hook])) {
            $count += count($hooks[$hook]);
        }
    }
    return $count;
}

function cron_hook_count_with_args($hook, array $args)
{
    $count = 0;
    foreach (_get_cron_array() as $timestamp => $hooks) {
        if (empty($hooks[$hook]) || !is_array($hooks[$hook])) {
            continue;
        }
        foreach ($hooks[$hook] as $event) {
            if (($event['args'] ?? array()) === $args) {
                $count++;
            }
        }
    }
    return $count;
}

function assert_single_logical_event($hook)
{
    assert_cron_contract(cron_event_count($hook) === 1, $hook . ' should have exactly one logical event.');
}

function cron_reflect($class, $method)
{
    $reflection = new ReflectionClass($class);
    $target = $reflection->getMethod($method);
    $target->setAccessible(true);
    return $target;
}

function clear_jazireh_cron_events()
{
    Jazireh_APOD_Localizer::clear_schedule();
    Jazireh_APOD_Service::clear_schedule();
    Jazireh_YouTube::clear_schedule();
    Jazireh_Sun_Service::clear_schedule();
    Jazireh_Settings::clear_widget_prewarm();
}

$original_cron = _get_cron_array();
$original_monitor = get_option(Jazireh_APOD_Localizer::OPTION_MONITOR, null);

try {
    clear_jazireh_cron_events();

    jazireh_core_activate();
    do_action('init');
    do_action('init');
    Jazireh_APOD_Service::schedule_refresh();
    Jazireh_APOD_Service::schedule_refresh();
    Jazireh_YouTube::maybe_schedule_refresh();
    Jazireh_YouTube::maybe_schedule_refresh();
    Jazireh_Sun_Service::maybe_schedule_refresh();
    Jazireh_Sun_Service::maybe_schedule_refresh();
    Jazireh_Settings::schedule_widget_prewarm();
    Jazireh_Settings::schedule_widget_prewarm();

    assert_single_logical_event(Jazireh_APOD_Service::REFRESH_HOOK);
    assert_single_logical_event(Jazireh_YouTube::REFRESH_HOOK);
    assert_single_logical_event(Jazireh_Sun_Service::REFRESH_HOOK);
    assert_single_logical_event(Jazireh_Settings::WIDGET_PREWARM_HOOK);

    assert_cron_contract(wp_get_schedule(Jazireh_APOD_Service::REFRESH_HOOK) === false, 'APOD refresh should remain a one-shot event.');
    assert_cron_contract(wp_get_schedule(Jazireh_YouTube::REFRESH_HOOK) === 'jazireh_every_4_hours', 'YouTube refresh recurrence is incorrect.');
    assert_cron_contract(wp_get_schedule(Jazireh_Sun_Service::REFRESH_HOOK) === 'jazireh_every_30_minutes', 'Sun refresh recurrence is incorrect.');
    assert_cron_contract(wp_get_schedule(Jazireh_Settings::WIDGET_PREWARM_HOOK) === 'jazireh_every_30_minutes', 'Widget prewarm recurrence is incorrect.');

    $schedules = wp_get_schedules();
    assert_cron_contract(($schedules['jazireh_every_4_hours']['interval'] ?? 0) === 4 * HOUR_IN_SECONDS, 'YouTube custom interval is not 4 hours.');
    assert_cron_contract(($schedules['jazireh_every_30_minutes']['interval'] ?? 0) === 30 * MINUTE_IN_SECONDS, 'Sun/prewarm custom interval is not 30 minutes.');

    assert_cron_contract(has_action(Jazireh_APOD_Service::REFRESH_HOOK) !== false, 'APOD refresh callback is not registered.');
    assert_cron_contract(has_action(Jazireh_APOD_Localizer::CRON_HOOK) !== false, 'APOD localization callback is not registered.');
    assert_cron_contract(has_action(Jazireh_YouTube::REFRESH_HOOK) !== false, 'YouTube refresh callback is not registered.');
    assert_cron_contract(has_action(Jazireh_Sun_Service::REFRESH_HOOK) !== false, 'Sun refresh callback is not registered.');
    assert_cron_contract(has_action(Jazireh_Settings::WIDGET_PREWARM_HOOK) !== false, 'Widget prewarm callback is not registered.');

    assert_cron_contract(is_callable(array('Jazireh_APOD_Service', 'refresh')), 'APOD refresh callback is not callable.');
    assert_cron_contract(is_callable(array('Jazireh_APOD_Localizer', 'process_date')), 'APOD localization callback is not callable.');
    assert_cron_contract(is_callable(array('Jazireh_YouTube', 'scheduled_refresh')), 'YouTube scheduled callback is not callable.');
    assert_cron_contract(is_callable(array('Jazireh_Sun_Service', 'refresh')), 'Sun refresh callback is not callable.');
    assert_cron_contract(is_callable(array('Jazireh_Settings', 'prewarm_widgets')), 'Widget prewarm callback is not callable.');

    $schedule_next_apod = cron_reflect('Jazireh_APOD_Service', 'schedule_next_refresh');
    wp_clear_scheduled_hook(Jazireh_APOD_Service::REFRESH_HOOK);
    $schedule_next_apod->invoke(null, array('status' => Jazireh_Widgets::STATE_READY));
    assert_single_logical_event(Jazireh_APOD_Service::REFRESH_HOOK);
    assert_cron_contract(wp_get_schedule(Jazireh_APOD_Service::REFRESH_HOOK) === false, 'APOD chained refresh should remain one-shot after success.');

    wp_clear_scheduled_hook(Jazireh_APOD_Service::REFRESH_HOOK);
    $schedule_next_apod->invoke(null, array('status' => Jazireh_Widgets::STATE_ERROR));
    assert_single_logical_event(Jazireh_APOD_Service::REFRESH_HOOK);
    assert_cron_contract(wp_get_schedule(Jazireh_APOD_Service::REFRESH_HOOK) === false, 'APOD retry refresh should remain one-shot after failure.');

    Jazireh_APOD_Localizer::queue_date('2099-01-03');
    Jazireh_APOD_Localizer::queue_date('2099-01-03');
    assert_cron_contract(cron_hook_count_with_args(Jazireh_APOD_Localizer::CRON_HOOK, array('2099-01-03')) === 1, 'APOD localization duplicate event was scheduled.');

    foreach (Jazireh_Settings::widget_descriptors() as $key => $descriptor) {
        assert_cron_contract(!empty($descriptor['prewarm_callback']) && is_callable($descriptor['prewarm_callback']), $key . ' prewarm callback is not callable.');
        assert_cron_contract(strpos(wp_json_encode($descriptor), 'JAZIREH_') === false, $key . ' descriptor exposed credential markers.');
    }

    jazireh_core_deactivate();
    assert_cron_contract(cron_event_count(Jazireh_APOD_Service::REFRESH_HOOK) === 0, 'Deactivation did not clear APOD refresh.');
    assert_cron_contract(cron_event_count(Jazireh_APOD_Localizer::CRON_HOOK) === 0, 'Deactivation did not clear APOD localization jobs.');
    assert_cron_contract(cron_event_count(Jazireh_YouTube::REFRESH_HOOK) === 0, 'Deactivation did not clear YouTube refresh.');
    assert_cron_contract(cron_event_count(Jazireh_Sun_Service::REFRESH_HOOK) === 0, 'Deactivation did not clear Sun refresh.');
    assert_cron_contract(cron_event_count(Jazireh_Settings::WIDGET_PREWARM_HOOK) === 0, 'Deactivation did not clear widget prewarm.');

    echo "Cron/prewarm contract tests passed.\n";
} finally {
    _set_cron_array(is_array($original_cron) ? $original_cron : array());
    if ($original_monitor === null) {
        delete_option(Jazireh_APOD_Localizer::OPTION_MONITOR);
    } else {
        update_option(Jazireh_APOD_Localizer::OPTION_MONITOR, $original_monitor, false);
    }
}
