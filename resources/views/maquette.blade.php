<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>RideReady by Michelin — Preview UI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <style>
    /* ============================================
       CHARTE MICHELIN — DESIGN TOKENS
       Source: Charte de Communication Michelin 2025
    ============================================ */
    :root {
      /* Couleurs principales (p.15) */
      --michelin-blue:    #27509B;   /* Pantone Reflex Blue */
      --michelin-yellow:  #FCE500;   /* Pantone Yellow */
      --michelin-white:   #FFFFFF;
      --michelin-black:   #000000;

      /* Couleurs secondaires (p.16) */
      --michelin-blue-dk: #00205B;   /* Bleu Foncé Michelin */
      --michelin-grey:    #53565A;   /* Gris Responsable */

      /* Couleurs fonctionnelles UI */
      --ui-ok:      #22C55E;
      --ui-warn:    #F97316;
      --ui-strava:  #FC4C02;

      /* Typographie (p.14) — Noto Sans = fallback web officiel Michelin */
      --font:  'Noto Sans', 'Open Sans', sans-serif;

      --sp-sm: 8px; --sp-md: 16px; --sp-lg: 24px; --sp-xl: 32px;
      --r-sm: 4px; --r-md: 8px; --r-lg: 16px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0a0a0a; font-family: var(--font); -webkit-font-smoothing: antialiased; }

    /* ============ BARRE DÉMO ============ */
    .demo-nav {
      position: fixed; top: 0; left: 0; right: 0;
      background: var(--michelin-blue-dk);
      border-bottom: 3px solid var(--michelin-yellow);
      display: flex; gap: 4px; padding: 8px 12px;
      z-index: 999; overflow-x: auto; scrollbar-width: none;
    }
    .demo-nav::-webkit-scrollbar { display: none; }
    .demo-label {
      color: var(--michelin-yellow); font-size: 11px; font-weight: 800;
      letter-spacing: 0.1em; text-transform: uppercase;
      display: flex; align-items: center; padding: 0 8px 0 4px; flex-shrink: 0;
    }
    .demo-btn {
      flex-shrink: 0; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.65);
      border: 1px solid rgba(255,255,255,0.15); border-radius: var(--r-sm);
      padding: 6px 12px; font-size: 11px; font-weight: 600; cursor: pointer;
      transition: all 0.15s; white-space: nowrap; font-family: var(--font);
    }
    .demo-btn:hover { background: rgba(255,255,255,0.15); color: white; }
    .demo-btn.active { background: var(--michelin-yellow); color: var(--michelin-blue-dk); border-color: var(--michelin-yellow); font-weight: 800; }

    /* ============ CADRE TÉLÉPHONE ============ */
    .screens-wrapper { display: flex; justify-content: center; padding: 60px 16px 40px; min-height: 100vh; }
    .phone-frame {
      width: 375px; min-height: 812px; background: var(--michelin-white);
      border-radius: 24px; overflow: hidden;
      box-shadow: 0 40px 100px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.1);
    }

    /* ============ ÉCRANS ============ */
    .screen { display: none; flex-direction: column; height: 812px; background: var(--michelin-white); }
    .screen.active { display: flex; }

    /* ============ TOPBAR ============ */
    .topbar {
      height: 56px; background: var(--michelin-blue);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 16px; flex-shrink: 0;
    }
    .topbar-cart {
      position: relative; width: 40px; height: 40px;
      display: flex; align-items: center; justify-content: center;
      color: white; cursor: pointer; border-radius: var(--r-sm);
    }
    .topbar-cart svg { width: 22px; height: 22px; }
    .cart-badge {
      position: absolute; top: 4px; right: 4px;
      background: var(--michelin-yellow); color: var(--michelin-blue-dk);
      font-size: 9px; font-weight: 800; width: 16px; height: 16px;
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
    }

    /* ============ BOTTOM NAV ============ */
    .bottom-nav {
      height: 64px; background: var(--michelin-blue);
      display: flex; align-items: stretch; flex-shrink: 0;
    }
    .nav-item {
      flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: 3px; color: rgba(255,255,255,0.45); cursor: pointer; position: relative; transition: color 0.15s;
    }
    .nav-item.active { color: var(--michelin-yellow); }
    .nav-item.active::before {
      content: ''; position: absolute; top: 0; left: 15%; right: 15%;
      height: 3px; background: var(--michelin-yellow);
    }
    .nav-item svg { width: 22px; height: 22px; }
    .nav-label { font-size: 10px; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; }
    .nav-alert-dot {
      position: absolute; top: 8px; right: calc(50% - 18px);
      background: #DC2626; color: white; font-size: 8px; font-weight: 800;
      width: 14px; height: 14px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }

    /* ============ CONTENU SCROLLABLE ============ */
    .screen-content { flex: 1; overflow-y: auto; scrollbar-width: none; background: var(--michelin-white); }
    .screen-content::-webkit-scrollbar { display: none; }

    /* ============ HEADER DE SECTION (fond bleu) ============ */
    .section-header { background: var(--michelin-blue); padding: 20px var(--sp-lg) 18px; color: white; }
    .section-header-label { font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--michelin-yellow); margin-bottom: 4px; }
    .section-header-title { font-size: 26px; font-weight: 800; color: white; line-height: 1.1; text-transform: uppercase; }
    .section-header-sub { font-size: 13px; color: rgba(255,255,255,0.7); margin-top: 4px; }

    /* ============ CORPS DE PAGE ============ */
    .page-body { padding: var(--sp-lg); display: flex; flex-direction: column; gap: var(--sp-md); }

    /* ============ CARTES ============ */
    .card {
      background: white; border-radius: var(--r-lg);
      border: 1.5px solid rgba(39,80,155,0.12); padding: var(--sp-lg);
      box-shadow: 0 2px 8px rgba(39,80,155,0.06);
    }
    .card-blue { background: var(--michelin-blue); border-radius: var(--r-lg); padding: var(--sp-lg); color: white; }
    .card-yellow { background: var(--michelin-yellow); border-radius: var(--r-lg); padding: var(--sp-lg); color: var(--michelin-blue-dk); }

    /* ============ BOUTONS ============ */
    .btn-primary {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      background: var(--michelin-yellow); color: var(--michelin-blue-dk);
      font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em;
      padding: 0 24px; border-radius: var(--r-sm); border: none; cursor: pointer;
      height: 52px; width: 100%; font-family: var(--font);
    }
    .btn-secondary {
      display: flex; align-items: center; justify-content: center;
      background: white; color: var(--michelin-blue); font-weight: 700; font-size: 13px;
      text-transform: uppercase; letter-spacing: 0.04em; padding: 0 24px;
      border-radius: var(--r-sm); border: 2px solid var(--michelin-blue);
      cursor: pointer; height: 48px; width: 100%; font-family: var(--font);
    }
    .btn-strava {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      background: var(--ui-strava); color: white; font-weight: 700; font-size: 16px;
      padding: 0 24px; border-radius: var(--r-sm); border: none; cursor: pointer;
      height: 56px; width: 100%; font-family: var(--font);
    }

    /* ============ BADGES ============ */
    .badge-source {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 10px; font-weight: 700; color: var(--michelin-blue);
      background: rgba(39,80,155,0.08); border: 1px solid rgba(39,80,155,0.2);
      padding: 2px 8px; border-radius: var(--r-sm);
      letter-spacing: 0.04em; text-transform: uppercase;
    }
    .badge-rider {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--michelin-blue); color: var(--michelin-yellow);
      font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em;
      padding: 10px 16px; border-radius: var(--r-sm); width: 100%;
    }
    .badge-reco-tag {
      display: inline-block; background: var(--michelin-blue); color: var(--michelin-yellow);
      font-size: 10px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase;
      padding: 4px 10px; border-radius: var(--r-sm); margin-bottom: 12px;
    }

    /* ============ LIGNES DE PROFIL ============ */
    .profile-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid rgba(39,80,155,0.08); }
    .profile-row:last-child { border-bottom: none; }
    .profile-key { font-size: 13px; color: var(--michelin-grey); }
    .profile-val { font-size: 13px; font-weight: 700; color: var(--michelin-blue-dk); }

    /* ============ ALERTE BANNER ============ */
    .alert-banner { display: flex; align-items: center; justify-content: space-between; background: var(--ui-warn); border-radius: var(--r-md); padding: 12px 16px; gap: 10px; }
    .alert-banner-left { display: flex; align-items: flex-start; gap: 8px; flex: 1; }
    .alert-banner-left svg { width: 18px; height: 18px; color: white; flex-shrink: 0; margin-top: 1px; }
    .alert-banner-title { font-size: 12px; font-weight: 800; color: white; text-transform: uppercase; letter-spacing: 0.04em; }
    .alert-banner-sub { font-size: 11px; color: rgba(255,255,255,0.85); margin-top: 2px; }
    .alert-banner-cta { font-size: 12px; font-weight: 800; color: white; text-decoration: underline; white-space: nowrap; cursor: pointer; text-transform: uppercase; letter-spacing: 0.04em; }

    /* ============ JAUGES ============ */
    .tires-row { display: flex; gap: 10px; }
    .gauge-card {
      flex: 1; background: white; border: 1.5px solid rgba(39,80,155,0.15);
      border-radius: var(--r-lg); padding: 14px 10px;
      display: flex; flex-direction: column; align-items: center; gap: 2px;
      box-shadow: 0 2px 8px rgba(39,80,155,0.06);
    }
    .gauge-pos { font-size: 10px; font-weight: 800; letter-spacing: 0.1em; color: var(--michelin-blue); text-transform: uppercase; }
    .gauge-km { font-size: 13px; font-weight: 700; color: var(--michelin-blue-dk); }
    .gauge-status { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }

    /* ============ TABLEAU COMPARATIF ============ */
    .compare-wrap { background: white; border-radius: var(--r-lg); border: 1.5px solid rgba(39,80,155,0.15); overflow: hidden; box-shadow: 0 2px 8px rgba(39,80,155,0.06); }
    .compare-header { background: var(--michelin-blue); padding: 12px 14px 10px; }
    .compare-header-title { font-size: 13px; font-weight: 800; color: var(--michelin-yellow); text-transform: uppercase; letter-spacing: 0.06em; }
    .compare-table { width: 100%; border-collapse: collapse; }
    .compare-table th { font-size: 10px; font-weight: 800; text-align: right; padding: 8px 12px 6px; border-bottom: 2px solid rgba(39,80,155,0.1); color: var(--michelin-grey); text-transform: uppercase; letter-spacing: 0.06em; }
    .compare-table th:first-child { text-align: left; }
    .th-reco { color: var(--michelin-blue) !important; }
    .compare-table td { padding: 9px 12px; border-bottom: 1px solid rgba(39,80,155,0.06); vertical-align: top; }
    .compare-table tr:last-child td { border-bottom: none; }
    .td-label { font-size: 12px; color: var(--michelin-blue-dk); font-weight: 600; }
    .td-src { display: block; font-size: 9px; color: var(--michelin-grey); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.04em; }
    .td-val { font-size: 14px; font-weight: 700; text-align: right; color: var(--michelin-grey); }
    .td-reco { color: var(--michelin-blue-dk); }
    .td-better { color: var(--ui-ok); }
    .td-delta { display: block; font-size: 10px; font-weight: 700; color: var(--ui-ok); text-align: right; }

    /* ============ PANIER ============ */
    .cart-row { display: flex; gap: 14px; align-items: flex-start; }
    .cart-img { width: 72px; height: 72px; background: var(--michelin-blue); border-radius: var(--r-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cart-name { font-size: 15px; font-weight: 800; color: var(--michelin-blue-dk); text-transform: uppercase; }
    .cart-specs { font-size: 12px; color: var(--michelin-grey); margin-top: 2px; }
    .cart-price { font-size: 22px; font-weight: 800; color: var(--michelin-blue); margin-top: 6px; }
    .stock-row { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--ui-ok); }
    .stock-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--ui-ok); flex-shrink: 0; }
    .delivery-sub { font-size: 11px; color: var(--michelin-grey); margin-top: 2px; margin-left: 13px; }
    .crossell-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(39,80,155,0.08); }
    .crossell-item:last-child { border-bottom: none; }
    .crossell-left { display: flex; align-items: center; gap: 10px; }
    .crossell-check { width: 20px; height: 20px; border: 2px solid var(--michelin-blue); border-radius: 4px; flex-shrink: 0; }
    .crossell-name { font-size: 13px; color: var(--michelin-blue-dk); font-weight: 500; }
    .crossell-price { font-size: 13px; color: var(--michelin-grey); font-weight: 600; }
    .total-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; }
    .total-label { font-size: 13px; color: var(--michelin-grey); text-transform: uppercase; font-weight: 700; letter-spacing: 0.06em; }
    .total-val { font-size: 24px; font-weight: 800; color: var(--michelin-blue); }
    .legal-note { font-size: 11px; color: var(--michelin-grey); text-align: center; line-height: 1.5; }
    .divider { border: none; border-top: 1.5px solid rgba(39,80,155,0.1); margin: var(--sp-md) 0; }

    /* ============ LANDING ============ */
    .landing-screen { background: white; height: 812px; display: flex; flex-direction: column; }
    .landing-hero {
      background: var(--michelin-blue); flex: 1;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 32px 28px 50px; position: relative; overflow: hidden;
    }
    .landing-hero::after {
      content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
      height: 36px; background: white; border-radius: 36px 36px 0 0;
    }
    .landing-title {
      font-size: 32px; font-weight: 800; color: white; text-align: center;
      text-transform: uppercase; line-height: 1.05; margin-top: 20px; letter-spacing: -0.01em;
    }
    .landing-title span { color: var(--michelin-yellow); }
    .landing-subtitle { font-size: 14px; color: rgba(255,255,255,0.72); text-align: center; line-height: 1.6; margin-top: 14px; max-width: 290px; }
    .landing-bottom { padding: 24px 24px 20px; display: flex; flex-direction: column; gap: 12px; }
    .landing-legal { font-size: 11px; color: var(--michelin-grey); text-align: center; line-height: 1.5; }

    /* ============ LOGO SVG RÉUTILISABLE (macro) ============ */
    /* Le logo est injecté inline pour garantir le rendu exact */
  </style>
</head>
<body>

<!-- ============ BARRE DÉMO ============ -->
<nav class="demo-nav">
  <span class="demo-label">RideReady</span>
  <button class="demo-btn active" onclick="showScreen('landing')">1 · Landing</button>
  <button class="demo-btn" onclick="showScreen('onboarding')">2 · Onboarding</button>
  <button class="demo-btn" onclick="showScreen('health')">3 · Tire Health</button>
  <button class="demo-btn" onclick="showScreen('alerts')">4 · Alerte</button>
  <button class="demo-btn" onclick="showScreen('reco')">5 · Recommandation</button>
  <button class="demo-btn" onclick="showScreen('cart')">6 · Panier</button>
  <button class="demo-btn" onclick="showScreen('chat')">7 · Chat RAG</button>
</nav>

<!-- SVG DEFS — Logo Michelin réutilisable (p.6: Bonhomme + MICHELIN + ligne de support) -->
<svg width="0" height="0" style="position:absolute;">
  <defs>
    <!-- Logo compact horizontal (blanc, pour topbar bleue) -->
    <symbol id="logo-michelin-compact" viewBox="0 0 175 44">
      <!-- Bonhomme (blanc rempli, contours noirs — p.9 version fond bleu) -->
      <ellipse cx="20" cy="38" rx="10.5" ry="3.8" fill="white" stroke="black" stroke-width="1.2"/>
      <ellipse cx="20" cy="32.5" rx="10" ry="3.5" fill="white" stroke="black" stroke-width="1.2"/>
      <ellipse cx="20" cy="27" rx="9.5" ry="3.2" fill="white" stroke="black" stroke-width="1.2"/>
      <ellipse cx="20" cy="21.5" rx="9" ry="3" fill="white" stroke="black" stroke-width="1.2"/>
      <ellipse cx="20" cy="17" rx="8" ry="2.8" fill="white" stroke="black" stroke-width="1.2"/>
      <!-- Bras droit pointant (p.5 logo commercial) -->
      <ellipse cx="30" cy="19" rx="5.5" ry="2.8" fill="white" stroke="black" stroke-width="1" transform="rotate(-25 30 19)"/>
      <ellipse cx="38" cy="15.5" rx="4.5" ry="2.2" fill="white" stroke="black" stroke-width="1" transform="rotate(-35 38 15.5)"/>
      <ellipse cx="44" cy="12" rx="3.5" ry="2" fill="white" stroke="black" stroke-width="1" transform="rotate(-20 44 12)"/>
      <!-- Bras gauche -->
      <ellipse cx="10" cy="20" rx="4.5" ry="2.5" fill="white" stroke="black" stroke-width="1" transform="rotate(20 10 20)"/>
      <!-- Tête -->
      <circle cx="20" cy="10" r="7" fill="white" stroke="black" stroke-width="1.2"/>
      <circle cx="17.5" cy="8.5" r="1.2" fill="black"/>
      <circle cx="22.5" cy="8.5" r="1.2" fill="black"/>
      <path d="M17 12 Q20 14.5 23 12" stroke="black" stroke-width="1.2" fill="none" stroke-linecap="round"/>
      <!-- Texte MICHELIN (italique, gras — p.11 Michelin Unit Titling / fallback) -->
      <text x="52" y="31" font-family="'Noto Sans','Open Sans',Arial,sans-serif" font-size="24" font-weight="900" fill="white" font-style="italic" letter-spacing="1">MICHELIN</text>
      <!-- Ligne de support jaune (3e élément obligatoire — p.6) -->
      <line x1="52" y1="37" x2="173" y2="37" stroke="#FCE500" stroke-width="4" stroke-linecap="round"/>
    </symbol>

    <!-- Logo grand (landing — blanc sur bleu) -->
    <symbol id="logo-michelin-grand" viewBox="0 0 240 100">
      <ellipse cx="48" cy="88" rx="22" ry="8" fill="white" stroke="black" stroke-width="2"/>
      <ellipse cx="48" cy="74" rx="21" ry="7.5" fill="white" stroke="black" stroke-width="2"/>
      <ellipse cx="48" cy="61" rx="20" ry="7" fill="white" stroke="black" stroke-width="2"/>
      <ellipse cx="48" cy="48" rx="19" ry="6.5" fill="white" stroke="black" stroke-width="2"/>
      <ellipse cx="48" cy="37" rx="18" ry="6" fill="white" stroke="black" stroke-width="2"/>
      <!-- Bras droit -->
      <ellipse cx="68" cy="40" rx="11" ry="5.5" fill="white" stroke="black" stroke-width="1.8" transform="rotate(-25 68 40)"/>
      <ellipse cx="84" cy="33" rx="9" ry="4.5" fill="white" stroke="black" stroke-width="1.8" transform="rotate(-35 84 33)"/>
      <ellipse cx="96" cy="27" rx="7" ry="4" fill="white" stroke="black" stroke-width="1.8" transform="rotate(-20 96 27)"/>
      <!-- Bras gauche -->
      <ellipse cx="28" cy="40" rx="9" ry="5" fill="white" stroke="black" stroke-width="1.8" transform="rotate(20 28 40)"/>
      <!-- Tête -->
      <circle cx="48" cy="22" r="14" fill="white" stroke="black" stroke-width="2"/>
      <circle cx="43.5" cy="19" r="2.5" fill="black"/>
      <circle cx="52.5" cy="19" r="2.5" fill="black"/>
      <path d="M43 25.5 Q48 30 53 25.5" stroke="black" stroke-width="2" fill="none" stroke-linecap="round"/>
      <!-- Texte MICHELIN -->
      <text x="108" y="72" font-family="'Noto Sans','Open Sans',Arial,sans-serif" font-size="38" font-weight="900" fill="white" font-style="italic" letter-spacing="2">MICHELIN</text>
      <!-- Ligne de support jaune -->
      <line x1="108" y1="80" x2="238" y2="80" stroke="#FCE500" stroke-width="5" stroke-linecap="round"/>
    </symbol>
  </defs>
</svg>

<div class="screens-wrapper">
<div class="phone-frame">

  <!-- =====================================================
       ÉCRAN 1 — LANDING
  ===================================================== -->
  <div class="screen active" id="screen-landing">
    <div class="landing-screen">
      <div class="landing-hero">
        <!-- Logo Michelin grand — obligatoire (p.6) -->
        <svg width="240" height="100" aria-label="Michelin">
          <use href="#logo-michelin-grand"/>
        </svg>

        <h1 class="landing-title">
          Tes pneus ont<br>une fin de vie.<br>
          <span>On te le dit</span><br>
          avant la crevaison.
        </h1>
        <p class="landing-subtitle">
          Connecte Strava. On analyse tes sorties, on prédit l'usure, on recommande le bon pneu Michelin.
        </p>
      </div>

      <div class="landing-bottom">
        <button class="btn-strava">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/></svg>
          Connect with Strava
        </button>
        <p class="landing-legal">On ne stocke pas tes données Strava. On les analyse, c'est tout.</p>
      </div>
    </div>
  </div>

  <!-- =====================================================
       ÉCRAN 2 — ONBOARDING
  ===================================================== -->
  <div class="screen" id="screen-onboarding">
    <div class="topbar">
      <svg width="140" height="36" aria-label="Michelin"><use href="#logo-michelin-compact"/></svg>
      <div style="width:32px;height:32px;border-radius:50%;background:#FCE500;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#00205B;">M</div>
    </div>

    <div class="section-header">
      <p class="section-header-label">Marc · Gravel · 6 mois analysés</p>
      <h2 class="section-header-title">Ton profil</h2>
      <p class="section-header-sub">Strava · données inférées · non déclaratives</p>
    </div>

    <div class="screen-content">
      <div class="page-body">

        <!-- ======= UC-9 · Synthèse "Ton année gravel" ======= -->
        <div style="background:linear-gradient(135deg,var(--michelin-blue-dk) 0%,var(--michelin-blue) 100%);border-radius:var(--r-lg);padding:20px;position:relative;overflow:hidden;">
          <!-- Cercle décoratif background -->
          <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(252,229,0,0.08);pointer-events:none;"></div>
          <div style="position:absolute;bottom:-20px;right:20px;width:70px;height:70px;border-radius:50%;background:rgba(252,229,0,0.05);pointer-events:none;"></div>

          <p style="font-size:10px;font-weight:800;color:var(--michelin-yellow);text-transform:uppercase;letter-spacing:0.12em;margin-bottom:14px;">Ton année gravel</p>

          <!-- Chiffre héros -->
          <div style="display:flex;align-items:baseline;gap:6px;margin-bottom:4px;">
            <span style="font-size:48px;font-weight:900;color:white;line-height:1;letter-spacing:-2px;">3 420</span>
            <span style="font-size:18px;font-weight:700;color:rgba(255,255,255,0.7);">km</span>
          </div>
          <!-- Analogie géographique -->
          <p style="font-size:12px;color:var(--michelin-yellow);font-weight:700;margin-bottom:14px;">= Lyon → Barcelone par les Pyrénées 🏔️</p>

          <!-- Stats secondaires -->
          <div style="display:flex;gap:16px;margin-bottom:16px;">
            <div>
              <p style="font-size:18px;font-weight:800;color:white;line-height:1;">42 800</p>
              <p style="font-size:10px;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.06em;">m D+</p>
            </div>
            <div style="width:1px;background:rgba(255,255,255,0.15);"></div>
            <div>
              <p style="font-size:18px;font-weight:800;color:white;line-height:1;">80</p>
              <p style="font-size:10px;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.06em;">sorties</p>
            </div>
            <div style="width:1px;background:rgba(255,255,255,0.15);"></div>
            <div>
              <p style="font-size:18px;font-weight:800;color:white;line-height:1;">140</p>
              <p style="font-size:10px;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.06em;">km / sem</p>
            </div>
          </div>

          <!-- Split terrain (barre visuelle) -->
          <div style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
              <span style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:0.06em;">Route</span>
              <span style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:0.06em;">Chemin</span>
            </div>
            <div style="height:6px;background:rgba(255,255,255,0.15);border-radius:3px;overflow:hidden;">
              <div style="height:100%;width:60%;background:var(--michelin-yellow);border-radius:3px;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:4px;">
              <span style="font-size:11px;font-weight:800;color:var(--michelin-yellow);">60%</span>
              <span style="font-size:11px;font-weight:800;color:rgba(255,255,255,0.6);">40%</span>
            </div>
          </div>

          <!-- Phrase LLM motivante -->
          <p style="font-size:12px;line-height:1.6;color:rgba(255,255,255,0.85);font-style:italic;border-top:1px solid rgba(255,255,255,0.12);padding-top:12px;margin-bottom:12px;">"Ton endurance s'est forgée sortie après sortie. Tes pneus ont porté chaque kilomètre — sur le bitume comme sur les chemins blancs du Beaujolais."</p>

          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:9px;font-weight:700;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.08em;">LLM · stats Strava</span>
            <!-- Bouton Partager -->
            <button onclick="event.stopPropagation()" style="display:flex;align-items:center;gap:6px;background:var(--michelin-yellow);color:var(--michelin-blue-dk);border:none;border-radius:var(--r-sm);padding:7px 14px;font-size:11px;font-weight:800;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer;font-family:var(--font);">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              Partager
            </button>
          </div>
        </div>
        <!-- ======= fin UC-9 ======= -->

        <div class="card">
          <p style="font-size:11px;font-weight:700;color:var(--michelin-grey);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">Ton profil de rider</p>
          <div class="badge-rider"><span>🚵</span><span>Gravel · Endurance</span></div>
          <p style="font-size:11px;color:var(--michelin-grey);margin-top:6px;margin-bottom:14px;">Longue distance, terrain mixte</p>
          <hr class="divider">
          <div class="profile-row"><span class="profile-key">Terrain dominant</span><span class="profile-val">60% route / 40% chemin</span></div>
          <div class="profile-row"><span class="profile-key">Distance type</span><span class="profile-val">~140 km / semaine</span></div>
          <div class="profile-row"><span class="profile-key">Style</span><span class="profile-val">Endurance régulière</span></div>
          <div class="profile-row"><span class="profile-key">Poids système</span><span class="profile-val">~80 kg (estimé)</span></div>
          <div style="margin-top:12px;"><span class="badge-source">Calculé · SCORE · Strava</span></div>
        </div>

        <div class="card">
          <p style="font-size:11px;font-weight:700;color:var(--michelin-grey);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Tes pneus actuels</p>
          <p style="font-size:15px;font-weight:800;color:var(--michelin-blue-dk);">Michelin Power Gravel</p>
          <p style="font-size:12px;color:var(--michelin-grey);margin-top:2px;">700×42C · Tubeless Ready</p>
          <p style="font-size:11px;color:var(--michelin-grey);margin-top:4px;">Monté le : 02/2026 · <span style="color:var(--michelin-blue);text-decoration:underline;cursor:pointer;font-weight:600;">Modifier</span></p>
        </div>

        <button class="btn-primary">C'est juste, on y va →</button>
        <button class="btn-secondary">Ajuster mon profil</button>
      </div>
    </div>

    <div class="bottom-nav">
      <div class="nav-item" onclick="showScreen('health')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span class="nav-label">Santé</span></div>
      <div class="nav-item" onclick="showScreen('alerts')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span class="nav-label">Alertes</span></div>
      <div class="nav-item" onclick="showScreen('reco')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span class="nav-label">Reco</span></div>
      <div class="nav-item active" onclick="showScreen('onboarding')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span class="nav-label">Profil</span></div>
    </div>
  </div>

  <!-- =====================================================
       ÉCRAN 3 — TIRE HEALTH
  ===================================================== -->
  <div class="screen" id="screen-health">
    <div class="topbar">
      <svg width="140" height="36" aria-label="Michelin"><use href="#logo-michelin-compact"/></svg>
      <div class="topbar-cart" onclick="showScreen('cart')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      </div>
    </div>

    <div class="section-header">
      <p class="section-header-label">Bonjour Marc 👋</p>
      <h2 class="section-header-title">Tes pneus aujourd'hui</h2>
    </div>

    <div class="screen-content">
      <div class="page-body">

        <div class="alert-banner" onclick="showScreen('alerts')" style="cursor:pointer;">
          <div class="alert-banner-left">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div>
              <p class="alert-banner-title">Pneu arrière — fin de vie</p>
              <p class="alert-banner-sub">~190 km restants · ~2 semaines</p>
            </div>
          </div>
          <span class="alert-banner-cta">Voir →</span>
        </div>

        <div class="tires-row">
          <div class="gauge-card">
            <span class="gauge-pos">Avant</span>
            <svg viewBox="0 0 100 80" width="130" height="104">
              <path d="M 10 72 A 44 44 0 1 1 90 72" fill="none" stroke="#E8EDF5" stroke-width="11" stroke-linecap="round"/>
              <path d="M 10 72 A 44 44 0 1 1 90 72" fill="none" stroke="#22C55E" stroke-width="11" stroke-linecap="round" stroke-dasharray="49 138"/>
              <text x="50" y="52" text-anchor="middle" font-family="'Noto Sans',sans-serif" font-size="20" font-weight="800" fill="#22C55E">72%</text>
              <text x="50" y="65" text-anchor="middle" font-family="'Noto Sans',sans-serif" font-size="7" fill="#53565A">d'usure</text>
            </svg>
            <span class="gauge-km">~310 km</span>
            <span class="gauge-status" style="color:var(--ui-ok);">● OK</span>
          </div>
          <div class="gauge-card">
            <span class="gauge-pos">Arrière</span>
            <svg viewBox="0 0 100 80" width="130" height="104">
              <path d="M 10 72 A 44 44 0 1 1 90 72" fill="none" stroke="#E8EDF5" stroke-width="11" stroke-linecap="round"/>
              <path d="M 10 72 A 44 44 0 1 1 90 72" fill="none" stroke="#F97316" stroke-width="11" stroke-linecap="round" stroke-dasharray="68 138"/>
              <text x="50" y="52" text-anchor="middle" font-family="'Noto Sans',sans-serif" font-size="20" font-weight="800" fill="#F97316">58%</text>
              <text x="50" y="65" text-anchor="middle" font-family="'Noto Sans',sans-serif" font-size="7" fill="#53565A">d'usure</text>
            </svg>
            <span class="gauge-km">~190 km</span>
            <span class="gauge-status" style="color:var(--ui-warn);">● ATTENTION</span>
          </div>
        </div>

        <!-- ======= UC-7 · Pression conseillée ======= -->
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <p style="font-size:12px;font-weight:800;color:var(--michelin-blue);text-transform:uppercase;letter-spacing:0.06em;">Pression conseillée aujourd'hui</p>
            <span style="font-size:10px;color:var(--michelin-grey);background:rgba(39,80,155,0.06);padding:3px 8px;border-radius:var(--r-sm);">Terrain mixte · 80 kg</span>
          </div>
          <div style="display:flex;gap:12px;margin-bottom:14px;">
            <div style="flex:1;background:linear-gradient(135deg,var(--michelin-blue-dk),var(--michelin-blue));border-radius:var(--r-md);padding:14px;text-align:center;">
              <p style="font-size:10px;font-weight:800;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Avant</p>
              <p style="font-size:32px;font-weight:900;color:white;line-height:1;letter-spacing:-1px;">1.9</p>
              <p style="font-size:11px;color:var(--michelin-yellow);font-weight:700;margin-top:2px;">bar</p>
            </div>
            <div style="flex:1;background:linear-gradient(135deg,var(--michelin-blue-dk),var(--michelin-blue));border-radius:var(--r-md);padding:14px;text-align:center;">
              <p style="font-size:10px;font-weight:800;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Arrière</p>
              <p style="font-size:32px;font-weight:900;color:white;line-height:1;letter-spacing:-1px;">2.1</p>
              <p style="font-size:11px;color:var(--michelin-yellow);font-weight:700;margin-top:2px;">bar</p>
            </div>
          </div>
          <div style="background:rgba(249,115,22,0.08);border-left:3px solid var(--ui-warn);border-radius:0 var(--r-sm) var(--r-sm) 0;padding:8px 10px;margin-bottom:10px;">
            <p style="font-size:11px;color:#92400e;font-weight:600;">Tu roules à ~2.2 bar — <strong>0.1 bar au-dessus</strong> de l'optimal. Tu perds du grip sur chemin mouillé.</p>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span class="badge-source">RAG · fiche Power Gravel · 80 kg</span>
            <span style="font-size:10px;color:var(--michelin-grey);">Plage : 1.5–3.5 bar</span>
          </div>
        </div>
        <!-- ======= fin UC-7 ======= -->

        <div class="card">
          <p style="font-size:12px;font-weight:800;color:var(--michelin-blue);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">Ce mois-ci</p>
          <div class="profile-row"><span class="profile-key">Distance</span><span class="profile-val">520 km</span></div>
          <div class="profile-row"><span class="profile-key">Dont chemin</span><span class="profile-val">210 km (40%)</span></div>
          <div class="profile-row"><span class="profile-key">Fin de vie estimée</span><span class="profile-val" style="color:var(--ui-warn);">25 juillet</span></div>
          <hr class="divider">
          <p style="font-size:13px;line-height:1.6;color:var(--michelin-grey);font-style:italic;">"Tes pneus ont bien bossé ce mois. Le poids des chemins accélère l'usure de l'arrière — surveille-le."</p>
          <div style="margin-top:10px;"><span class="badge-source">Calculé · SCORE · Strava</span></div>
        </div>

      </div>
    </div>

    <div class="bottom-nav">
      <div class="nav-item active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span class="nav-label">Santé</span></div>
      <div class="nav-item" onclick="showScreen('alerts')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span class="nav-alert-dot">1</span>
        <span class="nav-label">Alertes</span>
      </div>
      <div class="nav-item" onclick="showScreen('reco')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span class="nav-label">Reco</span></div>
      <div class="nav-item" onclick="showScreen('onboarding')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span class="nav-label">Profil</span></div>
    </div>
  </div>

  <!-- =====================================================
       ÉCRAN 4 — ALERTE
  ===================================================== -->
  <div class="screen" id="screen-alerts">
    <div class="topbar">
      <svg width="140" height="36" aria-label="Michelin"><use href="#logo-michelin-compact"/></svg>
      <div class="topbar-cart" onclick="showScreen('cart')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      </div>
    </div>

    <div class="section-header" style="text-align:center;padding-top:24px;padding-bottom:20px;">
      <div style="width:56px;height:56px;background:rgba(252,229,0,0.18);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FCE500" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <h2 class="section-header-title">Anticipons avant<br>la crevaison</h2>
    </div>

    <div class="screen-content">
      <div class="page-body">
        <div class="card-blue">
          <p style="font-size:14px;line-height:1.65;color:rgba(255,255,255,0.9);font-style:italic;">
            "Ton pneu arrière est à ~58% d'usure. Au rythme actuel, fin de vie vers le <strong style="color:#FCE500;">25 juillet</strong> — 3 jours avant ta rando du Beaujolais."
          </p>
        </div>

        <div class="card">
          <p style="font-size:11px;font-weight:700;color:var(--michelin-grey);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">Pneu concerné</p>
          <div class="profile-row"><span class="profile-key">Modèle</span><span class="profile-val">Power Gravel 700×42C</span></div>
          <div class="profile-row"><span class="profile-key">Usure estimée</span><span class="profile-val" style="color:var(--ui-warn);">58%</span></div>
          <div class="profile-row"><span class="profile-key">Km restants</span><span class="profile-val">~190 km</span></div>
          <div class="profile-row"><span class="profile-key">Fin de vie estimée</span><span class="profile-val" style="color:var(--ui-warn);">~25 juillet</span></div>
          <div style="margin-top:12px;"><span class="badge-source">Calculé · SCORE · Strava</span></div>
        </div>

        <button class="btn-primary" onclick="showScreen('reco')">Voir ma recommandation Michelin →</button>
        <button class="btn-secondary">Rappelle-moi dans 2 semaines</button>
      </div>
    </div>

    <div class="bottom-nav">
      <div class="nav-item" onclick="showScreen('health')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span class="nav-label">Santé</span></div>
      <div class="nav-item active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span class="nav-label">Alertes</span></div>
      <div class="nav-item" onclick="showScreen('reco')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span class="nav-label">Reco</span></div>
      <div class="nav-item" onclick="showScreen('onboarding')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span class="nav-label">Profil</span></div>
    </div>
  </div>

  <!-- =====================================================
       ÉCRAN 5 — RECOMMANDATION
  ===================================================== -->
  <div class="screen" id="screen-reco">
    <div class="topbar">
      <svg width="140" height="36" aria-label="Michelin"><use href="#logo-michelin-compact"/></svg>
      <div class="topbar-cart" onclick="showScreen('cart')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <span class="cart-badge">1</span>
      </div>
    </div>

    <div class="section-header">
      <p class="section-header-label">Gravel · Endurance · 3 420 km analysés</p>
      <h2 class="section-header-title">Notre recommandation</h2>
    </div>

    <div class="screen-content">
      <div class="page-body">

        <div class="card">
          <span class="badge-reco-tag">Recommandé pour ton profil</span>

          <!-- Visuel pneu vélo style "In Motion" Michelin (p.72-73) -->
          <!-- Fond bleu Michelin, pneu blanc à contours noirs, jante apparente -->
          <div style="background:var(--michelin-blue);border-radius:var(--r-md);height:100px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;overflow:hidden;">
            <svg viewBox="0 0 200 90" width="200" height="90">
              <!-- Pneu gravel style In Motion — blanc, contours noirs, jante apparente (p.72) -->
              <!-- Fond blanc du pneu -->
              <ellipse cx="70" cy="45" rx="40" ry="40" fill="white" stroke="black" stroke-width="2.5"/>
              <!-- Flanc intérieur -->
              <ellipse cx="70" cy="45" rx="31" ry="31" fill="none" stroke="black" stroke-width="2"/>
              <!-- Bande de roulement -->
              <ellipse cx="70" cy="45" rx="24" ry="24" fill="#27509B" stroke="black" stroke-width="2"/>
              <!-- Jante (laissée apparente — p.72) -->
              <ellipse cx="70" cy="45" rx="16" ry="16" fill="white" stroke="black" stroke-width="1.5"/>
              <ellipse cx="70" cy="45" rx="9" ry="9" fill="#27509B" stroke="black" stroke-width="1.5"/>
              <circle cx="70" cy="45" r="3" fill="black"/>
              <!-- Rayons jante -->
              <line x1="70" y1="36" x2="70" y2="45" stroke="black" stroke-width="1.5"/>
              <line x1="70" y1="45" x2="70" y2="54" stroke="black" stroke-width="1.5"/>
              <line x1="61" y1="45" x2="79" y2="45" stroke="black" stroke-width="1.5"/>
              <line x1="63.6" y1="38.6" x2="76.4" y2="51.4" stroke="black" stroke-width="1.5"/>
              <line x1="76.4" y1="38.6" x2="63.6" y2="51.4" stroke="black" stroke-width="1.5"/>
              <!-- Texte produit -->
              <text x="118" y="38" font-family="'Noto Sans',Arial,sans-serif" font-size="14" font-weight="900" fill="white" font-style="italic">POWER</text>
              <text x="118" y="56" font-family="'Noto Sans',Arial,sans-serif" font-size="14" font-weight="900" fill="#FCE500" font-style="italic">GRAVEL RS</text>
            </svg>
          </div>

          <p style="font-size:18px;font-weight:800;color:var(--michelin-blue-dk);text-transform:uppercase;">Power Gravel RS</p>
          <p style="font-size:12px;color:var(--michelin-grey);margin-top:2px;">700×42C · Tubeless Ready · TPI 120 · Racing Line</p>
          <hr class="divider">
          <p style="font-size:13px;line-height:1.6;color:var(--michelin-grey);font-style:italic;">"Ton mix 60/40 route-chemin demande un pneu qui roule bien sur bitume sans lâcher sur le chemin humide. Le RS y répond."</p>
          <div style="margin-top:10px;"><span class="badge-source">Source fiche Michelin 2026 · RAG</span></div>
        </div>

        <div class="compare-wrap">
          <div class="compare-header"><p class="compare-header-title">Ce que tu gagnes</p></div>
          <table class="compare-table">
            <thead>
              <tr>
                <th></th>
                <th>Actuel<br><span style="font-weight:400;color:var(--michelin-grey);font-size:9px;">Power Gravel</span></th>
                <th class="th-reco">RS<br><span style="font-weight:400;font-size:9px;color:var(--michelin-blue);">Recommandé</span></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="td-label">Résistance roulement<span class="td-src">RAG · fiche Michelin</span></td>
                <td class="td-val">22 W</td>
                <td class="td-val td-reco td-better">18 W<span class="td-delta">↓ −4 W</span></td>
              </tr>
              <tr>
                <td class="td-label">Grip off-road<span class="td-src">RAG · fiche Michelin</span></td>
                <td class="td-val" style="font-size:12px;">●●●○○</td>
                <td class="td-val td-reco td-better" style="font-size:12px;">●●●●○<span class="td-delta">↑ +1</span></td>
              </tr>
              <tr>
                <td class="td-label">Durée vie estimée<span class="td-src">SCORE · ton usage</span></td>
                <td class="td-val">~4 500 km</td>
                <td class="td-val td-reco td-better">~5 200 km<span class="td-delta">↑ +700 km</span></td>
              </tr>
              <tr>
                <td class="td-label">Pression conseillée<span class="td-src">RAG · 80 kg</span></td>
                <td class="td-val" style="font-size:11px;">2.1/2.3 bar</td>
                <td class="td-val td-reco" style="font-size:11px;">1.9/2.1 bar</td>
              </tr>
            </tbody>
          </table>
        </div>

        <button class="btn-primary" onclick="showScreen('cart')">Préparer mon panier — Power Gravel RS →</button>

        <!-- Bouton flottant UC-8 -->
        <button onclick="showScreen('chat')" style="display:flex;align-items:center;justify-content:center;gap:8px;background:white;color:var(--michelin-blue);border:2px solid var(--michelin-blue);border-radius:var(--r-sm);height:44px;width:100%;font-size:13px;font-weight:700;font-family:var(--font);cursor:pointer;letter-spacing:0.03em;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          Une question sur ce pneu ?
        </button>
      </div>
    </div>

    <div class="bottom-nav">
      <div class="nav-item" onclick="showScreen('health')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span class="nav-label">Santé</span></div>
      <div class="nav-item" onclick="showScreen('alerts')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span class="nav-label">Alertes</span></div>
      <div class="nav-item active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span class="nav-label">Reco</span></div>
      <div class="nav-item" onclick="showScreen('onboarding')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span class="nav-label">Profil</span></div>
    </div>
  </div>

  <!-- =====================================================
       ÉCRAN 6 — PANIER
  ===================================================== -->
  <div class="screen" id="screen-cart">
    <div class="topbar">
      <svg width="140" height="36" aria-label="Michelin"><use href="#logo-michelin-compact"/></svg>
      <div class="topbar-cart">
        <svg viewBox="0 0 24 24" fill="none" stroke="#FCE500" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <span class="cart-badge">2</span>
      </div>
    </div>

    <div class="section-header">
      <p class="section-header-label">Prêt à commander</p>
      <h2 class="section-header-title">Ton panier est prêt</h2>
      <p class="section-header-sub">On a tout prérempli pour ton vélo.</p>
    </div>

    <div class="screen-content">
      <div class="page-body">
        <div class="card">
          <div class="cart-row">
            <div class="cart-img">
              <!-- Pneu vélo style In Motion (compact) -->
              <svg viewBox="0 0 60 60" width="50" height="50">
                <ellipse cx="30" cy="30" rx="27" ry="27" fill="white" stroke="black" stroke-width="2"/>
                <ellipse cx="30" cy="30" rx="21" ry="21" fill="none" stroke="black" stroke-width="1.8"/>
                <ellipse cx="30" cy="30" rx="16" ry="16" fill="#27509B" stroke="black" stroke-width="1.8"/>
                <ellipse cx="30" cy="30" rx="10" ry="10" fill="white" stroke="black" stroke-width="1.5"/>
                <circle cx="30" cy="30" r="4" fill="#27509B" stroke="black" stroke-width="1"/>
              </svg>
            </div>
            <div style="flex:1;">
              <p class="cart-name">Power Gravel RS</p>
              <p class="cart-specs">700×42C · Tubeless Ready</p>
              <p class="cart-specs">Réf. ETRTO : 42-622</p>
              <p class="cart-specs" style="margin-top:4px;">Quantité : 2 (paire)</p>
              <p class="cart-price">91,80 €</p>
            </div>
          </div>
          <hr class="divider">
          <div class="stock-row"><span class="stock-dot"></span>En stock — Livraison jeu. 25 juillet</div>
          <p class="delivery-sub">Avant ta sortie du week-end 🎉</p>
        </div>

        <div class="card">
          <p style="font-size:11px;font-weight:700;color:var(--michelin-grey);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;">Compléter l'installation</p>
          <div class="crossell-item">
            <div class="crossell-left">
              <div class="crossell-check"></div>
              <div>
                <p class="crossell-name">Préventif tubeless Muc-Off 1L</p>
                <p style="font-size:10px;color:var(--michelin-grey);">Recommandé pour ton gravel</p>
              </div>
            </div>
            <span class="crossell-price">+12 €</span>
          </div>
          <div class="crossell-item">
            <div class="crossell-left">
              <div class="crossell-check"></div>
              <p class="crossell-name">Valves tubeless 60mm (×2)</p>
            </div>
            <span class="crossell-price">+8 €</span>
          </div>
        </div>

        <div class="total-row">
          <span class="total-label">Sous-total</span>
          <span class="total-val">91,80 €</span>
        </div>

        <button class="btn-primary" style="height:56px;">Commander chez Decathlon →</button>
        <p class="legal-note">Vous serez redirigé vers Decathlon.<br>Michelin ne stocke pas vos données de paiement.</p>
      </div>
    </div>

    <div class="bottom-nav">
      <div class="nav-item" onclick="showScreen('health')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span class="nav-label">Santé</span></div>
      <div class="nav-item" onclick="showScreen('alerts')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span class="nav-label">Alertes</span></div>
      <div class="nav-item" onclick="showScreen('reco')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span class="nav-label">Reco</span></div>
      <div class="nav-item" onclick="showScreen('onboarding')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span class="nav-label">Profil</span></div>
    </div>
  </div>

  <!-- =====================================================
       ÉCRAN 7 — CHATBOT RAG (UC-8)
  ===================================================== -->
  <div class="screen" id="screen-chat">
    <div class="topbar">
      <svg width="140" height="36" aria-label="Michelin"><use href="#logo-michelin-compact"/></svg>
      <div onclick="showScreen('reco')" style="display:flex;align-items:center;gap:6px;color:rgba(255,255,255,0.7);cursor:pointer;font-size:12px;font-weight:600;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Retour
      </div>
    </div>

    <div class="section-header">
      <p class="section-header-label">Assistant Michelin · RAG</p>
      <h2 class="section-header-title">Pose ta question</h2>
      <p class="section-header-sub">Réponses ancrées dans les fiches Michelin 2026</p>
    </div>

    <div class="screen-content">
      <div style="padding:16px;display:flex;flex-direction:column;gap:12px;">

        <!-- Suggestions rapides -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <span style="font-size:11px;background:rgba(39,80,155,0.08);color:var(--michelin-blue);border:1px solid rgba(39,80,155,0.2);padding:5px 10px;border-radius:20px;cursor:pointer;font-weight:600;">Tubeless ou chambre ?</span>
          <span style="font-size:11px;background:rgba(39,80,155,0.08);color:var(--michelin-blue);border:1px solid rgba(39,80,155,0.2);padding:5px 10px;border-radius:20px;cursor:pointer;font-weight:600;">Quelle largeur pour gravel ?</span>
          <span style="font-size:11px;background:rgba(39,80,155,0.08);color:var(--michelin-blue);border:1px solid rgba(39,80,155,0.2);padding:5px 10px;border-radius:20px;cursor:pointer;font-weight:600;">GUM-X, c'est quoi ?</span>
        </div>

        <!-- Message Marc -->
        <div style="display:flex;justify-content:flex-end;">
          <div style="max-width:75%;background:var(--michelin-blue);color:white;border-radius:16px 16px 4px 16px;padding:10px 14px;">
            <p style="font-size:13px;line-height:1.5;">Tubeless ou chambre à air pour mon gravel mixte route / chemin ?</p>
          </div>
        </div>

        <!-- Réponse RAG -->
        <div style="display:flex;justify-content:flex-start;gap:8px;align-items:flex-start;">
          <!-- Avatar -->
          <div style="width:30px;height:30px;background:var(--michelin-blue-dk);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
          </div>
          <div style="max-width:82%;">
            <div style="background:white;border:1.5px solid rgba(39,80,155,0.12);border-radius:4px 16px 16px 16px;padding:12px 14px;box-shadow:0 2px 8px rgba(39,80,155,0.06);">
              <p style="font-size:13px;line-height:1.6;color:var(--michelin-blue-dk);margin-bottom:10px;">Pour ton profil <strong>Gravel mixte 60/40</strong>, le montage <strong>tubeless est recommandé</strong>. Voici pourquoi :</p>
              <ul style="list-style:none;display:flex;flex-direction:column;gap:6px;margin-bottom:10px;">
                <li style="display:flex;gap:8px;font-size:12px;color:var(--michelin-grey);line-height:1.5;"><span style="color:var(--ui-ok);font-weight:800;flex-shrink:0;">✓</span>Pression plus basse possible (1.5 bar sur chemin) = meilleur grip et confort</li>
                <li style="display:flex;gap:8px;font-size:12px;color:var(--michelin-grey);line-height:1.5;"><span style="color:var(--ui-ok);font-weight:800;flex-shrink:0;">✓</span>Moins de risque de pinch flat sur cailloux</li>
                <li style="display:flex;gap:8px;font-size:12px;color:var(--michelin-grey);line-height:1.5;"><span style="color:var(--ui-ok);font-weight:800;flex-shrink:0;">✓</span>Le Power Gravel RS est <strong>Tubeless Ready</strong> (TLR) — compatibilité native</li>
              </ul>
              <!-- Source RAG -->
              <div style="background:rgba(39,80,155,0.05);border-radius:var(--r-sm);padding:8px 10px;">
                <p style="font-size:10px;font-weight:700;color:var(--michelin-blue);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Sources · RAG</p>
                <p style="font-size:11px;color:var(--michelin-grey);">Fiche Power Gravel RS 2026 · Guide montage tubeless Michelin · Conseil pression selon terrain</p>
              </div>
            </div>
            <p style="font-size:10px;color:var(--michelin-grey);margin-top:4px;margin-left:4px;">Michelin Assistant · maintenant</p>
          </div>
        </div>

        <!-- 2e échange : question Marc -->
        <div style="display:flex;justify-content:flex-end;">
          <div style="max-width:75%;background:var(--michelin-blue);color:white;border-radius:16px 16px 4px 16px;padding:10px 14px;">
            <p style="font-size:13px;line-height:1.5;">Et quel préventif tubeless tu conseilles ?</p>
          </div>
        </div>

        <!-- Réponse RAG 2 -->
        <div style="display:flex;justify-content:flex-start;gap:8px;align-items:flex-start;">
          <div style="width:30px;height:30px;background:var(--michelin-blue-dk);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
          </div>
          <div style="max-width:82%;">
            <div style="background:white;border:1.5px solid rgba(39,80,155,0.12);border-radius:4px 16px 16px 16px;padding:12px 14px;box-shadow:0 2px 8px rgba(39,80,155,0.06);">
              <p style="font-size:13px;line-height:1.6;color:var(--michelin-blue-dk);margin-bottom:8px;">Pour un <strong>700×42C gravel mixte</strong>, compte <strong>60–80 ml de préventif</strong> par pneu. Michelin ne fabrique pas de préventif — les références courantes : Orange Seal, Stans NoTubes, Muc-Off.</p>
              <div style="background:rgba(249,115,22,0.08);border-left:3px solid var(--ui-warn);border-radius:0 var(--r-sm) var(--r-sm) 0;padding:8px 10px;margin-bottom:10px;">
                <p style="font-size:11px;color:#92400e;font-weight:600;">Renouveler le préventif tous les 3–6 mois selon humidité et usage.</p>
              </div>
              <div style="background:rgba(39,80,155,0.05);border-radius:var(--r-sm);padding:8px 10px;">
                <p style="font-size:10px;font-weight:700;color:var(--michelin-blue);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;">Sources · RAG</p>
                <p style="font-size:11px;color:var(--michelin-grey);">Guide montage tubeless Michelin 2026 · Conseils entretien pneu gravel</p>
              </div>
            </div>
            <p style="font-size:10px;color:var(--michelin-grey);margin-top:4px;margin-left:4px;">Michelin Assistant · maintenant</p>
          </div>
        </div>

      </div>
    </div>

    <!-- Barre de saisie -->
    <div style="padding:12px 16px;background:white;border-top:1.5px solid rgba(39,80,155,0.1);display:flex;gap:8px;align-items:center;flex-shrink:0;">
      <div style="flex:1;background:rgba(39,80,155,0.05);border:1.5px solid rgba(39,80,155,0.15);border-radius:24px;padding:10px 16px;">
        <p style="font-size:13px;color:var(--michelin-grey);">Pose une question sur tes pneus…</p>
      </div>
      <div style="width:40px;height:40px;background:var(--michelin-blue);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      </div>
    </div>

    <div class="bottom-nav">
      <div class="nav-item" onclick="showScreen('health')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span class="nav-label">Santé</span></div>
      <div class="nav-item" onclick="showScreen('alerts')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span class="nav-label">Alertes</span></div>
      <div class="nav-item active" onclick="showScreen('reco')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span class="nav-label">Reco</span></div>
      <div class="nav-item" onclick="showScreen('onboarding')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span class="nav-label">Profil</span></div>
    </div>
  </div>

</div><!-- /phone-frame -->
</div><!-- /screens-wrapper -->

<script>
  function showScreen(id) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.demo-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('screen-' + id).classList.add('active');
    const map = { landing: 0, onboarding: 1, health: 2, alerts: 3, reco: 4, cart: 5, chat: 6 };
    document.querySelectorAll('.demo-btn')[map[id]].classList.add('active');
  }
</script>
</body>
</html>
