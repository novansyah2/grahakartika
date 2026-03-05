<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database handler
$db_file = 'warga.json';
$warga_list = [];
$edit_data = null;
$error_msg = '';

// Check if file is writable
$is_writable = is_writable(dirname(__FILE__) . '/');

// Load data dari JSON
function loadData() {
    global $db_file;
    if (file_exists($db_file)) {
        $json = file_get_contents($db_file);
        return json_decode($json, true) ?? [];
    }
    return [];
}

// Save data ke JSON
function saveData($data) {
    global $db_file, $is_writable;
    if (!$is_writable) {
        throw new Exception('Folder tidak memiliki permission write. Hubungi hosting provider.');
    }
    $result = file_put_contents($db_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($result === false) {
        throw new Exception('Gagal menyimpan data. Cek permission folder.');
    }
}

// Get next ID
function getNextId($data) {
    if (empty($data)) return 1;
    return max(array_column($data, 'id')) + 1;
}

// Load existing data
$warga_list = loadData();

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $warga_list = array_filter($warga_list, function($item) use ($id) {
        return $item['id'] != $id;
    });
    try {
        saveData($warga_list);
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
    if (!$error_msg) {
        header("Location: index.php");
        exit;
    }
}

// Handle add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $pekerjaan = trim($_POST['pekerjaan'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    
    if (!$nama || !$no_telepon || !$alamat) {
        $error_msg = 'Nama, Nomor Telepon, dan Alamat harus diisi!';
    } else {
        $id = isset($_POST['id']) && $_POST['id'] ? intval($_POST['id']) : null;
        
        try {
            if ($id) {
                // Update
                foreach ($warga_list as &$w) {
                    if ($w['id'] == $id) {
                        $w['nama'] = $nama;
                        $w['no_telepon'] = $no_telepon;
                        $w['alamat'] = $alamat;
                        $w['pekerjaan'] = $pekerjaan;
                        $w['jenis_kelamin'] = $jenis_kelamin;
                        break;
                    }
                }
            } else {
                // Insert
                $warga_list[] = [
                    'id' => getNextId($warga_list),
                    'nama' => $nama,
                    'no_telepon' => $no_telepon,
                    'alamat' => $alamat,
                    'pekerjaan' => $pekerjaan,
                    'jenis_kelamin' => $jenis_kelamin,
                    'tanggal_dibuat' => date('Y-m-d H:i:s')
                ];
            }
            
            saveData($warga_list);
            
            // Reload data
            $warga_list = loadData();
            
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }
    }
}

// Get data untuk edit
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    foreach ($warga_list as $w) {
        if ($w['id'] == $id) {
            $edit_data = $w;
            break;
        }
    }
}

// Sort by tanggal_dibuat DESC
usort($warga_list, function($a, $b) {
    return strtotime($b['tanggal_dibuat'] ?? '2000-01-01') - strtotime($a['tanggal_dibuat'] ?? '2000-01-01');
});

// Determine active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'data';
if ($edit_data) {
    $active_tab = 'form';
}
?>
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Warga</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            padding-bottom: 20px;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #3D5FD3 0%, #2E4CB8 100%);
            padding: 24px 20px;
            color: white;
            border-radius: 0 0 20px 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(61, 95, 211, 0.15);
        }
        
        .header h1 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        
        .header p {
            font-size: 13px;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .content {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 0 16px;
        }
        
        /* Stats Card */
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            text-align: center;
        }
        
        .stats-card .number {
            font-size: 32px;
            font-weight: 700;
            color: #3D5FD3;
            margin-bottom: 4px;
        }
        
        .stats-card .label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }
        
        .form-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .form-card h2 {
            color: #1a1a1a;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group:last-of-type {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3D5FD3;
            box-shadow: 0 0 0 3px rgba(61, 95, 211, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
        }
        
        button {
            flex: 1;
            padding: 13px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            letter-spacing: 0.3px;
            transition: all 0.2s ease;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #3D5FD3 0%, #2E4CB8 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(61, 95, 211, 0.2);
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(61, 95, 211, 0.3);
        }
        
        .btn-reset {
            background: #f0f2f7;
            color: #333;
        }
        
        .btn-reset:hover {
            background: #e8ecf5;
        }
        
        /* Data Card */
        .data-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .data-card h2 {
            color: #1a1a1a;
            margin-bottom: 16px;
            font-size: 16px;
            font-weight: 700;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        @media (min-width: 600px) {
            .data-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .warga-card {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #f0f0f0;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
        }
        
        .warga-card:hover {
            background: #fff;
            border-color: #e0e0e0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        
        .warga-card-header {
            margin-bottom: 10px;
        }
        
        .warga-card-name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 15px;
            margin-bottom: 2px;
        }
        
        .warga-card-phone {
            font-size: 12px;
            color: #999;
        }
        
        .warga-card-body {
            flex: 1;
            margin-bottom: 12px;
        }
        
        .warga-card-info {
            font-size: 12px;
            color: #666;
            margin: 6px 0;
            line-height: 1.4;
        }
        
        .warga-card-info strong {
            color: #333;
            font-weight: 600;
            display: block;
            font-size: 11px;
            margin-top: 6px;
        }
        
        .warga-card-footer {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px;
        }
        
        .warga-card .btn-action {
            padding: 8px 10px;
            font-size: 11px;
        }
        
        .warga-item {
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 12px;
            background: #f8f9fa;
            border: 1px solid #f0f0f0;
            transition: all 0.2s ease;
        }
        
        .warga-item:hover {
            background: #fff;
            border-color: #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .warga-item.last {
            margin-bottom: 0;
        }
        
        .warga-item {
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 12px;
            background: #f8f9fa;
            border: 1px solid #f0f0f0;
            transition: all 0.2s ease;
        }
        
        .warga-item:hover {
            background: #fff;
            border-color: #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .warga-item.last {
            margin-bottom: 0;
        }
        
        .btn-action {
            padding: 9px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.2s ease;
            letter-spacing: 0.3px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-whatsapp {
            background: #25D366;
            color: white;
        }
        
        .btn-whatsapp:hover {
            background: #20BA5A;
        }
        
        .btn-edit {
            background: #3D5FD3;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2E4CB8;
        }
        
        .btn-delete {
            background: #FF6B6B;
            color: white;
        }
        
        .btn-delete:hover {
            background: #FF5252;
        }
        
        .empty-data {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 14px;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 10px;
            margin: 0 16px 16px 16px;
            border: 1px solid #ef5350;
            font-size: 13px;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Modal-like edit state */
        .form-card h2::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #3D5FD3;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .divider {
            height: 1px;
            background: #f0f0f0;
            margin: 16px 0;
        }
        
        /* Tab Navigation */
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab-buttons {
            display: flex;
            gap: 8px;
            padding: 0 16px;
            border-bottom: 2px solid #f0f0f0;
            background: white;
            margin: 0 -16px;
            padding: 0 16px;
        }
        
        .tab-btn {
            padding: 14px 20px;
            border: none;
            background: transparent;
            color: #999;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            letter-spacing: 0.3px;
            transition: all 0.2s ease;
            position: relative;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab-btn.active {
            color: #3D5FD3;
            border-bottom-color: #3D5FD3;
        }
        
        .tab-btn:hover {
            color: #333;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Tab Content Styling */
        .tab-content-wrapper {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .form-card {
            padding: 20px;
        }
        
        .data-card {
            padding: 20px;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        @media (min-width: 600px) {
            .data-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .warga-card {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #f0f0f0;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
        }
        
        .warga-card:hover {
            background: #fff;
            border-color: #e0e0e0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        
        .warga-card-header {
            margin-bottom: 10px;
        }
        
        .warga-card-name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 15px;
            margin-bottom: 2px;
        }
        
        .warga-card-phone {
            font-size: 12px;
            color: #999;
        }
        
        .warga-card-body {
            flex: 1;
            margin-bottom: 12px;
        }
        
        .warga-card-info {
            font-size: 12px;
            color: #666;
            margin: 6px 0;
            line-height: 1.4;
        }
        
        .warga-card-info strong {
            color: #333;
            font-weight: 600;
            display: block;
            font-size: 11px;
            margin-top: 6px;
        }
        
        .warga-card-footer {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px;
        }
        
        .warga-card .btn-action {
            padding: 8px 10px;
            font-size: 11px;
        }
        
        @media (max-width: 480px) {
            .tab-buttons {
                padding: 0 12px;
                margin: 0 -12px;
            }
            
            .tab-btn {
                padding: 12px 16px;
                font-size: 13px;
            }
            
            .data-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                max-width: 100%;
            }
            
            body {
                padding-bottom: 10px;
            }
            
            .header {
                border-radius: 0;
                margin-bottom: 16px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 0 12px;
                gap: 12px;
            }
            
            .form-card, .data-card {
                border-radius: 12px;
                padding: 16px;
            }
            
            button {
                padding: 12px 14px;
                font-size: 13px;
            }
            
            .warga-actions {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Data Warga</h1>
            <p>Kelola data penduduk dengan mudah</p>
        </div>
        
        <?php if ($error_msg): ?>
            <div class="error-message">
                <strong>⚠️</strong> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>
        
        <div class="content">
            <!-- Stats -->
            <div class="stats-card">
                <div class="number"><?php echo count($warga_list); ?></div>
                <div class="label">Total Warga Terdaftar</div>
            </div>
            
            <!-- Tab Navigation -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-btn <?php echo $active_tab === 'form' ? 'active' : ''; ?>" onclick="switchTab('form')">➕ Input Data</button>
                    <button class="tab-btn <?php echo $active_tab === 'data' ? 'active' : ''; ?>" onclick="switchTab('data')">📋 Daftar Warga</button>
                </div>
            </div>
            
            <!-- Form Tab -->
            <div id="form-tab" class="tab-content <?php echo $active_tab === 'form' ? 'active' : ''; ?>">
                <div class="tab-content-wrapper">
                    <div class="form-card">
                        <h2><?php echo $edit_data ? 'Edit Data' : 'Tambah Data'; ?></h2>
                        <form method="POST">
                            <?php if ($edit_data): ?>
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama" required value="<?php echo $edit_data['nama'] ?? ''; ?>" placeholder="Nama">
                            </div>
                            
                            <div class="form-group">
                                <label>Nomor WhatsApp</label>
                                <input type="tel" name="no_telepon" required value="<?php echo $edit_data['no_telepon'] ?? ''; ?>" placeholder="62812345678">
                            </div>
                            
                            <div class="form-group">
                                <label>Alamat</label>
                                <textarea name="alamat" required placeholder="Alamat lengkap"><?php echo $edit_data['alamat'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <select name="jenis_kelamin">
                                    <option value="">Pilih</option>
                                    <option value="Laki-laki" <?php echo ($edit_data['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="Perempuan" <?php echo ($edit_data['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Pekerjaan</label>
                                <input type="text" name="pekerjaan" value="<?php echo $edit_data['pekerjaan'] ?? ''; ?>" placeholder="Pekerjaan">
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn-submit"><?php echo $edit_data ? 'Update' : 'Simpan'; ?></button>
                                <?php if ($edit_data): ?>
                                    <a href="index.php" style="flex: 1; text-decoration: none;">
                                        <button type="button" class="btn-reset" style="width: 100%;">Batal</button>
                                    </a>
                                <?php else: ?>
                                    <button type="reset" class="btn-reset">Reset</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Data Tab -->
            <div id="data-tab" class="tab-content <?php echo $active_tab === 'data' ? 'active' : ''; ?>">
                <div class="tab-content-wrapper">
                    <div class="data-card">
                        <h2>Daftar Warga</h2>
                        
                        <?php if (empty($warga_list)): ?>
                            <div class="empty-data">
                                <p>Belum ada data warga</p>
                            </div>
                        <?php else: ?>
                            <div class="data-grid">
                                <?php foreach ($warga_list as $w): ?>
                                    <div class="warga-card">
                                        <div class="warga-card-header">
                                            <div class="warga-card-name"><?php echo htmlspecialchars($w['nama']); ?></div>
                                            <div class="warga-card-phone"><?php echo htmlspecialchars($w['no_telepon']); ?></div>
                                        </div>
                                        
                                        <div class="warga-card-body">
                                            <div class="warga-card-info">
                                                <strong>Alamat</strong>
                                                <?php echo htmlspecialchars($w['alamat']); ?>
                                            </div>
                                            
                                            <?php if ($w['jenis_kelamin']): ?>
                                                <div class="warga-card-info">
                                                    <strong>Kelamin</strong>
                                                    <?php echo htmlspecialchars($w['jenis_kelamin']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($w['pekerjaan']): ?>
                                                <div class="warga-card-info">
                                                    <strong>Pekerjaan</strong>
                                                    <?php echo htmlspecialchars($w['pekerjaan']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="warga-card-footer">
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $w['no_telepon']); ?>" target="_blank" class="btn-action btn-whatsapp">Chat</a>
                                            <a href="?edit=<?php echo $w['id']; ?>&tab=form" class="btn-action btn-edit">Edit</a>
                                            <a href="?delete=<?php echo $w['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Hapus?');">Hapus</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.getElementById('form-tab').classList.remove('active');
            document.getElementById('data-tab').classList.remove('active');
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Update URL
            window.history.replaceState(null, null, '?tab=' + tabName);
        }
    </script>
</body>
</html>
