<?php
// Simple password protection
session_start();
$ADMIN_PASSWORD = 'Seme2026!Secure'; // CHANGE THIS

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: analytics.php');
    exit;
}

// Login check
if (isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Invalid password';
    }
}

// If not logged in, show login
if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login | David Seme Analytics</title>
        <style>
            body { background: #0a0a0f; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Poppins', sans-serif; }
            .login-box { background: #151a18; border: 1px solid #24302b; border-radius: 14px; padding: 2rem; width: 400px; max-width: 90vw; }
            h2 { color: #e4eae7; margin-bottom: 1.5rem; text-align: center; }
            input { width: 100%; padding: 12px; background: #0a0a0f; border: 1px solid #24302b; color: #e4eae7; border-radius: 8px; margin-bottom: 1rem; font-size: 1rem; }
            button { width: 100%; padding: 12px; background: linear-gradient(135deg, #1f8f6d, #35b98d); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; }
            .error { color: #e15c5c; text-align: center; margin-bottom: 1rem; font-size: 0.85rem; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔐 Admin Access</h2>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter password" required autofocus>
                <button type="submit">Unlock Dashboard</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ---- LOGGED IN ----

// Database setup (SQLite - no MySQL needed)
$db = new SQLite3('analytics.db');

// Create tables if not exist
$db->exec("CREATE TABLE IF NOT EXISTS visitors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip TEXT,
    page TEXT,
    referrer TEXT,
    user_agent TEXT,
    country TEXT,
    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS access_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    email TEXT,
    project TEXT,
    interest TEXT,
    message TEXT,
    status TEXT DEFAULT 'pending',
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Handle status update
if (isset($_POST['update_status'])) {
    $stmt = $db->prepare("UPDATE access_requests SET status = :status WHERE id = :id");
    $stmt->bindValue(':status', $_POST['status'], SQLITE3_TEXT);
    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: analytics.php');
    exit;
}

// Get stats
$totalVisitors = $db->querySingle("SELECT COUNT(*) FROM visitors");
$uniqueIPs = $db->querySingle("SELECT COUNT(DISTINCT ip) FROM visitors");
$totalRequests = $db->querySingle("SELECT COUNT(*) FROM access_requests");
$pendingRequests = $db->querySingle("SELECT COUNT(*) FROM access_requests WHERE status='pending'");
$todayVisitors = $db->querySingle("SELECT COUNT(*) FROM visitors WHERE date(visited_at) = date('now')");

// Get recent visitors
$recentVisitors = $db->query("SELECT * FROM visitors ORDER BY visited_at DESC LIMIT 20");

// Get access requests
$requests = $db->query("SELECT * FROM access_requests ORDER BY requested_at DESC");

// Get top pages
$topPages = $db->query("SELECT page, COUNT(*) as count FROM visitors GROUP BY page ORDER BY count DESC LIMIT 10");

// Get daily stats (last 14 days)
$dailyStats = $db->query("SELECT date(visited_at) as day, COUNT(*) as count FROM visitors WHERE visited_at >= date('now', '-14 days') GROUP BY day ORDER BY day DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard | David Seme</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --surface: #151a18;
            --border: #24302b;
            --text: #e4eae7;
            --text-secondary: #889992;
            --teal: #35b98d;
            --gold: #d9a441;
            --red: #e15c5c;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: 'Poppins', sans-serif; line-height: 1.6; }
        .container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        
        header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 1rem 0; margin-bottom: 2rem; }
        header .container { display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.2rem; font-weight: 600; }
        header a { color: var(--red); text-decoration: none; font-size: 0.8rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 2rem; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.2rem; text-align: center; }
        .stat-card .number { font-size: 2rem; font-weight: 700; color: var(--teal); font-family: 'JetBrains Mono', monospace; }
        .stat-card .label { font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; }
        .stat-card.pending .number { color: var(--gold); }
        
        .section { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .section h3 { font-size: 1rem; margin-bottom: 1rem; color: var(--teal); display: flex; align-items: center; gap: 8px; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        th { text-align: left; padding: 10px 12px; border-bottom: 2px solid var(--border); color: var(--text-secondary); font-weight: 600; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 10px 12px; border-bottom: 1px solid rgba(36,48,43,0.5); font-size: 0.8rem; }
        tr:hover { background: rgba(53,185,141,0.04); }
        
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; }
        .badge-pending { background: rgba(217,164,65,0.15); color: var(--gold); border: 1px solid rgba(217,164,65,0.3); }
        .badge-approved { background: rgba(53,185,141,0.15); color: var(--teal); border: 1px solid rgba(53,185,141,0.3); }
        .badge-rejected { background: rgba(225,92,92,0.15); color: var(--red); border: 1px solid rgba(225,92,92,0.3); }
        
        select { background: var(--bg); border: 1px solid var(--border); color: var(--text); padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; cursor: pointer; }
        .mono { font-family: 'JetBrains Mono', monospace; font-size: 0.72rem; color: var(--text-secondary); }
        
        .request-card { border: 1px solid var(--border); border-radius: 10px; padding: 1rem; margin-bottom: 0.8rem; }
        .request-card:hover { border-color: var(--teal); }
        .request-card .meta { font-size: 0.72rem; color: var(--text-secondary); margin-bottom: 4px; }
        .request-card .project-name { color: var(--teal); font-weight: 600; }
        .request-card .actions { margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-sm { padding: 5px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: var(--text); cursor: pointer; font-size: 0.7rem; font-weight: 500; }
        .btn-sm.approve { border-color: var(--teal); color: var(--teal); }
        .btn-sm.approve:hover { background: rgba(53,185,141,0.1); }
        .btn-sm.reject { border-color: var(--red); color: var(--red); }
        .btn-sm.reject:hover { background: rgba(225,92,92,0.1); }
        .btn-sm.copy { border-color: #5b8def; color: #5b8def; }
        
        .empty-state { text-align: center; padding: 2rem; color: var(--text-secondary); }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            table { font-size: 0.7rem; }
            th, td { padding: 8px; }
            .hide-mobile { display: none; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>📊 Portfolio Analytics Dashboard</h1>
            <a href="?logout=1">Logout</a>
        </div>
    </header>
    
    <div class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= $todayVisitors ?></div>
                <div class="label">Visitors Today</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $totalVisitors ?></div>
                <div class="label">Total Page Views</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $uniqueIPs ?></div>
                <div class="label">Unique Visitors</div>
            </div>
            <div class="stat-card pending">
                <div class="number"><?= $pendingRequests ?></div>
                <div class="label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $totalRequests ?></div>
                <div class="label">Total Requests</div>
            </div>
        </div>
        
        <!-- Access Requests -->
        <div class="section">
            <h3>🔐 Project Access Requests</h3>
            <?php 
            $hasRequests = false;
            while ($row = $requests->fetchArray(SQLITE3_ASSOC)): 
                $hasRequests = true;
            ?>
                <div class="request-card">
                    <div class="meta">
                        <?= date('M j, Y - g:i A', strtotime($row['requested_at'])) ?> • 
                        <span class="badge badge-<?= $row['status'] ?>"><?= strtoupper($row['status']) ?></span>
                    </div>
                    <strong><?= htmlspecialchars($row['name']) ?></strong> 
                    <span class="meta">(<?= htmlspecialchars($row['email']) ?>)</span>
                    <div class="project-name">📌 <?= htmlspecialchars($row['project']) ?></div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px;">
                        <strong>Interest:</strong> <?= ucfirst(htmlspecialchars($row['interest'])) ?>
                        <?php if ($row['message']): ?>
                            <br><strong>Message:</strong> "<?= htmlspecialchars($row['message']) ?>"
                        <?php endif; ?>
                    </div>
                    <div class="actions">
                        <?php if ($row['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" name="update_status" class="btn-sm approve">✓ Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" name="update_status" class="btn-sm reject">✗ Reject</button>
                            </form>
                        <?php endif; ?>
                        <button class="btn-sm copy" onclick="copyToClipboard('<?= htmlspecialchars($row['email']) ?>')">📧 Copy Email</button>
                        <a href="mailto:<?= htmlspecialchars($row['email']) ?>?subject=Re: <?= urlencode($row['project']) ?> Access Request" class="btn-sm" style="text-decoration:none;">✉️ Reply</a>
                    </div>
                </div>
            <?php endwhile; 
            if (!$hasRequests): ?>
                <div class="empty-state">No access requests yet.</div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Visitors -->
        <div class="section">
            <h3>👁️ Recent Visitors</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Page</th>
                            <th class="hide-mobile">IP</th>
                            <th class="hide-mobile">Referrer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $recentVisitors->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td class="mono"><?= date('M j, g:i A', strtotime($row['visited_at'])) ?></td>
                                <td><?= htmlspecialchars($row['page']) ?></td>
                                <td class="mono hide-mobile"><?= htmlspecialchars($row['ip']) ?></td>
                                <td class="hide-mobile" style="font-size:0.7rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['referrer'] ?: 'Direct') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Top Pages -->
        <div class="section">
            <h3>📄 Top Pages</h3>
            <table>
                <thead><tr><th>Page</th><th>Views</th></tr></thead>
                <tbody>
                    <?php while ($row = $topPages->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['page']) ?></td>
                            <td class="mono"><?= $row['count'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Email copied: ' + text);
            });
        }
    </script>
</body>
</html>