$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mimeType == 'application/pdf') {
    // Proceed with file upload
} else {
    echo "Invalid file type.";
}
