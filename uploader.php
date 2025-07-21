<?php
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentDirectory = getcwd();

//echo $currentDirectory; //debug E:\untitled\L@

$uploadDirectory = "C:\Users\Public\Uploads";

//E:\untitled\L@

$errors = [];
$maxFileSize = 4000000; // 4MB in bytes

$fileExtensionsAllowed = ['jpeg','jpg','png','xlsx','docx','pdf','txt','docb','doc','xlsm','xlsb','xls','xlam' ];
$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain'
]; 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileOwner = $_POST["nm"];
    $fileNotes = $_POST["txt"];

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    if (empty($fileOwner) || empty($fileNotes)) {
        $errors[] = "All fields are required";
    }

    $results = [];
    if (isset($_FILES["inpt"])) {
        $fileCount = count($_FILES['inpt']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = $_FILES['inpt']['name'][$i];
            $fileSize = $_FILES['inpt']['size'][$i];
            $fileTmpName = $_FILES['inpt']['tmp_name'][$i];
            $fileType = $_FILES['inpt']['type'][$i];
            $fileError = $_FILES['inpt']['error'][$i];

            // Sanitize filename
            $fileNameSanitized = preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
            $fileExtension = strtolower(pathinfo($fileNameSanitized, PATHINFO_EXTENSION));
            $uploadPath = $uploadDirectory . basename($fileNameSanitized);

            $fileResult = [
                'file' => $fileName,
                'status' => 'success',
                'message' => ''
            ];

            if ($fileError !== 0) {
                $fileResult['status'] = 'error';
                $fileResult['message'] = 'File upload error.';
                $results[] = $fileResult;
                continue;
            }
            if (!in_array($fileExtension, $fileExtensionsAllowed)) {
                $fileResult['status'] = 'error';
                $fileResult['message'] = 'File extension not allowed.';
                $results[] = $fileResult;
                continue;
            }
            if (!in_array($fileType, $allowedMimeTypes)) {
                $fileResult['status'] = 'error';
                $fileResult['message'] = 'File type not allowed.';
                $results[] = $fileResult;
                continue;
            }
            if ($fileSize > $maxFileSize) {
                $fileResult['status'] = 'error';
                $fileResult['message'] = 'File exceeds maximum size (4MB).';
                $results[] = $fileResult;
                continue;
            }
            // Check if file already exists
            if (file_exists($uploadPath)) {
                $fileNameSanitized = time() . '_' . $fileNameSanitized;
                $uploadPath = $uploadDirectory . $fileNameSanitized;
            }
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }
            $didUpload = move_uploaded_file($fileTmpName, $uploadPath);
            if ($didUpload) {
                $uploadNotes = date('Y-m-d H:i:s') . " | Owner: " . $fileOwner . " | File: " . $fileNameSanitized . " | Notes: " . $fileNotes;
                try {
                    $current = file_get_contents("ufiles/uploads.txt");
                    $current .= $uploadNotes . "\n";
                    file_put_contents("ufiles/uploads.txt", $current, LOCK_EX);
                    $fileResult['message'] = 'Uploaded successfully!';
                } catch (Exception $e) {
                    $fileResult['status'] = 'error';
                    $fileResult['message'] = 'File uploaded but logging failed.';
                }
            } else {
                $fileResult['status'] = 'error';
                $fileResult['message'] = 'An error occurred during upload.';
            }
            $results[] = $fileResult;
        }
    } else {
        $errors[] = "No files uploaded.";
    }

    // Prepare response
    $response = [];
    if (!empty($errors)) {
        $response['status'] = 'error';
        $response['message'] = implode("\n", $errors);
    } else {
        $response['status'] = 'success';
        $response['message'] = "<ul>" . implode("", array_map(function($r) {
            $icon = $r['status'] === 'success' ? '✅' : '❌';
            return "<li>" . $icon . " " . htmlspecialchars($r['file']) . ": " . htmlspecialchars($r['message']) . "</li>";
        }, $results)) . "</ul>";
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If not a POST request, return the CSRF token
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
?>