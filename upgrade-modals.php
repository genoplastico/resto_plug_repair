<?php
require_once __DIR__ . '/appliance-repair-manager.php';

// Ensure this script is being run from CLI
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Execute upgrade
$upgrade = new \ApplianceRepairManager\Core\Modal\Upgrade();
$result = $upgrade->execute();

if ($result) {
    echo "Modal system upgrade completed successfully!\n";
} else {
    echo "Error during modal system upgrade. Check error logs for details.\n";
    exit(1);
}