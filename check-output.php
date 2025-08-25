<?php
// Check for any output buffering or stuck content
ob_clean();
ob_end_clean();

echo "<!DOCTYPE html><html><body>";
echo "<h1>Output Buffer Check</h1>";
echo "<p>If you see the timestamp here, it's coming from PHP output buffering.</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Buffer level: " . ob_get_level() . "</p>";
echo "</body></html>";
?>