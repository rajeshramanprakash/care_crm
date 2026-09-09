<?php
// ⚠️ Remove this script after use for security reasons.

echo "<pre>";

// ✅ Set the path to your Laravel project (absolute path)
$laravelPath = "/home/crmcarelix/public_html"; // <-- Make sure this is correct

if (!is_dir($laravelPath)) {
    die("❌ Laravel path not found: $laravelPath");
}

// Change working directory
chdir($laravelPath);
echo "👉 Changed to Laravel directory: $laravelPath\n\n";

// 1. Clear Laravel Cache
echo "🧹 Clearing Laravel Cache...\n";
echo shell_exec('php artisan cache:clear');

// 2. Clear Config Cache
echo "\n🧹 Clearing Config Cache...\n";
echo shell_exec('php artisan config:clear');
echo shell_exec('php artisan config:cache');

// 3. Clear Route Cache
echo "\n🧹 Clearing Route Cache...\n";
echo shell_exec('php artisan route:clear');

// 4. Clear View Cache
echo "\n🧹 Clearing View Cache...\n";
echo shell_exec('php artisan view:clear');

// 5. Create Storage Symlink
echo "\n🔗 Creating storage symlink...\n";
echo shell_exec('php artisan storage:link');

// 6. Check Disk Usage
echo "\n💾 Checking Disk Usage...\n";
echo shell_exec('df -h');

// 7. Top Large Files
echo "\n📦 Top Large Files:\n";
echo shell_exec('du -ah . | sort -rh | head -n 10');

echo "</pre>";
?>
