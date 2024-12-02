<?php
namespace ApplianceRepairManager\Core\Debug;

class TranslationDebugger {
    private static $instance = null;
    private $untranslated = [];
    private $debug_mode;

    private function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG && get_option('arm_translation_debug_enabled', 0);
        if ($this->debug_mode) {
            add_filter('gettext', [$this, 'track_untranslated'], 999, 3);
            add_filter('gettext_with_context', [$this, 'track_untranslated_with_context'], 999, 4);
        }
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function track_untranslated($translation, $text, $domain) {
        if ($domain === 'appliance-repair-manager' && $translation === $text) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $this->get_caller_info($backtrace);
            $this->untranslated[$text] = [
                'file' => $caller['file'],
                'line' => $caller['line'],
                'function' => $caller['function'],
                'count' => ($this->untranslated[$text]['count'] ?? 0) + 1
            ];
            error_log(sprintf(
                'ARM Translation Debug: Untranslated string "%s" in %s:%d',
                $text,
                $caller['file'],
                $caller['line']
            ));
        }
        return $translation;
    }

    public function track_untranslated_with_context($translation, $text, $context, $domain) {
        if ($domain === 'appliance-repair-manager' && $translation === $text) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $this->get_caller_info($backtrace);
            $key = $context . "\x04" . $text;
            $this->untranslated[$key] = [
                'file' => $caller['file'],
                'line' => $caller['line'],
                'function' => $caller['function'],
                'context' => $context,
                'count' => ($this->untranslated[$key]['count'] ?? 0) + 1
            ];
            error_log(sprintf(
                'ARM Translation Debug: Untranslated string "%s" with context "%s" in %s:%d',
                $text,
                $context,
                $caller['file'],
                $caller['line']
            ));
        }
        return $translation;
    }

    private function get_caller_info($backtrace) {
        $caller = [
            'file' => 'unknown',
            'line' => 0,
            'function' => 'unknown'
        ];

        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && 
                strpos($trace['file'], 'wp-includes') === false && 
                strpos($trace['file'], 'wp-admin') === false) {
                $caller['file'] = str_replace(WP_PLUGIN_DIR, '', $trace['file']);
                $caller['line'] = $trace['line'] ?? 0;
                $caller['function'] = $trace['function'] ?? 'unknown';
                break;
            }
        }

        return $caller;
    }

    public function get_untranslated_strings() {
        return $this->untranslated;
    }

    public function print_debug_info() {
        if (!$this->debug_mode || !current_user_can('manage_options')) {
            return;
        }
        
        echo '<div id="arm-translation-debug" class="notice notice-warning is-dismissible" style="margin: 20px 0;">';
        echo '<h3>Translation Debug Information</h3>';
        
        if (empty($this->untranslated)) {
            echo '<p>No untranslated strings found.</p>';
        } else {
            echo '<p>The following strings are not being translated:</p>';
            echo '<table class="widefat">';
            echo '<thead><tr><th>String</th><th>Context</th><th>Location</th><th>Count</th></tr></thead>';
            echo '<tbody>';
            foreach ($this->untranslated as $key => $info) {
                $text = isset($info['context']) ? substr($key, strpos($key, "\x04") + 1) : $key;
                $context = isset($info['context']) ? $info['context'] : '';
                printf(
                    '<tr><td><code>%s</code></td><td>%s</td><td><small>%s:%d</small></td><td>%d</td></tr>',
                    esc_html($text),
                    esc_html($context),
                    esc_html($info['file']),
                    intval($info['line']),
                    intval($info['count'])
                );
            }
            echo '</tbody></table>';
            echo '<p><small>Note: This information is only visible to administrators when WP_DEBUG is enabled. Check the error log for more details.</small></p>';
        }
        echo '</div>';
        
        // Add some JavaScript to make the notice dismissible
        echo '<script>
            jQuery(document).ready(function($) {
                $(".notice-warning.is-dismissible").on("click", ".notice-dismiss", function() {
                    $(this).parent().slideUp();
                });
            });
        </script>';
    }
}