<?php
/* Create new image object */

$image = new ZBarCodeImage(getcwd().'/test.jpg');

/* Create a barcode scanner */
$scanner = new ZBarCodeScanner();

/* Scan the image */
$barcode = $scanner->scan($image);

echo '<img src="/test.jpg"><br>';

/* Loop through possible barcodes */
if (!empty($barcode)) {
	foreach ($barcode as $code) {
		echo $code['data'];
	}
}