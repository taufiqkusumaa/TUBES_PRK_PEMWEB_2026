<?php
// Set zona waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

// Penanganan Formulir Pesan / Pengajuan Peminjaman Lab
$pesan_status = null;
$pesan_tipe = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'kirim_pesan') {
        $nama    = trim(htmlspecialchars($_POST['nama'] ?? ''));
        $nim     = trim(htmlspecialchars($_POST['nim'] ?? ''));
        $email   = trim(htmlspecialchars($_POST['email'] ?? ''));
        $kategori = trim(htmlspecialchars($_POST['kategori'] ?? ''));
        $pesan   = trim(htmlspecialchars($_POST['pesan'] ?? ''));

        if (!empty($nama) && !empty($email) && !empty($pesan)) {
            $pesan_status = "Terima kasih, <strong>{$nama}</strong> (" . (!empty($nim) ? "NIM: {$nim}" : "Pengunjung") . "). Pengajuan/Pesan Anda untuk kategori <strong>{$kategori}</strong> berhasil dikirimkan ke Asisten Laboratorium!";
            $pesan_tipe = "success";
        } else {
            $pesan_status = "Mohon lengkapi nama, email, dan pesan Anda sebelum mengirimkan formulir.";
            $pesan_tipe = "error";
        }
    }
}

// Data Dinamis Laboratorium Teknik Komputer
$info_lab = [
    'nama' => 'Laboratorium Teknik Komputer',
    'singkatan' => 'Lab Tekkom',
    'kelompok' => 'Kelompok 50',
    'tahun' => date('Y'),
    'gedung' => 'Gedung Laboratorium Terpadu Lt. 3, Ruang 304',
    'jam_buka' => 'Senin - Jumat: 08.00 - 17.00 WIB',
    'status_lab' => 'Aktif / Buka untuk Praktikum & Riset'
];

$statistik_lab = [
    ['nilai' => '4', 'label' => 'Divisi Riset', 'icon' => 'code'],
    ['nilai' => '40+', 'label' => 'Unit Workstation & Kit', 'icon' => 'cpu'],
    ['nilai' => '250+', 'label' => 'Praktikan Semester Ini', 'icon' => 'users'],
    ['nilai' => '99.8%', 'label' => 'Uptime Server Lab', 'icon' => 'activity']
];

$divisi_riset = [
    [
        'id' => 'embedded',
        'kategori' => 'hardware',
        'judul' => 'Sistem Tertanam & IoT',
        'deskripsi' => 'Pengembangan micro-controller berbasis ESP32, STM32, ARM Cortex, sensor cerdas, serta integrasi jaringan nirkabel LoRaWAN dan MQTT.',
        'badge' => 'Hardware & Sensor',
        'alat' => ['ESP32-S3', 'STM32 Nucleo', 'Logic Analyzer', 'LoRa Transceiver']
    ],
    [
        'id' => 'network',
        'kategori' => 'jaringan',
        'judul' => 'Jaringan Komputer & Keamanan Siber',
        'deskripsi' => 'Konfigurasi routing protokol lanjut, VLAN, virtualisasi jaringan, pengujian penetrasi etis (penetration testing), dan packet inspection.',
        'badge' => 'Networking & Sec',
        'alat' => ['Cisco 2960 Switch', 'MikroTik Cloud Core', 'Wireshark Server', 'GNS3 Appliance']
    ],
    [
        'id' => 'robotics',
        'kategori' => 'robotika',
        'judul' => 'Robotika & Sistem Kendali Otonom',
        'deskripsi' => 'Implementasi Robot Operating System (ROS), computer vision untuk deteksi objek secara real-time, motor servo digital, dan navigasi robotik.',
        'badge' => 'Automation & ROS',
        'alat' => ['NVIDIA Jetson Nano', 'LIDAR Scanner', 'Depth Camera', 'DC Motor Driver']
    ],
    [
        'id' => 'vlsi',
        'kategori' => 'hardware',
        'judul' => 'Arsitektur Komputer & Desain VLSI/FPGA',
        'deskripsi' => 'Sintesis rangkaian logika digital, perancangan soft-core prosesor RISC-V, pemodelan hardware menggunakan VHDL/Verilog pada papan FPGA.',
        'badge' => 'VLSI & FPGA',
        'alat' => ['Xilinx Spartan-7', 'Altera Cyclone IV', 'Quartus Prime', 'Oscilloscope 100MHz']
    ]
];

$fasilitas_unggulan = [
    [
        'nama' => 'Stasiun Kerja Komputasi Kinerja Tinggi',
        'spesifikasi' => 'Intel Core i7 Gen-14, 32GB RAM DDR5, NVIDIA RTX 4070 untuk simulasi FPGA dan Machine Learning.',
        'tag' => 'Workstation'
    ],
    [
        'nama' => 'Rak Router & Switch Cisco Enterprise',
        'spesifikasi' => 'Dilengkapi perangkat Cisco Catalyst Series dan MikroTik RouterBoard untuk simulasi topologi jaringan nyata.',
        'tag' => 'Jaringan'
    ],
    [
        'nama' => 'Laboratorium Soldering & SMT Rework Digital',
        'spesifikasi' => 'Stasiun solder temperatur terkontrol presisi, hot air gun, mikroskop digital PCB, dan multimeter True-RMS.',
        'tag' => 'Prototyping'
    ],
    [
        'nama' => 'Digital Storage Oscilloscope & Logic Analyzer',
        'spesifikasi' => 'Bandwidth hingga 100MHz 4-channel untuk analisis sinyal digital bus I2C, SPI, UART, dan CAN Bus.',
        'tag' => 'Instrumentasi'
    ]
];

$jadwal_praktikum = [
    [
        'hari' => 'Senin',
        'jam' => '08.00 - 10.30 WIB',
        'mata_kuliah' => 'Praktikum Sistem Tertanam & Mikroprosesor',
        'kelas' => 'Teknik Komputer A',
        'asisten' => 'Tim Asisten Lab 50',
        'status' => 'Selesai'
    ],
    [
        'hari' => 'Rabu',
        'jam' => '13.00 - 15.30 WIB',
        'mata_kuliah' => 'Praktikum Jaringan Komputer Lanjut',
        'kelas' => 'Teknik Komputer B',
        'asisten' => 'Tim Asisten Lab 50',
        'status' => 'Berlangsung'
    ],
    [
        'hari' => 'Kamis',
        'jam' => '09.30 - 12.00 WIB',
        'mata_kuliah' => 'Praktikum Perancangan Sistem VLSI / FPGA',
        'kelas' => 'Teknik Komputer C',
        'asisten' => 'Tim Asisten Lab 50',
        'status' => 'Terjadwal'
    ],
    [
        'hari' => 'Jumat',
        'jam' => '14.00 - 16.30 WIB',
        'mata_kuliah' => 'Open Lab & Bimbingan Proyek Akhir',
        'kelas' => 'Semua Praktikan',
        'asisten' => 'Seluruh Asisten',
        'status' => 'Terjadwal'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorium Teknik Komputer - Kelompok 50</title>
    <meta name="description" content="Portal resmi Laboratorium Teknik Komputer - Pusat praktikum, riset sistem tertanam, jaringan komputer, robotika, dan arsitektur komputer modern.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #0a0f1d;
            --bg-card: rgba(17, 24, 39, 0.75);
            --bg-card-hover: rgba(30, 41, 59, 0.85);
            --primary: #06b6d4;
            --primary-glow: rgba(6, 182, 212, 0.35);
            --secondary: #3b82f6;
            --accent: #10b981;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-accent: rgba(6, 182, 212, 0.4);
            --radius-lg: 16px;
            --radius-md: 10px;
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-main);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(6, 182, 212, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 65%, rgba(59, 130, 246, 0.12) 0%, transparent 40%),
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 48px 48px, 48px 48px;
        }

        /* Container & Utilities */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(6, 182, 212, 0.1);
            color: var(--primary);
            border: 1px solid var(--border-accent);
            backdrop-filter: blur(8px);
        }

        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .section-header h2 {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-top: 12px;
            background: linear-gradient(135deg, #ffffff 40%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-header p {
            color: var(--text-muted);
            max-width: 620px;
            margin: 12px auto 0;
            font-size: 1.05rem;
        }

        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(10, 15, 29, 0.82);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-glass);
            transition: all 0.3s ease;
        }

        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 76px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #06b6d4, #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px var(--primary-glow);
            color: #fff;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #fff;
        }

        .brand-sub {
            font-size: 0.75rem;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-cta-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff !important;
            padding: 9px 18px;
            border-radius: var(--radius-md);
            font-weight: 600;
            box-shadow: 0 4px 14px var(--primary-glow);
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
        }

        .nav-cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.5);
        }

        /* Hero Section */
        .hero-section {
            padding: 80px 0 60px;
            position: relative;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 48px;
            align-items: center;
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin: 20px 0;
        }

        .hero-title span {
            background: linear-gradient(135deg, #22d3ee, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            font-size: 1.12rem;
            color: var(--text-muted);
            margin-bottom: 32px;
            line-height: 1.7;
        }

        .hero-action-group {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 13px 28px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: var(--radius-md);
            box-shadow: 0 8px 24px var(--primary-glow);
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(6, 182, 212, 0.55);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 13px 26px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-glass);
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        /* Hero Terminal / Telemetry Card */
        .terminal-card {
            background: rgba(13, 19, 33, 0.9);
            border: 1px solid var(--border-accent);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 30px rgba(6, 182, 212, 0.15);
            font-family: var(--font-mono);
        }

        .terminal-header {
            background: rgba(255, 255, 255, 0.04);
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-glass);
        }

        .terminal-dots {
            display: flex;
            gap: 6px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot.red { background: #ef4444; }
        .dot.yellow { background: #f59e0b; }
        .dot.green { background: #10b981; }

        .terminal-title {
            font-size: 0.8rem;
            color: var(--text-muted);
            letter-spacing: 0.02em;
        }

        .terminal-body {
            padding: 20px;
            font-size: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .telemetry-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.06);
        }

        .telemetry-label {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .telemetry-value {
            color: #38bdf8;
            font-weight: 600;
        }

        .telemetry-status-ok {
            color: #34d399;
            font-weight: 700;
        }

        .pulse-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Statistics Bar */
        .stats-section {
            padding: 30px 0;
            margin-top: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            padding: 24px;
            text-align: center;
            backdrop-filter: blur(12px);
            transition: transform 0.25s ease, border-color 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--border-accent);
        }

        .stat-val {
            font-size: 2.3rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #ffffff, #38bdf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-lbl {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 500;
        }

        /* Research Areas Section */
        .section-block {
            padding: 70px 0;
        }

        .filter-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            color: var(--text-muted);
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background: rgba(6, 182, 212, 0.15);
            border-color: var(--primary);
            color: #fff;
        }

        .research-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .research-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            padding: 32px;
            backdrop-filter: blur(12px);
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .research-card:hover {
            transform: translateY(-5px);
            background: var(--bg-card-hover);
            border-color: var(--border-accent);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.4);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .card-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            background: rgba(6, 182, 212, 0.12);
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .research-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
        }

        .research-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .tags-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag-item {
            font-size: 0.78rem;
            font-family: var(--font-mono);
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Facilities Section */
        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .facility-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            padding: 28px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
            transition: all 0.25s ease;
        }

        .facility-card:hover {
            border-color: rgba(59, 130, 246, 0.4);
            transform: translateX(4px);
        }

        .facility-icon-wrap {
            min-width: 48px;
            height: 48px;
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .facility-info h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
        }

        .facility-info p {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.55;
        }

        /* Schedule Table */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-glass);
            background: var(--bg-card);
            backdrop-filter: blur(12px);
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.93rem;
        }

        .schedule-table th {
            background: rgba(255, 255, 255, 0.04);
            padding: 16px 20px;
            color: #cbd5e1;
            font-weight: 600;
            border-bottom: 1px solid var(--border-glass);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.04em;
        }

        .schedule-table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--text-main);
        }

        .schedule-table tr:last-child td {
            border-bottom: none;
        }

        .schedule-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-berlangsung {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-terjadwal {
            background: rgba(6, 182, 212, 0.15);
            color: #22d3ee;
            border: 1px solid rgba(6, 182, 212, 0.3);
        }

        .status-selesai {
            background: rgba(148, 163, 184, 0.12);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        /* Contact & Form Section */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: stretch;
        }

        .contact-info-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            padding: 36px;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .contact-item {
            display: flex;
            gap: 16px;
            margin-top: 24px;
        }

        .contact-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(6, 182, 212, 0.12);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .contact-detail h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2px;
        }

        .contact-detail p {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            padding: 36px;
            backdrop-filter: blur(12px);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 8px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            color: #fff;
            font-size: 0.92rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Alert notifications */
        .alert-box {
            padding: 14px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #f87171;
        }

        /* Footer */
        .footer {
            border-top: 1px solid var(--border-glass);
            background: rgba(8, 12, 22, 0.95);
            padding: 48px 0 32px;
            margin-top: 80px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
            }
            .hero-title {
                font-size: 2.5rem;
            }
            .research-grid, .facilities-grid, .contact-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .hero-title {
                font-size: 2.1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Header & Navigation -->
    <header class="navbar" id="navbar">
        <div class="container navbar-content">
            <a href="#" class="brand-logo" id="brandLogo">
                <div class="brand-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="4" width="16" height="16" rx="2"/>
                        <rect x="9" y="9" width="6" height="6"/>
                        <line x1="9" y1="1" x2="9" y2="4"/>
                        <line x1="15" y1="1" x2="15" y2="4"/>
                        <line x1="9" y1="20" x2="9" y2="23"/>
                        <line x1="15" y1="20" x2="15" y2="23"/>
                        <line x1="20" y1="9" x2="23" y2="9"/>
                        <line x1="20" y1="14" x2="23" y2="14"/>
                        <line x1="1" y1="9" x2="4" y2="9"/>
                        <line x1="1" y1="14" x2="4" y2="14"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <span class="brand-title"><?php echo htmlspecialchars($info_lab['singkatan']); ?></span>
                    <span class="brand-sub"><?php echo htmlspecialchars($info_lab['kelompok']); ?></span>
                </div>
            </a>

            <nav>
                <ul class="nav-links">
                    <li><a href="#hero">Beranda</a></li>
                    <li><a href="#riset">Bidang Riset</a></li>
                    <li><a href="#fasilitas">Fasilitas</a></li>
                    <li><a href="#jadwal">Jadwal Praktikum</a></li>
                    <li><a href="#kontak" class="nav-cta-btn">Ajukan Layanan</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero-section" id="hero">
            <div class="container hero-grid">
                <div>
                    <div class="badge-pill">
                        <span class="pulse-indicator"></span>
                        <span><?php echo htmlspecialchars($info_lab['status_lab']); ?></span>
                    </div>

                    <h1 class="hero-title">
                        Pusat Eksplorasi <span>Sistem Tertanam</span>, Jaringan & Komputasi Cerdas
                    </h1>

                    <p class="hero-description">
                        Selamat datang di <strong><?php echo htmlspecialchars($info_lab['nama']); ?></strong>. 
                        Wadah pembelajaran praktikum, eksplorasi mikrokontroler, rekayasa logika digital FPGA, 
                        hingga arsitektur jaringan skala tinggi untuk menghasilkan inovasi teknologi masa depan.
                    </p>

                    <div class="hero-action-group">
                        <a href="#jadwal" class="btn-primary" id="btnJadwal">
                            <span>Lihat Jadwal Praktikum</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                        <a href="#kontak" class="btn-secondary" id="btnPeminjaman">
                            <span>Formulir Kunjungan & Alat</span>
                        </a>
                    </div>
                </div>

                <!-- Live Lab Telemetry Simulation Card -->
                <div class="terminal-card" id="telemetryCard">
                    <div class="terminal-header">
                        <div class="terminal-dots">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                        </div>
                        <span class="terminal-title">lab-tekkom-node-01: ~ /telemetry</span>
                        <span style="font-size:0.75rem; color: #10b981;">● ONLINE</span>
                    </div>
                    <div class="terminal-body">
                        <div class="telemetry-row">
                            <span class="telemetry-label">🕒 Waktu Server Lab:</span>
                            <span class="telemetry-value" id="liveClock"><?php echo date('d M Y - H:i:s'); ?> WIB</span>
                        </div>
                        <div class="telemetry-row">
                            <span class="telemetry-label">📍 Lokasi Lab:</span>
                            <span class="telemetry-value"><?php echo htmlspecialchars($info_lab['gedung']); ?></span>
                        </div>
                        <div class="telemetry-row">
                            <span class="telemetry-label">🌡️ Sensor Suhu Ruangan:</span>
                            <span class="telemetry-value">21.4 °C (Normal AC)</span>
                        </div>
                        <div class="telemetry-row">
                            <span class="telemetry-label">📶 Switch Core Cisco:</span>
                            <span class="telemetry-status-ok">ACTIVE (Gigabit 1000/FD)</span>
                        </div>
                        <div class="telemetry-row">
                            <span class="telemetry-label">⚡ FPGA Testbed Rack:</span>
                            <span class="telemetry-value">Ready (3/4 Node Free)</span>
                        </div>
                        <div class="telemetry-row">
                            <span class="telemetry-label">🔒 Status Keamanan:</span>
                            <span class="telemetry-status-ok">FIREWALL OK (Zero Threat)</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Counter Section -->
        <section class="stats-section">
            <div class="container">
                <div class="stats-grid">
                    <?php foreach ($statistik_lab as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-val"><?php echo htmlspecialchars($stat['nilai']); ?></div>
                            <div class="stat-lbl"><?php echo htmlspecialchars($stat['label']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Research Divisions / Bidang Riset -->
        <section class="section-block" id="riset">
            <div class="container">
                <div class="section-header">
                    <div class="badge-pill">Fokus Penelitian & Praktikum</div>
                    <h2>Divisi Riset Teknik Komputer</h2>
                    <p>Laboratorium kami mengintegrasikan aspek perangkat keras (hardware), jaringan komputer, dan rekayasa cerdas.</p>
                </div>

                <!-- Interactive Category Filter -->
                <div class="filter-controls">
                    <button class="filter-btn active" onclick="filterCards('all', this)" id="filterAll">Semua Divisi</button>
                    <button class="filter-btn" onclick="filterCards('hardware', this)" id="filterHardware">Hardware & VLSI</button>
                    <button class="filter-btn" onclick="filterCards('jaringan', this)" id="filterJaringan">Jaringan & Cyber</button>
                    <button class="filter-btn" onclick="filterCards('robotika', this)" id="filterRobotika">Robotika & AI</button>
                </div>

                <div class="research-grid" id="researchCardsContainer">
                    <?php foreach ($divisi_riset as $divisi): ?>
                        <div class="research-card" data-category="<?php echo htmlspecialchars($divisi['kategori']); ?>">
                            <div>
                                <div class="card-top">
                                    <span class="card-badge"><?php echo htmlspecialchars($divisi['badge']); ?></span>
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="16 18 22 12 16 6"></polyline>
                                        <polyline points="8 6 2 12 8 18"></polyline>
                                    </svg>
                                </div>
                                <h3 class="research-title"><?php echo htmlspecialchars($divisi['judul']); ?></h3>
                                <p class="research-desc"><?php echo htmlspecialchars($divisi['deskripsi']); ?></p>
                            </div>
                            <div>
                                <div class="tags-wrapper">
                                    <?php foreach ($divisi['alat'] as $item): ?>
                                        <span class="tag-item">#<?php echo htmlspecialchars($item); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Facilities Section -->
        <section class="section-block" id="fasilitas" style="background: rgba(255,255,255,0.01);">
            <div class="container">
                <div class="section-header">
                    <div class="badge-pill">Peralatan Mutakhir</div>
                    <h2>Fasilitas & Instrumen Lab</h2>
                    <p>Didukung peralatan industri untuk menunjang praktikum mahasiswa dan penelitian mutakhir.</p>
                </div>

                <div class="facilities-grid">
                    <?php foreach ($fasilitas_unggulan as $fasilitas): ?>
                        <div class="facility-card">
                            <div class="facility-icon-wrap">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                    <line x1="8" y1="21" x2="16" y2="21"></line>
                                    <line x1="12" y1="17" x2="12" y2="21"></line>
                                </svg>
                            </div>
                            <div class="facility-info">
                                <h3><?php echo htmlspecialchars($fasilitas['nama']); ?></h3>
                                <p><?php echo htmlspecialchars($fasilitas['spesifikasi']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Schedule Section -->
        <section class="section-block" id="jadwal">
            <div class="container">
                <div class="section-header">
                    <div class="badge-pill">Jadwal Praktikum Mingguan</div>
                    <h2>Kalender & Agenda Praktikum</h2>
                    <p>Pantau jam operasional kelas praktikum aktif di laboratorium semester ini.</p>
                </div>

                <div class="table-responsive">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Hari & Waktu</th>
                                <th>Mata Kuliah Praktikum</th>
                                <th>Kelas Peserta</th>
                                <th>Penanggung Jawab</th>
                                <th>Status Sesi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jadwal_praktikum as $jadwal): 
                                $statusClass = 'status-terjadwal';
                                if ($jadwal['status'] === 'Berlangsung') $statusClass = 'status-berlangsung';
                                if ($jadwal['status'] === 'Selesai') $statusClass = 'status-selesai';
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($jadwal['hari']); ?></strong><br>
                                        <small style="color: var(--text-muted); font-family: var(--font-mono);"><?php echo htmlspecialchars($jadwal['jam']); ?></small>
                                    </td>
                                    <td>
                                        <strong style="color: #fff;"><?php echo htmlspecialchars($jadwal['mata_kuliah']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($jadwal['kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($jadwal['asisten']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($jadwal['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Contact & Booking Section -->
        <section class="section-block" id="kontak">
            <div class="container">
                <div class="section-header">
                    <div class="badge-pill">Layanan Mahasiswa & Tamu</div>
                    <h2>Pengajuan Peminjaman Alat & Kunjungan</h2>
                    <p>Butuh peminjaman alat mikrokontroler, konsultasi riset, atau perizinan riset mandiri? Hubungi kami.</p>
                </div>

                <div class="contact-grid">
                    <!-- Left: Contact Details -->
                    <div class="contact-info-card">
                        <div>
                            <h3 style="font-size: 1.4rem; color: #fff; margin-bottom: 8px;">Informasi Sekretariat Lab</h3>
                            <p style="color: var(--text-muted); font-size: 0.95rem;">
                                Tim asisten laboratorium siap melayani pertanyaan seputar modul praktikum, asistensi tugas besar, serta peminjaman kit riset.
                            </p>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </div>
                                <div class="contact-detail">
                                    <h4>Lokasi Fisik</h4>
                                    <p><?php echo htmlspecialchars($info_lab['gedung']); ?></p>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <div class="contact-detail">
                                    <h4>Jam Operasional Layanan</h4>
                                    <p><?php echo htmlspecialchars($info_lab['jam_buka']); ?></p>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </div>
                                <div class="contact-detail">
                                    <h4>Email Resmi</h4>
                                    <p>labtekkom.kelompok50@universitas.ac.id</p>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 24px; padding: 16px; background: rgba(6,182,212,0.06); border-radius: var(--radius-md); border: 1px dashed rgba(6,182,212,0.3);">
                            <small style="color: var(--primary); font-weight: 600;">ℹ️ Prosedur Peminjaman:</small>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                                Pengajuan alat wajib dilakukan maksimal H-2 sebelum kegiatan dengan membawa Kartu Tanda Mahasiswa (KTM).
                            </p>
                        </div>
                    </div>

                    <!-- Right: PHP Form Submission -->
                    <div class="form-card">
                        <?php if (!empty($pesan_status)): ?>
                            <div class="alert-box <?php echo $pesan_tipe === 'success' ? 'alert-success' : 'alert-error'; ?>" id="alertBox">
                                <span><?php echo $pesan_tipe === 'success' ? '✅' : '⚠️'; ?></span>
                                <div><?php echo $pesan_status; ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="#kontak" method="POST" id="formPeminjaman">
                            <input type="hidden" name="action" value="kirim_pesan">

                            <div class="form-group">
                                <label for="nama" class="form-label">Nama Lengkap Mahasiswa / Tamu *</label>
                                <input type="text" id="nama" name="nama" class="form-input" placeholder="Contoh: Muhammad Raihan" required>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group">
                                    <label for="nim" class="form-label">NIM (Nomor Induk)</label>
                                    <input type="text" id="nim" name="nim" class="form-input" placeholder="Contoh: 120140050">
                                </div>
                                <div class="form-group">
                                    <label for="email" class="form-label">Alamat Email Aktif *</label>
                                    <input type="email" id="email" name="email" class="form-input" placeholder="nama@student.ac.id" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="kategori" class="form-label">Kategori Keperluan</label>
                                <select id="kategori" name="kategori" class="form-select">
                                    <option value="Peminjaman Alat & Sensor">Peminjaman Kit / Alat Praktikum</option>
                                    <option value="Perizinan Riset Mandiri / Skripsi">Perizinan Riset Mandiri / Skripsi</option>
                                    <option value="Konsultasi Modul & Tugas Besar">Konsultasi Modul & Tugas Besar</option>
                                    <option value="Kunjungan & Pengenalan Lab">Kunjungan / Studi Banding</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="pesan" class="form-label">Rincian Pengajuan / Pesan *</label>
                                <textarea id="pesan" name="pesan" class="form-textarea" placeholder="Tuliskan nama alat yang dibutuhkan, tanggal pemakaian, atau pertanyaan Anda..." required></textarea>
                            </div>

                            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;" id="btnSubmitForm">
                                <span>Kirim Permohonan ke Asisten Lab</span>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <div class="brand-icon" style="width: 32px; height: 32px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="4" width="16" height="16" rx="2"/>
                                <rect x="9" y="9" width="6" height="6"/>
                            </svg>
                        </div>
                        <strong style="color: #fff; font-size: 1.1rem;"><?php echo htmlspecialchars($info_lab['nama']); ?></strong>
                    </div>
                    <p style="max-width: 440px; font-size: 0.88rem; line-height: 1.6;">
                        Laboratorium penyelenggara kegiatan pendidikan praktikum, riset rekayasa sistem komputer terintegrasi, 
                        dan pengabdian masyarakat di lingkungan Departemen Teknik Komputer.
                    </p>
                </div>

                <div class="footer-col">
                    <h4>Tautan Cepat</h4>
                    <ul class="footer-links">
                        <li><a href="#hero">Beranda Utama</a></li>
                        <li><a href="#riset">Fokus Divisi Riset</a></li>
                        <li><a href="#fasilitas">Spesifikasi Fasilitas</a></li>
                        <li><a href="#jadwal">Kalender Praktikum</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Pustaka & Modul</h4>
                    <ul class="footer-links">
                        <li><a href="#riset">Modul Mikroprosesor</a></li>
                        <li><a href="#riset">Modul Jaringan Komputer</a></li>
                        <li><a href="#riset">Panduan Verilog FPGA</a></li>
                        <li><a href="#kontak">Standard Operating Procedure</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    &copy; <?php echo $info_lab['tahun']; ?> <strong><?php echo htmlspecialchars($info_lab['nama']); ?></strong> - <?php echo htmlspecialchars($info_lab['kelompok']); ?>. All Rights Reserved.
                </div>
                <div style="font-family: var(--font-mono); font-size: 0.8rem; color: var(--primary);">
                    TUBES PEMROGRAMAN WEB 2026
                </div>
            </div>
        </div>
    </footer>

    <!-- Interactive Script -->
    <script>
        // Live Clock updater on Terminal Card
        function updateLiveClock() {
            const clockEl = document.getElementById('liveClock');
            if (clockEl) {
                const now = new Date();
                const options = { 
                    day: '2-digit', 
                    month: 'short', 
                    year: 'numeric', 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit',
                    hour12: false 
                };
                clockEl.textContent = now.toLocaleDateString('id-ID', options) + ' WIB';
            }
        }
        setInterval(updateLiveClock, 1000);

        // Research Card Filter functionality
        function filterCards(category, btnElement) {
            // Update active state on buttons
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            if (btnElement) {
                btnElement.classList.add('active');
            }

            // Filter cards
            const cards = document.querySelectorAll('.research-card');
            cards.forEach(card => {
                const cardCategory = card.getAttribute('data-category');
                if (category === 'all' || cardCategory === category) {
                    card.style.display = 'flex';
                    card.style.animation = 'fadeIn 0.4s ease forwards';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
