<?php
// Load configuration
$config = json_decode(file_get_contents('config.json'), true);
$paths = $config['paths'];
$allowedExtensions = $config['allowed_extensions'];

// File upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    foreach ($_FILES['file']['name'] as $key => $name) {
        $file = [
            'name' => $name,
            'tmp_name' => $_FILES['file']['tmp_name'][$key],
            'size' => $_FILES['file']['size'][$key],
            'error' => $_FILES['file']['error'][$key]
        ];
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Check if the file extension is allowed
        if (!in_array($ext, $allowedExtensions)) {
            die("File type not allowed!");
        }

        // Determine the target directory
        $targetDir = "";
        if (in_array($ext, ["txt", "pdf", "docx", "pptx"])) {
            $targetDir = $paths['documents'];
        } elseif (in_array($ext, ["png", "jpg", "jpeg"])) {
            $targetDir = $paths['images'];
        } elseif (in_array($ext, ["mp3", "wav"])) {
            $targetDir = $paths['audio'];
        } elseif (in_array($ext, ["mp4", "mov"])) {
            $targetDir = $paths['videos'];
        }

        // Move file to target directory
        if (!empty($targetDir)) {
            $filePath = $targetDir . DIRECTORY_SEPARATOR . basename($file['name']);
            move_uploaded_file($file['tmp_name'], $filePath);
        }
    }
}

// Delete file
if (isset($_POST['delete'])) {
    $filePath = $_POST['delete'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Rename file (only change base name, keep extension)
if (isset($_POST['rename']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $oldPath = $_POST['old_name'];
    $newName = $_POST['new_name'];
    
    // Keep the file extension
    $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
    $newPath = dirname($oldPath) . '/' . $newName . '.' . $ext;

    // Rename the file if it exists
    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
    }
}

// Get all files from directories
$files = [];
foreach ($paths as $type => $dir) {
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if ($file !== "." && $file !== "..") {
                $filePath = $dir . DIRECTORY_SEPARATOR . $file;
                // Check if file exists before using filemtime
                if (file_exists($filePath)) {
                    $files[] = [
                        "path" => $filePath,
                        "name" => $file,
                        "type" => $type,
                        "date" => date("d.m.Y H:i", filemtime($filePath))
                    ];
                }
            }
        }
    }
}

// File download functionality
if (isset($_GET['file'])) {
    $filePath = urldecode($_GET['file']);
    if (file_exists($filePath)) {
        $fileName = basename($filePath);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Manager - Private - Beta</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #1e2a47;
            color: #f0f1f6;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        h2 {
            text-align: center;
            color: #64ffda;
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        .file-upload {
            margin-bottom: 40px;
            position: relative;
        }
        .file-upload input[type="file"] {
            background-color: #64ffda;
            border: 2px solid #0a192f;
            padding: 10px 20px;
            color: #0a192f;
            cursor: pointer;
            font-size: 1.1rem;
            border-radius: 5px;
        }
        .file-upload input[type="file"]:hover {
            background-color: #52d1c5;
        }
        .file-upload .drop-area {
            width: 100%;
            height: 100px;
            background-color: #2b3a58;
            border: 2px dashed #64ffda;
            color: #64ffda;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            border-radius: 10px;
            margin-top: 20px;
            cursor: pointer;
        }
        .file-upload .drop-area:hover {
            background-color: #3d4f6d;
        }
        .file-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .file-item {
            background: #112240;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 220px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
            overflow: hidden;
        }
        .file-item:hover {
            transform: scale(1.05);
        }
        .file-actions {
            margin-top: 10px;
        }
        .btn {
            background: #64ffda;
            color: #0a192f;
            padding: 8px 15px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin: 3px;
            text-transform: uppercase;
            font-size: 0.9rem;
            display: inline-block;
        }
        .btn:hover {
            background: #52d1c5;
        }
        .btn:active {
            transform: scale(0.98);
        }
        .file-actions input[type="text"] {
            padding: 5px 10px;
            margin: 5px;
            border-radius: 5px;
        }
        .file-item p {
            margin: 5px 0;
        }

        /* Popup/Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            overflow: auto;
            padding-top: 60px;
        }
        .modal-content {
            margin: 5% auto;
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
        }
        .modal img, .modal video {
            width: 100%;
            border-radius: 10px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Cloud Manager</h2>

    <!-- File upload -->
    <div class="file-upload">
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file[]" multiple onchange="this.form.submit()" style="display:none;">
            <div class="drop-area" id="drop-area" ondrop="handleDrop(event)" ondragover="allowDrop(event)">
                Drag and drop files here or click to select files.
            </div>
        </form>
    </div>

    <!-- File list -->
    <div class="file-list">
        <?php foreach ($files as $file): ?>
            <div class="file-item">
                <!-- Emoji for different file types -->
                <?php
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ["png", "jpg", "jpeg"])) {
                    $emoji = "🖼️"; // Emoji for image files
                } elseif (in_array($ext, ["mp4", "mov"])) {
                    $emoji = "🎥"; // Emoji for video files
                } else {
                    $emoji = "📄"; // Emoji for other file types
                }
                ?>
                <p><?= $file['name'] ?> (<?= strtoupper($ext) ?>)</p>
                <p><?= $file['date'] ?></p>

                <!-- File actions -->
                <div class="file-actions">
                    <!-- Delete -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete" value="<?= $file['path'] ?>">
                        <button class="btn" type="submit">❌</button>
                    </form>

                    <!-- Rename -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="old_name" value="<?= $file['path'] ?>">
                        <input type="text" name="new_name" placeholder="New Name" required>
                        <button class="btn" type="submit" name="rename">✏️</button>
                    </form>

                    <!-- Download -->
                    <a href="index.php?file=<?= urlencode($file['path']) ?>" class="btn">⬇️</a>

                    <!-- Preview button (Emoji) -->
                    <button class="btn" onclick="openModal('<?= $file['path'] ?>')">
                        <?= $emoji ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal (Popup) -->
<div id="myModal" class="modal">
    <span class="close">&times;</span>
    <div class="modal-content">
        <!-- Content will be loaded here dynamically -->
    </div>
</div>

<script>
    function allowDrop(event) {
        event.preventDefault(); // Prevent default behavior
    }

    function handleDrop(event) {
        event.preventDefault();
        var fileInput = document.querySelector('input[type="file"]');
        var files = event.dataTransfer.files;

        // Insert the dropped files into the upload form
        fileInput.files = files;
        // Automatically submit the form
        document.forms[0].submit();
    }

    // Function to open the modal
    function openModal(filePath) {
        var modalContent = document.querySelector('.modal-content');
        var fileExtension = filePath.split('.').pop().toLowerCase();

        // Check if the file is an image or video
        if (fileExtension === 'jpg' || fileExtension === 'png' || fileExtension === 'jpeg') {
            modalContent.innerHTML = '<img src="' + filePath + '" alt="File">';
        } else if (fileExtension === 'mp4' || fileExtension === 'mov') {
            modalContent.innerHTML = '<video src="' + filePath + '" controls></video>';
        } else {
            modalContent.innerHTML = "<p>Preview for this document is not available.</p>";
        }

        // Show the modal
        document.getElementById("myModal").style.display = "block";
    }

    // Close the modal when the 'x' is clicked
    document.querySelector('.close').addEventListener('click', function() {
        document.getElementById("myModal").style.display = "none";
    });

    // Also close the modal when the user clicks outside of the modal
    window.onclick = function(event) {
        if (event.target == document.getElementById("myModal")) {
            document.getElementById("myModal").style.display = "none";
        }
    };
</script>

</body>
</html>
