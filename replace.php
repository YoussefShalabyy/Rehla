<?php
$dirs = ['tests', 'database/factories'];

foreach ($dirs as $dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            
            $newContent = str_replace(
                ['ListingStatus::Published', 'ListingStatus::Pending', 'ListingStatus::Rejected', "'status' => 'published'", "'status' => 'pending'", "'status' => 'rejected'", "'status', 'published'", "'status', 'pending'", "'status', 'disabled'", '"published"'],
                ['ListingStatus::Active', 'ListingStatus::Hidden', 'ListingStatus::Disabled', "'status' => 'active'", "'status' => 'hidden'", "'status' => 'disabled'", "'status', 'active'", "'status', 'hidden'", "'status', 'disabled'", '"active"'],
                $content
            );
            
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                echo "Updated $path\n";
            }
        }
    }
}
echo "Done.\n";
