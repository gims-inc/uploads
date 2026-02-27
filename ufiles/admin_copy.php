<?php
// Path to the uploads log file
$logFile = 'uploads.txt';
// Directory where uploaded files are stored
$uploadDir = "C:/Users/Public/Uploads/";

// Check if the log file exists
if (!file_exists($logFile)) {
    die("Log file not found.");
}

// Read the log file into an array
$logEntries = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
// Reverse the array to get the latest entries first
$logEntries = array_reverse($logEntries);

$filesToday = [];
$filesYesterday = [];
$filesOlder = [];

// Set timezone to avoid date issues
date_default_timezone_set('UTC');
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

foreach ($logEntries as $entry) {
    $fileInfo = [];
    $entryDate = null;
    if (strpos($entry, '|') !== false) {
        // New format: 2025-07-21 12:04:43 | Owner: 5457875845 | File: receipt__5_.pdf | Notes: bnfdgfdgcbvc
        $parts = explode('|', $entry);
        if (count($parts) >= 4) {
            $fileInfo['date'] = trim($parts[0]);
            $entryDate = date('Y-m-d', strtotime($fileInfo['date']));
            $fileInfo['owner'] = trim(str_replace('Owner:', '', $parts[1]));
            $fileInfo['file'] = trim(str_replace('File:', '', $parts[2]));
            $fileInfo['notes'] = trim(str_replace('Notes:', '', $parts[3]));
        }
    } else {
        // Old format: Owner: gabrielFile: gamble.jpgNotes: 2 pages
        preg_match('/Owner:(.*?)File:(.*?)Notes:(.*)/', $entry, $matches);
        if (count($matches) >= 4) {
            $fileInfo['date'] = 'N/A';
            $fileInfo['owner'] = trim($matches[1]);
            $fileInfo['file'] = trim($matches[2]);
            $fileInfo['notes'] = trim($matches[3]);
        }
    }

    if (!empty($fileInfo)) {
        // Add a link to the file for preview
        $fileInfo['preview_link'] = 'preview.php?filename=' . urlencode($fileInfo['file']);
        
        // Categorize by date
        if ($entryDate === $today) {
            $filesToday[] = $fileInfo;
        } elseif ($entryDate === $yesterday) {
            $filesYesterday[] = $fileInfo;
        } else {
            $filesOlder[] = $fileInfo;
        }
    }
}

function displayFiles($files, $title) {
    if (empty($files)) {
        return;
    }
    echo '<h3>' . htmlspecialchars($title) . '</h3>';
    echo '<table>';
    echo '<thead><tr><th>Date</th><th>Owner</th><th>Filename</th><th>Notes</th></tr></thead>';
    echo '<tbody>';
    foreach ($files as $file) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($file['date']) . '</td>';
        echo '<td>' . htmlspecialchars($file['owner']) . '</td>';
        echo '<td><a href="' . $file['preview_link'] . '" target="_blank">' . htmlspecialchars($file['file']) . '</a>';
        // Add download button
        $download_link = $file['preview_link'] . '&download=1';
        echo ' <a href="' . $download_link . '" style="margin-left:10px;" title="view"><button type="button">view</button></a></td>';
        echo '<td>' . htmlspecialchars($file['notes']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<br>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Uploaded Files</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2em; }
        th, td { padding: 8px 12px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f4f4f4; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        h3 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>Uploaded Files</h2>
    <p>
        <a href="<?php echo $uploadDir; ?>" target="_blank">Open Uploads Folder</a> | 
        <a href="../index.html">Back to Upload</a>
    </p>

    <?php
    displayFiles($filesToday, 'Today');
    displayFiles($filesYesterday, 'Yesterday');
    displayFiles($filesOlder, 'Older');

    if (empty($filesToday) && empty($filesYesterday) && empty($filesOlder)) {
        echo '<p>No files uploaded yet.</p>';
    }
    ?>


   <p> powered by &copy gimsinc</p>
   <a href="https://gimsinc.co.ke" target="_blank">gimsinc.co.ke</a>
</body>
</html>