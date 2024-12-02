<?php
// Add to the init_managers() method in Plugin.php
private function init_managers() {
    // ... existing managers ...
    
    // Add the upgrade manager
    if (is_admin()) {
        new \ApplianceRepairManager\Admin\UpgradeManager();
    }
}