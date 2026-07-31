<?php
$srcPath = __DIR__ . '/assets/img/logo.png';
$src = imagecreatefrompng($srcPath);
if (!$src) {
    die("Could not read logo.png\n");
}
$w = imagesx($src);
$h = imagesy($src);

// Create 512x512
$bg512 = imagecreatetruecolor(512, 512);
imagefill($bg512, 0, 0, imagecolorallocate($bg512, 5, 5, 5));
$scale = min(400/$w, 400/$h);
$nw = $w * $scale;
$nh = $h * $scale;
imagecopyresampled($bg512, $src, (512-$nw)/2, (512-$nh)/2, 0, 0, $nw, $nh, $w, $h);
imagepng($bg512, __DIR__ . '/assets/img/icon-512x512.png');
echo "Created icon-512x512.png\n";

// Create 192x192
$bg192 = imagecreatetruecolor(192, 192);
imagefill($bg192, 0, 0, imagecolorallocate($bg192, 5, 5, 5));
$scale192 = min(150/$w, 150/$h);
$nw192 = $w * $scale192;
$nh192 = $h * $scale192;
imagecopyresampled($bg192, $src, (192-$nw192)/2, (192-$nh192)/2, 0, 0, $nw192, $nh192, $w, $h);
imagepng($bg192, __DIR__ . '/assets/img/icon-192x192.png');
echo "Created icon-192x192.png\n";
?>
