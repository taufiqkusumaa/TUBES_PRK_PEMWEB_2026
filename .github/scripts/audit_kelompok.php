<?php
/**
 * Script Audit & Rekapitulasi Kontribusi Git per Kelompok
 * Tubes Praktikum Pemrograman Web 2026 - LabKom TI Unila
 * 
 * Penggunaan:
 *   php scripts/audit_kelompok.php --kelompok=22
 *   php scripts/audit_kelompok.php --all
 *   php scripts/audit_kelompok.php --all --export=rekap_kontribusi.md
 *   php scripts/audit_kelompok.php --all --export=rekap_kontribusi.csv
 */

// Pastikan dijalankan dari CLI
if (php_sapi_name() !== 'cli') {
    die("Script ini hanya dapat dijalankan melalui CLI/terminal.\n");
}

$options = getopt('', ['kelompok:', 'all', 'export:', 'limit::', 'help']);

if (isset($options['help']) || (empty($options['kelompok']) && !isset($options['all']))) {
    echo "===============================================================\n";
    echo "📋 AUDIT KONTRIBUSI GIT PER KELOMPOK - PEMWEB 2026\n";
    echo "===============================================================\n";
    echo "Penggunaan:\n";
    echo "  php scripts/audit_kelompok.php --kelompok=XX\n";
    echo "      Contoh: php scripts/audit_kelompok.php --kelompok=22\n\n";
    echo "  php scripts/audit_kelompok.php --all\n";
    echo "      Audit semua kelompok yang ada di folder kelompok/\n\n";
    echo "  php scripts/audit_kelompok.php --all --export=laporan.md\n";
    echo "      Simpan hasil audit ke file Markdown atau CSV\n\n";
    echo "  --limit=N\n";
    echo "      Jumlah commit terakhir yang ditampilkan per kelompok (default: 10)\n";
    echo "===============================================================\n";
    exit(0);
}

$commitLimit = isset($options['limit']) ? (int)$options['limit'] : 10;
$exportFile = $options['export'] ?? null;

// Deteksi folder repo root
$repoRoot = realpath(__DIR__ . '/../..');
chdir($repoRoot);

// Cek apakah git terpasang dan merupakan repositori git
exec('git rev-parse --is-inside-work-tree 2>&1', $gitCheck, $gitReturn);
if ($gitReturn !== 0) {
    die("❌ Error: Direktori saat ini bukan repositori Git yang valid.\n");
}

function getAvailableKelompok(): array {
    $dirs = glob('kelompok/kelompok_*', GLOB_ONLYDIR);
    $kelompokList = [];
    foreach ($dirs as $dir) {
        $basename = basename($dir);
        if (preg_match('/^kelompok_(\d+)$/', $basename, $matches)) {
            $kelompokList[] = [
                'folder' => $basename,
                'path' => 'kelompok/' . $basename,
                'id' => $matches[1]
            ];
        }
    }
    usort($kelompokList, fn($a, $b) => (int)$a['id'] <=> (int)$b['id']);
    return $kelompokList;
}

function auditKelompok(string $folderPath, int $commitLimit): ?array {
    if (!is_dir($folderPath)) {
        return null;
    }

    $folder = rtrim($folderPath, '/\\');
    
    // 1. Ambil data commit numstat untuk menghitung baris tambah/kurang per author
    $cmdNumstat = "git log --no-merges --numstat --pretty=format:\"COMMIT_META:%H|%an|%ae|%ad|%s\" --date=short -- \"$folder/\"";
    exec($cmdNumstat, $numstatOutput, $retNumstat);

    if ($retNumstat !== 0 || empty($numstatOutput)) {
        return [
            'folder' => basename($folder),
            'total_commits' => 0,
            'authors' => [],
            'recent_commits' => []
        ];
    }

    $authors = [];
    $commits = [];
    $currentAuthor = null;
    $currentEmail = null;

    foreach ($numstatOutput as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        if (str_starts_with($line, 'COMMIT_META:')) {
            $meta = substr($line, strlen('COMMIT_META:'));
            $parts = explode('|', $meta, 5);
            $hash = $parts[0] ?? '';
            $name = $parts[1] ?? 'Unknown';
            $email = $parts[2] ?? '';
            $date = $parts[3] ?? '';
            $msg = $parts[4] ?? '';

            $currentAuthor = $name;
            $currentEmail = $email;
            $authorKey = strtolower(trim($email ?: $name));

            if (!isset($authors[$authorKey])) {
                $authors[$authorKey] = [
                    'name' => $name,
                    'email' => $email,
                    'commits' => 0,
                    'added' => 0,
                    'deleted' => 0,
                    'files' => []
                ];
            }
            $authors[$authorKey]['commits']++;

            if (!isset($commits[$hash])) {
                $commits[$hash] = [
                    'hash' => substr($hash, 0, 7),
                    'full_hash' => $hash,
                    'author' => $name,
                    'email' => $email,
                    'date' => $date,
                    'message' => $msg
                ];
            }
        } elseif ($currentAuthor !== null && preg_match('/^(\d+|-)\s+(\d+|-)\s+(.+)$/', $line, $m)) {
            $authorKey = strtolower(trim($currentEmail ?: $currentAuthor));
            $add = ($m[1] === '-') ? 0 : (int)$m[1];
            $del = ($m[2] === '-') ? 0 : (int)$m[2];
            $file = $m[3];

            $authors[$authorKey]['added'] += $add;
            $authors[$authorKey]['deleted'] += $del;
            if (!in_array($file, $authors[$authorKey]['files'])) {
                $authors[$authorKey]['files'][] = $file;
            }
        }
    }

    $totalCommits = count($commits);

    // Hitung persentase kontribusi per anggota
    foreach ($authors as $key => &$data) {
        $data['percentage'] = $totalCommits > 0 ? round(($data['commits'] / $totalCommits) * 100, 1) : 0;
        $data['file_count'] = count($data['files']);
    }
    unset($data);

    // Urutkan author berdasarkan jumlah commit terbanyak
    uasort($authors, fn($a, $b) => $b['commits'] <=> $a['commits']);

    // Ambil commit terakhir
    $recentCommits = array_slice(array_values($commits), 0, $commitLimit);

    return [
        'folder' => basename($folder),
        'total_commits' => $totalCommits,
        'authors' => array_values($authors),
        'recent_commits' => $recentCommits
    ];
}

// Tentukan kelompok yang akan diaudit
$targets = [];
if (isset($options['all'])) {
    $targets = getAvailableKelompok();
} else {
    $input = trim($options['kelompok']);
    $folderName = str_starts_with($input, 'kelompok_') ? $input : 'kelompok_' . str_pad($input, 2, '0', STR_PAD_LEFT);
    $path = 'kelompok/' . $folderName;
    if (!is_dir($path)) {
        die("❌ Error: Folder '$path' tidak ditemukan.\n");
    }
    $targets[] = [
        'folder' => $folderName,
        'path' => $path,
        'id' => preg_replace('/^kelompok_/', '', $folderName)
    ];
}

$results = [];
echo "\n🔍 Menjalankan audit riwayat Git per folder kelompok...\n";

foreach ($targets as $target) {
    $audit = auditKelompok($target['path'], $commitLimit);
    if ($audit) {
        $results[] = $audit;
    }
}

// Render output ke CLI
foreach ($results as $res) {
    echo "\n" . str_repeat('=', 70) . "\n";
    echo "📁 KELOMPOK: " . strtoupper($res['folder']) . " (Total Non-Merge Commits: " . $res['total_commits'] . ")\n";
    echo str_repeat('=', 70) . "\n";

    if ($res['total_commits'] === 0) {
        echo "⚠️ Belum ada commit yang tercatat di folder ini.\n";
        continue;
    }

    printf("%-25s | %-7s | %-6s | %-10s | %-10s\n", "Nama Kontributor", "Commits", "%", "Baris (+)", "Baris (-)");
    echo str_repeat('-', 70) . "\n";
    foreach ($res['authors'] as $a) {
        $displayName = mb_strimwidth($a['name'], 0, 24, '..');
        printf("%-25s | %-7d | %5.1f%% | %-10s | %-10s\n", 
            $displayName, 
            $a['commits'], 
            $a['percentage'], 
            "+" . number_format($a['added']), 
            "-" . number_format($a['deleted'])
        );
    }
    echo str_repeat('-', 70) . "\n";

    echo "🕒 Commit Terakhir (" . count($res['recent_commits']) . " terbaru):\n";
    foreach ($res['recent_commits'] as $c) {
        $shortMsg = mb_strimwidth($c['message'], 0, 45, '...');
        echo "  • [" . $c['hash'] . "] " . $c['date'] . " - " . $c['author'] . ": " . $shortMsg . "\n";
    }
}

// Export jika parameter --export diisi
if ($exportFile) {
    $ext = strtolower(pathinfo($exportFile, PATHINFO_EXTENSION));
    if ($ext === 'csv') {
        $fp = fopen($exportFile, 'w');
        fputcsv($fp, ['Kelompok', 'Nama Kontributor', 'Email', 'Jumlah Commit', 'Persentase Commit', 'Baris Ditambah (+)', 'Baris Dihapus (-)']);
        foreach ($results as $res) {
            foreach ($res['authors'] as $a) {
                fputcsv($fp, [
                    $res['folder'],
                    $a['name'],
                    $a['email'],
                    $a['commits'],
                    $a['percentage'] . '%',
                    $a['added'],
                    $a['deleted']
                ]);
            }
        }
        fclose($fp);
        echo "\n✅ Hasil audit berhasil diekspor ke format CSV: $exportFile\n";
    } else {
        // Default Markdown
        $md = "# 📊 Rekapitulasi Audit Kontribusi Tugas Besar Pemweb 2026\n\n";
        $md .= "Tanggal Generate: " . date('Y-m-d H:i:s') . "\n\n";
        $md .= "---\n\n";

        foreach ($results as $res) {
            $md .= "## 📁 " . strtoupper($res['folder']) . "\n";
            $md .= "- **Total Non-Merge Commits:** " . $res['total_commits'] . "\n\n";

            if ($res['total_commits'] === 0) {
                $md .= "_Belum ada commit yang tercatat di folder ini._\n\n";
                continue;
            }

            $md .= "| Nama Kontributor | Email | Jumlah Commit | % Kontribusi | Baris (+) | Baris (-) |\n";
            $md .= "| :--- | :--- | :---: | :---: | :---: | :---: |\n";
            foreach ($res['authors'] as $a) {
                $md .= sprintf(
                    "| %s | %s | %d | %.1f%% | +%s | -%s |\n",
                    $a['name'],
                    $a['email'] ? '`' . $a['email'] . '`' : '-',
                    $a['commits'],
                    $a['percentage'],
                    number_format($a['added']),
                    number_format($a['deleted'])
                );
            }
            $md .= "\n<details>\n<summary>🔍 <b>Klik untuk melihat rincian commit terakhir</b></summary>\n\n";
            foreach ($res['recent_commits'] as $c) {
                $md .= "- `" . $c['hash'] . "` (" . $c['date'] . ") **" . htmlspecialchars($c['author']) . "**: " . htmlspecialchars($c['message']) . "\n";
            }
            $md .= "</details>\n\n---\n\n";
        }

        file_put_contents($exportFile, $md);
        echo "\n✅ Hasil audit berhasil diekspor ke file Markdown: $exportFile\n";
    }
}

echo "\n✨ Audit selesai.\n";

