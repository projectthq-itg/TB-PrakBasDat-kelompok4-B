<?php
/**
 * deraAI - API Endpoint for Chat
 * 
 * This file handles AJAX requests from the chat popup.
 * It processes messages and returns AI responses.
 * 
 * To integrate with Google Gemini API, uncomment and configure
 * the Gemini section below.
 */

// Set header for JSON response
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($input['message']) : '';

// Validate input
if (empty($message)) {
    echo json_encode([
        'success' => false,
        'error' => 'Pesan tidak boleh kosong'
    ]);
    exit;
}

// ============================================
// OPTION 1: Simple rule-based responses
// (Default - works without API key)
// ============================================
function getRuleBasedReply($message) {
    $message = strtolower($message);
    
    $responses = [

    // ---------- SAPAAN & PERKENALAN ----------
    'halo' => 'Halo! Senang bertemu Anda. Ada yang bisa saya bantu tentang kesehatan hewan peliharaan Anda? 😊🐾',
    'hai' => 'Hai! Selamat datang di Klinik Hewan. Ada yang bisa saya bantu? 🌟',
    'assalamualaikum' => 'Waalaikumsalam! Semoga Anda dan hewan kesayangan selalu sehat. Ada yang bisa saya bantu? 🤗',
    'selamat pagi' => 'Selamat pagi! Semoga hari Anda menyenangkan. Ada yang ingin ditanyakan tentang hewan peliharaan? ☀️🐶',
    'selamat siang' => 'Selamat siang! Saya siap membantu Anda. 😊',
    'selamat sore' => 'Selamat sore! Ada yang bisa saya bantu? 🌅',
    'selamat malam' => 'Selamat malam! Jangan ragu untuk bertanya, saya di sini untuk membantu. 🌙',

    // ---------- UCAPAN TERIMA KASIH ----------
    'terima kasih' => 'Sama-sama! Senang bisa membantu Anda. Jika ada pertanyaan lain, jangan ragu untuk bertanya. 😄',
    'makasih' => 'Sama-sama! Semoga hewan peliharaan Anda sehat selalu. 🐱🐶',
    'thanks' => 'You\'re welcome! Always happy to help. 🐾',
    'thank you' => 'You\'re very welcome! Have a great day. 🌟',

    // ---------- IDENTITAS CHATBOT ----------
    'nama' => 'Saya **Perawat AI**, asisten virtual dari Klinik Hewan. Saya di sini untuk membantu Anda dengan pertanyaan seputar kesehatan hewan, layanan, janji temu, dan lainnya. 🩺🤖',
    'kamu siapa' => 'Saya Perawat AI, asisten digital Klinik Hewan. Saya siap membantu 24/7! 😊',
    'siapa kamu' => 'Saya Perawat AI, teman bicara Anda di Klinik Hewan. Ada yang bisa saya bantu?',
    'perkenalan' => 'Saya Perawat AI, asisten virtual di Klinik Hewan. Saya bisa memberikan informasi tentang layanan, jadwal, harga, dan tips perawatan hewan. 🐾',

    // ---------- ALAMAT & LOKASI ----------
    'alamat' => '🏥 Klinik Hewan kami beralamat di **Jl. Kesehatan No. 1, Garoet**. Kami buka setiap hari (kecuali Minggu) dengan jam operasional yang fleksibel. Silakan datang atau hubungi kami!',
    'lokasi' => '📍 Kami berada di **Jl. Kesehatan No. 1, Garoet**. Sangat mudah dijangkau. 🚗',
    'dimana' => 'Klinik kami terletak di **Jl. Kesehatan No. 1, Garoet**. Anda juga bisa cek di Google Maps dengan nama "Klinik Hewan Garoet". 🗺️',
    'cara ke sana' => 'Anda bisa datang ke Jl. Kesehatan No. 1, Garoet. Kami dekat dengan pusat kota. Jika bingung, hubungi kami di (021) 1234-5678. 📞',

    // ---------- JAM BUKA ----------
    'jam buka' => '🕐 Kami buka setiap **Senin–Jumat pukul 08.00 – 20.00**, **Sabtu 08.00 – 17.00**, dan **Tutup pada hari Minggu**. Silakan sesuaikan jadwal kunjungan Anda!',
    'jam operasional' => 'Jam operasional kami: Senin–Jumat 08:00–20:00, Sabtu 08:00–17:00, Minggu tutup. 😊',
    'buka jam berapa' => 'Kami buka mulai pukul 08.00 setiap hari kerja, dan tutup lebih awal di Sabtu. Minggu kami libur. Terima kasih!',
    'hari libur' => 'Kami tutup pada hari Minggu. Untuk hari besar nasional, kami akan mengumumkan di website dan media sosial. 🗓️',

    // ---------- KONTAK (TELEPON, EMAIL) ----------
    'telepon' => '📞 Anda dapat menghubungi kami di **(021) 1234-5678** untuk konsultasi, janji temu, atau keadaan darurat. Kami siap membantu!',
    'nomor telepon' => 'Nomor telepon Klinik Hewan: **(021) 1234-5678**. Jangan ragu untuk menelepon, ya! ☎️',
    'kontak' => 'Kontak kami: Telepon (021) 1234-5678, Email: info@klinikhewan.com. Kami juga aktif di media sosial! 📱',
    'email' => '📧 Kirim email ke **info@klinikhewan.com** untuk pertanyaan atau kritik saran. Kami akan merespon secepatnya.',
    'ig' => 'Ikuti Instagram kami @klinikhewan_grt untuk tips perawatan dan promo menarik! 📸',
    'instagram' => '@klinikhewan_grt – follow kami untuk update seputar kesehatan hewan!',

    // ---------- LAYANAN & HARGA ----------
    'layanan' => '🏥 Kami menyediakan berbagai layanan kesehatan untuk hewan peliharaan Anda:
- Konsultasi Umum (Rp150.000)
- Vaksinasi (Rp200.000)
- Laboratorium (Rp350.000)
- Perawatan Gigi (Rp250.000)
- Operasi (Rp1.500.000)
- Perawatan Intensif (Rp500.000)
- Grooming (Rp100.000)
- Fisioterapi (Rp300.000)

Untuk detail lebih lanjut, silakan tanyakan langsung ke resepsionis atau dokter kami. 😊',
    'daftar layanan' => 'Kami punya 8 layanan utama, mulai dari konsultasi hingga fisioterapi. Biaya mulai Rp100.000. Cek daftar lengkap di website atau tanyakan pada saya! 🐾',
    'harga' => '💰 Harga layanan bervariasi. Berikut perkiraan biaya:
- Konsultasi: Rp150.000
- Vaksinasi: Rp200.000
- Laboratorium: Rp350.000
- Perawatan Gigi: Rp250.000
- Operasi: Rp1.500.000
- Perawatan Intensif: Rp500.000
- Grooming: Rp100.000
- Fisioterapi: Rp300.000

Harga dapat berubah, konfirmasi langsung ke klinik untuk kepastian.',
    'biaya konsultasi' => 'Konsultasi umum dengan dokter hewan kami hanya **Rp150.000** per kunjungan. Sudah termasuk pemeriksaan fisik dan rekomendasi perawatan. 🩺',
    'biaya vaksin' => 'Vaksinasi lengkap mulai dari **Rp200.000**. Sangat penting untuk melindungi hewan kesayangan dari penyakit berbahaya. 💉',
    'biaya operasi' => 'Biaya operasi tergantung pada jenis tindakan, mulai dari **Rp1.500.000**. Kami selalu transparan dan diskusi biaya sebelum tindakan.',
    'grooming' => 'Layanan grooming kami hanya **Rp100.000** untuk pemotongan bulu, mandi, dan perawatan kuku. Hewan Anda akan tampil segar dan rapi! ✨🐕',
    'fisioterapi' => 'Fisioterapi untuk pemulihan cedera atau pasca operasi seharga **Rp300.000** per sesi. Sangat bermanfaat untuk mobilitas hewan. 🦴',
    'laboratorium' => 'Pemeriksaan laboratorium lengkap meliputi darah, urine, dan feses dengan harga **Rp350.000**. Hasil akurat untuk diagnosa tepat. 🔬',

    // ---------- PERAWAT & DOKTER ----------
    'dokter' => '👨‍⚕️ Kami memiliki tim dokter hewan profesional dan berpengalaman, termasuk dr. Dhera, dr. Azril, dan dr. Thoriq. Mereka siap memberikan perawatan terbaik untuk hewan kesayangan Anda.',
    'perawat' => '🧑‍⚕️ Tim perawat kami sangat terampil dan penuh kasih. Mereka adalah dr. Dhera (ahli bedah telinga), dr. Azril (bedah gigi), dr. Thoriq (bedah saraf), dan B. Yusuf (teknisi alat). Semua siap membantu dengan sepenuh hati.',
    'dokter hewan' => 'Dokter hewan kami lulusan terbaik dan memiliki sertifikasi. Mereka rutin mengikuti pelatihan untuk tetap update dengan ilmu terbaru.',
    'spesialis' => 'Kami memiliki spesialis di bidang bedah, gigi, saraf, dan teknologi medis. Silakan konsultasikan kebutuhan hewan Anda.',

    // ---------- HEWAN PELIHARAAN ----------
    'hewan' => '🐶🐱 Kami merawat berbagai jenis hewan peliharaan: kucing, anjing, kelinci, burung, dan hewan kecil lainnya. Semua mendapat perawatan yang sama istimewanya!',
    'kucing' => 'Untuk kucing, kami menyediakan layanan konsultasi, vaksinasi, perawatan gigi, grooming, dan operasi jika diperlukan. Contohnya Milo, kucing Persia kesayangan pelanggan kami. 🐈',
    'anjing' => 'Untuk anjing, kami siap membantu dengan vaksinasi, perawatan gigi, fisioterapi, dan grooming. Anjing Anda akan mendapatkan perawatan terbaik! 🐕',
    'kelinci' => 'Kami juga merawat kelinci! Konsultasi, vaksinasi, dan perawatan umum tersedia. 😊',
    'burung' => 'Untuk burung, kami menyediakan konsultasi dan pemeriksaan kesehatan. Silakan hubungi kami untuk detail.',
    'hewan lain' => 'Selain kucing dan anjing, kami juga menerima hamster, marmut, dan reptil peliharaan dengan konsultasi terlebih dahulu.',

    // ---------- JANJI TEMU ----------
    'janji temu' => '📅 Anda bisa membuat janji temu dengan mudah melalui website atau datang langsung ke klinik. Saat ini kami memiliki jadwal untuk tanggal 3 Februari 2027 dengan layanan Perawatan Intensif. Silakan daftar sekarang!',
    'buat janji' => 'Untuk membuat janji temu, silakan login ke akun pelanggan Anda di website, pilih layanan, pilih dokter/perawat, dan tentukan tanggal & waktu. Atau hubungi resepsionis kami. 🗓️',
    'reservasi' => 'Reservasi janji temu dapat dilakukan secara online. Kami akan mengirimkan notifikasi status janji (pending, confirmed, selesai, batal) ke email Anda. 📧',
    'jadwal periksa' => 'Kami memiliki jadwal pemeriksaan setiap hari kerja. Untuk melihat ketersediaan, silakan cek di halaman janji temu. Atau tanyakan langsung ke kami.',
    'batalkan janji' => 'Jika ingin membatalkan janji, silakan hubungi kami atau batalkan melalui akun Anda minimal 24 jam sebelum jadwal. Terima kasih.',
    'status janji' => 'Status janji temu Anda bisa dilihat di dashboard pelanggan: pending (menunggu), confirmed (dikonfirmasi), selesai, atau batal. Kami selalu update!',

    // ---------- PENDAFTARAN & AKUN ----------
    'daftar' => '📝 Untuk mendaftar sebagai pelanggan, silakan buat akun di website kami. Isi data diri, dan Anda bisa langsung menambahkan hewan peliharaan serta membuat janji temu.',
    'registrasi' => 'Registrasi mudah! Kunjungi halaman daftar, masukkan username, email, dan password. Setelah itu, Anda akan memiliki akses penuh ke layanan kami.',
    'login' => '🔑 Sudah punya akun? Silakan login menggunakan username dan password Anda. Jika lupa password, gunakan fitur "Lupa Password" untuk reset.',
    'lupa password' => 'Tidak ingat password? Klik "Lupa Password" di halaman login, kami akan kirimkan tautan reset ke email Anda. 📧',
    'profile' => 'Anda bisa memperbarui profil, foto, dan data hewan peliharaan di dashboard masing-masing. Jangan lupa perbarui data secara berkala.',
    'ubah data' => 'Untuk mengubah data diri atau data hewan, masuk ke akun Anda dan pilih menu "Profil" atau "Hewan Peliharaan".',

    // ---------- NOTIFIKASI ----------
    'notifikasi' => '🔔 Kami mengirimkan notifikasi penting ke dashboard Anda, seperti konfirmasi janji, pengingat vaksinasi, atau tips perawatan. Pastikan Anda membacanya!',
    'pengingat' => 'Anda akan mendapat pengingat otomatis untuk jadwal vaksinasi dan kontrol kesehatan. Cek notifikasi di akun Anda secara rutin.',
    'tips' => '💡 Kami sering membagikan tips perawatan hewan, seperti "Jangan lupa vaksinasi rutin setiap 6 bulan" dan "Perhatikan berat badan hewan". Simak notifikasi Anda!',

    // ---------- DARURAT ----------
    'darurat' => '🚨 Untuk keadaan darurat, segera hubungi kami di **(021) 1234-5678** atau datang langsung ke klinik. Kami siap tanggap darurat untuk hewan peliharaan Anda!',
    'emergency' => 'In case of emergency, please call us immediately at (021) 1234-5678. We are ready to help your pet. 🆘',
    'kecelakaan' => 'Jika hewan Anda mengalami kecelakaan, jangan panik. Hubungi kami dan ikuti instruksi dari tim medis kami. Kami akan segera bertindak.',
    '24 jam' => 'Kami tidak buka 24 jam, namun untuk kasus darurat, Anda bisa menghubungi nomor darurat yang tersedia. Silakan simpan nomor kami.',

    // ---------- PEMBAYARAN ----------
    'pembayaran' => '💳 Kami menerima pembayaran tunai, transfer bank, dan kartu debit/kredit (EDC). Untuk informasi lebih lanjut, tanyakan ke resepsionis.',
    'bayar' => 'Pembayaran dapat dilakukan di kasir klinik setelah pelayanan. Kami juga menyediakan invoice elektronik untuk keperluan Anda.',
    'metode bayar' => 'Metode pembayaran: Tunai, Transfer via bank, QRIS, dan kartu kredit/debit. Mudah dan aman!',
    'promo' => '🎉 Saat ini kami tidak memiliki promo khusus, tetapi pantau terus media sosial kami untuk penawaran menarik di masa mendatang!',
    'diskon' => 'Kami sering memberikan diskon untuk pelanggan setia atau paket layanan tertentu. Tanyakan langsung ke staf kami.',

    // ---------- PERAWATAN & KESEHATAN ----------
    'vaksinasi' => '💉 Vaksinasi sangat penting untuk melindungi hewan dari penyakit seperti rabies, distemper, dan influenza. Kami menawarkan paket vaksinasi lengkap. Konsultasikan dengan dokter kami untuk jadwal yang tepat.',
    'vaksin' => 'Vaksinasi rutin setiap 6–12 bulan, tergantung jenisnya. Hubungi kami untuk jadwal vaksinasi anak hewan dan dewasa.',
    'gigi' => 'Perawatan gigi meliputi pembersihan karang gigi, pencabutan jika perlu, dan perawatan mulut. Mulai dari Rp250.000. 🌟',
    'operasi' => 'Operasi seperti sterilisasi, pengangkatan tumor, atau bedah lainnya ditangani oleh dokter spesialis kami. Kami menjamin keamanan dan kenyamanan hewan Anda.',
    'steril' => 'Kami menyediakan layanan sterilisasi (kastrasi/spay) dengan harga terjangkau dan prosedur yang aman. Konsultasikan usia dan kondisi hewan Anda.',
    'perawatan intensif' => 'Perawatan intensif (ICU) untuk hewan sakit kritis dengan pemantauan 24 jam oleh perawat berpengalaman. Biaya Rp500.000 per hari.',
    'grooming' => 'Grooming meliputi pemotongan bulu, mandi, pembersihan kuku dan telinga. Hasilnya hewan peliharaan Anda akan wangi dan sehat! 🧼🐩',
    'fisioterapi' => 'Fisioterapi membantu pemulihan cedera atau pasca operasi. Kami menggunakan alat modern dan teknik manual. Sangat direkomendasikan.',

    // ---------- UMUM & LAINNYA ----------
    'website' => '🌐 Kunjungi website resmi kami di **www.klinikhewan.com** untuk informasi lengkap, daftar layanan, dan janji temu online.',
    'sosial media' => '📱 Ikuti kami di Instagram @klinikhewan_grt dan Facebook Klinik Hewan Garoet untuk tips harian dan promo!',
    'testimoni' => 'Kami memiliki banyak pelanggan puas, seperti Bapak Budi dengan kucingnya Milo. Mereka sangat merekomendasikan layanan kami. 😊',
    'info' => 'Informasi lebih lanjut bisa Anda dapatkan dengan menghubungi kami atau mengunjungi klinik secara langsung. Kami siap melayani!',
    'bantuan' => 'Butuh bantuan? Saya di sini untuk membantu Anda. Tanyakan apa saja tentang kesehatan hewan peliharaan Anda. 🤗',
    'terima kasih banyak' => 'Terima kasih kembali! Jika ada yang bisa saya bantu lagi, jangan sungkan. Salam hangat dari tim Klinik Hewan! 🐾💖',

    // ---------- DEFAULT / TIDAK DIKENAL ----------
    'default' => 'Maaf, saya belum mengerti pertanyaan Anda. 😅 Silakan tanyakan tentang layanan, jadwal, harga, atau hal lain seputar kesehatan hewan. Kami siap membantu! Jika perlu, hubungi resepsionis di (021) 1234-5678. 📞',
    ];
    
    // Check for keyword matches
    foreach ($responses as $keyword => $reply) {
        if (strpos($message, $keyword) !== false) {
            return $reply;
        }
    }
    
    // Default response if no keyword matches
    return 'Saya mengerti pertanyaan Anda. Namun Untuk informasi lebih lanjut, silakan hubungi resepsionis kami di (021) 1234-5678 atau datang langsung ke klinik kami di Jl. Kesehatan No. 1, Garoet. Apakah ada hal lain yang bisa saya bantu?';
}

// ============================================
// OPTION 2: Google Gemini API Integration
// (Uncomment and configure to use)
// ============================================
/*
function getGeminiReply($message) {
    $apiKey = 'YOUR_GEMINI_API_KEY_HERE'; // Ganti dengan API key Anda
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => "Anda adalah asisten AI untuk Klinik Hewan. Nama Anda adalah Perawat AI. Jawablah pertanyaan berikut dengan ramah dan informatif:\n\n" . $message
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 500,
            'topP' => 0.8,
            'topK' => 40
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
    }
    
    // Fallback to rule-based if API fails
    return getRuleBasedReply($message);
}
*/

// ============================================
// Process the message
// ============================================

// Use rule-based by default
$reply = getRuleBasedReply($message);

// Uncomment below to use Gemini
// $reply = getGeminiReply($message);

// Return response
echo json_encode([
    'success' => true,
    'reply' => $reply
]);
