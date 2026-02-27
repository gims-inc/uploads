<?php
// ─────────────────────────────────────────────
// CONFIGURATION
// ─────────────────────────────────────────────
$logFile   = 'uploads.txt';
$uploadDir = 'E:\\UPLOADS_DO_NOT_DELETE\\Uploads\\';

date_default_timezone_set('UTC');

// ─────────────────────────────────────────────
// DOWNLOAD HANDLER
// Triggered when ?download=1&filename=... is set
// Must run before any output
// ─────────────────────────────────────────────
if (isset($_GET['download'], $_GET['filename'])) {
    $allowedDir = realpath($uploadDir);
    $requested  = basename($_GET['filename']); // strip any path traversal
    $fullPath   = realpath($allowedDir . DIRECTORY_SEPARATOR . $requested);

    // Security: ensure resolved path is inside the allowed directory
    if ($fullPath === false || strpos($fullPath, $allowedDir) !== 0) {
        http_response_code(403);
        die('Access denied.');
    }

    if (!file_exists($fullPath)) {
        http_response_code(404);
        die('File not found.');
    }

    // 
    
    if (function_exists('mime_content_type')) {
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
    } elseif (function_exists('finfo_open')) {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fullPath) ?: 'application/octet-stream';
        finfo_close($finfo);
    } else {
        $ext      = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeMap  = [
            'pdf'  => 'application/pdf',
            'zip'  => 'application/zip',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $requested . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    ob_clean();
    flush();
    readfile($fullPath);
    exit;
}

// ─────────────────────────────────────────────
// READ & PARSE LOG FILE
// ─────────────────────────────────────────────
if (!file_exists($logFile)) {
    die('Log file not found.');
}

$logEntries = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$logEntries = array_reverse($logEntries);

$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$filesToday     = [];
$filesYesterday = [];
$filesOlder     = [];

foreach ($logEntries as $entry) {
    $fileInfo  = [];
    $entryDate = null;

    if (strpos($entry, '|') !== false) {
        // New format: 2025-07-21 12:04:43 | Owner: 5457875845 | File: receipt.pdf | Notes: some note
        $parts = explode('|', $entry);
        if (count($parts) >= 4) {
            $fileInfo['date']  = trim($parts[0]);
            $entryDate         = date('Y-m-d', strtotime($fileInfo['date']));
            $fileInfo['owner'] = trim(str_replace('Owner:', '', $parts[1]));
            $fileInfo['file']  = trim(str_replace('File:',  '', $parts[2]));
            $fileInfo['notes'] = trim(str_replace('Notes:', '', $parts[3]));
        }
    } else {
        // Old format: Owner: gabrielFile: gamble.jpgNotes: 2 pages
        preg_match('/Owner:(.*?)File:(.*?)Notes:(.*)/', $entry, $matches);
        if (count($matches) >= 4) {
            $fileInfo['date']  = 'N/A';
            $fileInfo['owner'] = trim($matches[1]);
            $fileInfo['file']  = trim($matches[2]);
            $fileInfo['notes'] = trim($matches[3]);
        }
    }

    if (!empty($fileInfo)) {
        $encodedName = urlencode($fileInfo['file']);
        $fileInfo['preview_link']  = 'preview.php?filename=' . $encodedName;
        $fileInfo['download_link'] = '?download=1&filename='  . $encodedName;

        if ($entryDate === $today) {
            $filesToday[] = $fileInfo;
        } elseif ($entryDate === $yesterday) {
            $filesYesterday[] = $fileInfo;
        } else {
            $filesOlder[] = $fileInfo;
        }
    }
}

// ─────────────────────────────────────────────
// DISPLAY FUNCTION
// ─────────────────────────────────────────────
function displayFiles(array $files, string $title): void {
    if (empty($files)) {
        return;
    }

    echo '<h3>' . htmlspecialchars($title) . '</h3>';
    echo '<table>';
    echo '<thead>
            <tr>
              <th>Date</th>
              <th>Owner</th>
              <th>Filename</th>
              <th>Notes</th>
              <th>Actions</th>
            </tr>
          </thead>';
    echo '<tbody>';

    foreach ($files as $file) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($file['date'])  . '</td>';
        echo '<td>' . htmlspecialchars($file['owner']) . '</td>';
        echo '<td>' . htmlspecialchars($file['file'])  . '</td>';
        echo '<td>' . htmlspecialchars($file['notes']) . '</td>';
        echo '<td class="actions">';

        // View / Preview
        echo '<a href="' . htmlspecialchars($file['preview_link']) . '" target="_blank">'
           . '<button type="button" class="btn btn-view">&#128065; View</button></a>';

        // Download
        echo '<a href="' . htmlspecialchars($file['download_link']) . '">'
           . '<button type="button" class="btn btn-download">&#11015; Download</button></a>';

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table><br>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Files</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: sans-serif;
            margin: 2em;
            color: #333;
        }

        h2 { margin-bottom: 0.25em; }

        .nav-links {
            margin-bottom: 1.5em;
            font-size: 0.95em;
        }
        .nav-links a { color: #007bff; text-decoration: none; }
        .nav-links a:hover { text-decoration: underline; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2em;
        }

        th, td {
            padding: 8px 12px;
            border: 1px solid #ccc;
            text-align: left;
            vertical-align: middle;
        }

        th { background-color: #f4f4f4; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        h3 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        td.actions {
            white-space: nowrap;
            display: flex;
            gap: 6px;
        }

        .btn {
            padding: 4px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85em;
        }

        .btn-view {
            background-color: #007bff;
            color: #fff;
        }
        .btn-view:hover { background-color: #0056b3; }

        .btn-download {
            background-color: #28a745;
            color: #fff;
        }
        .btn-download:hover { background-color: #1e7e34; }

        footer {
            margin-top: 3em;
            font-size: 0.85em;
            color: #888;
        }
        footer a { color: #888; }
    </style>
</head>
<body>

    <h2>Uploaded Files</h2>

    <div class="nav-links">
        <a href="../index.html">&#8592; Back to Upload</a>
    </div>

    <?php
    displayFiles($filesToday,     'Today');
    displayFiles($filesYesterday, 'Yesterday');
    displayFiles($filesOlder,     'Older');

    if (empty($filesToday) && empty($filesYesterday) && empty($filesOlder)) {
        echo '<p>No files uploaded yet.</p>';
    }
    ?>

    <footer>
        <p>Powered by &copy; <a href="https://gimsinc.co.ke" target="_blank">gimsinc.co.ke</a></p>
    </footer>

</body>
</html>
