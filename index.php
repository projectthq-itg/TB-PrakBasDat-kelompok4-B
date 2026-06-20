<?php
require_once 'config.php';

// Cek koneksi database
try {
    // Ambil data perawat untuk ditampilkan
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM perawat p JOIN users u ON p.user_id = u.id WHERE p.status = 'aktif'");
    $stmt->execute();
    $perawatList = $stmt->fetchAll();
} catch (PDOException $e) {
    $perawatList = [];
    $error = "Error mengambil data perawat: " . $e->getMessage();
}

$dokterCount = count($perawatList);

// Ambil daftar foto untuk background hero (img/bg/*.jpg) secara dinamis
$heroImages = [];
$heroDir = __DIR__ . '/img/bg';
if (is_dir($heroDir)) {
    $files = glob($heroDir . '/*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);
    natsort($files);
    foreach ($files as $f) {
        $heroImages[] = 'img/bg/' . rawurlencode(basename($f));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Polwan - Home</title>
    <link rel="icon" type="image/png" href="img/logo.png">

    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           DESIGN TOKENS
           ============================================ */
        :root{
            --ink:#16241B;
            --ink-soft:#233326;
            --sage:#6F8F76;
            --sage-light:#DCE7DD;
            --gold:#C8A664;
            --gold-dark:#A8855B;
            --gold-soft:#E9DBB8;
            --cream:#F7F3EA;
            --paper:#FFFFFF;
            --text:#38423A;
            --text-muted:#6E7669;
            --shadow-sm:0 4px 14px rgba(22,36,27,0.08);
            --shadow-md:0 14px 40px rgba(22,36,27,0.14);
            --shadow-lg:0 30px 70px rgba(15,22,16,0.28);
            --radius-sm:10px;
            --radius-md:18px;
            --radius-lg:28px;
            --ease:cubic-bezier(.16,.84,.44,1);
        }

        *{margin:0;padding:0;box-sizing:border-box;}

        html{scroll-behavior:smooth;}

        body{
            font-family:'Inter',sans-serif;
            line-height:1.65;
            color:var(--text);
            background:var(--paper);
            overflow-x:hidden;
        }

        .container{max-width:1180px;margin:0 auto;padding:0 24px;}

        h1,h2,h3{font-family:'Fraunces',serif;color:var(--ink);}

        em{font-style:italic;color:var(--gold);font-weight:500;}

        a{color:inherit;}

        a:focus-visible, button:focus-visible{outline:2px solid var(--gold);outline-offset:3px;}

        .eyebrow{
            display:inline-flex;align-items:center;gap:10px;
            font-size:12.5px;font-weight:600;letter-spacing:.18em;text-transform:uppercase;
            color:var(--gold-dark);
        }
        .eyebrow::before{content:'';width:26px;height:2px;background:var(--gold);display:block;}
        .hero .eyebrow{color:var(--gold-soft);}
        .hero .eyebrow::before{background:var(--gold-soft);}

        .section-head{max-width:640px;margin:0 auto 56px;text-align:left;}
        .section-head.center{text-align:center;margin-left:auto;margin-right:auto;}
        .section-head h2{font-size:clamp(28px,4vw,42px);font-weight:600;margin:16px 0 16px;line-height:1.2;}
        .section-head p.lead{color:var(--text-muted);font-size:17px;}

        /* Scroll-reveal (progressive enhancement, see JS) */
        .reveal{transition:opacity .8s var(--ease), transform .8s var(--ease);}
        .reveal.reveal-armed{opacity:0;transform:translateY(30px);}
        .reveal.reveal-armed.is-visible{opacity:1;transform:translateY(0);}

        /* ============================================
           NAVBAR
           ============================================ */
        .navbar{
            position:fixed;top:0;left:0;right:0;z-index:1000;
            padding:24px 0;
            background:transparent;
            transition:background .5s var(--ease), backdrop-filter .5s var(--ease), padding .4s var(--ease), box-shadow .4s var(--ease);
        }
        .navbar.scrolled{
            padding:14px 0;
            background:rgba(247,243,234,0.74);
            backdrop-filter:blur(18px) saturate(160%);
            -webkit-backdrop-filter:blur(18px) saturate(160%);
            box-shadow:var(--shadow-sm);
        }
        .navbar .container{display:flex;justify-content:space-between;align-items:center;}

        .nav-brand{display:flex;align-items:center;gap:10px;font-size:21px;font-weight:600;font-family:'Fraunces',serif;color:#fff;transition:color .4s;}
        .navbar.scrolled .nav-brand{color:var(--ink);}
        .nav-brand img{border-radius:50%;filter:drop-shadow(0 2px 6px rgba(0,0,0,.25));}

        .nav-menu{display:flex;list-style:none;gap:34px;align-items:center;}
        .nav-menu a{text-decoration:none;color:rgba(255,255,255,.92);font-weight:500;font-size:14.5px;letter-spacing:.01em;transition:color .3s;position:relative;}
        .navbar.scrolled .nav-menu a{color:var(--ink-soft);}
        .nav-menu li:not(.nav-cta) a::after{content:'';position:absolute;left:0;right:0;bottom:-8px;height:2px;background:var(--gold);transform:scaleX(0);transition:transform .35s var(--ease);}
        .nav-menu li:not(.nav-cta) a:hover::after{transform:scaleX(1);}
        .nav-menu a:hover{color:var(--gold);}

        .btn-nav{background:var(--gold);color:var(--ink) !important;padding:10px 24px;border-radius:30px;font-weight:600;transition:background .3s, transform .3s, box-shadow .3s;}
        .btn-nav:hover{background:var(--gold-dark);transform:translateY(-2px);box-shadow:0 10px 24px rgba(168,133,91,.35);}
        .btn-nav::after{display:none !important;}

        .hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;}
        .hamburger span{width:24px;height:2px;background:#fff;transition:.3s;}
        .navbar.scrolled .hamburger span{background:var(--ink);}

        @media(max-width:768px){
            .nav-menu{
                display:none;position:absolute;top:100%;left:0;right:0;
                background:rgba(247,243,234,0.97);backdrop-filter:blur(20px);
                flex-direction:column;align-items:flex-start;gap:18px;
                padding:26px 24px;box-shadow:var(--shadow-md);
            }
            .nav-menu.active{display:flex;}
            .nav-menu.active a{color:var(--ink) !important;}
            .hamburger{display:flex;}
        }

        /* ============================================
           HERO
           ============================================ */
        .hero{
            position:relative;min-height:100vh;display:flex;align-items:center;
            overflow:hidden;padding:150px 0 90px;
        }
        .hero-bg{position:absolute;inset:0;z-index:0;background:linear-gradient(135deg,#1c2e21,#0e1610);}
        .hero-slide{position:absolute;inset:0;background-size:cover;background-position:center;opacity:0;transition:opacity 1.4s ease;}
        .hero-slide.active{opacity:1;animation:kenburns 9s ease-out forwards;}
        @keyframes kenburns{from{transform:scale(1);}to{transform:scale(1.12);}}
        .hero-overlay{
            position:absolute;inset:0;z-index:1;
            background:linear-gradient(180deg, rgba(13,20,15,.5) 0%, rgba(13,20,15,.66) 50%, rgba(11,17,13,.92) 100%);
        }
        .hero .container{position:relative;z-index:2;}
        .hero-content{max-width:640px;}
        .hero-content h1{
            font-size:clamp(32px,5vw,56px);font-weight:600;color:#fff;
            line-height:1.14;margin:18px 0 22px;
        }
        .hero-content p.lead{color:rgba(255,255,255,.84);font-size:18px;max-width:520px;margin-bottom:36px;}

        .hero-buttons{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:54px;}
        .btn-primary,.btn-secondary{
            padding:14px 32px;border-radius:40px;text-decoration:none;font-weight:600;
            font-size:15px;transition:all .35s var(--ease);display:inline-flex;align-items:center;gap:8px;
        }
        .btn-primary{background:var(--gold);color:var(--ink);box-shadow:var(--shadow-md);}
        .btn-primary:hover{background:#d6b878;transform:translateY(-3px);box-shadow:0 18px 40px rgba(200,166,100,.4);}
        .btn-secondary{background:rgba(255,255,255,.07);color:#fff;border:1.5px solid rgba(255,255,255,.5);}
        .btn-secondary:hover{background:rgba(255,255,255,.16);border-color:#fff;transform:translateY(-3px);}

        .hero-stats{
            display:grid;grid-template-columns:repeat(3,1fr);
            background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.18);
            border-radius:var(--radius-lg);backdrop-filter:blur(16px) saturate(160%);
            max-width:660px;box-shadow:var(--shadow-lg);
        }
        .hero-stat{padding:24px 22px;border-right:1px solid rgba(255,255,255,.14);}
        .hero-stat:last-child{border-right:none;}
        .hero-stat strong{display:block;font-family:'Fraunces',serif;font-size:28px;color:#fff;font-weight:600;}
        .hero-stat span{font-size:12px;letter-spacing:.06em;color:rgba(255,255,255,.68);text-transform:uppercase;}

        @media(max-width:600px){
            .hero-stats{grid-template-columns:1fr;}
            .hero-stat{border-right:none;border-bottom:1px solid rgba(255,255,255,.14);}
            .hero-stat:last-child{border-bottom:none;}
        }

        /* ============================================
           LAYANAN
           ============================================ */
        .layanan{padding:110px 0;background:var(--cream);}
        .layanan-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:28px;}
        .layanan-card{
            background:var(--paper);border:1px solid rgba(22,36,27,.07);border-radius:var(--radius-md);
            padding:42px 28px;transition:transform .5s var(--ease), box-shadow .5s var(--ease), border-color .5s;
        }
        .layanan-card:hover{transform:translateY(-8px);box-shadow:var(--shadow-md);border-color:var(--gold);}
        .layanan-card:nth-child(1){transition-delay:.04s;}
        .layanan-card:nth-child(2){transition-delay:.12s;}
        .layanan-card:nth-child(3){transition-delay:.2s;}
        .layanan-card:nth-child(4){transition-delay:.28s;}
        .icon-ring{
            width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;
            background:var(--sage-light);font-size:27px;margin-bottom:24px;
            transition:transform .5s var(--ease), background .5s var(--ease);
        }
        .layanan-card:hover .icon-ring{transform:rotate(-8deg) scale(1.07);background:var(--gold-soft);}
        .layanan-card h3{font-size:20px;font-weight:600;margin-bottom:10px;}
        .layanan-card p{color:var(--text-muted);font-size:15px;}

        /* ============================================
           PERAWAT
           ============================================ */
        .perawat{padding:110px 0;background:var(--paper);}
        .alert{padding:14px 18px;border-radius:12px;margin-bottom:32px;font-size:14px;}
        .alert-warning{background:#FFF6E5;color:#8A6D1F;border:1px solid #F3DFA6;}
        .alert-danger{background:#FDEAEA;color:#B23B3B;border:1px solid #F5C6CB;}
        .perawat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:32px;}
        .perawat-card{
            background:var(--cream);border-radius:var(--radius-md);padding:36px 28px;text-align:center;
            transition:transform .5s var(--ease), box-shadow .5s var(--ease);
        }
        .perawat-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-md);}
        .perawat-card:nth-child(1){transition-delay:.04s;}
        .perawat-card:nth-child(2){transition-delay:.12s;}
        .perawat-card:nth-child(3){transition-delay:.2s;}
        .perawat-card:nth-child(4){transition-delay:.28s;}
        .perawat-image{
            width:132px;height:132px;margin:0 auto 22px;border-radius:50%;overflow:hidden;
            border:3px solid var(--gold);
        }
        .perawat-image img{width:100%;height:100%;object-fit:cover;transition:transform .6s var(--ease);}
        .perawat-card:hover .perawat-image img{transform:scale(1.08);}
        .perawat-card h3{font-size:19px;font-weight:600;margin-bottom:4px;}
        .perawat-card .keahlian{color:var(--sage);font-weight:600;font-size:12.5px;letter-spacing:.05em;text-transform:uppercase;margin-bottom:8px;}
        .perawat-card .pengalaman{color:var(--text-muted);font-size:14px;margin-bottom:20px;}
        .btn-small{
            display:inline-block;padding:9px 26px;border:1.5px solid var(--gold);color:var(--ink);
            text-decoration:none;border-radius:24px;font-weight:600;font-size:14px;
            transition:background .3s, color .3s, transform .3s;
        }
        .btn-small:hover{background:var(--gold);color:var(--ink);transform:translateY(-2px);}

        /* ============================================
           TENTANG (about)
           ============================================ */
        .tentang{padding:110px 0;background:var(--cream);}
        .tentang-grid{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center;}
        .tentang-frame{position:relative;}
        .tentang-frame::before{
            content:'';position:absolute;inset:22px -22px -22px 22px;
            border:2px solid var(--gold);border-radius:var(--radius-lg);z-index:0;
        }
        .tentang-frame img{position:relative;z-index:1;display:block;width:100%;border-radius:var(--radius-lg);box-shadow:var(--shadow-md);background:var(--paper);}
        .tentang-content p.lead{margin:16px 0 26px;color:var(--text-muted);font-size:16.5px;}
        .checklist{list-style:none;display:grid;gap:15px;}
        .checklist li{position:relative;padding-left:32px;font-size:15.5px;color:var(--text);}
        .checklist li::before{
            content:'✓';position:absolute;left:0;top:1px;width:20px;height:20px;border-radius:50%;
            background:var(--sage);color:#fff;font-size:11px;line-height:20px;text-align:center;
        }
        @media(max-width:900px){
            .tentang-grid{grid-template-columns:1fr;gap:48px;}
            .tentang-frame{order:2;}
        }

        /* ============================================
           FOOTER
           ============================================ */
        footer{background:var(--ink);color:#fff;padding:80px 0 26px;position:relative;}
        footer::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);}
        .footer-content{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:40px;margin-bottom:50px;}
        .footer-section h3{color:#fff;font-size:22px;margin-bottom:14px;}
        .footer-section h4{color:var(--gold-soft);font-size:13px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:18px;font-weight:600;}
        .footer-section p{color:rgba(255,255,255,.62);margin-bottom:10px;font-size:14.5px;}
        .footer-bottom{text-align:center;padding-top:24px;border-top:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.42);font-size:13.5px;}

        /* ============================================
           DERA-AI FLOATING BUTTON - MODIFIED
           ============================================ */
        .dera-fab{
            position:fixed;right:26px;bottom:26px;z-index:1200;
            display:flex;align-items:center;justify-content:center;
            width:80px;height:80px;
            background:transparent;
            text-decoration:none;
            transition:transform .4s var(--ease);
            cursor:pointer;
            border:none;
            padding:0;
        }
        .dera-fab:hover{
            transform:scale(1.08) translateY(-4px);
        }
        .dera-fab img{
            width:200%;height:200%;
            object-fit:contain;
            border-radius:10%;
            background: transparent;
            transition:all .4s var(--ease);
        }
       
        .dera-fab-pulse{
            position:absolute;inset:-8px;border-radius:50%;border:2px solid var(--gold);
            opacity:.55;animation:fabpulse 2.6s ease-out infinite;
        }
        @keyframes fabpulse{
            0%{transform:scale(.94);opacity:.65;}
            70%{transform:scale(1.18);opacity:0;}
            100%{opacity:0;}
        }
        .dera-fab-tooltip{
            position:absolute;right:100%;top:50%;transform:translateY(-50%);
            background:var(--ink);color:#fff;padding:8px 16px;border-radius:8px;
            font-size:13px;font-weight:500;white-space:nowrap;
            opacity:0;pointer-events:none;transition:all .3s var(--ease);
            margin-right:16px;
            box-shadow:var(--shadow-md);
        }
        .dera-fab-tooltip::after{
            content:'';position:absolute;left:100%;top:50%;transform:translateY(-50%);
            border:6px solid transparent;border-left-color:var(--ink);
        }
        .dera-fab:hover .dera-fab-tooltip{
            opacity:1;
            transform:translateY(-50%) translateX(4px);
        }

        /* ============================================
           CHAT POPUP OVERLAY
           ============================================ */
        .chat-overlay{
            position:fixed;inset:0;z-index:2000;
            background:rgba(0,0,0,0.6);
            backdrop-filter:blur(8px);
            -webkit-backdrop-filter:blur(8px);
            display:none;
            justify-content:center;
            align-items:center;
            animation:fadeIn .3s var(--ease);
        }
        .chat-overlay.active{
            display:flex;
        }
        @keyframes fadeIn{
            from{opacity:0;}
            to{opacity:1;}
        }

        .chat-popup{
            position:relative;
            width:90%;
            max-width:480px;
            height:600px;
            max-height:90vh;
            background:var(--paper);
            border-radius:var(--radius-lg);
            box-shadow:var(--shadow-lg);
            display:flex;
            flex-direction:column;
            overflow:hidden;
            animation:slideUp .4s var(--ease);
        }
        @keyframes slideUp{
            from{transform:translateY(40px) scale(.96);opacity:0;}
            to{transform:translateY(0) scale(1);opacity:1;}
        }

        .chat-header{
            padding:20px 24px;
            background:var(--ink);
            color:#fff;
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-shrink:0;
        }
        .chat-header-title{
            display:flex;align-items:center;gap:12px;
        }
        .chat-header-title img{
            width:40px;height:40px;border-radius:50%;object-fit:cover;
            border:2px solid var(--gold);
        }
        .chat-header-title div{
            display:flex;flex-direction:column;
        }
        .chat-header-title h3{
            color:#fff;font-size:18px;font-weight:600;margin:0;
        }
        .chat-header-title span{
            color:rgba(255,255,255,.7);font-size:12px;
        }
        .chat-close{
            background:rgba(255,255,255,.1);
            border:none;color:#fff;font-size:24px;cursor:pointer;
            width:36px;height:36px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            transition:background .3s;
            line-height:1;
        }
        .chat-close:hover{
            background:rgba(255,255,255,.2);
        }

        .chat-messages{
            flex:1;
            overflow-y:auto;
            padding:20px 24px;
            background:var(--cream);
            display:flex;
            flex-direction:column;
            gap:12px;
        }
        .chat-messages::-webkit-scrollbar{
            width:4px;
        }
        .chat-messages::-webkit-scrollbar-track{
            background:transparent;
        }
        .chat-messages::-webkit-scrollbar-thumb{
            background:var(--gold);
            border-radius:4px;
        }

        .message{
            max-width:80%;
            padding:12px 16px;
            border-radius:14px;
            font-size:14px;
            line-height:1.5;
            animation:msgIn .3s var(--ease);
        }
        @keyframes msgIn{
            from{opacity:0;transform:translateY(10px) scale(.96);}
            to{opacity:1;transform:translateY(0) scale(1);}
        }
        .message.bot{
            align-self:flex-start;
            background:var(--paper);
            color:var(--text);
            border-bottom-left-radius:4px;
            box-shadow:var(--shadow-sm);
        }
        .message.user{
            align-self:flex-end;
            background:var(--gold);
            color:var(--ink);
            border-bottom-right-radius:4px;
        }
        .message.typing{
            align-self:flex-start;
            background:var(--paper);
            color:var(--text-muted);
            border-bottom-left-radius:4px;
            padding:14px 20px;
            display:flex;
            align-items:center;
            gap:6px;
            min-height:50px;
        }
        .typing-dots span{
            display:inline-block;
            width:8px;height:8px;
            border-radius:50%;
            background:var(--gold);
            animation:typingDot 1.2s ease-in-out infinite;
        }
        .typing-dots span:nth-child(2){animation-delay:.2s;}
        .typing-dots span:nth-child(3){animation-delay:.4s;}
        @keyframes typingDot{
            0%,60%,100%{transform:translateY(0);}
            30%{transform:translateY(-8px);}
        }

        .chat-input-area{
            padding:16px 20px;
            background:var(--paper);
            border-top:1px solid rgba(22,36,27,.08);
            display:flex;
            gap:12px;
            flex-shrink:0;
        }
        .chat-input-area input{
            flex:1;
            padding:12px 16px;
            border:1.5px solid var(--sage-light);
            border-radius:30px;
            font-size:14px;
            font-family:'Inter',sans-serif;
            transition:border-color .3s;
            outline:none;
            background:var(--cream);
        }
        .chat-input-area input:focus{
            border-color:var(--gold);
        }
        .chat-input-area button{
            padding:12px 24px;
            background:var(--gold);
            border:none;
            border-radius:30px;
            font-weight:600;
            color:var(--ink);
            cursor:pointer;
            transition:all .3s var(--ease);
            font-size:14px;
            font-family:'Inter',sans-serif;
            white-space:nowrap;
        }
        .chat-input-area button:hover{
            background:var(--gold-dark);
            transform:translateY(-2px);
            box-shadow:0 8px 20px rgba(168,133,91,.3);
        }
        .chat-input-area button:disabled{
            opacity:.5;
            cursor:not-allowed;
            transform:none !important;
        }

        @media(max-width:600px){
            .chat-popup{
                width:100%;
                max-width:100%;
                height:100vh;
                max-height:100vh;
                border-radius:0;
            }
            .chat-messages{
                padding:16px;
            }
            .message{
                max-width:90%;
            }
            .dera-fab{
                width:64px;height:64px;right:16px;bottom:16px;
            }
            .dera-fab-tooltip{
                display:none;
            }
        }

        @media (prefers-reduced-motion: reduce){
            *{animation-duration:.01ms !important;animation-iteration-count:1 !important;transition-duration:.01ms !important;scroll-behavior:auto !important;}
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <img src="img/logo.png  " alt="Logo" width="36">
                <span>POLWAN (POLISI HEWAN)</span>
            </div>
            <ul class="nav-menu">
                <li><a href="#home">Beranda</a></li>
                <li><a href="#layanan">Layanan</a></li>
                <li><a href="#perawat">Perawat</a></li>
                <li><a href="#tentang">Tentang</a></li>
                <li class="nav-cta"><a href="login.php" class="btn-nav">Login</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-bg">
            <?php if (!empty($heroImages)): ?>
                <?php foreach ($heroImages as $i => $img): ?>
                    <div class="hero-slide<?php echo $i === 0 ? ' active' : ''; ?>" style="background-image:url('<?php echo htmlspecialchars($img); ?>');"></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="hero-overlay"></div>

        <div class="container">
            <div class="hero-content">
                <span class="eyebrow">Perawatan Hewan Profesional</span>
                <h1>Karena Mereka Bukan Sekadar <em>Peliharaan</em></h1>
                <p class="lead">Tim dokter hewan berpengalaman dan fasilitas modern, merawat hewan kesayangan Anda dengan ketelitian dan kasih sayang yang mereka layak dapatkan.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn-primary">Buat Janji Temu</a>
                    <a href="#layanan" class="btn-secondary">Lihat Layanan</a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <strong>4</strong>
                        <span>Layanan Utama</span>
                    </div>
                    <div class="hero-stat">
                        <strong><?php echo $dokterCount > 0 ? $dokterCount . '+' : 'Tim'; ?></strong>
                        <span>Dokter Berpengalaman</span>
                    </div>
                    <div class="hero-stat">
                        <strong>6 Hari</strong>
                        <span>Operasional</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Layanan Section -->
    <section id="layanan" class="layanan">
        <div class="container">
            <div class="section-head center">
                <span class="eyebrow">Layanan Kami</span>
                <h2>Perawatan yang <em>Komprehensif</em></h2>
                <p class="lead">Empat pilar layanan yang kami jaga kualitasnya, dari konsultasi harian hingga perawatan intensif.</p>
            </div>
            <div class="layanan-grid">
                <div class="layanan-card reveal">
                    <div class="icon-ring">🏥</div>
                    <h3>Konsultasi</h3>
                    <p>Konsultasi kesehatan hewan dengan dokter profesional</p>
                </div>
                <div class="layanan-card reveal">
                    <div class="icon-ring">💉</div>
                    <h3>Vaksinasi</h3>
                    <p>Program vaksinasi lengkap untuk hewan peliharaan</p>
                </div>
                <div class="layanan-card reveal">
                    <div class="icon-ring">🔬</div>
                    <h3>Laboratorium</h3>
                    <p>Pemeriksaan laboratorium dengan alat modern</p>
                </div>
                <div class="layanan-card reveal">
                    <div class="icon-ring">🏨</div>
                    <h3>Perawatan</h3>
                    <p>Perawatan intensif untuk hewan sakit</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Perawat Section -->
    <!-- Perawat Section -->
    <section id="perawat" class="perawat">
        <div class="container">
            <div class="section-head center">
                <span class="eyebrow">Tim Kami</span>
                <h2>Dokter yang <em>Mengenal</em> Hewan Anda</h2>
                <p class="lead">Tim perawat dan dokter hewan berpengalaman yang siap merawat hewan kesayangan Anda.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-warning"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="perawat-grid">
                <?php if (!empty($perawatList)): ?>
                    <?php foreach ($perawatList as $perawat): ?>
                    <div class="perawat-card reveal">
                        <div class="perawat-image">
                            <img src="<?php
                                // PERBAIKAN: Cek dan perbaiki path foto
                                if (!empty($perawat['foto_profile'])) {
                                    // Hapus '../' di awal jika ada, karena kita di root
                                    $foto = $perawat['foto_profile'];
                                    // Jika path dimulai dengan '../', hapus
                                    $foto = str_replace('../', '', $foto);
                                    // Jika path tidak dimulai dengan 'uploads/', tambahkan
                                    if (strpos($foto, 'uploads/') !== 0) {
                                        $foto = 'uploads/' . $foto;
                                    }
                                    echo htmlspecialchars($foto);
                                } else {
                                    echo 'https://ui-avatars.com/api/?name=' . urlencode($perawat['nama_lengkap']) . '&size=200&background=C8A664&color=fff';
                                }
                            ?>"
                            alt="<?php echo htmlspecialchars($perawat['nama_lengkap']); ?>">
                        </div>
                        <h3><?php echo htmlspecialchars($perawat['nama_lengkap']); ?></h3>
                        <p class="keahlian"><?php echo htmlspecialchars($perawat['keahlian'] ?: 'Dokter Hewan'); ?></p>
                        <p class="pengalaman">⭐ <?php echo $perawat['pengalaman'] ?: '0'; ?> tahun pengalaman</p>
                        <a href="login.php" class="btn-small">Buat Janji</a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="perawat-card" style="grid-column:1/-1;text-align:center;padding:40px;">
                        <p style="font-size:18px;color:var(--text-muted);">Belum ada data perawat. Silahkan tambahkan data perawat di database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Tentang Section -->
    <section id="tentang" class="tentang">
        <div class="container tentang-grid">
            <div class="tentang-frame reveal">
                <img src="https://awsimages.detik.net.id/community/media/visual/2018/03/08/e4f37f25-852d-4b83-a6db-286def186111_43.jpeg?w=1200" alt="Ilustrasi perawatan hewan di klinik">
            </div>
            <div class="tentang-content reveal">
                <span class="eyebrow">Tentang Kami</span>
                <h2>Komitmen pada <em>Kesembuhan</em>, Bukan Sekadar Pengobatan</h2>
                <p class="lead">Kami percaya setiap hewan berhak mendapat perhatian penuh, bukan sekadar antrean pasien. Itu sebabnya setiap kunjungan dirancang tenang, personal, dan didampingi tenaga medis yang benar-benar peduli.</p>
                <ul class="checklist">
                    <li>Dokter hewan bersertifikat &amp; berpengalaman</li>
                    <li>Fasilitas laboratorium dan rawat inap modern</li>
                    <li>Pendekatan personal untuk setiap hewan</li>
                    <li>Lingkungan klinik yang tenang dan higienis</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Klinik Hewan</h3>
                    <p>Sehatkan hewan kesayangan Anda dengan perawatan terbaik</p>
                </div>
                <div class="footer-section">
                    <h4>Kontak</h4>
                    <p>📞 (021) 1234-5678</p>
                    <p>📧 info@polwan.com</p>
                    <p>📍 Jl. Kesehatan No. 1, Garoet</p>
                </div>
                <div class="footer-section">
                    <h4>Jam Operasional</h4>
                    <p>Senin - Jumat: 08:00 - 20:00</p>
                    <p>Sabtu: 08:00 - 17:00</p>
                    <p>Minggu: Tutup</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Klinik Hewan. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Tombol Chat ke deraAI - MODIFIED -->
    <button class="dera-fab" id="deraFab" aria-label="Tanya deraAI">
        <span class="dera-fab-pulse"></span>
        <img src="img/ai.png" alt="Perawat AI">
        <span class="dera-fab-tooltip">Tanya Perawat AI 💬</span>
    </button>

    <!-- Chat Overlay -->
    <div class="chat-overlay" id="chatOverlay">
        <div class="chat-popup">
            <div class="chat-header">
                <div class="chat-header-title">
                    <img src="https://ui-avatars.com/api/?name=Perawat+AI&size=200&background=C8A664&color=fff&bold=true" alt="Perawat AI">
                    <div>
                        <h3>Perawat AI</h3>
                        <span>Online • Siap membantu</span>
                    </div>
                </div>
                <button class="chat-close" id="chatClose">✕</button>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="message bot">
                    👋 Halo! Saya Perawat AI dari Klinik Hewan. Ada yang bisa saya bantu?
                </div>
            </div>
            <div class="chat-input-area">
                <input type="text" id="chatInput" placeholder="Tanyakan tentang kesehatan hewan..." autocomplete="off">
                <button id="chatSend">Kirim</button>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.nav-menu');
        hamburger.addEventListener('click', function () {
            navMenu.classList.toggle('active');
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const targetEl = document.querySelector(this.getAttribute('href'));
                if (targetEl) {
                    e.preventDefault();
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    navMenu.classList.remove('active');
                }
            });
        });

        // Navbar: blur + solid background once the page is scrolled
        const navbar = document.querySelector('.navbar');
        const onScroll = () => {
            navbar.classList.toggle('scrolled', window.scrollY > 60);
        };
        window.addEventListener('scroll', onScroll);
        onScroll();

        // Hero background slideshow — ganti gambar setiap 2 detik
        const slides = document.querySelectorAll('.hero-slide');
        if (slides.length > 1) {
            let current = 0;
            setInterval(() => {
                slides[current].classList.remove('active');
                current = (current + 1) % slides.length;
                slides[current].classList.add('active');
            }, 2000);
        }

        // Scroll-reveal animations
        const revealEls = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window && revealEls.length) {
            revealEls.forEach(el => el.classList.add('reveal-armed'));
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });
            revealEls.forEach(el => observer.observe(el));
        }

        // ============================================
        // CHAT FUNCTIONALITY
        // ============================================
        const deraFab = document.getElementById('deraFab');
        const chatOverlay = document.getElementById('chatOverlay');
        const chatClose = document.getElementById('chatClose');
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const chatSend = document.getElementById('chatSend');

        // Open chat
        function openChat() {
            chatOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            chatInput.focus();
        }

        // Close chat
        function closeChat() {
            chatOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        deraFab.addEventListener('click', openChat);
        chatClose.addEventListener('click', closeChat);
        chatOverlay.addEventListener('click', function(e) {
            if (e.target === this) closeChat();
        });

        // Close with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && chatOverlay.classList.contains('active')) {
                closeChat();
            }
        });

        // Send message
        async function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;

            // Add user message
            addMessage(message, 'user');
            chatInput.value = '';
            chatInput.focus();

            // Show typing indicator
            const typingId = showTyping();

            try {
                // Call deraAI API
                const response = await fetch('deraAI/index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();
                removeTyping(typingId);

                if (data.success) {
                    addMessage(data.reply, 'bot');
                } else {
                    addMessage('Maaf, terjadi kesalahan. Silakan coba lagi.', 'bot');
                }
            } catch (error) {
                removeTyping(typingId);
                addMessage('Maaf, terjadi kesalahan koneksi. Silakan coba lagi.', 'bot');
                console.error('Error:', error);
            }
        }

        function addMessage(text, sender) {
            const div = document.createElement('div');
            div.className = `message ${sender}`;
            div.textContent = text;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showTyping() {
            const div = document.createElement('div');
            div.className = 'message typing';
            div.id = 'typing-' + Date.now();
            div.innerHTML = `
                <span>Perawat AI sedang mengetik</span>
                <div class="typing-dots">
                    <span></span><span></span><span></span>
                </div>
            `;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return div.id;
        }

        function removeTyping(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        // Event listeners for send
        chatSend.addEventListener('click', sendMessage);
        chatInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>
</html>