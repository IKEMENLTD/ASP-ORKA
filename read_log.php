<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Registration Debug Log</h1>";

$log_file = '/var/www/html/regist_debug.log';

if (file_exists($log_file)) {
    $lines = file($log_file);
    $last_lines = array_slice($lines, -150);

    echo "<h2>Last 150 lines:</h2>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto; font-size: 11px;'>";
    echo htmlspecialchars(implode('', $last_lines));
    echo "</pre>";

    $all_content = file_get_contents($log_file);
    echo "<h2>Step Statistics:</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Step</th><th>Count</th></tr>";

    $steps = [
        'STEP 11.5: About to call drawRegistForm',
        'STEP 11.6: drawRegistForm completed',
        'getTemplate() : no hit',
        'getTemplate() : hit'
    ];

    foreach ($steps as $step) {
        $count = substr_count($all_content, $step);
        $color = $count > 0 ? 'green' : 'red';
        echo "<tr><td>$step</td><td style='color: $color; font-weight: bold;'>$count</td></tr>";
    }
    echo "</table>";

} else {
    echo "<p style='color: red;'>Log file not found</p>";
}
?>
