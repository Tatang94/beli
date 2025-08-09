<?php
/**
 * PPOB Indonesia - Help & Customer Service
 * Bantuan dan layanan pelanggan
 */
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan & Customer Service - PPOB Indonesia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 1rem;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .content {
            padding: 2rem;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .help-sections {
            display: grid;
            gap: 2rem;
        }
        
        .help-section {
            border: 1px solid #e1e1e1;
            border-radius: 12px;
            padding: 1.5rem;
            background: white;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: #333;
            font-weight: 600;
        }
        
        .section-icon {
            font-size: 1.5rem;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .contact-card {
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .contact-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .contact-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .contact-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .contact-info {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .contact-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .contact-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .faq-list {
            space-y: 1rem;
        }
        
        .faq-item {
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .faq-question {
            background: #f8f9fa;
            padding: 1rem;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-question:hover {
            background: #e9ecef;
        }
        
        .faq-answer {
            padding: 1rem;
            background: white;
            color: #666;
            line-height: 1.6;
            display: none;
        }
        
        .faq-answer.show {
            display: block;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            color: #155724;
        }
        
        .guide-steps {
            counter-reset: step-counter;
        }
        
        .guide-step {
            counter-increment: step-counter;
            margin-bottom: 1.5rem;
            padding-left: 3rem;
            position: relative;
        }
        
        .guide-step::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 2rem;
            height: 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .guide-step h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .guide-step p {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🆘 Bantuan & Customer Service</h1>
            <p>Kami siap membantu Anda 24/7 untuk semua kebutuhan transaksi PPOB</p>
        </div>
        
        <div class="content">
            <a href="ppob.php" class="back-link">← Kembali ke Beranda</a>
            
            <div class="help-sections">
                
                <!-- Customer Service -->
                <div class="help-section">
                    <div class="section-title">
                        <span class="section-icon">📞</span>
                        Customer Service 24/7
                    </div>
                    
                    <div class="status-indicator">
                        <span>🟢</span>
                        <span><strong>Status:</strong> Online - Siap melayani Anda</span>
                    </div>
                    
                    <div class="contact-grid" style="margin-top: 1rem;">
                        <div class="contact-card">
                            <div class="contact-icon">💬</div>
                            <div class="contact-title">WhatsApp CS</div>
                            <div class="contact-info">Response: < 5 menit<br>24 jam sehari</div>
                            <a href="https://wa.me/628123456789" class="contact-btn">Chat WhatsApp</a>
                        </div>
                        
                        <div class="contact-card">
                            <div class="contact-icon">📧</div>
                            <div class="contact-title">Email Support</div>
                            <div class="contact-info">Response: < 1 jam<br>cs@ppob-indonesia.com</div>
                            <a href="mailto:cs@ppob-indonesia.com" class="contact-btn">Kirim Email</a>
                        </div>
                        
                        <div class="contact-card">
                            <div class="contact-icon">☎️</div>
                            <div class="contact-title">Call Center</div>
                            <div class="contact-info">24/7 Service<br>021-1234-5678</div>
                            <a href="tel:02112345678" class="contact-btn">Hubungi Sekarang</a>
                        </div>
                        
                        <div class="contact-card">
                            <div class="contact-icon">💬</div>
                            <div class="contact-title">Live Chat</div>
                            <div class="contact-info">Real-time support<br>Di website</div>
                            <a href="#" class="contact-btn">Mulai Chat</a>
                        </div>
                    </div>
                </div>
                
                <!-- FAQ -->
                <div class="help-section">
                    <div class="section-title">
                        <span class="section-icon">❓</span>
                        Frequently Asked Questions (FAQ)
                    </div>
                    
                    <div class="faq-list">
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFaq(this)">
                                <span>Bagaimana cara melakukan deposit saldo?</span>
                                <span>▼</span>
                            </div>
                            <div class="faq-answer">
                                <p>Anda dapat melakukan deposit melalui:</p>
                                <ul style="margin-left: 1rem; margin-top: 0.5rem;">
                                    <li>Transfer Bank (BCA, Mandiri, BRI, BNI)</li>
                                    <li>Virtual Account</li>
                                    <li>QRIS (Scan QR Code)</li>
                                    <li>E-Wallet (GoPay, OVO, DANA)</li>
                                </ul>
                                <p style="margin-top: 0.5rem;">Minimal deposit Rp 10.000, maksimal Rp 10.000.000 per transaksi.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFaq(this)">
                                <span>Berapa lama proses transaksi?</span>
                                <span>▼</span>
                            </div>
                            <div class="faq-answer">
                                <p>Waktu proses transaksi:</p>
                                <ul style="margin-left: 1rem; margin-top: 0.5rem;">
                                    <li><strong>Pulsa & Data:</strong> Real-time (1-3 detik)</li>
                                    <li><strong>Token PLN:</strong> Real-time (1-5 detik)</li>
                                    <li><strong>E-Money:</strong> Real-time (1-10 detik)</li>
                                    <li><strong>Game Voucher:</strong> Real-time (1-5 detik)</li>
                                    <li><strong>Tagihan:</strong> 1-15 menit</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFaq(this)">
                                <span>Apa yang harus dilakukan jika transaksi gagal?</span>
                                <span>▼</span>
                            </div>
                            <div class="faq-answer">
                                <p>Jika transaksi gagal:</p>
                                <ol style="margin-left: 1rem; margin-top: 0.5rem;">
                                    <li>Cek status transaksi di menu Riwayat</li>
                                    <li>Jika saldo terpotong, tunggu 1x24 jam untuk refund otomatis</li>
                                    <li>Jika belum ada refund, hubungi Customer Service dengan menyertakan ID transaksi</li>
                                    <li>Tim CS akan memproses refund maksimal 2x24 jam</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFaq(this)">
                                <span>Apakah ada biaya admin untuk transaksi?</span>
                                <span>▼</span>
                            </div>
                            <div class="faq-answer">
                                <p>Struktur biaya:</p>
                                <ul style="margin-left: 1rem; margin-top: 0.5rem;">
                                    <li><strong>Pulsa & Data:</strong> Sesuai harga yang tertera</li>
                                    <li><strong>Token PLN:</strong> +Rp 1.500 dari nilai token</li>
                                    <li><strong>E-Money:</strong> +Rp 1.000-2.500 tergantung provider</li>
                                    <li><strong>Tagihan:</strong> +Rp 2.500 per transaksi</li>
                                    <li><strong>Deposit:</strong> Gratis via transfer bank</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFaq(this)">
                                <span>Bagaimana cara cek saldo dan riwayat transaksi?</span>
                                <span>▼</span>
                            </div>
                            <div class="faq-answer">
                                <p>Untuk melihat saldo dan riwayat:</p>
                                <ol style="margin-left: 1rem; margin-top: 0.5rem;">
                                    <li>Saldo real-time terlihat di bagian atas halaman utama</li>
                                    <li>Klik menu "Riwayat" untuk melihat semua transaksi</li>
                                    <li>Anda dapat filter berdasarkan tanggal, kategori, atau status</li>
                                    <li>Detail lengkap termasuk ID transaksi dan referensi tersedia</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Panduan -->
                <div class="help-section">
                    <div class="section-title">
                        <span class="section-icon">📋</span>
                        Panduan Transaksi
                    </div>
                    
                    <div class="guide-steps">
                        <div class="guide-step">
                            <h4>Deposit Saldo</h4>
                            <p>Lakukan deposit minimal Rp 10.000 melalui metode pembayaran yang tersedia. Saldo akan masuk secara otomatis setelah pembayaran terkonfirmasi.</p>
                        </div>
                        
                        <div class="guide-step">
                            <h4>Pilih Kategori</h4>
                            <p>Pilih kategori layanan yang diinginkan (Pulsa, Data, PLN, Game, dll) dari halaman utama PPOB Indonesia.</p>
                        </div>
                        
                        <div class="guide-step">
                            <h4>Pilih Produk</h4>
                            <p>Browse dan pilih produk yang sesuai dengan kebutuhan Anda. Pastikan nominal dan operator sudah benar.</p>
                        </div>
                        
                        <div class="guide-step">
                            <h4>Isi Data Tujuan</h4>
                            <p>Masukkan nomor HP, ID Player, atau data tujuan lainnya dengan benar. Double check sebelum melanjutkan.</p>
                        </div>
                        
                        <div class="guide-step">
                            <h4>Konfirmasi & Bayar</h4>
                            <p>Review detail transaksi, konfirmasi, dan proses pembayaran. Transaksi akan diproses secara real-time.</p>
                        </div>
                        
                        <div class="guide-step">
                            <h4>Selesai</h4>
                            <p>Transaksi berhasil! Cek status di menu Riwayat dan simpan bukti transaksi untuk referensi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const arrow = element.querySelector('span:last-child');
            
            if (answer.classList.contains('show')) {
                answer.classList.remove('show');
                arrow.textContent = '▼';
            } else {
                // Close all other FAQ items
                document.querySelectorAll('.faq-answer.show').forEach(item => {
                    item.classList.remove('show');
                });
                document.querySelectorAll('.faq-question span:last-child').forEach(arrow => {
                    arrow.textContent = '▼';
                });
                
                // Open clicked FAQ item
                answer.classList.add('show');
                arrow.textContent = '▲';
            }
        }
    </script>
</body>
</html>