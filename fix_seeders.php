<?php

// Script to fix all seeder files to use updateOrInsert() instead of insert()

$seederDir = __DIR__ . '/database/seeders/Tables/';
$files = glob($seederDir . '*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Skip if already using updateOrInsert
    if (strpos($content, 'updateOrInsert') !== false) {
        continue;
    }
    
    // Extract table name from the insert statement
    if (preg_match('/DB::table\("([^"]+)"\)->insert\(\$dataTables\);/', $content, $matches)) {
        $tableName = $matches[1];
        
        // Replace the insert statement with updateOrInsert loop
        $replacement = "foreach (\$dataTables as \$data) {\n            DB::table(\"$tableName\")->updateOrInsert(['id' => \$data['id']], \$data);\n        }";
        
        $newContent = str_replace(
            "DB::table(\"$tableName\")->insert(\$dataTables);",
            $replacement,
            $content
        );
        
        file_put_contents($file, $newContent);
        echo "Fixed: " . basename($file) . "\n";
    }
}

echo "All seeder files have been updated to use updateOrInsert()\n";
