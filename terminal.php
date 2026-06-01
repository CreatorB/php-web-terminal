<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
define('PASS', 'asdf');

if (!isset($_SESSION['auth'])) {
    if (($_POST['p'] ?? '') === PASS) {
        $_SESSION['auth'] = 1;
    } else { ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Terminal Login</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#1e1e1e;display:flex;justify-content:center;align-items:center;height:100vh;font-family:'Courier New',monospace}
.box{border:2px solid #0f0;padding:40px;text-align:center;color:#0f0;width:min(92vw,520px)}
h2{margin-bottom:24px;font-size:24px}
input[type=password]{background:#000;color:#0f0;border:1px solid #0f0;padding:15px 20px;font-size:16px;font-family:inherit;width:100%;outline:none}
input[type=submit]{background:#0f0;color:#000;border:none;padding:15px 30px;font-size:16px;font-weight:bold;cursor:pointer;margin-top:15px;width:100%}
</style>
</head>
<body>
<div class="box">
  <h2>⚡ Terminal v13.0</h2>
  <form method="post">
    <input type="password" name="p" autofocus placeholder="password">
    <input type="submit" value="LOGIN">
  </form>
</div>
</body>
</html>
<?php exit; }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: terminal.php');
    exit;
}

if (!isset($_SESSION['cwd'])) $_SESSION['cwd'] = getcwd();

function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function findNodeVersions(): array {
    $base = '/opt/alt';
    $found = [];
    if (is_dir($base)) {
        foreach (glob($base . '/alt-nodejs*/root/usr/bin/node') as $nodePath) {
            if (preg_match('~/alt-nodejs(\d+)/root/usr/bin/node$~', $nodePath, $m)) {
                $ver = $m[1];
                $binDir = dirname($nodePath);
                $found[$ver] = [
                    'version' => $ver,
                    'bin'     => $binDir,
                    'node'    => $binDir . '/node',
                    'npm'     => $binDir . '/npm',
                    'npx'     => $binDir . '/npx',
                ];
            }
        }
    }
    krsort($found, SORT_NATURAL);
    return $found;
}

function getSelectedNodeVersion(array $versions): ?string {
    $selected = $_SESSION['node_version'] ?? null;
    if ($selected && isset($versions[$selected])) return $selected;
    foreach (['22','20','18','16','14'] as $prefer) {
        if (isset($versions[$prefer])) {
            $_SESSION['node_version'] = $prefer;
            return $prefer;
        }
    }
    $keys = array_keys($versions);
    if ($keys) {
        $_SESSION['node_version'] = $keys[0];
        return $keys[0];
    }
    return null;
}

function buildNodeEnv(): array {
    $versions = findNodeVersions();
    $selected = getSelectedNodeVersion($versions);
    $home = getenv('HOME') ?: '/home/syathiby';

    $basePath = '/usr/local/cpanel/3rdparty/lib/path-bin:/usr/share/Modules/bin:/usr/local/bin:/bin:/usr/bin:/usr/local/sbin:/usr/sbin';
    $nodeBin = ($selected && isset($versions[$selected])) ? $versions[$selected]['bin'] : null;
    $fullPath = $nodeBin ? ($nodeBin . ':' . $basePath) : $basePath;

    return [
        'versions' => $versions,
        'selected' => $selected,
        'home'     => $home,
        'env'      => array_merge($_ENV, [
            'HOME' => $home,
            'USER' => get_current_user(),
            'PATH' => $fullPath,
            'LANG' => 'en_US.UTF-8',
            'LC_ALL' => 'en_US.UTF-8',
            'NODEJS_HOME' => $nodeBin ?: '',
            'npm_config_scripts_prepend_node_path' => 'true',
            'npm_config_loglevel' => 'warn',
            'npm_config_update_notifier' => 'false',
            'npm_config_fund' => 'false',
            'npm_config_audit' => 'false',
            'CI' => '1',
            'NEXT_TELEMETRY_DISABLED' => '1',
            'PRISMA_HIDE_UPDATE_MESSAGE' => '1',
            'NODE_OPTIONS' => '--max-old-space-size=1536',
        ])
    ];
}

function getPromptNodeInfo(): string {
    $ctx = buildNodeEnv();
    if (!$ctx['selected']) return 'Node: not found';
    return 'Node: alt-nodejs' . $ctx['selected'];
}

function githubKnownHostsBlock(): string {
    return trim(<<<'TXT'
github.com ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOMqqnkVzrm0SdG6UOoqKLsabgH5C9okWi0dh2l9GKJl
github.com ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBEmKSENjQEezOmxkZMy7opKgwFB9nkt5YRrYMjNuG5N87uRgg6CLrbo5wAdT/y6v0mKV0U2w0WZ2YB/++Tpockg=
github.com ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQCj7ndNxQowgcQnjshcLrqPEiiphnt+VTTvDP6mHBL9j1aNUkY4Ue1gvwnGLVlOhGeYrnZaMgRK6+PKCUXaDbC7qtbW8gIkhL7aGCsOr/C56SJMy/BCZfxd1nWzAOxSDPgVsmerOBYfNqltV9/hWCqBywINIR+5dIg6JTJ72pcEpEjcYgXkE2YEFXV1JHnsKgbLWNlhScqb2UmyRkQyytRLtL+38TGxkxCflmO+5Z8CSSNY7GidjMIZ7Q4zMjA2n1nGrlTDkzwDCsw+wqFPGQA179cnfGWOWRVruj16z6XyvxvjJwbz0wQZ75XK5tKSb7FNyeIEs4TT4jk+S4dhPeAUC5y+bDYirYgM4GC7uEnztnZyaVWQ7B381AK4Qdrwt51ZqExKbQpTUNn+EjqoTwvqNj4kqx5QUCI0ThS/YkOxJCXmPUWZbhjpCg56i+2aB6CmK2JGhn57K5mj0MNdBXA4/WnwH6XoPWJzK5Nyu2zB3nAZp+S5hpQs+p1vN1/wsjk=
TXT);
}

function ensureGithubKnownHosts(string $home): array {
    $sshDir = rtrim($home, '/') . '/.ssh';
    $knownHosts = $sshDir . '/known_hosts';

    if (!is_dir($sshDir)) @mkdir($sshDir, 0700, true);
    @chmod($sshDir, 0700);

    $current = is_file($knownHosts) ? (string)@file_get_contents($knownHosts) : '';
    $block = githubKnownHostsBlock();

    if (strpos($current, 'github.com ssh-ed25519') === false) {
        $append = ($current !== '' && substr($current, -1) !== "\n") ? "\n" : '';
        @file_put_contents($knownHosts, $append . $block . "\n", FILE_APPEND);
    }
    @chmod($knownHosts, 0600);

    return ['sshDir' => $sshDir, 'knownHosts' => $knownHosts];
}

function convertGithubSshToHttps(string $cmd): string {
    return preg_replace_callback(
        '#git@github\.com:([A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?)(?:\.git)?(?=(\s|$))#i',
        function ($m) {
            return 'https://github.com/' . $m[1] . '.git';
        },
        $cmd
    );
}

function detectProjectRoot(string $cwd): ?string {
    $dir = $cwd;
    for ($i = 0; $i < 8; $i++) {
        if (is_file($dir . '/package.json')) return $dir;
        if (is_file($dir . '/apps/mahadaly/package.json')) return $dir . '/apps/mahadaly';
        $parent = dirname($dir);
        if ($parent === $dir) break;
        $dir = $parent;
    }
    return null;
}

function detectAppDir(string $cwd): string {
    if (is_file($cwd . '/package.json')) return $cwd;
    if (is_file($cwd . '/apps/mahadaly/package.json')) return $cwd . '/apps/mahadaly';
    $root = detectProjectRoot($cwd);
    return $root ?: $cwd;
}

function commandExists(string $bin, array $env, string $cwd): bool {
    $desc = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
    $p = @proc_open(['/bin/sh', '-lc', 'command -v ' . escapeshellarg($bin) . ' >/dev/null 2>&1'], $desc, $pipes, $cwd, $env);
    if (!is_resource($p)) return false;
    fclose($pipes[0]);
    if (isset($pipes[1])) fclose($pipes[1]);
    if (isset($pipes[2])) fclose($pipes[2]);
    $code = proc_close($p);
    return $code === 0;
}

function appNameFromPath(string $appDir): string {
    $base = basename($appDir);
    $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base);
    return $base ?: 'next-app';
}

function createServerJs(string $appDir): string {
    $file = rtrim($appDir, '/') . '/server.js';
    $content = <<<'JS'
const { createServer } = require('http');
const next = require('next');

const port = parseInt(process.env.PORT || '3000', 10);
const hostname = '0.0.0.0';
const dev = false;

const app = next({ dev, hostname, port });
const handle = app.getRequestHandler();

app.prepare().then(() => {
  createServer((req, res) => handle(req, res)).listen(port, hostname, () => {
    console.log(`> Ready on http://${hostname}:${port}`);
  });
}).catch((err) => {
  console.error(err);
  process.exit(1);
});
JS;
    @file_put_contents($file, $content);
    return $file;
}

function installProdCommand(string $appDir): string {
    return implode(' && ', [
        'cd ' . escapeshellarg($appDir),
        'echo "[STEP] Install production dependencies..."',
        'npm install --omit=dev --omit=optional --no-audit --no-fund',
        'echo "[OK] Install selesai"'
    ]);
}

function prismaProdCommand(string $appDir): string {
    return implode(' && ', [
        'cd ' . escapeshellarg($appDir),
        'echo "[STEP] Prisma generate..."',
        'npx prisma generate',
        'echo "[STEP] Prisma migrate deploy..."',
        'npx prisma migrate deploy',
        'echo "[OK] Prisma production step selesai"'
    ]);
}

function seedProdCommand(string $appDir): string {
    return implode(' && ', [
        'cd ' . escapeshellarg($appDir),
        'echo "[STEP] Menjalankan seed..."',
        'npm run db:seed',
        'echo "[OK] Seed selesai"'
    ]);
}

function buildProdCommand(string $appDir): string {
    return implode(' && ', [
        'cd ' . escapeshellarg($appDir),
        'mkdir -p uploads',
        'chmod 755 uploads || true',
        'echo "[STEP] Build Next.js..."',
        'npm run build',
        'echo "[OK] Build selesai"'
    ]);
}

function deployFirstLiteCommand(string $appDir): string {
    return implode(' && ', [
        'cd ' . escapeshellarg($appDir),
        'echo "[STEP] App dir: ' . addslashes($appDir) . '"',
        'if [ -f .env ]; then echo "[STEP] .env ditemukan"; else echo "[ERROR] File .env belum ada"; exit 1; fi',
        'npm install --omit=dev --omit=optional --no-audit --no-fund',
        'mkdir -p uploads',
        'chmod 755 uploads || true',
        'npx prisma generate',
        'npx prisma migrate deploy',
        'npm run db:seed',
        'npm run build',
        'echo "[OK] deploy-first-lite selesai"'
    ]);
}

function deployUpdateLiteCommand(string $appDir): string {
    return implode(' && ', [
        'cd ' . escapeshellarg($appDir),
        'echo "[STEP] App dir: ' . addslashes($appDir) . '"',
        'if [ -f .env ]; then echo "[STEP] .env ditemukan"; else echo "[ERROR] File .env belum ada"; exit 1; fi',
        'npm install --omit=dev --omit=optional --no-audit --no-fund',
        'mkdir -p uploads',
        'chmod 755 uploads || true',
        'npx prisma generate',
        'npx prisma migrate deploy',
        'npm run build',
        'echo "[OK] deploy-update-lite selesai"'
    ]);
}

function packageScriptsHint(string $appDir): string {
    $pkg = $appDir . '/package.json';
    if (!is_file($pkg)) return 'package.json tidak ditemukan';
    $json = @json_decode((string)@file_get_contents($pkg), true);
    if (!is_array($json)) return 'package.json tidak valid';
    $scripts = $json['scripts'] ?? [];
    $start = $scripts['start'] ?? '(tidak ada)';
    $build = $scripts['build'] ?? '(tidak ada)';
    $seed = $scripts['db:seed'] ?? '(tidak ada)';
    return "start={$start} | build={$build} | db:seed={$seed}";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_git_auth'])) {
    $_SESSION['git_username'] = trim($_POST['git_username'] ?? '');
    $_SESSION['git_token'] = trim($_POST['git_token'] ?? '');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'message' => 'GitHub HTTPS credentials tersimpan di session.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_git_auth'])) {
    unset($_SESSION['git_username'], $_SESSION['git_token']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'message' => 'GitHub HTTPS credentials dihapus dari session.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd']) && isset($_GET['stream'])) {
    @set_time_limit(0);
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);

    while (ob_get_level() > 0) { @ob_end_flush(); }
    ob_implicit_flush(true);

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Accel-Buffering: no');

    $cmd = trim($_POST['cmd']);
    $cwd = $_SESSION['cwd'] ?? getcwd();

    if (!@chdir($cwd)) {
        echo "Cannot access working directory: $cwd\n";
        flush();
        exit;
    }

    if (preg_match('/^cd\s*(.*)$/', $cmd, $m)) {
        $target = trim($m[1]) ?: (getenv('HOME') ?: '/home/syathiby');
        if (@chdir($target)) {
            $_SESSION['cwd'] = getcwd();
            echo "__CWD__:" . getcwd() . "\n";
        } else {
            echo "cd: no such file or directory: $target\n";
        }
        flush();
        exit;
    }

    if ($cmd === 'pwd') {
        echo getcwd() . "\n";
        echo "[EXIT CODE] 0\n";
        flush();
        exit;
    }

    if ($cmd === 'node-versions') {
        $ctx = buildNodeEnv();
        if (!$ctx['versions']) {
            echo "No Alt Node.js ditemukan.\n";
            echo "[EXIT CODE] 1\n";
            flush();
            exit;
        }
        foreach ($ctx['versions'] as $ver => $info) {
            $mark = ($ctx['selected'] === $ver) ? '*' : ' ';
            echo "{$mark} node{$ver} => {$info['bin']}\n";
        }
        echo "[EXIT CODE] 0\n";
        flush();
        exit;
    }

    if (preg_match('/^use\s+node(\d+)$/i', $cmd, $m)) {
        $want = $m[1];
        $ctx = buildNodeEnv();
        if (isset($ctx['versions'][$want])) {
            $_SESSION['node_version'] = $want;
            echo "Switched to alt-nodejs{$want}\n";
            echo "__NODEINFO__:alt-nodejs{$want}\n";
            echo "[EXIT CODE] 0\n";
        } else {
            echo "Node version {$want} tidak tersedia.\n";
            echo "[EXIT CODE] 1\n";
        }
        flush();
        exit;
    }

    $ctx = buildNodeEnv();
    $env = $ctx['env'];
    $home = $ctx['home'];
    $appDir = detectAppDir($cwd);

    if ($cmd === 'git-fix') {
        $info = ensureGithubKnownHosts($home);
        echo "[OK] GitHub known_hosts siap: {$info['knownHosts']}\n";
        echo "[EXIT CODE] 0\n";
        flush();
        exit;
    }

    if ($cmd === 'node-check') {
        echo "Working dir: " . getcwd() . "\n";
        echo "Prompt Node: " . getPromptNodeInfo() . "\n";
        echo "App dir: " . $appDir . "\n";
        echo "node: " . (commandExists('node', $env, $cwd) ? 'OK' : 'NOT FOUND') . "\n";
        echo "npm: " . (commandExists('npm', $env, $cwd) ? 'OK' : 'NOT FOUND') . "\n";
        echo "npx: " . (commandExists('npx', $env, $cwd) ? 'OK' : 'NOT FOUND') . "\n";
        echo "git: " . (commandExists('git', $env, $cwd) ? 'OK' : 'NOT FOUND') . "\n";
        echo "scripts: " . packageScriptsHint($appDir) . "\n";
        echo "[EXIT CODE] 0\n";
        flush();
        exit;
    }

    if ($cmd === 'where-app') {
        echo $appDir . "\n";
        echo "[EXIT CODE] 0\n";
        flush();
        exit;
    }

    if ($cmd === 'make-serverjs') {
        $file = createServerJs($appDir);
        echo "[OK] server.js dibuat: {$file}\n";
        echo "[INFO] Untuk cPanel Node.js App, startup file sering diisi: server.js\n";
        echo "[EXIT CODE] 0\n";
        flush();
        exit;
    }

    if ($cmd === 'install-prod') {
        $cmd = installProdCommand($appDir);
    }

    if ($cmd === 'prisma-prod') {
        $cmd = prismaProdCommand($appDir);
    }

    if ($cmd === 'seed-prod') {
        $cmd = seedProdCommand($appDir);
    }

    if ($cmd === 'build-prod') {
        $cmd = buildProdCommand($appDir);
    }

    if ($cmd === 'deploy-first-lite') {
        $cmd = deployFirstLiteCommand($appDir);
    }

    if ($cmd === 'deploy-update-lite') {
        $cmd = deployUpdateLiteCommand($appDir);
    }

    if ($cmd === 'start-hint') {
        echo "[INFO] Shared hosting cPanel: biasanya jalankan app via Node.js Selector / Restart App.\n";
        echo "[INFO] Jika Next.js tidak mau start langsung, buat server.js lalu set startup file = server.js.\n";
        echo "[INFO] Urutan aman: install-prod -> prisma-prod -> seed-prod (sekali) -> build-prod -> Restart App.\n";
        echo "[EXIT CODE] 0\n";
        flush();
        exit;
    }

    $gitUser = trim($_SESSION['git_username'] ?? '');
    $gitToken = trim($_SESSION['git_token'] ?? '');
    $askpassFile = null;
    $isGitCommand = preg_match('/(^|\s)git(\s|$)/i', $cmd) === 1;

    if ($isGitCommand) {
        ensureGithubKnownHosts($home);

        $askpassFile = sys_get_temp_dir() . '/git-askpass-' . session_id() . '.sh';
        $script = "#!/bin/sh\n"
                . "case \"\$1\" in\n"
                . "  *Username* ) printf '%s\\n' \"\$GIT_HTTP_USER\" ;;\n"
                . "  *Password* ) printf '%s\\n' \"\$GIT_HTTP_TOKEN\" ;;\n"
                . "  * ) printf '\\n' ;;\n"
                . "esac\n";
        file_put_contents($askpassFile, $script);
        @chmod($askpassFile, 0700);

        $env['GIT_ASKPASS'] = $askpassFile;
        $env['GIT_TERMINAL_PROMPT'] = '0';
        $env['GIT_HTTP_USER'] = $gitUser;
        $env['GIT_HTTP_TOKEN'] = $gitToken;

        if ($gitUser !== '' && $gitToken !== '' && str_contains($cmd, 'git@github.com:')) {
            $old = $cmd;
            $cmd = convertGithubSshToHttps($cmd);
            echo "[INFO] GitHub SSH URL dikonversi ke HTTPS agar token bisa dipakai.\n";
            echo "[FROM] $old\n";
            echo "[TO]   $cmd\n";
            flush();
        }

        if (preg_match('/^\s*git\s+(pull|fetch|push|clone)\b/i', $cmd) && stripos($cmd, '--progress') === false) {
            $cmd .= ' --progress';
        }
    }

    if (preg_match('/\bprisma\s+migrate\s+dev\b/i', $cmd)) {
        echo "[WARNING] Terdeteksi prisma migrate dev. Untuk production gunakan: npx prisma migrate deploy\n";
        flush();
    }

    $bootstrap = '';
    if (!empty($env['NODEJS_HOME'])) {
        $bootstrap .= 'export PATH="' . addslashes($env['NODEJS_HOME']) . ':$PATH"; ';
        $bootstrap .= 'export npm_config_scripts_prepend_node_path=true; ';
        $bootstrap .= 'export NEXT_TELEMETRY_DISABLED=1; ';
        $bootstrap .= 'export PRISMA_HIDE_UPDATE_MESSAGE=1; ';
        $bootstrap .= 'export CI=1; ';
        $bootstrap .= 'export NODE_ENV=production; ';
    }

    $finalCmd = $bootstrap . $cmd;

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(['/bin/sh', '-lc', $finalCmd], $descriptors, $pipes, $cwd, $env);

    if (!is_resource($process)) {
        if ($askpassFile && is_file($askpassFile)) @unlink($askpassFile);
        echo "Failed to start process.\n";
        flush();
        exit;
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdoutBuf = '';
    $stderrBuf = '';
    $lastStatus = null;

    echo "[RUNNING] $cmd\n";
    if (!empty($env['NODEJS_HOME'])) {
        echo "[NODE] " . $env['NODEJS_HOME'] . "\n";
    }
    flush();

    while (true) {
        $status = proc_get_status($process);
        $lastStatus = $status;
        $running = $status['running'];

        $read = [];
        if (isset($pipes[1]) && is_resource($pipes[1]) && !feof($pipes[1])) $read[] = $pipes[1];
        if (isset($pipes[2]) && is_resource($pipes[2]) && !feof($pipes[2])) $read[] = $pipes[2];

        if (!$running && empty($read)) break;

        if (!empty($read)) {
            $write = null;
            $except = null;
            @stream_select($read, $write, $except, 0, 200000);

            foreach ($read as $r) {
                $chunk = fread($r, 8192);
                if ($chunk === false || $chunk === '') continue;

                if ($r === $pipes[1]) {
                    $stdoutBuf .= $chunk;
                    while (($pos = strpos($stdoutBuf, "\n")) !== false) {
                        $line = substr($stdoutBuf, 0, $pos + 1);
                        $stdoutBuf = substr($stdoutBuf, $pos + 1);
                        echo $line;
                        flush();
                    }
                } else {
                    $stderrBuf .= str_replace("\r", "\n", $chunk);
                    while (($pos = strpos($stderrBuf, "\n")) !== false) {
                        $line = substr($stderrBuf, 0, $pos + 1);
                        $stderrBuf = substr($stderrBuf, $pos + 1);
                        echo $line;
                        flush();
                    }
                }
            }
        } else {
            usleep(100000);
        }

        if (connection_aborted()) {
            @proc_terminate($process);
            break;
        }
    }

    if ($stdoutBuf !== '') {
        echo $stdoutBuf;
        flush();
    }
    if ($stderrBuf !== '') {
        echo str_replace("\r", "\n", $stderrBuf);
        flush();
    }

    if (isset($pipes[1]) && is_resource($pipes[1])) fclose($pipes[1]);
    if (isset($pipes[2]) && is_resource($pipes[2])) fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode === -1 && is_array($lastStatus) && isset($lastStatus['running']) && $lastStatus['running'] === false && isset($lastStatus['exitcode']) && $lastStatus['exitcode'] !== -1) {
        $exitCode = $lastStatus['exitcode'];
    }

    if ($askpassFile && is_file($askpassFile)) {
        @unlink($askpassFile);
    }

    if ($exitCode !== 0) {
        echo "\n[TIPS]\n";
        echo "- Shared hosting: jalankan bertahap, jangan langsung semua.\n";
        echo "- Coba urutan: install-prod -> prisma-prod -> seed-prod -> build-prod\n";
        echo "- Jika build/start bermasalah di cPanel, jalankan make-serverjs lalu set startup file ke server.js\n";
        echo "- Production Prisma: gunakan migrate deploy, bukan migrate dev\n";
    }

    echo "\n[EXIT CODE] {$exitCode}\n";
    flush();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Terminal</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#1a1a1a;color:#ccc;font:15px/1.5 'Courier New',monospace;height:100vh;display:flex;flex-direction:column;overflow:hidden}
#topbar{background:#2d2d2d;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #444;flex-shrink:0;gap:10px;flex-wrap:wrap}
#topbar .left{display:flex;flex-direction:column;gap:4px}
#topbar span{color:#0f0;font-weight:bold}
#node-info{color:#7CFC00;font-size:13px}
#topbar a{color:#ff6666;font-size:13px;text-decoration:none}
#gitbar{background:#202020;border-bottom:1px solid #333;padding:12px 20px;display:grid;grid-template-columns:1fr 1fr auto auto;gap:10px;align-items:center;flex-shrink:0}
#gitbar input{background:#000;color:#0f0;border:1px solid #0f0;padding:10px 12px;font:inherit;outline:none;width:100%}
#gitbar button{border:none;padding:10px 16px;font-weight:bold;cursor:pointer;border-radius:3px}
#saveGit{background:#0f0;color:#000}
#clearGit{background:#ff6666;color:#000}
#gitStatus{grid-column:1/-1;color:#888;font-size:13px;white-space:pre-wrap}
#output{flex:1;overflow-y:auto;padding:20px;background:#1a1a1a;scroll-behavior:smooth}
.line-block{margin-bottom:2px}
.prompt-line{color:#0f0;font-weight:bold}
.output-text{color:#ccc;white-space:pre-wrap;word-break:break-word}
.error-text{color:#ff6666;white-space:pre-wrap;word-break:break-word}
.sys-text{color:#666;font-size:13px}
#inputbar{background:#2d2d2d;border-top:1px solid #444;padding:12px 20px;display:flex;align-items:center;flex-shrink:0;gap:10px}
#current-dir{color:#0f0;font-weight:bold;white-space:nowrap;flex-shrink:0}
#cmd{flex:1;background:transparent;color:#fff;border:none;outline:none;font:inherit;font-size:15px;caret-color:#0f0}
#run-btn{background:#0f0;color:#000;border:none;padding:10px 25px;font-weight:bold;font-size:14px;cursor:pointer;border-radius:3px;white-space:nowrap}
#run-btn:hover{background:#00cc33}
@media (max-width:860px){
  #gitbar{grid-template-columns:1fr}
  #inputbar{flex-wrap:wrap}
  #current-dir{width:100%}
}
</style>
</head>
<body>
<div id="topbar">
  <div class="left">
    <span id="dir-title"><?= h($_SESSION['cwd']) ?>$</span>
    <div id="node-info"><?= h(getPromptNodeInfo()) ?></div>
  </div>
  <a href="?logout=1">❌ Logout</a>
</div>

<div id="gitbar">
  <input id="git_username" type="text" placeholder="GitHub Username" value="<?= h($_SESSION['git_username'] ?? '') ?>">
  <input id="git_token" type="password" placeholder="GitHub Token" value="<?= h($_SESSION['git_token'] ?? '') ?>">
  <button id="saveGit" type="button">SAVE GIT AUTH</button>
  <button id="clearGit" type="button">CLEAR</button>
  <div id="gitStatus">Ketik: use node20, node-check, where-app, install-prod, prisma-prod, seed-prod, build-prod, deploy-first-lite, deploy-update-lite, make-serverjs, start-hint</div>
</div>

<div id="output">
  <div class="line-block"><span class="sys-text">Terminal v13.0 | <?= date('d/m/Y H:i:s') ?> | PHP <?= h(PHP_VERSION) ?> | <?= h(get_current_user()) ?></span></div>
  <div class="line-block"><span class="sys-text"><?= h(getPromptNodeInfo()) ?> | PATH Node diinject otomatis ke shell command.</span></div>
  <div class="line-block"><span class="sys-text">Built-in: cd, pwd, node-versions, use node20, node-check, where-app, install-prod, prisma-prod, seed-prod, build-prod, deploy-first-lite, deploy-update-lite, make-serverjs, start-hint</span></div>
  <div class="line-block sys-text">──────────────────────────────────────────────────────────────</div>
</div>

<div id="inputbar">
  <span id="current-dir"><?= h($_SESSION['cwd']) ?>$</span>
  <input id="cmd" type="text" autocomplete="off" autofocus placeholder="ketik command...">
  <button id="run-btn">ENTER</button>
</div>

<script>
let cwd = <?= json_encode($_SESSION['cwd']) ?>;
const output = document.getElementById('output');
const cmdInput = document.getElementById('cmd');
const dirTitle = document.getElementById('dir-title');
const currentDir = document.getElementById('current-dir');
const gitStatus = document.getElementById('gitStatus');
const nodeInfo = document.getElementById('node-info');
let history = [], hIdx = -1;

function scrollBottom(){ output.scrollTop = output.scrollHeight; }
function appendLine(html){
  const d = document.createElement('div');
  d.className = 'line-block';
  d.innerHTML = html;
  output.appendChild(d);
  scrollBottom();
}
function escapeHtml(s){
  return String(s)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}
function updateDir(){
  dirTitle.textContent = cwd + '$';
  currentDir.textContent = cwd + '$';
}
async function runCmd(){
  const cmd = cmdInput.value.trim();
  if(!cmd) return;

  history.unshift(cmd);
  if(history.length > 100) history.pop();
  hIdx = -1;

  appendLine('<span class="prompt-line">' + escapeHtml(cwd) + '$ ' + escapeHtml(cmd) + '</span>');
  cmdInput.value = '';

  const response = await fetch(location.pathname + '?stream=1', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'cmd=' + encodeURIComponent(cmd)
  });

  if(!response.body){
    const txt = await response.text();
    appendLine('<span class="output-text">' + escapeHtml(txt || '(no output)') + '</span>');
    return;
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';

  while(true){
    const {value, done} = await reader.read();
    if(done) break;

    buffer += decoder.decode(value, {stream:true});
    let parts = buffer.split('\n');
    buffer = parts.pop();

    for(const line of parts){
      const trimmed = line.replace(/\r/g, '');
      if(trimmed.startsWith('__CWD__:')){
        cwd = trimmed.replace('__CWD__:', '').trim();
        updateDir();
        appendLine('<span class="output-text">➜ ' + escapeHtml(cwd) + '</span>');
      } else if(trimmed.startsWith('__NODEINFO__:')){
        nodeInfo.textContent = 'Node: ' + trimmed.replace('__NODEINFO__:', '').trim();
        appendLine('<span class="sys-text">' + escapeHtml(nodeInfo.textContent) + '</span>');
      } else if(trimmed.startsWith('[ERROR]') || trimmed.includes('fatal:') || trimmed.includes('npm ERR!') || trimmed.includes('Error:')){
        appendLine('<span class="error-text">' + escapeHtml(trimmed) + '</span>');
      } else if(trimmed.startsWith('[INFO]') || trimmed.startsWith('[STEP]') || trimmed.startsWith('[OK]') || trimmed.startsWith('[WARNING]') || trimmed.startsWith('[TIPS]') || trimmed.startsWith('[FROM]') || trimmed.startsWith('[TO]')){
        appendLine('<span class="sys-text">' + escapeHtml(trimmed) + '</span>');
      } else {
        appendLine('<span class="output-text">' + escapeHtml(trimmed) + '</span>');
      }
    }
  }

  if(buffer.trim() !== ''){
    const trimmed = buffer.replace(/\r/g, '');
    if(trimmed.startsWith('__CWD__:')){
      cwd = trimmed.replace('__CWD__:', '').trim();
      updateDir();
      appendLine('<span class="output-text">➜ ' + escapeHtml(cwd) + '</span>');
    } else if(trimmed.startsWith('__NODEINFO__:')){
      nodeInfo.textContent = 'Node: ' + trimmed.replace('__NODEINFO__:', '').trim();
      appendLine('<span class="sys-text">' + escapeHtml(nodeInfo.textContent) + '</span>');
    } else if(trimmed.startsWith('[ERROR]') || trimmed.includes('fatal:') || trimmed.includes('npm ERR!') || trimmed.includes('Error:')){
      appendLine('<span class="error-text">' + escapeHtml(trimmed) + '</span>');
    } else if(trimmed.startsWith('[INFO]') || trimmed.startsWith('[STEP]') || trimmed.startsWith('[OK]') || trimmed.startsWith('[WARNING]') || trimmed.startsWith('[TIPS]') || trimmed.startsWith('[FROM]') || trimmed.startsWith('[TO]')){
      appendLine('<span class="sys-text">' + escapeHtml(trimmed) + '</span>');
    } else {
      appendLine('<span class="output-text">' + escapeHtml(trimmed) + '</span>');
    }
  }
}

function saveGitAuth(){
  const user = document.getElementById('git_username').value.trim();
  const token = document.getElementById('git_token').value.trim();

  fetch(location.href, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'save_git_auth=1&git_username=' + encodeURIComponent(user) + '&git_token=' + encodeURIComponent(token)
  })
  .then(r => r.json())
  .then(res => {
    gitStatus.textContent = res.message || 'Saved.';
    appendLine('<span class="sys-text">' + escapeHtml(gitStatus.textContent) + '</span>');
  })
  .catch(e => {
    gitStatus.textContent = 'Gagal simpan Git auth: ' + e.message;
    appendLine('<span class="error-text">' + escapeHtml(gitStatus.textContent) + '</span>');
  });
}

function clearGitAuth(){
  document.getElementById('git_username').value = '';
  document.getElementById('git_token').value = '';

  fetch(location.href, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'clear_git_auth=1'
  })
  .then(r => r.json())
  .then(res => {
    gitStatus.textContent = res.message || 'Cleared.';
    appendLine('<span class="sys-text">' + escapeHtml(gitStatus.textContent) + '</span>');
  })
  .catch(e => {
    gitStatus.textContent = 'Gagal hapus Git auth: ' + e.message;
    appendLine('<span class="error-text">' + escapeHtml(gitStatus.textContent) + '</span>');
  });
}

cmdInput.addEventListener('keydown', e => {
  if(e.key === 'Enter'){ runCmd(); return; }
  if(e.key === 'ArrowUp'){
    e.preventDefault();
    if(hIdx < history.length - 1){
      hIdx++;
      cmdInput.value = history[hIdx] || '';
    }
    return;
  }
  if(e.key === 'ArrowDown'){
    e.preventDefault();
    if(hIdx > 0){
      hIdx--;
      cmdInput.value = history[hIdx];
    } else {
      hIdx = -1;
      cmdInput.value = '';
    }
  }
});

document.getElementById('run-btn').onclick = runCmd;
document.getElementById('saveGit').onclick = saveGitAuth;
document.getElementById('clearGit').onclick = clearGitAuth;

cmdInput.focus();
scrollBottom();
</script>
</body>
</html>
