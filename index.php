<?php
require_once 'config/database.php';
session_start();

$db = new Database();
$conn = $db->getConnection();

$query = "SELECT o.*, u.nom, u.prenom, e.nom_entreprise 
          FROM offres o 
          JOIN utilisateurs u ON o.recruteur_id = u.id 
          LEFT JOIN entreprises e ON u.id = e.utilisateur_id 
          WHERE o.est_active = TRUE 
          ORDER BY o.date_publication DESC 
          LIMIT 6";
$stmt = $conn->prepare($query);
$stmt->execute();
$offres = $stmt->fetchAll();

$stats_query = "SELECT 
    (SELECT COUNT(*) FROM utilisateurs WHERE role = 'candidat') as candidats,
    (SELECT COUNT(*) FROM utilisateurs WHERE role = 'recruteur') as recruteurs,
    (SELECT COUNT(*) FROM offres WHERE est_active = TRUE) as offres,
    (SELECT COUNT(*) FROM candidatures) as candidatures";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Recrutement Djibouti — Plateforme Officielle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ink: #0a0f1e;
            --ink-soft: #1e2840;
            --azure: #1a56ff;
            --azure-light: #3b74ff;
            --azure-pale: #e8eeff;
            --gold: #f0a500;
            --gold-light: #ffd066;
            --surface: #f7f8fc;
            --white: #ffffff;
            --muted: #6b7a99;
            --border: #e2e6f0;
            --radius: 16px;
            --radius-lg: 28px;
            --shadow: 0 4px 24px rgba(10,15,30,0.08);
            --shadow-lg: 0 16px 64px rgba(10,15,30,0.14);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            color: var(--ink);
            overflow-x: hidden;
        }

        /* ─── MOBILE MENU ─── */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--ink);
            cursor: pointer;
            padding: 8px;
            margin-left: auto;
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 320px;
            height: 100vh;
            background: var(--white);
            z-index: 2000;
            box-shadow: -4px 0 32px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .mobile-menu.open {
            right: 0;
        }

        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .mobile-menu-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--muted);
        }

        .mobile-menu-links {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .mobile-menu-links a {
            text-decoration: none;
            color: var(--ink);
            font-size: 16px;
            font-weight: 500;
            padding: 8px 0;
        }

        .mobile-menu-cta {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: auto;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1999;
            display: none;
        }

        .mobile-overlay.open {
            display: block;
        }

        /* ─── NAVBAR ─── */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 0 24px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226,230,240,0.6);
            transition: box-shadow 0.3s;
        }

        @media (min-width: 1025px) {
            .navbar {
                padding: 0 48px;
            }
        }

        .navbar.scrolled { box-shadow: 0 2px 32px rgba(10,15,30,0.1); }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: var(--azure);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .brand-text {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: var(--ink);
            letter-spacing: -0.3px;
        }

        .brand-text span { color: var(--azure); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 1024px) {
            .nav-links {
                display: none;
            }
            .mobile-menu-toggle {
                display: block;
            }
        }

        .nav-links a {
            text-decoration: none;
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .nav-links a:hover { color: var(--ink); background: var(--surface); }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 640px) {
            .nav-cta .btn-ghost {
                display: none;
            }
        }

        .btn-ghost {
            padding: 8px 18px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: transparent;
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-ghost:hover { border-color: var(--azure); color: var(--azure); }

        .btn-primary {
            padding: 8px 18px;
            border-radius: 10px;
            border: none;
            background: var(--azure);
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary:hover {
            background: var(--azure-light);
            transform: translateY(-1px);
        }

        /* ─── HERO ─── */
        .hero {
            min-height: 100vh;
            padding-top: 72px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 1024px) {
            .hero {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .hero-right {
                display: none;
            }
        }

        .hero-left {
            padding: 60px 24px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        @media (min-width: 1025px) {
            .hero-left {
                padding: 100px 48px 80px 80px;
            }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--azure-pale);
            color: var(--azure);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
            margin-bottom: 24px;
            width: fit-content;
            border: 1px solid rgba(26,86,255,0.2);
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            background: var(--azure);
            border-radius: 50%;
            animation: blink 1.5s ease infinite;
        }

        @keyframes blink {
            0%,100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .hero-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(2rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            color: var(--ink);
            letter-spacing: -1px;
            margin-bottom: 20px;
        }

        .hero-title .accent {
            color: var(--azure);
            position: relative;
        }

        .hero-title .accent::after {
            content: '';
            position: absolute;
            bottom: 2px; left: 0; right: 0;
            height: 3px;
            background: var(--gold);
            border-radius: 2px;
        }

        .hero-desc {
            font-size: 16px;
            line-height: 1.6;
            color: var(--muted);
            max-width: 460px;
            margin-bottom: 32px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 40px;
        }

        @media (max-width: 480px) {
            .hero-actions {
                flex-direction: column;
            }
            .hero-actions a {
                text-align: center;
                justify-content: center;
            }
        }

        .btn-hero-primary {
            padding: 12px 28px;
            border-radius: 12px;
            border: none;
            background: var(--azure);
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-hero-secondary {
            padding: 12px 28px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .hero-trust {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .trust-avatars {
            display: flex;
        }

        .trust-avatars span {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid white;
            margin-right: -10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }

        .trust-text {
            font-size: 12px;
            color: var(--muted);
        }

        .hero-right {
            position: relative;
            overflow: hidden;
            background: linear-gradient(145deg, var(--ink) 0%, var(--ink-soft) 100%);
        }

        .hero-right-content {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .hero-graphic {
            position: relative;
            width: 100%;
            max-width: 380px;
        }

        .hero-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: var(--radius-lg);
            padding: 20px;
            color: white;
            margin-bottom: 14px;
            animation: floatCard 6s ease-in-out infinite;
        }

        .hero-card:nth-child(2) {
            margin-left: 30px;
            animation-delay: -3s;
        }

        @keyframes floatCard {
            0%,100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .hero-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .hero-card-title {
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: 14px;
        }

        .hero-card-badge {
            background: rgba(26,86,255,0.4);
            color: #88aaff;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 50px;
        }

        .hero-card-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            margin-bottom: 4px;
        }

        .hero-card-salary {
            font-family: 'Sora', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--gold-light);
            margin-top: 10px;
        }

        /* ─── STATS ─── */
        .stats-section {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .stats-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }

        @media (max-width: 768px) {
            .stats-inner {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .stat-item {
            padding: 32px 20px;
            border-right: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: background 0.2s;
        }

        @media (max-width: 480px) {
            .stat-item {
                padding: 24px 16px;
                gap: 12px;
            }
        }

        .stat-item:nth-child(2) { border-right: 1px solid var(--border); }
        .stat-item:nth-child(4) { border-right: none; }

        @media (max-width: 768px) {
            .stat-item:nth-child(2) { border-right: none; }
        }

        .stat-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        @media (max-width: 480px) {
            .stat-icon-wrap {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }
        }

        .stat-num {
            font-family: 'Sora', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -1px;
            line-height: 1;
        }

        @media (max-width: 480px) {
            .stat-num {
                font-size: 1.3rem;
            }
        }

        .stat-label {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
        }

        /* ─── SECTION ─── */
        .section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 64px 20px;
        }

        @media (min-width: 1025px) {
            .section {
                padding: 96px 48px;
            }
        }

        .section-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 20px;
        }

        .section-kicker {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            color: var(--azure);
            margin-bottom: 8px;
        }

        .section-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.6px;
            line-height: 1.2;
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: transparent;
            color: var(--ink);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        /* ─── OFFER CARDS ─── */
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        @media (max-width: 640px) {
            .offers-grid {
                grid-template-columns: 1fr;
            }
        }

        .offer-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1.5px solid var(--border);
            transition: all 0.3s cubic-bezier(0.2,0,0,1);
            position: relative;
            overflow: hidden;
        }

        .offer-card:hover {
            border-color: transparent;
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .offer-company-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .company-logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--azure-pale);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--azure);
            font-weight: 700;
            font-size: 16px;
        }

        .badge-tag {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 50px;
        }

        .badge-tag.cdi { background: rgba(16,185,129,0.1); color: #059669; }
        .badge-tag.cdd { background: rgba(245,158,11,0.1); color: #d97706; }
        .badge-tag.stage { background: rgba(139,92,246,0.1); color: #7c3aed; }
        .badge-tag.urgent { background: rgba(239,68,68,0.1); color: #dc2626; }

        .offer-title {
            font-family: 'Sora', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 12px;
        }

        .offer-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .offer-meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .offer-meta-row i {
            width: 16px;
            color: var(--azure);
            font-size: 12px;
        }

        .offer-salary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }

        .salary-amount {
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
        }

        .btn-apply {
            padding: 7px 16px;
            border-radius: 8px;
            border: none;
            background: var(--azure-pale);
            color: var(--azure);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* ─── FEATURES ─── */
        .features-section {
            background: var(--ink);
        }

        .features-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 64px 20px;
        }

        @media (min-width: 1025px) {
            .features-inner {
                padding: 96px 48px;
            }
        }

        .features-top {
            text-align: center;
            margin-bottom: 48px;
        }

        .features-kicker {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--azure-light);
            margin-bottom: 12px;
        }

        .features-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.6rem, 4vw, 2.8rem);
            font-weight: 800;
            color: white;
            letter-spacing: -0.8px;
            max-width: 560px;
            margin: 0 auto;
            line-height: 1.15;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1px;
            background: rgba(255,255,255,0.06);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .feature-item {
            background: var(--ink-soft);
            padding: 32px 24px;
            transition: background 0.3s;
        }

        @media (min-width: 768px) {
            .feature-item {
                padding: 44px 36px;
            }
        }

        .feature-num {
            font-family: 'Sora', sans-serif;
            font-size: 40px;
            font-weight: 800;
            color: rgba(26,86,255,0.2);
            line-height: 1;
            margin-bottom: 16px;
        }

        .feature-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(26,86,255,0.15);
            border: 1px solid rgba(26,86,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--azure-light);
            font-size: 20px;
            margin-bottom: 20px;
        }

        .feature-name {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: 13px;
            line-height: 1.6;
            color: rgba(255,255,255,0.5);
        }

        /* ─── TESTIMONIALS ─── */
        .testimonials-section { background: var(--surface); }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .testimonial-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1.5px solid var(--border);
            transition: all 0.3s;
            position: relative;
        }

        .testimonial-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .stars {
            display: flex;
            gap: 3px;
            margin-bottom: 16px;
        }

        .stars i { color: var(--gold); font-size: 12px; }

        .testimonial-quote {
            font-size: 14px;
            line-height: 1.7;
            color: var(--ink-soft);
            margin-bottom: 20px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .author-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--ink);
        }

        .author-role {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }

        .big-quote {
            position: absolute;
            top: 20px;
            right: 24px;
            font-size: 48px;
            line-height: 1;
            color: rgba(26,86,255,0.06);
            font-family: Georgia, serif;
        }

        /* ─── CTA ─── */
        .cta-section-wrap {
            background: var(--white);
        }

        .cta-block {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }

        @media (min-width: 1025px) {
            .cta-block {
                padding: 48px 48px 96px;
            }
        }

        .cta-inner {
            background: linear-gradient(135deg, var(--ink) 0%, var(--ink-soft) 100%);
            border-radius: 28px;
            padding: 48px 32px;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 32px;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .cta-inner {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 40px 24px;
            }
        }

        .cta-kicker {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            color: var(--azure-light);
            margin-bottom: 12px;
        }

        .cta-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.4rem, 4vw, 2.6rem);
            font-weight: 800;
            color: white;
            letter-spacing: -0.8px;
            line-height: 1.15;
            margin-bottom: 12px;
        }

        .cta-sub {
            font-size: 14px;
            color: rgba(255,255,255,0.55);
            line-height: 1.6;
            max-width: 420px;
        }

        @media (max-width: 768px) {
            .cta-sub {
                max-width: 100%;
            }
        }

        .btn-cta-primary {
            padding: 12px 32px;
            border-radius: 12px;
            border: none;
            background: var(--azure);
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        @media (max-width: 480px) {
            .btn-cta-primary {
                white-space: normal;
                padding: 12px 24px;
            }
        }

        .btn-cta-secondary {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }

        /* ─── FOOTER ─── */
        .footer {
            background: var(--ink);
            color: white;
            padding: 48px 0 0;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 32px;
        }

        @media (min-width: 1025px) {
            .footer-inner {
                padding: 0 48px 48px;
                grid-template-columns: 2fr 1fr 1fr 1fr;
            }
        }

        .footer-brand p {
            font-size: 13px;
            line-height: 1.6;
            color: rgba(255,255,255,0.4);
            margin-top: 14px;
            max-width: 260px;
        }

        .footer-social {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .social-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }

        .footer-col h6 {
            font-family: 'Sora', sans-serif;
            font-size: 12px;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            margin-bottom: 16px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 8px;
        }

        .footer-col ul li a {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            margin-bottom: 8px;
        }

        .footer-contact-item i { color: var(--azure-light); font-size: 12px; width: 14px; }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.07);
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .footer-bottom {
                flex-direction: column;
                padding: 20px 16px;
            }
        }

        .footer-bottom p {
            font-size: 11px;
            color: rgba(255,255,255,0.3);
        }

        /* ─── ANIMATIONS ─── */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 48px 0;
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            background: var(--surface);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 24px;
            color: var(--muted);
        }
    </style>
</head>
<body>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="brand-text" style="font-size: 18px;">Recrutement <span style="color: var(--azure);">Djibouti</span></div>
            <button class="mobile-menu-close" onclick="closeMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mobile-menu-links">
            <a href="#">Accueil</a>
            <a href="pages/candidat/opportunites.php">Offres d'emploi</a>
            <a href="#">Entreprises</a>
            <a href="#">À propos</a>
        </div>
        <div class="mobile-menu-cta">
            <a href="connexion.php" class="btn-ghost" style="text-align: center;">Se connecter</a>
            <a href="inscription.php" class="btn-primary" style="text-align: center; justify-content: center;">
                <i class="fas fa-user-plus"></i> Créer un compte
            </a>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <a href="#" class="nav-brand">
            <div class="brand-icon"><i class="fas fa-briefcase"></i></div>
            <div class="brand-text">Recrutement <span>Djibouti</span></div>
        </a>
        <div class="nav-links">
            <a href="#">Accueil</a>
            <a href="pages/candidat/opportunites.php">Offres d'emploi</a>
            <a href="#">Entreprises</a>
            <a href="#">À propos</a>
        </div>
        <div class="nav-cta">
            <a href="connexion.php" class="btn-ghost">Se connecter</a>
            <a href="inscription.php" class="btn-primary"><i class="fas fa-user-plus"></i> Créer un compte</a>
            <button class="mobile-menu-toggle" onclick="openMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-left">
            <div class="hero-badge">
                <div class="badge-dot"></div>
                Plateforme officielle · Djibouti
            </div>
            <h1 class="hero-title">
                Trouvez l'emploi<br>qui vous <span class="accent">correspond</span>
            </h1>
            <p class="hero-desc">
                La première plateforme de recrutement intelligente à Djibouti. Connectez vos compétences aux meilleures entreprises en quelques clics.
            </p>
            <div class="hero-actions">
                <a href="inscription.php" class="btn-hero-primary">
                    <i class="fas fa-rocket"></i> Commencer gratuitement
                </a>
                <a href="pages/candidat/opportunites.php" class="btn-hero-secondary">
                    <i class="fas fa-search"></i> Voir les offres
                </a>
            </div>
            <div class="hero-trust">
                <div class="trust-avatars">
                    <span style="background:#2563eb;">FA</span>
                    <span style="background:#0ea5e9;">AH</span>
                    <span style="background:#8b5cf6;">MS</span>
                    <span style="background:#10b981;">KI</span>
                </div>
                <div class="trust-text">
                    <strong><?php echo number_format($stats['candidats']); ?>+ candidats</strong> nous font déjà confiance
                </div>
            </div>
        </div>
        <div class="hero-right">
            <div class="hero-right-content">
                <div class="hero-graphic">
                    <div class="hero-card">
                        <div class="hero-card-header">
                            <div class="hero-card-title">Responsable Marketing</div>
                            <div class="hero-card-badge urgent"><i class="fas fa-fire me-1"></i> Urgent</div>
                        </div>
                        <div class="hero-card-row"><i class="fas fa-building"></i> Daallo Airlines</div>
                        <div class="hero-card-row"><i class="fas fa-map-marker-alt"></i> Djibouti-Ville</div>
                        <div class="hero-card-salary">350 000 – 480 000 <span style="font-size:12px;">FDJ</span></div>
                    </div>
                    <div class="hero-card">
                        <div class="hero-card-header">
                            <div class="hero-card-title">Développeur Full Stack</div>
                            <div class="hero-card-badge">CDI</div>
                        </div>
                        <div class="hero-card-row"><i class="fas fa-building"></i> Port de Djibouti</div>
                        <div class="hero-card-row"><i class="fas fa-map-marker-alt"></i> Djibouti-Ville</div>
                        <div class="hero-card-salary">400 000 – 600 000 <span style="font-size:12px;">FDJ</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="stats-inner">
            <div class="stat-item reveal">
                <div class="stat-icon-wrap blue"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?php echo number_format($stats['candidats']); ?></div>
                    <div class="stat-label">Candidats inscrits</div>
                </div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-icon-wrap gold"><i class="fas fa-building"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?php echo number_format($stats['recruteurs']); ?></div>
                    <div class="stat-label">Entreprises partenaires</div>
                </div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-icon-wrap green"><i class="fas fa-briefcase"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?php echo number_format($stats['offres']); ?></div>
                    <div class="stat-label">Offres d'emploi actives</div>
                </div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-icon-wrap purple"><i class="fas fa-file-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?php echo number_format($stats['candidatures']); ?></div>
                    <div class="stat-label">Candidatures envoyées</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Offres Section -->
    <div class="section">
        <div class="section-header reveal">
            <div>
                <div class="section-kicker">Opportunités récentes</div>
                <h2 class="section-title">Dernières offres <span>disponibles</span></h2>
            </div>
            <a href="pages/candidat/opportunites.php" class="btn-outline">
                Voir toutes les offres <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="offers-grid">
            <?php foreach ($offres as $index => $offre):
                $company_name = $offre['nom_entreprise'] ?: ($offre['nom'] . ' ' . $offre['prenom']);
                $initials = mb_strtoupper(mb_substr($company_name, 0, 2));
                $contract_colors = ['CDI'=>'cdi','CDD'=>'cdd','Stage'=>'stage','Alternance'=>'stage'];
                $badge_class = $contract_colors[$offre['type_contrat']] ?? 'cdi';
            ?>
            <div class="offer-card reveal" style="transition-delay: <?php echo $index * 0.08; ?>s">
                <div class="offer-company-row">
                    <div class="company-logo"><?php echo $initials; ?></div>
                    <?php if ($offre['est_urgent']): ?>
                        <span class="badge-tag urgent"><i class="fas fa-fire"></i> Urgent</span>
                    <?php else: ?>
                        <span class="badge-tag <?php echo $badge_class; ?>"><?php echo htmlspecialchars($offre['type_contrat']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="offer-title"><?php echo htmlspecialchars($offre['titre']); ?></div>
                <div class="offer-meta">
                    <div class="offer-meta-row">
                        <i class="fas fa-building"></i>
                        <?php echo htmlspecialchars($company_name); ?>
                    </div>
                    <div class="offer-meta-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($offre['localisation'] ?: 'Djibouti'); ?>
                    </div>
                    <div class="offer-meta-row">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($offre['type_contrat']); ?>
                    </div>
                </div>
                <div class="offer-salary">
                    <div>
                        <div class="salary-amount">
                            <?php echo number_format($offre['salaire_min'], 0, ',', ' '); ?> – <?php echo number_format($offre['salaire_max'], 0, ',', ' '); ?>
                            <span class="salary-currency">FDJ</span>
                        </div>
                    </div>
                    <a href="pages/candidat/postuler.php?id=<?php echo $offre['id']; ?>" class="btn-apply">
                        Postuler <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($offres)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                <p style="color:var(--muted);">Aucune offre pour le moment</p>
                <p style="color:var(--muted);font-size:13px;margin-top:6px;">Revenez bientôt pour découvrir de nouvelles opportunités.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Features Section -->
    <section class="features-section">
        <div class="features-inner">
            <div class="features-top reveal">
                <div class="features-kicker">Nos avantages</div>
                <h2 class="features-title">Pourquoi choisir <span>Recrutement Djibouti</span>&nbsp;?</h2>
            </div>
            <div class="features-grid">
                <div class="feature-item reveal">
                    <div class="feature-num">01</div>
                    <div class="feature-icon-box"><i class="fas fa-robot"></i></div>
                    <div class="feature-name">IA de Matching</div>
                    <div class="feature-desc">Notre intelligence artificielle analyse vos compétences et les compare aux offres en temps réel.</div>
                </div>
                <div class="feature-item reveal" style="transition-delay:0.1s">
                    <div class="feature-num">02</div>
                    <div class="feature-icon-box"><i class="fas fa-shield-check"></i></div>
                    <div class="feature-name">Recruteurs vérifiés</div>
                    <div class="feature-desc">Chaque entreprise est validée par des documents officiels. Zéro arnaque, sécurité totale.</div>
                </div>
                <div class="feature-item reveal" style="transition-delay:0.2s">
                    <div class="feature-num">03</div>
                    <div class="feature-icon-box"><i class="fas fa-bolt"></i></div>
                    <div class="feature-name">Postulez en 1 clic</div>
                    <div class="feature-desc">Votre profil complet, disponible instantanément. Postulez sans ressaisir vos informations.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <div class="section testimonials-section">
        <div class="section-header reveal">
            <div>
                <div class="section-kicker">Témoignages</div>
                <h2 class="section-title">Ils nous font <span>confiance</span></h2>
            </div>
        </div>
        <div class="testimonials-grid">
            <div class="testimonial-card reveal">
                <div class="big-quote">"</div>
                <div class="stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-quote">Grâce à cette plateforme, j'ai trouvé un emploi en moins d'une semaine. Le matching est incroyablement précis.</p>
                <div class="testimonial-author">
                    <div class="author-avatar" style="background: linear-gradient(135deg, #1a56ff, #6366f1);">FA</div>
                    <div>
                        <div class="author-name">Fatouma A.</div>
                        <div class="author-role">Chargée de communication</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card reveal" style="transition-delay:0.1s">
                <div class="big-quote">"</div>
                <div class="stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-quote">En tant que recruteur, nous recevons des candidatures de qualité. L'IA nous fait gagner un temps précieux.</p>
                <div class="testimonial-author">
                    <div class="author-avatar" style="background: linear-gradient(135deg, #f0a500, #ef4444);">AH</div>
                    <div>
                        <div class="author-name">Ahmed H.</div>
                        <div class="author-role">DRH, Daallo Airlines</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card reveal" style="transition-delay:0.2s">
                <div class="big-quote">"</div>
                <div class="stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p class="testimonial-quote">La vérification des recruteurs rassure vraiment. Une plateforme fiable et professionnelle.</p>
                <div class="testimonial-author">
                    <div class="author-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">MS</div>
                    <div>
                        <div class="author-name">Mohamed S.</div>
                        <div class="author-role">Ingénieur logistique</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section-wrap">
        <div class="cta-block">
            <div class="cta-inner reveal">
                <div>
                    <div class="cta-kicker">Rejoignez la communauté</div>
                    <div class="cta-title">Prêt à démarrer votre<br>parcours professionnel ?</div>
                    <p class="cta-sub">Créez votre compte gratuitement et accédez aux meilleures opportunités à Djibouti dès aujourd'hui.</p>
                </div>
                <div>
                    <a href="inscription.php" class="btn-cta-primary">
                        <i class="fas fa-user-plus"></i> Créer mon compte gratuit
                    </a>
                    <div class="cta-sub" style="margin-top: 12px;">
                        <a href="connexion.php" class="btn-cta-secondary">J'ai déjà un compte →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="#" class="nav-brand" style="display:inline-flex;">
                    <div class="brand-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="brand-text" style="color:white;">Recrutement <span>Djibouti</span></div>
                </a>
                <p>La plateforme officielle de recrutement à Djibouti. Connectez talents et entreprises efficacement.</p>
                <div class="footer-social">
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h6>Plateforme</h6>
                <ul>
                    <li><a href="#">À propos</a></li>
                    <li><a href="pages/candidat/opportunites.php">Offres d'emploi</a></li>
                    <li><a href="#">Entreprises</a></li>
                    <li><a href="#">Blog RH</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Légal</h6>
                <ul>
                    <li><a href="#">Conditions d'utilisation</a></li>
                    <li><a href="#">Confidentialité</a></li>
                    <li><a href="#">Cookies</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Contact</h6>
                <div class="footer-contact-item"><i class="fas fa-envelope"></i> contact@recrutement.dj</div>
                <div class="footer-contact-item"><i class="fas fa-phone"></i> +253 77 00 00 00</div>
                <div class="footer-contact-item"><i class="fas fa-map-marker-alt"></i> Djibouti-Ville, DJ</div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2024 Recrutement Djibouti — Tous droits réservés.</p>
            <p>Conçu & développé à Djibouti 🇩🇯</p>
        </div>
    </footer>

    <script>
        // Navbar scroll
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        });

        // Scroll reveal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // Mobile menu functions
        function openMobileMenu() {
            document.getElementById('mobileMenu').classList.add('open');
            document.getElementById('mobileOverlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            document.getElementById('mobileMenu').classList.remove('open');
            document.getElementById('mobileOverlay').classList.remove('open');
            document.body.style.overflow = '';
        }

        // Close menu when clicking overlay
        document.getElementById('mobileOverlay').addEventListener('click', closeMobileMenu);
    </script>
</body>
</html>