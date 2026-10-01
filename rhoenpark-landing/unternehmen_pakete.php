<?php
// Firmenpakete von variado – Landingpage für Unternehmenskunden
// Aufbau wie feuer.php: eigenes, gekapseltes Styling (Präfix "vu-"), Template über header_dynamic.php und footer.php.
  $title = "Teamentwicklung für Unternehmen";
  $description = "Teambuilding, Achtsamkeit, Resilienz, Führung und Strategie von variado. Die ErLebenswerkstatt: sechs Pakete für 10 bis 300 Personen in der Rhön, erfahrungsbasiert und individuell zugeschnitten.";
  $keywords = "Teambuilding Rhön, Teamentwicklung, Firmenevent, Resilienztraining, Achtsamkeit, Führungstraining, Familien-Unternehmens-Retreat, Thüringer Hütte";
  $img = 'images/angebote/unternehmen/';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,400;1,9..144,500&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  /* Layout nach feuer.php: dunkler Hero mit Morph-Fotos, helle Kartenbänder, grünes Erdungskapitel,
     Galerie, Eckdaten-Karte, Anfrage, dunkler Schluss. Farben aus dem variado-Logo:
     Orange = Mensch, Grün = Natur, Blau = Technik. */
  .vu{
    --vu-navy:#0B2537;
    --vu-navy-2:#071A28;
    --vu-blue:#005F98;
    --vu-blue-soft:#E8F1F8;
    --vu-orange:#F0A617;
    --vu-orange-ink:#8F5D00;
    --vu-green:#007F5C;
    --vu-forest:#0D3A2D;
    --vu-forest-2:#145C45;
    --vu-paper:#F4F7F9;
    --vu-white:#FFFFFF;
    --vu-ink:#132634;
    --vu-ink-soft:#4E6374;
    --vu-on-dark:#E9F1F7;
    --vu-line:rgba(19,38,52,.10);
    background:var(--vu-white);
    color:var(--vu-ink);
    font-family:'Work Sans', system-ui, sans-serif;
    font-size:17px;
    line-height:1.6;
    -webkit-font-smoothing:antialiased;
  }
  .vu *{box-sizing:border-box;}
  .vu [hidden]{display:none !important;}
  html{scroll-behavior:smooth;}
  @media (prefers-reduced-motion: reduce){ .vu *{animation:none !important; transition:none !important;} html{scroll-behavior:auto;} }

  .vu h1,.vu h2,.vu h3,.vu h4{font-family:'Fraunces', Georgia, serif; font-weight:600; letter-spacing:-0.01em; margin:0; line-height:1.1; text-transform:none; text-wrap:balance;}
  .vu p{margin:0;}
  .vu a{color:inherit;}
  .vu img{max-width:100%;}
  .vu :focus-visible{outline:3px solid var(--vu-orange); outline-offset:3px;}
  .vu-wrap{max-width:1160px; margin:0 auto; padding:0 1.6rem;}
  .vu-title{color:var(--vu-ink); font-size:clamp(1.9rem, 3.8vw, 2.8rem); margin-bottom:.7rem;}
  .vu-title em{font-style:italic; font-weight:400; color:var(--vu-blue);}
  .vu-lede{color:var(--vu-ink-soft); font-size:1.08rem; max-width:60ch;}
  .vu-head{max-width:680px; margin:0 0 3rem;}
  .vu-head--c{text-align:center; margin:0 auto 3rem;}
  .vu-head--c .vu-lede{margin:0 auto;}
  .vu-eyebrow{
    font-family:'Work Sans', sans-serif; text-transform:uppercase; letter-spacing:.16em;
    font-size:.74rem; font-weight:600; color:var(--vu-blue);
    display:inline-flex; align-items:center; gap:.55rem; margin-bottom:.9rem;
  }
  .vu-eyebrow::before{content:""; width:18px; height:2px; background:var(--vu-orange); display:inline-block;}
  .vu-eyebrow.on-dark{color:var(--vu-orange);}

  .vu-btn{
    display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
    font-family:'Work Sans', sans-serif; font-weight:600; font-size:1rem; line-height:1;
    padding:.95rem 1.8rem; border-radius:100px; border:0; cursor:pointer; text-decoration:none !important;
    transition:transform .15s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease;
  }
  .vu-btn svg{width:18px; height:18px;}
  .vu-btn--primary{background:linear-gradient(135deg, #F7B733, var(--vu-orange)); color:var(--vu-navy) !important; box-shadow:0 8px 24px -8px rgba(240,166,23,.65);}
  .vu-btn--primary:hover{transform:translateY(-2px); box-shadow:0 12px 28px -8px rgba(240,166,23,.8);}
  .vu-btn--ghost{background:transparent; color:var(--vu-on-dark) !important; border:1px solid rgba(233,241,247,.35);}
  .vu-btn--ghost:hover{border-color:var(--vu-orange); background:rgba(240,166,23,.08);}
  .vu-btn--blue{background:var(--vu-blue); color:#fff !important;}
  .vu-btn--blue:hover{background:var(--vu-navy); transform:translateY(-1px);}
  .vu-btn--soft{background:var(--vu-blue-soft); color:var(--vu-blue) !important;}

  /* ---------- NAV ---------- */
  .vu-nav{position:sticky; top:0; z-index:50; background:rgba(255,255,255,.94); backdrop-filter:blur(8px); border-bottom:1px solid var(--vu-line);}
  .vu-nav .vu-wrap{display:flex; align-items:center; justify-content:space-between; gap:1rem; padding-top:.7rem; padding-bottom:.7rem;}
  .vu-nav img{height:40px !important; width:auto !important; display:block;}
  .vu-nav-links{display:flex; align-items:center; gap:1.6rem;}
  .vu-nav-links a{text-decoration:none; font-weight:500; font-size:.92rem; color:var(--vu-ink);}
  .vu-nav-links a:hover{color:var(--vu-blue);}
  .vu-nav-links .vu-btn{padding:.6rem 1.2rem; font-size:.88rem;}
  @media (max-width:940px){ .vu-nav-links a:not(.vu-btn){display:none;} }

  /* ---------- HERO ---------- */
  .vu-hero{position:relative; overflow:hidden; padding:5rem 0 6.5rem; background:radial-gradient(ellipse at 70% 120%, #0E4466 0%, var(--vu-navy) 55%, var(--vu-navy-2) 100%); color:var(--vu-on-dark);}
  .vu-hero-inner{position:relative; z-index:3; display:grid; grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr); gap:2.5rem; align-items:center;}
  .vu-hero h1{color:#fff; font-size:clamp(2.5rem, 5.4vw, 4.2rem); line-height:1.04;}
  .vu-hero h1 em{display:block; font-style:italic; font-weight:400; color:var(--vu-orange); min-height:1.1em; transition:opacity .35s ease, transform .35s ease;}
  .vu-hero h1 em.out{opacity:0; transform:translateY(.2em);}
  .vu-hero .vu-sub{color:#C9DBE8; font-size:clamp(1rem,1.5vw,1.15rem); max-width:46ch; margin-top:1.3rem;}
  .vu-meta{display:flex; flex-wrap:wrap; gap:.7rem 1rem; margin-top:2rem;}
  .vu-pill{border:1px solid rgba(233,241,247,.22); background:rgba(233,241,247,.05); color:var(--vu-on-dark); padding:.5rem 1rem; border-radius:100px; font-size:.88rem; font-weight:500; display:flex; align-items:center; gap:.5rem;}
  .vu-pill svg{width:15px; height:15px; flex-shrink:0;}
  .vu-hero-cta{margin-top:2.4rem; display:flex; gap:.9rem; flex-wrap:wrap;}

  .vu-photos{position:relative; height:470px;}
  .vu-blob{position:absolute !important; overflow:hidden !important; box-shadow:0 24px 48px -16px rgba(0,0,0,.55);}
  .vu-blob img{width:100% !important; height:100% !important; max-width:none !important; object-fit:cover !important; display:block !important; margin:0 !important; border:0 !important; border-radius:0 !important;}
  .vu-blob--lg{width:76%; height:86%; top:2%; right:0; border-radius:63% 37% 54% 46% / 48% 43% 57% 52%; border:3px solid rgba(240,166,23,.4); animation:vu-morph 14s ease-in-out infinite;}
  .vu-blob--sm{width:44%; height:42%; bottom:-3%; left:-2%; z-index:2; border-radius:41% 59% 60% 40% / 55% 48% 52% 45%; border:3px solid rgba(0,127,92,.75); animation:vu-morph 11s ease-in-out infinite reverse;}
  @keyframes vu-morph{0%,100%{border-radius:63% 37% 54% 46% / 48% 43% 57% 52%;} 50%{border-radius:46% 54% 38% 62% / 55% 40% 60% 45%;}}
  .vu-badge{position:absolute; z-index:3; bottom:9%; right:5%; background:rgba(11,37,55,.86); border:1px solid rgba(240,166,23,.45); color:var(--vu-orange); font-size:.76rem; font-weight:600; letter-spacing:.05em; padding:.45rem .9rem; border-radius:100px; backdrop-filter:blur(3px);}
  .vu-badge b{color:#fff; font-weight:600;}

  /* aufsteigende Puzzleteile */
  .vu-float{position:absolute; inset:0; z-index:1; pointer-events:none;}
  .vu-float svg{position:absolute; bottom:-30px; opacity:0; animation:vu-rise linear infinite;}
  @keyframes vu-rise{0%{transform:translateY(0) rotate(0); opacity:0;} 12%{opacity:.55;} 85%{opacity:.25;} 100%{transform:translateY(-560px) rotate(var(--vu-rot,120deg)); opacity:0;}}

  @media (max-width:880px){
    .vu-hero-inner{grid-template-columns:minmax(0,1fr);}
    .vu-photos{height:340px; max-width:440px; width:100%; margin:0 auto;}
  }

  /* Zahlenleiste */
  .vu-stats{background:var(--vu-navy-2); color:#fff;}
  .vu-stats .vu-wrap{display:grid; grid-template-columns:repeat(4,1fr);}
  .vu-stat{padding:1.5rem 1.2rem; border-left:1px solid rgba(255,255,255,.1);}
  .vu-stat:first-child{border-left:0; padding-left:0;}
  .vu-stat b{display:block; font-family:'Fraunces', serif; font-weight:600; font-size:clamp(1.6rem,3vw,2.2rem); line-height:1.1; font-variant-numeric:tabular-nums;}
  .vu-stat b i{font-style:normal; color:var(--vu-orange);}
  .vu-stat span{color:#9FB6C7; font-size:.9rem;}
  @media (max-width:760px){
    .vu-stats .vu-wrap{grid-template-columns:1fr 1fr;}
    .vu-stat:nth-child(3){border-left:0; padding-left:0;}
    .vu-stat:nth-child(n+3){border-top:1px solid rgba(255,255,255,.1);}
  }

  /* ---------- Bänder ---------- */
  .vu-band{padding:5.5rem 0;}
  .vu-band--paper{background:var(--vu-paper);}

  /* Mensch Natur Technik */
  .vu-grid3{display:grid; grid-template-columns:repeat(3,1fr); gap:1.1rem;}
  .vu-card{background:#fff; border:1px solid var(--vu-line); border-radius:18px; padding:1.8rem 1.6rem; position:relative; transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;}
  .vu-card:hover{transform:translateY(-4px); box-shadow:0 16px 32px -16px rgba(19,38,52,.25);}
  .vu-icon{width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; margin-bottom:1rem;}
  .vu-icon svg{width:26px; height:26px;}
  .vu-icon--m{background:linear-gradient(135deg, #F7B733, #E58E00);}
  .vu-icon--n{background:linear-gradient(135deg, #1FA37A, var(--vu-green));}
  .vu-icon--t{background:linear-gradient(135deg, #1E86C8, var(--vu-blue));}
  .vu-card h3{color:var(--vu-ink); font-size:1.3rem; margin-bottom:.45rem;}
  .vu-card p{color:var(--vu-ink-soft); font-size:.95rem;}
  .vu-tags{display:flex; flex-wrap:wrap; gap:.4rem; margin-top:1rem;}
  .vu-tag{background:var(--vu-paper); border:1px solid var(--vu-line); color:var(--vu-ink); padding:.35rem .8rem; border-radius:100px; font-size:.82rem; font-weight:500;}
  .vu-loop{list-style:none; margin:2.8rem 0 0; padding:0; display:grid; grid-template-columns:repeat(3,1fr); gap:1.1rem;}
  .vu-loop li{display:flex; gap:.9rem; align-items:flex-start;}
  .vu-loop .n{flex:none; font-family:'Fraunces', serif; font-weight:600; font-size:1.05rem; width:42px; height:42px; border-radius:50%; display:grid; place-items:center; color:#fff; background:var(--vu-blue);}
  .vu-loop li:nth-child(2) .n{background:var(--vu-orange); color:var(--vu-navy);}
  .vu-loop li:nth-child(3) .n{background:var(--vu-green);}
  .vu-loop b{display:block; font-family:'Fraunces', serif; font-weight:600; font-size:1.15rem;}
  .vu-loop span{color:var(--vu-ink-soft); font-size:.92rem;}

  /* Gruppengrößen */
  .vu-size .vu-range{font-family:'Fraunces', serif; font-weight:600; font-size:2.4rem; line-height:1; color:var(--vu-blue); font-variant-numeric:tabular-nums;}
  .vu-size .vu-range small{font-family:'Work Sans', sans-serif; font-size:.85rem; font-weight:500; color:var(--vu-ink-soft); margin-left:.4rem;}
  .vu-dots{display:flex; flex-wrap:wrap; gap:4px; min-height:62px; align-content:flex-start; margin:1rem 0;}
  .vu-dots i{width:8px; height:8px; border-radius:50%; background:#C3D5E2; display:block;}
  .vu-dots i.t{background:var(--vu-orange);}
  .vu-fit{display:block; margin-top:1rem; font-size:.74rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase; color:var(--vu-green);}

  @media (max-width:880px){ .vu-grid3, .vu-loop{grid-template-columns:1fr;} }

  /* ---------- Pakete ---------- */
  .vu-filter{display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:.8rem; background:#fff; border:1px solid var(--vu-line); border-radius:100px; padding:.5rem; margin-bottom:1rem; box-shadow:0 12px 30px -20px rgba(19,38,52,.35);}
  .vu-fgroup{display:flex; flex-wrap:wrap; align-items:center; gap:.25rem;}
  .vu-fgroup > span{font-size:.72rem; font-weight:600; letter-spacing:.12em; text-transform:uppercase; color:var(--vu-ink-soft); margin:0 .5rem 0 .7rem;}
  .vu-chip-btn{font-family:'Work Sans', sans-serif; font-weight:500; font-size:.9rem; line-height:1; color:var(--vu-ink); background:transparent; border:0; border-radius:100px; padding:.7rem 1rem; cursor:pointer; font-variant-numeric:tabular-nums;}
  .vu-chip-btn:hover{background:var(--vu-paper);}
  .vu-chip-btn[aria-pressed="true"]{background:var(--vu-blue); color:#fff;}
  .vu-count{font-size:.9rem; color:var(--vu-ink-soft); margin-bottom:1.6rem;}
  .vu-count b{color:var(--vu-blue);}
  @media (max-width:960px){ .vu-filter{border-radius:18px; flex-direction:column; align-items:stretch;} }

  .vu-pkgs{display:grid; grid-template-columns:repeat(3,1fr); gap:1.3rem;}
  .vu-pkg{background:#fff; border:1px solid var(--vu-line); border-radius:20px; overflow:hidden; display:flex; flex-direction:column; min-width:0; position:relative; transition:transform .2s ease, box-shadow .2s ease, opacity .25s, filter .25s;}
  .vu-pkg:hover{transform:translateY(-4px); box-shadow:0 20px 40px -18px rgba(19,38,52,.3);}
  .vu-pkg.is-dim{opacity:.35; filter:grayscale(.8); transform:none;}
  .vu-pkg-img{position:relative; aspect-ratio:16/10; overflow:hidden;}
  .vu-pkg-img img{width:100% !important; height:100% !important; max-width:none !important; object-fit:cover !important; display:block !important; margin:0 !important; transition:transform .5s ease;}
  .vu-pkg:hover .vu-pkg-img img{transform:scale(1.05);}
  .vu-pkg-img::after{content:""; position:absolute; inset:0; background:linear-gradient(180deg, rgba(11,37,55,0) 50%, rgba(11,37,55,.6) 100%); pointer-events:none;}
  .vu-pkg-meta{position:absolute; left:.9rem; right:.9rem; bottom:.9rem; z-index:1; display:flex; flex-wrap:wrap; gap:.4rem;}
  .vu-pkg-meta span{background:rgba(255,255,255,.95); color:var(--vu-navy); font-size:.78rem; font-weight:600; padding:.32rem .7rem; border-radius:100px; display:inline-flex; align-items:center; gap:.35rem; font-variant-numeric:tabular-nums;}
  .vu-pkg-meta svg{width:13px; height:13px;}
  .vu-fitbadge{position:absolute; top:.9rem; right:.9rem; z-index:2; background:var(--vu-green); color:#fff; font-size:.76rem; font-weight:600; padding:.32rem .75rem; border-radius:100px;}
  .vu-pkg-body{padding:1.4rem 1.5rem 1.5rem; display:flex; flex-direction:column; gap:.9rem; flex:1;}
  .vu-kicker{font-size:.72rem; font-weight:600; letter-spacing:.12em; text-transform:uppercase; display:inline-flex; align-items:center; gap:.45rem;}
  .vu-kicker i{width:9px; height:9px; border-radius:50%; display:inline-block;}
  .vu-kicker.m{color:var(--vu-orange-ink);} .vu-kicker.m i{background:var(--vu-orange);}
  .vu-kicker.n{color:var(--vu-green);} .vu-kicker.n i{background:var(--vu-green);}
  .vu-kicker.t{color:var(--vu-blue);} .vu-kicker.t i{background:var(--vu-blue);}
  .vu-pkg h3{color:var(--vu-ink); font-size:1.45rem;}
  .vu-pkg .vu-claim{color:var(--vu-ink-soft); font-size:.95rem;}
  .vu-ticks{list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.45rem; font-size:.9rem;}
  .vu-ticks li{display:flex; gap:.55rem; align-items:flex-start;}
  .vu-ticks svg{width:17px; height:17px; flex-shrink:0; margin-top:3px; stroke:var(--vu-green);}
  .vu-gain{background:var(--vu-blue-soft); border-radius:12px; padding:.75rem .9rem; font-size:.88rem;}
  .vu-gain b{display:block; font-size:.7rem; letter-spacing:.12em; text-transform:uppercase; color:var(--vu-blue); margin-bottom:.15rem;}
  .vu-pkg details{border-top:1px solid var(--vu-line); padding-top:.75rem; font-size:.88rem;}
  .vu-pkg summary{cursor:pointer; list-style:none; font-weight:600; color:var(--vu-blue); display:flex; justify-content:space-between; align-items:center;}
  .vu-pkg summary::-webkit-details-marker{display:none;}
  .vu-pkg summary::after{content:"+"; font-size:1.25rem; line-height:1;}
  .vu-pkg details[open] summary::after{content:"–";}
  .vu-tl{list-style:none; margin:.7rem 0 0; padding:0; display:flex; flex-direction:column; gap:.35rem;}
  .vu-tl li{display:grid; grid-template-columns:4.3em 1fr; gap:.6rem;}
  .vu-tl time{font-weight:600; color:var(--vu-ink-soft); font-variant-numeric:tabular-nums;}
  .vu-tl .day{grid-template-columns:1fr; font-weight:700; color:var(--vu-blue); margin-top:.3rem;}
  .vu-pkg .vu-go{margin-top:auto; padding-top:.3rem;}
  .vu-pkg .vu-go .vu-btn{width:100%; padding:.85rem 1rem; font-size:.95rem;}
  @media (max-width:1040px){ .vu-pkgs{grid-template-columns:repeat(2,1fr);} }
  @media (max-width:660px){ .vu-pkgs{grid-template-columns:1fr;} }

  .vu-custom{margin-top:1.6rem; background:var(--vu-navy); color:var(--vu-on-dark); border-radius:20px; padding:1.8rem 2rem; display:flex; flex-wrap:wrap; gap:1rem 2rem; align-items:center; justify-content:space-between;}
  .vu-custom h3{color:#fff; font-size:1.4rem; margin-bottom:.3rem;}
  .vu-custom p{color:#9FB6C7; font-size:.95rem; max-width:60ch;}

  /* ---------- Retreat (grünes Erdungskapitel wie .safety) ---------- */
  .vu-retreat{background:linear-gradient(180deg, var(--vu-forest) 0%, var(--vu-forest-2) 100%); color:#E6F3EC; padding:6rem 0;}
  .vu-retreat-top{display:grid; grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr); gap:3.5rem; align-items:start;}
  .vu-retreat h2{color:#fff; font-size:clamp(1.9rem,3.4vw,2.6rem); line-height:1.15; margin-bottom:1.1rem;}
  .vu-retreat h2 em{font-style:italic; font-weight:400; color:var(--vu-orange);}
  .vu-retreat .vu-p{color:rgba(230,243,236,.88); font-size:1.05rem; margin-bottom:1.1rem;}
  .vu-new{display:inline-flex; align-items:center; gap:.45rem; background:var(--vu-orange); color:var(--vu-navy); font-weight:600; font-size:.82rem; padding:.35rem .85rem; border-radius:100px; margin-bottom:1.1rem;}
  .vu-callout{border-left:3px solid var(--vu-orange); padding:.9rem 0 .9rem 1.2rem; font-family:'Fraunces', serif; font-style:italic; font-size:1.2rem; color:#fff; margin-top:1.5rem;}
  .vu-plist-title{font-size:.76rem; text-transform:uppercase; letter-spacing:.12em; color:var(--vu-orange); font-weight:600; margin-bottom:.85rem;}
  .vu-plist{list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.8rem;}
  .vu-plist li{display:flex; gap:.9rem; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.14); border-radius:14px; padding:.95rem 1.1rem;}
  .vu-plist .num{font-family:'Fraunces', serif; color:var(--vu-orange); font-weight:600; flex-shrink:0;}
  .vu-plist b{color:#fff; font-weight:600; display:block;}
  .vu-plist span{font-size:.9rem; font-style:italic; color:rgba(230,243,236,.8);}
  .vu-split{margin-top:2.6rem; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.14); border-radius:18px; padding:1.3rem 1.4rem;}
  .vu-split-bar{display:flex; height:40px; border-radius:10px; overflow:hidden; font-family:'Fraunces', serif; font-weight:600; font-variant-numeric:tabular-nums; margin:.6rem 0 .8rem;}
  .vu-split-bar div{display:grid; place-items:center;}
  .vu-split-bar .a{flex:50; background:var(--vu-blue); color:#fff;}
  .vu-split-bar .b{flex:30; background:var(--vu-orange); color:var(--vu-navy);}
  .vu-split-bar .c{flex:20; background:#E6F3EC; color:var(--vu-forest);}
  .vu-split-legend{display:grid; grid-template-columns:5fr 3fr 2fr; gap:.6rem; font-size:.85rem; color:rgba(230,243,236,.8);}
  .vu-split-legend b{display:block; color:#fff;}
  .vu-tracks{display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-top:1.2rem;}
  .vu-track{background:#fff; color:var(--vu-ink); border-radius:18px; padding:1.5rem;}
  .vu-track small{display:block; font-size:.72rem; font-weight:600; letter-spacing:.12em; text-transform:uppercase; color:var(--vu-ink-soft); margin-bottom:.4rem;}
  .vu-track h3{color:var(--vu-ink); font-size:1.3rem; margin-bottom:.8rem;}
  .vu-track .vu-ticks{margin-bottom:.9rem;}
  .vu-track .out{font-size:.86rem; background:#E8F5EE; border-radius:10px; padding:.6rem .8rem;}
  .vu-track .out b{color:var(--vu-green);}
  .vu-retreat-foot{margin-top:2rem; display:flex; flex-wrap:wrap; gap:1rem 2rem; align-items:center; justify-content:space-between;}
  .vu-retreat-foot p{color:rgba(230,243,236,.85); font-size:.95rem; max-width:62ch;}
  @media (max-width:880px){ .vu-retreat-top, .vu-tracks{grid-template-columns:1fr;} .vu-retreat-top{gap:2.5rem;} }
  @media (max-width:520px){ .vu-split-legend{grid-template-columns:1fr;} }

  /* ---------- Bausteine ---------- */
  .vu-mods{display:grid; grid-template-columns:repeat(4,1fr); gap:.9rem;}
  .vu-mod{background:#fff; border:1px solid var(--vu-line); border-radius:16px; padding:1.2rem 1.25rem; transition:transform .2s ease, box-shadow .2s ease;}
  .vu-mod:hover{transform:translateY(-3px); box-shadow:0 14px 28px -16px rgba(19,38,52,.25);}
  .vu-mod h3{font-family:'Work Sans', sans-serif; font-weight:600; letter-spacing:0; font-size:1rem; color:var(--vu-ink); display:flex; align-items:center; gap:.5rem; margin-bottom:.35rem;}
  .vu-mod h3 i{width:10px; height:10px; border-radius:3px; transform:rotate(45deg); flex:none;}
  .vu-mod p{color:var(--vu-ink-soft); font-size:.88rem;}
  .vu-mod small{display:block; margin-top:.55rem; font-size:.78rem; font-weight:600; color:var(--vu-blue);}
  @media (max-width:1000px){ .vu-mods{grid-template-columns:repeat(2,1fr);} }
  @media (max-width:520px){ .vu-mods{grid-template-columns:1fr;} }

  /* ---------- Galerie ---------- */
  .vu-gallery{background:var(--vu-navy-2); padding:5.5rem 0;}
  .vu-gallery .vu-title{color:#fff;}
  .vu-gallery .vu-lede{color:#9FB6C7;}
  .vu-ggrid{display:grid; grid-template-columns:repeat(4,1fr); grid-auto-rows:160px; gap:.7rem;}
  .vu-g{position:relative !important; overflow:hidden !important; border-radius:14px; cursor:zoom-in; border:1px solid rgba(240,166,23,.15); padding:0; background:#0B2537;}
  .vu-g img{width:100% !important; height:100% !important; max-width:none !important; object-fit:cover !important; display:block !important; margin:0 !important; transition:transform .5s ease;}
  .vu-g:hover img{transform:scale(1.08);}
  .vu-g span{position:absolute; left:.7rem; bottom:.6rem; z-index:1; color:#fff; font-size:.8rem; font-weight:600; text-shadow:0 1px 6px rgba(0,0,0,.6);}
  .vu-g.tall{grid-row:span 2;} .vu-g.wide{grid-column:span 2;}
  .vu-lightbox{position:fixed; inset:0; z-index:1000; background:rgba(7,26,40,.94); display:none; align-items:center; justify-content:center; padding:2rem;}
  .vu-lightbox.open{display:flex;}
  .vu-lightbox img{max-width:min(92vw,1100px) !important; max-height:86vh !important; width:auto !important; height:auto !important; border-radius:12px;}
  .vu-lightbox button{position:absolute; top:1.4rem; right:1.4rem; width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.3); color:#fff; font-size:1.4rem; cursor:pointer;}
  @media (max-width:880px){ .vu-ggrid{grid-template-columns:repeat(2,1fr);} }

  /* ---------- Eckdaten ---------- */
  .vu-info{background:#fff; border:1px solid var(--vu-line); border-radius:22px; overflow:hidden; display:grid; grid-template-columns:repeat(3,1fr); box-shadow:0 24px 48px -30px rgba(19,38,52,.35);}
  .vu-info > div{padding:1.5rem 1.7rem; border-bottom:1px solid var(--vu-line); border-right:1px solid var(--vu-line);}
  .vu-info > div:nth-child(3n){border-right:0;}
  .vu-info > div:nth-last-child(-n+3){border-bottom:0;}
  .vu-info .l{font-size:.72rem; text-transform:uppercase; letter-spacing:.12em; font-weight:600; color:var(--vu-orange-ink); margin-bottom:.35rem;}
  .vu-info .v{font-weight:500; color:var(--vu-ink);}
  .vu-info .v small{display:block; color:var(--vu-ink-soft); font-weight:400; font-size:.86rem; margin-top:.2rem;}
  @media (max-width:880px){
    .vu-info{grid-template-columns:1fr;}
    .vu-info > div{border-right:0 !important; border-bottom:1px solid var(--vu-line) !important;}
    .vu-info > div:last-child{border-bottom:0 !important;}
  }

  /* ---------- Team ---------- */
  .vu-team{display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:3.5rem; align-items:center;}
  .vu-team-photo{border-radius:63% 37% 54% 46% / 48% 43% 57% 52%; overflow:hidden; border:3px solid rgba(0,95,152,.25); aspect-ratio:4/3.2; animation:vu-morph 16s ease-in-out infinite;}
  .vu-team-photo img{width:100% !important; height:100% !important; max-width:none !important; object-fit:cover !important; display:block !important;}
  .vu-people{display:flex; flex-direction:column; gap:1rem; margin-top:1.6rem;}
  .vu-person{display:flex; gap:1rem; align-items:center;}
  .vu-person img{width:64px !important; height:64px !important; border-radius:50% !important; object-fit:cover; flex:none;}
  .vu-person b{display:block; font-family:'Fraunces', serif; font-weight:600; font-size:1.1rem;}
  .vu-person span{font-size:.9rem; color:var(--vu-ink-soft);}
  .vu-skills{display:flex; flex-wrap:wrap; gap:.4rem; margin-top:1.4rem;}
  @media (max-width:880px){ .vu-team{grid-template-columns:minmax(0,1fr); gap:2.5rem;} }

  /* ---------- FAQ ---------- */
  .vu-faq{display:grid; grid-template-columns:1fr 1fr; gap:.8rem 1.4rem; align-items:start;}
  .vu-faq details{background:#fff; border:1px solid var(--vu-line); border-radius:14px; padding:1rem 1.2rem;}
  .vu-faq summary{cursor:pointer; list-style:none; font-weight:600; display:flex; justify-content:space-between; gap:1rem; align-items:center;}
  .vu-faq summary::-webkit-details-marker{display:none;}
  .vu-faq summary::after{content:"+"; flex:none; width:28px; height:28px; border-radius:50%; background:var(--vu-blue-soft); color:var(--vu-blue); display:grid; place-items:center; font-size:1.15rem;}
  .vu-faq details[open] summary::after{content:"–"; background:var(--vu-blue); color:#fff;}
  .vu-faq details p{margin-top:.6rem; color:var(--vu-ink-soft); font-size:.94rem;}
  @media (max-width:760px){ .vu-faq{grid-template-columns:1fr;} }

  /* ---------- Anfrage ---------- */
  .vu-form-wrap{max-width:860px; margin:0 auto; background:#fff; border:1px solid var(--vu-line); border-radius:22px; padding:2rem; box-shadow:0 24px 48px -30px rgba(19,38,52,.35);}
  .vu-form{display:grid; grid-template-columns:1fr 1fr; gap:1rem 1.2rem;}
  .vu-f{display:flex; flex-direction:column; gap:.35rem; min-width:0;}
  .vu-f--full{grid-column:1 / -1;}
  .vu-f label{font-size:.88rem; font-weight:600; color:var(--vu-ink); margin:0;}
  .vu-f input, .vu-f select, .vu-f textarea{font-family:'Work Sans', sans-serif; font-size:1rem; color:var(--vu-ink); background:var(--vu-paper); border:1.5px solid var(--vu-line); border-radius:12px; padding:.75rem .9rem; width:100%; height:auto; box-shadow:none;}
  .vu-f input:focus, .vu-f select:focus, .vu-f textarea:focus{outline:none; border-color:var(--vu-blue); background:#fff; box-shadow:0 0 0 4px rgba(0,95,152,.12);}
  .vu-f textarea{min-height:130px; resize:vertical;}
  .vu-check{display:flex; gap:.6rem; align-items:flex-start; font-size:.86rem; color:var(--vu-ink-soft); margin:0;}
  .vu-check input{width:18px; height:18px; margin-top:3px; flex:none; accent-color:var(--vu-green);}
  .vu-form-foot{grid-column:1 / -1; display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between;}
  .vu-form-foot small{color:var(--vu-ink-soft); font-size:.82rem;}
  .vu-msg{border-radius:12px; padding:1rem 1.2rem; margin-bottom:1.2rem; font-size:.95rem;}
  .vu-msg--ok{background:#E8F5EE; color:#0D5A41; border:1px solid rgba(0,127,92,.3);}
  .vu-msg--err{background:#FDECEA; color:#8A1C12; border:1px solid rgba(180,35,24,.25);}
  .vu-msg ul{margin:.3rem 0 0; padding-left:1.1rem;}
  .vu-hp{position:absolute !important; left:-9999px !important; width:1px; height:1px; overflow:hidden;}
  @media (max-width:620px){ .vu-form{grid-template-columns:1fr;} .vu-form-wrap{padding:1.3rem;} }

  /* ---------- Schluss ---------- */
  .vu-final{background:radial-gradient(ellipse at 50% 0%, #0E4466 0%, var(--vu-navy) 60%); padding:6rem 0 5rem; text-align:center; color:var(--vu-on-dark);}
  .vu-final h2{color:#fff; font-size:clamp(2rem,4vw,2.9rem); max-width:20ch; margin:0 auto 1rem;}
  .vu-final h2 em{font-style:italic; font-weight:400; color:var(--vu-orange);}
  .vu-final p{color:#C9DBE8; max-width:48ch; margin:0 auto 2.2rem; font-size:1.05rem;}
  .vu-final .line{margin-top:3rem; color:rgba(233,241,247,.55); font-size:.85rem;}
</style>
<?php
  include '/volume1/web/variado/template/header_dynamic.php';
?>
</head>
<body>
<div class="vu">

<!-- SVG-Symbole -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <path id="vu-pz" d="M20 20 H40 C42 16 36 12 40 6 C44 0 56 0 60 6 C64 12 58 16 60 20 H80 V40 C84 42 88 36 94 40 C100 44 100 56 94 60 C88 64 84 58 80 60 V80 H60 C58 76 64 72 60 66 C56 60 44 60 40 66 C36 72 42 76 40 80 H20 V60 C24 58 28 64 34 60 C40 56 40 44 34 40 C28 36 24 42 20 40 Z"/>
    <symbol id="vu-check" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="vu-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="vu-group" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c.5-3.8 3.1-6 6.5-6s6 2.2 6.5 6M16 4.5a3.5 3.5 0 0 1 0 7M18 14c2 .6 3.3 2.6 3.5 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="vu-pin" viewBox="0 0 24 24"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
  </defs>
</svg>

<nav class="vu-nav">
  <div class="vu-wrap">
    <a href="https://www.variado.de" aria-label="variado Startseite"><img src="<?= $img ?>logo.png" alt="variado. Die ErLebenswerkstatt" width="98" height="40"></a>
    <div class="vu-nav-links">
      <a href="#pakete">Pakete</a>
      <a href="#gruppen">Gruppengrößen</a>
      <a href="#retreat">Familien-Retreat</a>
      <a href="#bausteine">Bausteine</a>
      <a href="#eckdaten">Eckdaten</a>
      <a class="vu-btn vu-btn--primary" href="#anfrage">Unverbindlich anfragen</a>
    </div>
  </div>
</nav>

<!-- ================= HERO ================= -->
<header class="vu-hero">
  <div class="vu-float" id="vuFloat" aria-hidden="true"></div>
  <div class="vu-wrap vu-hero-inner">
    <div>
      <div class="vu-eyebrow on-dark">Teamentwicklung für Unternehmen</div>
      <h1>Teams, die <em id="vuRot">zusammenwachsen.</em></h1>
      <p class="vu-sub">Teambuilding, Achtsamkeit, Resilienz, Führung und Strategie. Euer Team erlebt, statt nur zuzuhören: draußen in der Natur der Rhön, am Feuer und im Raumschiff-Simulator.</p>
      <div class="vu-meta">
        <div class="vu-pill"><svg aria-hidden="true"><use href="#vu-group"/></svg>10 bis 300 Personen</div>
        <div class="vu-pill"><svg aria-hidden="true"><use href="#vu-clock"/></svg>½ bis 3 Tage</div>
        <div class="vu-pill"><svg aria-hidden="true"><use href="#vu-pin"/></svg>Rhön · Thüringer Hütte · bei Euch</div>
      </div>
      <div class="vu-hero-cta">
        <a href="#pakete" class="vu-btn vu-btn--primary">Pakete entdecken</a>
        <a href="#anfrage" class="vu-btn vu-btn--ghost">Kostenloses Vorgespräch</a>
      </div>
    </div>
    <div class="vu-photos">
      <div class="vu-blob vu-blob--lg"><img src="<?= $img ?>teamtraining.jpg" alt="Ein Team hebt eine Person gemeinsam durch ein Seilnetz im Wald"></div>
      <div class="vu-blob vu-blob--sm"><img src="<?= $img ?>feuer.jpg" alt="Gruppe am Lagerfeuer bei Nacht"></div>
      <div class="vu-badge"><b>Mensch</b> · <b>Natur</b> · <b>Technik</b></div>
    </div>
  </div>
</header>

<section class="vu-stats" aria-label="Auf einen Blick">
  <div class="vu-wrap">
    <div class="vu-stat"><b>6</b><span>Pakete für Eure Ziele</span></div>
    <div class="vu-stat"><b>12</b><span>Bausteine zum Kombinieren</span></div>
    <div class="vu-stat"><b>10<i>–</i>300</b><span>Personen pro Gruppe</span></div>
    <div class="vu-stat"><b>365</b><span>Tage im Jahr, drinnen &amp; draußen</span></div>
  </div>
</section>

<!-- ================= WARUM ================= -->
<section class="vu-band" id="warum">
  <div class="vu-wrap">
    <div class="vu-head">
      <div class="vu-eyebrow">Warum variado</div>
      <h2 class="vu-title">Lernen, das man <em>spürt.</em></h2>
      <p class="vu-lede">Teams verändern sich nicht durch Folien. Sie verändern sich durch Erlebnisse, die man gemeinsam auswertet. Dafür verbinden wir drei Welten.</p>
    </div>
    <div class="vu-grid3">
      <div class="vu-card">
        <div class="vu-icon vu-icon--m"><svg viewBox="14 0 88 86" aria-hidden="true"><use href="#vu-pz" fill="#fff"/></svg></div>
        <h3>Mensch</h3>
        <p>Vertrauen, Kommunikation, Rollen und Stärken. Wir arbeiten an dem, was Teams im Kern zusammenhält.</p>
        <div class="vu-tags"><span class="vu-tag">Teamtraining</span><span class="vu-tag">Coaching</span><span class="vu-tag">Konfliktprävention</span></div>
      </div>
      <div class="vu-card">
        <div class="vu-icon vu-icon--n"><svg viewBox="14 0 88 86" aria-hidden="true"><use href="#vu-pz" fill="#fff"/></svg></div>
        <h3>Natur</h3>
        <p>Die Rhön ist unser Lernraum. Draußen kommen Menschen zur Ruhe, werden mutig und begegnen sich neu.</p>
        <div class="vu-tags"><span class="vu-tag">Achtsamkeit</span><span class="vu-tag">Waldbaden</span><span class="vu-tag">Feuer</span></div>
      </div>
      <div class="vu-card">
        <div class="vu-icon vu-icon--t"><svg viewBox="14 0 88 86" aria-hidden="true"><use href="#vu-pz" fill="#fff"/></svg></div>
        <h3>Technik</h3>
        <p>Immersive Simulationen und strukturierte Methoden für Führung, Strategie und Innovation.</p>
        <div class="vu-tags"><span class="vu-tag">Raumschiff-Simulator</span><span class="vu-tag">3D-Netz</span><span class="vu-tag">ThinkTank</span></div>
      </div>
    </div>
    <ol class="vu-loop" aria-label="So wirkt jede Einheit">
      <li><span class="n">1</span><div><b>Erleben</b><span>Eine Aufgabe, die nur gemeinsam lösbar ist.</span></div></li>
      <li><span class="n">2</span><div><b>Reflektieren</b><span>Was ist passiert? Was hat geholfen, was gebremst?</span></div></li>
      <li><span class="n">3</span><div><b>Übertragen</b><span>Was heißt das für Montagmorgen im Büro?</span></div></li>
    </ol>
  </div>
</section>

<!-- ================= GRUPPENGRÖSSEN ================= -->
<section class="vu-band vu-band--paper" id="gruppen">
  <div class="vu-wrap">
    <div class="vu-head">
      <div class="vu-eyebrow">Für jede Gruppengröße</div>
      <h2 class="vu-title">Vom Führungskreis bis zur <em>ganzen Belegschaft.</em></h2>
      <p class="vu-lede">Mit einem festen Trainer-Duo, mit mehreren Teams parallel oder als großes Gemeinschaftserlebnis.</p>
    </div>
    <div class="vu-grid3">
      <div class="vu-card vu-size">
        <div class="vu-range">10–30<small>Personen</small></div>
        <div class="vu-dots" data-n="20" data-t="2" aria-hidden="true"></div>
        <h3>Volle Tiefe</h3>
        <p>Ein festes Trainer-Duo begleitet Euch durchgehend. Alle Formate sind möglich, auch Raumschiff-Simulator und ThinkTank.</p>
        <span class="vu-fit">Alle Pakete</span>
      </div>
      <div class="vu-card vu-size">
        <div class="vu-range">30–80<small>Personen</small></div>
        <div class="vu-dots" data-n="60" data-t="5" aria-hidden="true"></div>
        <h3>Parallel arbeiten</h3>
        <p>Mehrere Teamer:innen arbeiten mit Kleingruppen von 10 bis 15 Personen gleichzeitig. Im Plenum führen wir alles zusammen.</p>
        <span class="vu-fit">Teamtag · Klausur · Achtsamkeit · Festival</span>
      </div>
      <div class="vu-card vu-size">
        <div class="vu-range">80–300<small>Personen</small></div>
        <div class="vu-dots" data-n="150" data-t="10" aria-hidden="true"></div>
        <h3>Ein gemeinsames Erlebnis</h3>
        <p>Gemeinsamer Auftakt, dann rotieren gemischte Gruppen durch Themeninseln. Ein großes variado-Team ist vor Ort.</p>
        <span class="vu-fit">Teamtag · Achtsamkeit · Festival</span>
      </div>
    </div>
  </div>
</section>

<!-- ================= PAKETE ================= -->
<section class="vu-band" id="pakete">
  <div class="vu-wrap">
    <div class="vu-head">
      <div class="vu-eyebrow">Unsere Pakete</div>
      <h2 class="vu-title">Sechs Formate. <em>Euer Ziel.</em></h2>
      <p class="vu-lede">Wählt Gruppengröße und Ziel, wir zeigen Euch, was passt. Jedes Paket stimmen wir im Vorgespräch auf Euer Team ab.</p>
    </div>

    <div class="vu-filter" role="group" aria-label="Pakete filtern">
      <div class="vu-fgroup" id="vuSize">
        <span>Gruppe</span>
        <button class="vu-chip-btn" type="button" data-size="all" aria-pressed="true">Alle</button>
        <button class="vu-chip-btn" type="button" data-size="s" aria-pressed="false">10–30</button>
        <button class="vu-chip-btn" type="button" data-size="m" aria-pressed="false">30–80</button>
        <button class="vu-chip-btn" type="button" data-size="l" aria-pressed="false">80–300</button>
      </div>
      <div class="vu-fgroup" id="vuGoal">
        <span>Ziel</span>
        <button class="vu-chip-btn" type="button" data-goal="all" aria-pressed="true">Alle</button>
        <button class="vu-chip-btn" type="button" data-goal="team" aria-pressed="false">Teamgeist</button>
        <button class="vu-chip-btn" type="button" data-goal="health" aria-pressed="false">Gesundheit</button>
        <button class="vu-chip-btn" type="button" data-goal="lead" aria-pressed="false">Führung</button>
        <button class="vu-chip-btn" type="button" data-goal="strategy" aria-pressed="false">Strategie</button>
      </div>
    </div>
    <p class="vu-count" id="vuCount" aria-live="polite"><b>6 von 6</b> Paketen passen.</p>

    <div class="vu-pkgs">

      <article class="vu-pkg" data-sizes="s m l" data-goals="team" data-pkg="Teamtag „Gemeinsam stark“">
        <div class="vu-pkg-img"><img src="<?= $img ?>team-tauziehen.jpg" alt="Lachende Kolleginnen beim Tauziehen auf einer Wiese">
          <div class="vu-pkg-meta"><span><svg aria-hidden="true"><use href="#vu-clock"/></svg>1 Tag</span><span><svg aria-hidden="true"><use href="#vu-group"/></svg>10–300 Personen</span></div></div>
        <div class="vu-pkg-body">
          <span class="vu-kicker m"><i></i>Teamgeist</span>
          <h3>Teamtag „Gemeinsam stark“</h3>
          <p class="vu-claim">Ein Tag voller Aufgaben, die nur gemeinsam lösbar sind. Danach seid Ihr spürbar mehr Team.</p>
          <ul class="vu-ticks">
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Kooperation: Säureteich, Pipeline, Blinde Mathematik</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Kommunikation: Teamkran, LandArt, Funkorientierung</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Koordination: Turmbau, Mini-Floßbau, Speed-Ball</li>
          </ul>
          <div class="vu-gain"><b>Ihr nehmt mit</b>Ein gemeinsames Erfolgserlebnis und klare Spielregeln für die Zusammenarbeit.</div>
          <details><summary>Beispielablauf</summary>
            <ol class="vu-tl">
              <li><time>09:00</time><span>Ankommen, Warm-up, Erwartungen</span></li>
              <li><time>09:30</time><span>Einheit 1: Kooperation</span></li>
              <li><time>11:15</time><span>Einheit 2: Kommunikation</span></li>
              <li><time>12:30</time><span>Mittagspause</span></li>
              <li><time>13:30</time><span>Einheit 3: große Teamaufgabe</span></li>
              <li><time>15:15</time><span>Reflexion und Transfer</span></li>
              <li><time>16:00</time><span>Abschluss</span></li>
            </ol></details>
          <div class="vu-go"><a class="vu-btn vu-btn--blue" href="#anfrage" data-vu-pick>Paket anfragen</a></div>
        </div>
      </article>

      <article class="vu-pkg" data-sizes="s m" data-goals="team lead" data-pkg="Team-Klausur „Zusammenwachsen“">
        <div class="vu-pkg-img"><img src="<?= $img ?>feuer.jpg" alt="Gruppe am Lagerfeuer bei Nacht mit Funkenspuren">
          <div class="vu-pkg-meta"><span><svg aria-hidden="true"><use href="#vu-clock"/></svg>2 Tage</span><span><svg aria-hidden="true"><use href="#vu-group"/></svg>10–80 Personen</span></div></div>
        <div class="vu-pkg-body">
          <span class="vu-kicker m"><i></i>Teamkultur</span>
          <h3>Team-Klausur „Zusammenwachsen“</h3>
          <p class="vu-claim">Draußen erleben, am Feuer zusammenrücken und am zweiten Tag verbindlich vereinbaren, wie Ihr arbeiten wollt.</p>
          <ul class="vu-ticks">
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Outdoor-Teamtraining am ersten Tag</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Feuerabend: Fackeln, Feuer ohne Feuerzeug, Gruppenfoto in Flammen</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Rollen, Stärken, Kommunikation und Teamvereinbarung</li>
          </ul>
          <div class="vu-gain"><b>Ihr nehmt mit</b>Eine Teamvereinbarung mit Verantwortlichkeiten und nächsten Schritten.</div>
          <details><summary>Beispielablauf</summary>
            <ol class="vu-tl">
              <li class="day">Tag 1</li>
              <li><time>10:00</time><span>Auftakt und Erwartungen</span></li>
              <li><time>11:00</time><span>Teamtraining draußen</span></li>
              <li><time>18:30</time><span>Pizza aus dem Holzbackofen</span></li>
              <li><time>20:00</time><span>Feuerabend mit Gruppenritual</span></li>
              <li class="day">Tag 2</li>
              <li><time>09:00</time><span>Rollen und Stärken im Team</span></li>
              <li><time>11:00</time><span>Kommunikation und Konfliktprävention</span></li>
              <li><time>14:00</time><span>Teamvereinbarung, nächste Schritte</span></li>
            </ol></details>
          <div class="vu-go"><a class="vu-btn vu-btn--blue" href="#anfrage" data-vu-pick>Paket anfragen</a></div>
        </div>
      </article>

      <article class="vu-pkg" data-sizes="s m l" data-goals="health" data-pkg="„Kraftquelle Rhön“ – Achtsamkeit &amp; Resilienz">
        <div class="vu-pkg-img"><img src="<?= $img ?>bachlauf.jpg" alt="Klarer Bachlauf zwischen moosbedeckten Steinen im Wald">
          <div class="vu-pkg-meta"><span><svg aria-hidden="true"><use href="#vu-clock"/></svg>1–2 Tage</span><span><svg aria-hidden="true"><use href="#vu-group"/></svg>10–300 Personen</span></div></div>
        <div class="vu-pkg-body">
          <span class="vu-kicker n"><i></i>Gesundheit &amp; Resilienz</span>
          <h3>„Kraftquelle Rhön“</h3>
          <p class="vu-claim">Achtsamkeit, Stressmanagement und Resilienz für Teams, die viel leisten und gesund bleiben wollen.</p>
          <ul class="vu-ticks">
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Achtsamkeitswanderung und Waldbaden</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Stressmuster erkennen, Resilienz trainieren</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Impulse aus dem Glücksmanagement</li>
          </ul>
          <div class="vu-gain"><b>Ihr nehmt mit</b>Einen persönlichen Kraftplan und gesündere Routinen im Team.</div>
          <details><summary>Beispielablauf (1 Tag)</summary>
            <ol class="vu-tl">
              <li><time>09:00</time><span>Ankommen mit Achtsamkeitsübung</span></li>
              <li><time>09:45</time><span>Was stresst uns, was stärkt uns?</span></li>
              <li><time>11:00</time><span>Achtsamkeitswanderung und Waldbaden</span></li>
              <li><time>13:00</time><span>Mittagspause</span></li>
              <li><time>14:00</time><span>Resilienzfaktoren trainieren</span></li>
              <li><time>15:30</time><span>Persönlicher Kraftplan, Abschluss</span></li>
            </ol></details>
          <div class="vu-go"><a class="vu-btn vu-btn--blue" href="#anfrage" data-vu-pick>Paket anfragen</a></div>
        </div>
      </article>

      <article class="vu-pkg" data-sizes="s" data-goals="lead" data-pkg="LeaderShip „Kurs halten“">
        <div class="vu-pkg-img"><img src="<?= $img ?>spaceship.jpg" alt="Teilnehmende an Konsolen im Raumschiff-Simulator">
          <div class="vu-pkg-meta"><span><svg aria-hidden="true"><use href="#vu-clock"/></svg>½–2 Tage</span><span><svg aria-hidden="true"><use href="#vu-group"/></svg>4–30 Personen</span></div></div>
        <div class="vu-pkg-body">
          <span class="vu-kicker t"><i></i>Führung</span>
          <h3>LeaderShip „Kurs halten“</h3>
          <p class="vu-claim">Was macht einen guten Captain aus? Führung live erleben im Raumschiff-Simulator und in den Alltag übertragen.</p>
          <ul class="vu-ticks">
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Crews à 4–6 Personen, weitere beobachten</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Rollenanalyse, Feedbackkultur, Zielsetzung</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Optional: Business-Coaching und Konfliktprävention</li>
          </ul>
          <div class="vu-gain"><b>Ihr nehmt mit</b>Ein gemeinsames Führungsverständnis und persönliche Entwicklungsziele.</div>
          <details><summary>Ablauf der Raumfahrt</summary>
            <ol class="vu-tl">
              <li><time>Start</time><span>Eure Führungskultur in Kürze</span></li>
              <li><time>Briefing</time><span>Rollen, Feedbackregeln, Ziel</span></li>
              <li><time>Flug 1</time><span>Übungsflug, Missionswahl</span></li>
              <li><time>Flug 2</time><span>Mission, Feedback, Learnings</span></li>
              <li><time>Transfer</time><span>Was heißt das für unseren Alltag?</span></li>
            </ol></details>
          <div class="vu-go"><a class="vu-btn vu-btn--blue" href="#anfrage" data-vu-pick>Paket anfragen</a></div>
        </div>
      </article>

      <article class="vu-pkg" data-sizes="s" data-goals="strategy lead" data-pkg="Zukunftswerkstatt „Strategie &amp; Innovation“">
        <div class="vu-pkg-img"><img src="<?= $img ?>3d-netz.jpg" alt="Workshop mit Seilnetz und Karten auf dem Boden">
          <div class="vu-pkg-meta"><span><svg aria-hidden="true"><use href="#vu-clock"/></svg>2–3 Tage</span><span><svg aria-hidden="true"><use href="#vu-group"/></svg>5–30 Personen</span></div></div>
        <div class="vu-pkg-body">
          <span class="vu-kicker t"><i></i>Strategie &amp; Innovation</span>
          <h3>Zukunftswerkstatt</h3>
          <p class="vu-claim">Vom ehrlichen Blick auf das Unternehmen zum umsetzbaren Prototyp, mit 3D-Netz und ThinkTank.</p>
          <ul class="vu-ticks">
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>3D-Netz: Euer Unternehmen in Mensch, Natur, Technik</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Potenzialanalyse und Zoom-on-Innovation</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>ThinkTank in 5 Phasen bis zum Prototyp</li>
          </ul>
          <div class="vu-gain"><b>Ihr nehmt mit</b>Priorisierte Handlungsfelder, einen Prototyp und einen Umsetzungsplan.</div>
          <details><summary>Die Phasen</summary>
            <ol class="vu-tl">
              <li><time>1</time><span>Ist-Analyse im Drei-Dimensionen-Netz</span></li>
              <li><time>2</time><span>Potenziale erkennen und auswählen</span></li>
              <li><time>3</time><span>Recherche und Netzwerk-Mindmap</span></li>
              <li><time>4</time><span>Innovation und Prototyping</span></li>
              <li><time>5</time><span>Präsentation und Umsetzungsplanung</span></li>
            </ol></details>
          <div class="vu-go"><a class="vu-btn vu-btn--blue" href="#anfrage" data-vu-pick>Paket anfragen</a></div>
        </div>
      </article>

      <article class="vu-pkg" data-sizes="m l" data-goals="team health" data-pkg="Team-Festival „Großgruppe in Bewegung“">
        <div class="vu-pkg-img"><img src="<?= $img ?>thueringer-huette.jpg" alt="Lange Menschenkette auf einer Wiese vor einem roten Holzhaus">
          <div class="vu-pkg-meta"><span><svg aria-hidden="true"><use href="#vu-clock"/></svg>1 Tag</span><span><svg aria-hidden="true"><use href="#vu-group"/></svg>80–300 Personen</span></div></div>
        <div class="vu-pkg-body">
          <span class="vu-kicker n"><i></i>Großgruppe</span>
          <h3>Team-Festival</h3>
          <p class="vu-claim">Ein Tag für die ganze Belegschaft: gemeinsam starten, durch Themeninseln rotieren, gemeinsam feiern.</p>
          <ul class="vu-ticks">
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Themeninseln: Teamchallenges, Achtsamkeit, Feuer, LandArt</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Gemischte Gruppen à 12–20 mit eigenen Teamer:innen</li>
            <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Großes Finale: eine Aufgabe für alle</li>
          </ul>
          <div class="vu-gain"><b>Ihr nehmt mit</b>Neue Verbindungen quer durchs Unternehmen und ein Erlebnis, über das man lange spricht.</div>
          <details><summary>Beispielablauf</summary>
            <ol class="vu-tl">
              <li><time>09:30</time><span>Auftakt im Plenum, Gruppen bilden</span></li>
              <li><time>10:15</time><span>Rotation 1 und 2</span></li>
              <li><time>12:30</time><span>Mittagspause</span></li>
              <li><time>13:30</time><span>Rotation 3 und 4</span></li>
              <li><time>15:30</time><span>Finale aller Gruppen</span></li>
              <li><time>16:30</time><span>Abschluss im Plenum</span></li>
            </ol></details>
          <div class="vu-go"><a class="vu-btn vu-btn--blue" href="#anfrage" data-vu-pick>Paket anfragen</a></div>
        </div>
      </article>
    </div>

    <div class="vu-custom">
      <div><h3>Nichts dabei, was genau passt?</h3><p>Wir bauen Euer Programm aus unseren Bausteinen: als halben Tag, als Ergänzung zu Eurer Tagung oder als mehrtägige Klausur.</p></div>
      <a class="vu-btn vu-btn--primary" href="#bausteine">Zu den Bausteinen</a>
    </div>
  </div>
</section>

<!-- ================= RETREAT ================= -->
<section class="vu-retreat" id="retreat">
  <div class="vu-wrap">
    <div class="vu-retreat-top">
      <div>
        <span class="vu-new">Neu · Pilotunternehmen gesucht</span><br>
        <div class="vu-eyebrow on-dark">Familien-Unternehmens-Retreat</div>
        <h2>Weiterkommen, <em>ohne Familienzeit zu opfern.</em></h2>
        <p class="vu-p">Ein Wochenende, an dem Mitarbeitende mit Partner:innen und Kindern anreisen. Das Unternehmen arbeitet an Team, Kultur und Zukunft. Familien und Kinder erleben ihr eigenes Programm. An den Übergängen kommen alle zusammen: beim Essen, am Pizzaofen, am Lagerfeuer.</p>
        <p class="vu-p">Freitag bis Sonntag, optional ab Donnerstag. Für Kinder ab Grundschulalter.</p>
        <div class="vu-callout">„Stärkere Teams, tiefere Beziehungen, mehr Vertrauen und neue Perspektiven für die Zukunft.“</div>
      </div>
      <div>
        <div class="vu-plist-title">Die Reise des Wochenendes</div>
        <ul class="vu-plist">
          <li><span class="num">01</span><div><b>Ankommen</b><span>Wo bin ich und mit wem bin ich hier?</span></div></li>
          <li><span class="num">02</span><div><b>Erleben &amp; Entwickeln</b><span>Was steckt eigentlich in uns?</span></div></li>
          <li><span class="num">03</span><div><b>Verbinden &amp; Handeln</b><span>Was bedeutet das für unser gemeinsames Leben?</span></div></li>
          <li><span class="num">04</span><div><b>Integrieren &amp; Mitnehmen</b><span>Wie lebt dieses Wochenende weiter?</span></div></li>
        </ul>
        <div class="vu-split">
          <div class="vu-plist-title" style="margin:0">So verteilt sich die Zeit</div>
          <div class="vu-split-bar" role="img" aria-label="50 Prozent Unternehmen, 30 Prozent Familie, 20 Prozent Gemeinschaft"><div class="a">50 %</div><div class="b">30 %</div><div class="c">20 %</div></div>
          <div class="vu-split-legend"><span><b>Unternehmen</b>erfahrungsbasierte Entwicklung</span><span><b>Familie</b>gemeinsam erleben</span><span><b>Gemeinschaft</b>Essen, Feuer, Abende</span></div>
        </div>
      </div>
    </div>

    <div class="vu-tracks">
      <div class="vu-track">
        <small>Unternehmen &amp; Mitarbeitende</small>
        <h3>Team &amp; Zukunft</h3>
        <ul class="vu-ticks">
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Teamvertrauen, Führung, Kommunikation, Kultur, Strategie</li>
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Teamtraining, Simulator, 3D-Netz, Outdoor-Challenges</li>
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Optional ThinkTank mit Perspektiven von Familien und Jugendlichen</li>
        </ul>
        <p class="out"><b>Ergebnis:</b> Team gestärkt, Kultur entwickelt, Zukunftsthemen bearbeitet.</p>
      </div>
      <div class="vu-track">
        <small>Partner:innen &amp; Familien</small>
        <h3>Familie erleben</h3>
        <ul class="vu-ticks">
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Naturmurmelbahn, Familien-Kunstwerk, Kreativwerkstatt</li>
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Familien-Challenges, Zukunftswerkstatt, Gala-Dinner</li>
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Impulse zu Partnerschaft und Familienkommunikation</li>
        </ul>
        <p class="out"><b>Ergebnis:</b> Qualitätszeit und mehr Verständnis für die Arbeitswelt.</p>
      </div>
      <div class="vu-track">
        <small>Kinder ab Grundschulalter</small>
        <h3>Abenteuer Natur</h3>
        <ul class="vu-ticks">
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Geländerallye, Leben im Wald, Feuer, Wasser, Energie</li>
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Teamaufgaben, Nachtwanderung, Lagerfeuer</li>
          <li><svg aria-hidden="true"><use href="#vu-check"/></svg>Auf Basis unserer Schullandheim-Pädagogik</li>
        </ul>
        <p class="out"><b>Ergebnis:</b> Selbstwirksamkeit, Gemeinschaft, Natur erleben.</p>
      </div>
    </div>
    <div class="vu-retreat-foot">
      <p>Wir suchen Unternehmen, die das Format als Erste mit uns erleben und mitgestalten wollen.</p>
      <a class="vu-btn vu-btn--primary" href="#anfrage" data-vu-pick-name="Familien-Unternehmens-Retreat">Pilotunternehmen werden</a>
    </div>
  </div>
</section>

<!-- ================= BAUSTEINE ================= -->
<section class="vu-band vu-band--paper" id="bausteine">
  <div class="vu-wrap">
    <div class="vu-head">
      <div class="vu-eyebrow">Baukasten</div>
      <h2 class="vu-title">12 Bausteine. <em>Frei kombinierbar.</em></h2>
      <p class="vu-lede">Aus diesen Modulen entstehen unsere Pakete und Euer individuelles Programm.</p>
    </div>
    <div class="vu-mods">
      <div class="vu-mod"><h3><i style="background:var(--vu-orange)"></i>Teamtraining</h3><p>Erlebnispädagogische Übungen für Kooperation, Kommunikation und Vertrauen.</p><small>10–300 Personen</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-orange)"></i>Feuertag</h3><p>Fackeln, Feuer ohne Feuerzeug, Feuer-Fakir und Gruppenritual.</p><small>10–30 Personen · 4 h</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-blue)"></i>LeaderShip</h3><p>Führungskompetenz im Raumschiff-Simulator mit Auswertung.</p><small>4–6 aktiv je Crew · 3 h</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-blue)"></i>3D-Netz</h3><p>Unternehmensanalyse in Mensch, Natur und Technik mit Umsetzungsplan.</p><small>5–30 Personen</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-blue)"></i>ThinkTank</h3><p>In 5 Phasen von der Fragestellung zum Prototyp.</p><small>5–30 Personen</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-green)"></i>Achtsamkeit</h3><p>Geführte Achtsamkeitswanderungen und Waldbaden in der Rhön.</p><small>auch für Großgruppen</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-green)"></i>Resilienz</h3><p>Stressmanagement und Resilienztraining mit gesunden Teamroutinen.</p><small>10–300 Personen</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-orange)"></i>Konfliktprävention</h3><p>Gewaltfreie Kommunikation und Konfliktlösung, bevor Spannungen eskalieren.</p><small>10–30 Personen</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-orange)"></i>Glücksmanagement</h3><p>Was Menschen bei der Arbeit zufrieden macht und wie Ihr es fördert.</p><small>Impuls oder Workshop</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-orange)"></i>Business-Coaching</h3><p>Einzel- und Teamcoaching für Führungskräfte.</p><small>einzeln oder im Team</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-blue)"></i>Unternehmensberatung</h3><p>Organisationsentwicklung, Potenzialanalyse, Zeitgewinnung.</p><small>vor Ort oder begleitend</small></div>
      <div class="vu-mod"><h3><i style="background:var(--vu-green)"></i>Abendprogramme</h3><p>Pizza aus dem Holzbackofen, Lagerfeuer, Nachtwanderung.</p><small>für jede Gruppe</small></div>
    </div>
  </div>
</section>

<!-- ================= GALERIE ================= -->
<section class="vu-gallery">
  <div class="vu-wrap">
    <div class="vu-head vu-head--c">
      <div class="vu-eyebrow on-dark">Impressionen</div>
      <h2 class="vu-title">So fühlt sich ein Tag mit uns an</h2>
      <p class="vu-lede">Echte Momente aus unseren Programmen. Zum Vergrößern anklicken.</p>
    </div>
    <div class="vu-ggrid" id="vuGallery">
      <button type="button" class="vu-g wide tall"><img src="<?= $img ?>thueringer-huette.jpg" alt="Menschenkette vor dem Energiehaus der Thüringer Hütte"><span>Thüringer Hütte</span></button>
      <button type="button" class="vu-g"><img src="<?= $img ?>spaceship.jpg" alt="Raumschiff-Simulator"><span>Raumschiff-Simulator</span></button>
      <button type="button" class="vu-g tall"><img src="<?= $img ?>teamtraining.jpg" alt="Teamübung im Seilnetz"><span>Teamtraining</span></button>
      <button type="button" class="vu-g"><img src="<?= $img ?>feuer.jpg" alt="Feuerabend"><span>Feuerabend</span></button>
      <button type="button" class="vu-g"><img src="<?= $img ?>3d-netz.jpg" alt="Workshop mit dem 3D-Netz"><span>3D-Netz</span></button>
      <button type="button" class="vu-g"><img src="<?= $img ?>thinktank.jpg" alt="ThinkTank-Workshop am Flipchart"><span>ThinkTank</span></button>
      <button type="button" class="vu-g"><img src="<?= $img ?>bachlauf.jpg" alt="Bachlauf im Wald der Rhön"><span>Natur der Rhön</span></button>
      <button type="button" class="vu-g"><img src="<?= $img ?>herbst.jpg" alt="Herbstliche Buche an der Thüringer Hütte"><span>Herbst an der Hütte</span></button>
    </div>
  </div>
</section>
<div class="vu-lightbox" id="vuLightbox" role="dialog" aria-modal="true" aria-label="Bild vergrößert">
  <button type="button" id="vuLbClose" aria-label="Schließen">&times;</button>
  <img id="vuLbImg" src="" alt="">
</div>

<!-- ================= ECKDATEN ================= -->
<section class="vu-band" id="eckdaten">
  <div class="vu-wrap">
    <div class="vu-head vu-head--c">
      <div class="vu-eyebrow">Eckdaten</div>
      <h2 class="vu-title">Alles auf einen Blick</h2>
    </div>
    <div class="vu-info">
      <div><div class="l">Gruppengröße</div><div class="v">10 bis 300 Personen<small>Großgruppen: Teambuilding, Achtsamkeit, Outdoor</small></div></div>
      <div><div class="l">Dauer</div><div class="v">½ Tag bis 3 Tage<small>Retreat: Freitag bis Sonntag</small></div></div>
      <div><div class="l">Wo</div><div class="v">Rhön &amp; Thüringer Hütte<small>Rother Kuppe 3, 97647 Hausen, oder in Euren Tagungsräumen</small></div></div>
      <div><div class="l">Inklusive</div><div class="v">Vorgespräch, Konzept, Team, Material<small>Reflexion und Transfer in jedem Format</small></div></div>
      <div><div class="l">Kosten</div><div class="v">Individuelles Angebot<small>Programmbausteine ab 60 € pro Person</small></div></div>
      <div><div class="l">Fragen</div><div class="v">Direkt bei variado<small>anfrage@variado.de · +49 176 77860490</small></div></div>
    </div>
  </div>
</section>

<!-- ================= TEAM ================= -->
<section class="vu-band vu-band--paper" id="team">
  <div class="vu-wrap vu-team">
    <div class="vu-team-photo"><img src="<?= $img ?>variado-team.jpg" alt="Das variado-Team in weinroten T-Shirts winkt in die Kamera"></div>
    <div>
      <div class="vu-eyebrow">Wer mit Euch arbeitet</div>
      <h2 class="vu-title">Ein Team. <em>Viele Disziplinen.</em></h2>
      <p class="vu-lede">Beratung, Coaching, Pädagogik und Technik unter einem Dach. Für Euer Programm stellen wir genau das Team zusammen, das Ihr braucht. variado eG ist eine gemeinnützige Genossenschaft, wir arbeiten demokratisch und auf Augenhöhe.</p>
      <div class="vu-skills">
        <span class="vu-tag">Unternehmensberatung</span><span class="vu-tag">Business-Coaching</span><span class="vu-tag">Stressmanagement</span><span class="vu-tag">Resilienztraining</span><span class="vu-tag">Gewalt- &amp; Konfliktprävention</span><span class="vu-tag">Glücksmanagement</span><span class="vu-tag">Achtsamkeit</span><span class="vu-tag">Erlebnispädagogik</span>
      </div>
      <div class="vu-people">
        <div class="vu-person"><img src="<?= $img ?>teresa.jpg" alt="Teresa Radovic"><div><b>Teresa Radovic</b><span>Buchung und Anfragen · anfrage@variado.de</span></div></div>
        <div class="vu-person"><img src="<?= $img ?>jonas.jpg" alt="Jonas Dietz"><div><b>Jonas Dietz</b><span>Fragen zum Programm · jonas@variado.de</span></div></div>
        <div class="vu-person"><img src="<?= $img ?>daniel.jpg" alt="Daniel Friedrich"><div><b>Daniel Friedrich</b><span>Unternehmensentwicklung · daniel@variado.de</span></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ================= FAQ ================= -->
<section class="vu-band" id="faq">
  <div class="vu-wrap">
    <div class="vu-head vu-head--c">
      <div class="vu-eyebrow">Häufige Fragen</div>
      <h2 class="vu-title">Gut zu wissen</h2>
    </div>
    <div class="vu-faq">
      <details><summary>Wie viele Personen sind möglich?</summary><p>10 bis 30 Personen gehen immer und in allen Formaten. Mit 30 bis 80 Personen arbeiten wir mit mehreren Teamer:innen parallel. Für 80 bis 300 Personen eignen sich Teambuilding, Achtsamkeit und Outdoor-Formate mit Themeninseln.</p></details>
      <details><summary>Muss man dafür sportlich sein?</summary><p>Nein. Wir fragen im Vorgespräch nach der Fitness aller Teilnehmenden und wählen die Übungen so, dass alle mitmachen können.</p></details>
      <details><summary>Was passiert bei Regen?</summary><p>Wir arbeiten das ganze Jahr. Viele Übungen funktionieren auch bei Regen, und für jedes Programm planen wir Räume drinnen mit ein.</p></details>
      <details><summary>Können wir Pakete kombinieren?</summary><p>Ja. Die Pakete sind Vorschläge. Aus unseren Bausteinen stellen wir Euer eigenes Programm zusammen, zum Beispiel eine Fachtagung mit Teambuilding-Nachmittag und Feuerabend.</p></details>
      <details><summary>Was kostet ein Programm?</summary><p>Das hängt von Dauer, Gruppengröße und Bausteinen ab. Programmbausteine beginnen bei 60 € pro Person. Nach dem Vorgespräch bekommt Ihr ein konkretes Angebot.</p></details>
      <details><summary>Wo findet das Programm statt?</summary><p>In der Natur der Rhön, in unserem Bildungszentrum Thüringer Hütte mit Energiehaus, Feuerplatz und Raumschiff-Simulator oder in Euren Tagungsräumen vor Ort.</p></details>
    </div>
  </div>
</section>

<!-- ================= ANFRAGE ================= -->
<section class="vu-band vu-band--paper" id="anfrage">
  <div class="vu-wrap">
    <div class="vu-head vu-head--c">
      <div class="vu-eyebrow">Anfrage</div>
      <h2 class="vu-title">Erzählt uns von <em>Eurem Team.</em></h2>
      <p class="vu-lede">Wir melden uns innerhalb von zwei Werktagen für ein kostenloses Vorgespräch.</p>
    </div>
    <div class="vu-form-wrap">
      <?php include 'kontaktformular_unternehmen.php'; ?>
    </div>
  </div>
</section>

<!-- ================= SCHLUSS ================= -->
<section class="vu-final">
  <div class="vu-wrap">
    <div class="vu-eyebrow on-dark">Wir. Ihr. Gemeinsam.</div>
    <h2>Bereit für ein Teamerlebnis, <em>das bleibt?</em></h2>
    <p>Ein kurzes Vorgespräch genügt. Danach bekommt Ihr ein Programm, das zu Eurem Team, Euren Zielen und Eurer Gruppengröße passt.</p>
    <a href="#anfrage" class="vu-btn vu-btn--primary">Jetzt anfragen</a>
    <div class="line">variado eG · gemeinnützige Genossenschaft · Mensch · Natur · Technik</div>
  </div>
</section>

</div><!-- /.vu -->

<?php
  include 'footer.php';
?>

<script>
// Wechselndes Wort im Hero
try {
  var vuRot = document.getElementById('vuRot');
  var vuWords = ['zusammen­wachsen.', 'Feuer fangen.', 'durchatmen.', 'Kurs halten.', 'Zukunft bauen.'];
  if (vuRot && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var vuI = 0;
    setInterval(function () {
      vuRot.classList.add('out');
      setTimeout(function () { vuI = (vuI + 1) % vuWords.length; vuRot.textContent = vuWords[vuI]; vuRot.classList.remove('out'); }, 350);
    }, 2800);
  }
} catch (err) { console.error('Unternehmen: Hero-Animation', err); }

// Aufsteigende Puzzleteile im Hero
try {
  var vuFloat = document.getElementById('vuFloat');
  if (vuFloat && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var vuCols = ['#F0A617', '#007F5C', '#1E86C8'];
    for (var k = 0; k < 16; k++) {
      var s = 10 + Math.random() * 14;
      var el = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      el.setAttribute('viewBox', '14 0 88 86');
      el.setAttribute('width', s); el.setAttribute('height', s);
      el.innerHTML = '<use href="#vu-pz" fill="' + vuCols[k % 3] + '"/>';
      el.style.left = (Math.random() * 100) + '%';
      el.style.animationDuration = (9 + Math.random() * 8) + 's';
      el.style.animationDelay = (Math.random() * 10) + 's';
      el.style.setProperty('--vu-rot', (Math.random() * 240 - 120) + 'deg');
      vuFloat.appendChild(el);
    }
  }
} catch (err) { console.error('Unternehmen: Puzzle-Animation', err); }

// Punkte für Gruppengrößen
try {
  document.querySelectorAll('.vu-dots').forEach(function (d) {
    var n = +d.getAttribute('data-n'), t = +d.getAttribute('data-t');
    for (var i = 0; i < n; i++) { var e = document.createElement('i'); if (i < t) e.className = 't'; d.appendChild(e); }
  });
} catch (err) { console.error('Unternehmen: Gruppengrößen', err); }

// Paketfilter
try {
  var vuState = { size: 'all', goal: 'all' };
  var vuCards = Array.prototype.slice.call(document.querySelectorAll('.vu-pkg'));
  var vuApply = function () {
    var hits = 0, filtered = vuState.size !== 'all' || vuState.goal !== 'all';
    vuCards.forEach(function (c) {
      var ok = (vuState.size === 'all' || c.getAttribute('data-sizes').split(' ').indexOf(vuState.size) > -1) &&
               (vuState.goal === 'all' || c.getAttribute('data-goals').split(' ').indexOf(vuState.goal) > -1);
      c.classList.toggle('is-dim', !ok);
      var b = c.querySelector('.vu-fitbadge');
      if (ok && filtered) { if (!b) { b = document.createElement('span'); b.className = 'vu-fitbadge'; b.textContent = 'Passt'; c.querySelector('.vu-pkg-img').appendChild(b); } }
      else if (b) { b.remove(); }
      if (ok) hits++;
    });
    document.getElementById('vuCount').innerHTML = hits
      ? '<b>' + hits + ' von ' + vuCards.length + '</b> Paketen passen.'
      : '<b>Kein Paket passt genau.</b> Wir stellen Euch gern ein eigenes Programm aus unseren Bausteinen zusammen.';
  };
  [['vuSize', 'size'], ['vuGoal', 'goal']].forEach(function (p) {
    var g = document.getElementById(p[0]);
    g.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      g.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
      vuState[p[1]] = b.getAttribute('data-' + p[1]); vuApply();
    });
  });
  vuApply();
} catch (err) { console.error('Unternehmen: Filter', err); }

// Paket ins Formular übernehmen
try {
  var vuPick = function (name) {
    var sel = document.getElementById('vu-paket');
    if (!sel) return;
    var norm = function (s) { return s.replace(/\s+/g, ' ').trim(); };
    for (var i = 0; i < sel.options.length; i++) { if (norm(sel.options[i].text) === norm(name)) { sel.selectedIndex = i; break; } }
    if (name === 'Familien-Unternehmens-Retreat') { var f = document.getElementById('vu-familie'); if (f) f.checked = true; }
  };
  document.querySelectorAll('[data-vu-pick]').forEach(function (a) {
    a.addEventListener('click', function () { var t = document.createElement('textarea'); t.innerHTML = a.closest('.vu-pkg').getAttribute('data-pkg'); vuPick(t.value); });
  });
  document.querySelectorAll('[data-vu-pick-name]').forEach(function (a) {
    a.addEventListener('click', function () { vuPick(a.getAttribute('data-vu-pick-name')); });
  });
} catch (err) { console.error('Unternehmen: Paketauswahl', err); }

// Galerie-Lightbox
try {
  var vuLb = document.getElementById('vuLightbox'), vuLbImg = document.getElementById('vuLbImg');
  var vuClose = function () { vuLb.classList.remove('open'); vuLbImg.src = ''; };
  document.querySelectorAll('#vuGallery .vu-g').forEach(function (item) {
    item.addEventListener('click', function () { var im = item.querySelector('img'); vuLbImg.src = im.src; vuLbImg.alt = im.alt; vuLb.classList.add('open'); });
  });
  document.getElementById('vuLbClose').addEventListener('click', vuClose);
  vuLb.addEventListener('click', function (e) { if (e.target === vuLb) vuClose(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') vuClose(); });
} catch (err) { console.error('Unternehmen: Lightbox', err); }
</script>

</body>
</html>
