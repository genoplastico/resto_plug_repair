<?php
namespace ApplianceRepairManager\Core\Debug;

class TranslationDebugger {
    private static $instance = null;
    private $untranslated = [];
    private $debug_mode;

    private function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        if ($this->debug_mode) {
            add_filter('gettext', [$this, 'track_untranslated'], 10, 3);
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
            $this->untranslated[$text] = [
                'file' => $backtrace[2]['file'] ?? 'unknown',
                'line' => $backtrace[2]['line'] ?? 'unknown',
                'count' => ($this->untranslated[$text]['count'] ?? 0) + 1
            ];
        }
        return $translation;
    }

    public function get_untranslated_strings() {
        return $this->untranslated;
    }

    public function print_debug_info() {
        if (!$this->debug_mode || !current_user_can('manage_options')) {
            return;
        }
        
        echo '<div id="arm-translation-debug" class="notice notice-warning is-dismissible">';
        echo '<h3>Translation Debug Information</h3>';
        
        if (empty($this->untranslated)) {
            echo '<p>No untranslated strings found.</p>';
        } else {
            echo '<p>The following strings are not being translated:</p>';
            echo '<table class="widefat">';
            echo '<thead><tr><th>String</th><th>File</th><th>Line</th><th>Count</th></tr></thead>';
            echo '<tbody>';
            foreach ($this->untranslated as $text => $info) {
                printf(
                    '<tr><td><code>%s</code></td><td><small>%s</small></td><td>%d</td><td>%d</td></tr>',
                    esc_html($text),
                    esc_html(str_replace(WP_PLUGIN_DIR, '', $info['file'])),
                    esc_html($info['line']),
                    intval($info['count'])
                );
            }
            echo '</tbody></table>';
            echo '<p><small>Note: This information is only visible to administrators when WP_DEBUG is enabled.</small></p>';
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