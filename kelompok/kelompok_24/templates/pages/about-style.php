<style>
/* =====================================
   ADVOKITA - PROFESSIONAL DESIGN
===================================== */

.advokita-page {
  --adv-navy: #24344d;
  --adv-navy-dark: #18263b;
  --adv-red: #b42332;
  --adv-red-dark: #941c29;
  --adv-text: #45546a;
  --adv-muted: #748196;
  --adv-bg: #f7f8fa;
  --adv-border: #e7eaf0;
  --adv-white: #ffffff;
  color: var(--adv-text);
  font-family: inherit;
  overflow: hidden;
}

.advokita-page * {
  box-sizing: border-box;
}

.advokita-page a {
  text-decoration: none;
}

.advokita-page section[id] {
  scroll-margin-top: 120px;
}

/* HERO */

.adv-hero {
  position: relative;
  min-height: 400px;
  padding: 65px 55px;
  background: linear-gradient(120deg, #18263b, #2d405d);
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: white;
  margin-bottom: 35px;
}

.adv-hero-content {
  max-width: 590px;
  position: relative;
  z-index: 2;
}

.adv-label {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  padding: 8px 13px;
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 30px;
  color: #e7eaf1;
  font-size: 11px;
  letter-spacing: 2px;
  font-weight: 700;
}

.adv-label-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #ee7880;
}

.adv-hero h1 {
  font-size: clamp(32px, 5vw, 47px);
  font-weight: 800;
  line-height: 1.2;
  margin: 25px 0 17px;
  letter-spacing: -1.5px;
  color: white;
}

.adv-hero h1 span {
  color: #ee8b91;
}

.adv-hero-content > p {
  max-width: 510px;
  color: #d0d7e2;
  font-size: 15px;
  line-height: 1.9;
  margin-bottom: 25px;
}

.adv-hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.adv-btn-primary,
.adv-btn-outline,
.adv-btn-light {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px 19px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 700;
  transition: .25s ease;
}

.adv-btn-primary {
  background: var(--adv-red);
  color: white !important;
}

.adv-btn-primary:hover {
  background: #d13c49;
  transform: translateY(-3px);
  box-shadow: 0 7px 20px rgba(180,35,50,.25);
}

.adv-btn-outline {
  border: 1px solid rgba(255,255,255,.4);
  color: white !important;
}

.adv-btn-outline:hover {
  background: white;
  color: var(--adv-navy) !important;
}

.adv-hero-caption {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #bfcada;
  font-size: 12px;
  margin-top: 28px;
}

.adv-hero-caption i {
  color: #ed8a91;
  font-size: 17px;
}

.adv-hero-decoration {
  position: absolute;
  right: 45px;
  top: 0;
  width: 340px;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.adv-hero-circle {
  width: 255px;
  height: 255px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,.16);
  background: rgba(255,255,255,.035);
  box-shadow: 0 0 0 35px rgba(255,255,255,.025),
              0 0 0 70px rgba(255,255,255,.018);
}

.adv-hero-icon {
  position: absolute;
  width: 150px;
  height: 150px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 30px;
  backdrop-filter: blur(10px);
  color: #f0a0a5;
  font-size: 75px;
  transform: rotate(-8deg);
}

.adv-floating-card {
  position: absolute;
  bottom: 55px;
  left: -5px;
  display: flex;
  align-items: center;
  gap: 12px;
  background: white;
  color: var(--adv-navy);
  padding: 13px 17px;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0,0,0,.15);
}

.adv-floating-card > i {
  color: var(--adv-red);
  font-size: 25px;
}

.adv-floating-card strong,
.adv-floating-card small {
  display: block;
}

.adv-floating-card strong {
  font-size: 12px;
}

.adv-floating-card small {
  color: var(--adv-muted);
  font-size: 10px;
  margin-top: 3px;
}

.adv-hero-watermark {
  position: absolute;
  font-size: 350px;
  font-weight: 900;
  color: rgba(255,255,255,.025);
  right: 10px;
  top: -90px;
}

/* NAVIGATION */

.adv-navigation {
  position: sticky;
  top: 12px;
  z-index: 100;
  padding: 13px 18px;
  background: rgba(255,255,255,.96);
  border: 1px solid var(--adv-border);
  border-radius: 12px;
  box-shadow: 0 6px 25px rgba(28,40,60,.06);
  backdrop-filter: blur(15px);
  margin-bottom: 35px;
}

.adv-nav-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  padding: 0 5px;
}

.adv-nav-heading > span:first-child {
  color: #9aa4b3;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 2px;
}

.adv-nav-indicator {
  font-size: 10px;
  color: var(--adv-muted);
}

.adv-nav-links {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 8px;
}

.adv-nav-link {
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 13px;
  border-radius: 7px;
  color: var(--adv-text);
  font-size: 12px;
  font-weight: 600;
  transition: .25s;
  white-space: nowrap;
}

.adv-nav-link i {
  font-size: 15px;
}

.adv-nav-link:hover,
.adv-nav-link.active {
  color: var(--adv-red);
  background: #fff0f1;
}

/* SECTION GENERAL */

.adv-section {
  padding: 30px 10px;
}

.adv-section-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 28px;
}

.adv-eyebrow {
  display: block;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 2px;
  color: var(--adv-red);
  margin-bottom: 10px;
}

.adv-section-heading h2 {
  font-size: 29px;
  color: var(--adv-navy);
  font-weight: 800;
  letter-spacing: -.7px;
  margin: 0;
}

.adv-section-heading h2 span {
  color: var(--adv-red);
}

.adv-section-description {
  color: var(--adv-muted);
  font-size: 14px;
  line-height: 1.8;
  margin-top: 10px;
  margin-bottom: 0;
}

.adv-section-number {
  font-size: 45px;
  font-weight: 800;
  color: #edf0f4;
  line-height: 1;
}

.adv-divider {
  height: 1px;
  background: linear-gradient(to right, transparent, #dfe3e9, transparent);
  margin: 15px 0;
}

/* HISTORY */

.adv-history-layout {
  display: grid;
  grid-template-columns: .8fr 1.6fr;
  gap: 35px;
  align-items: stretch;
}

.adv-history-side {
  background: var(--adv-navy);
  padding: 30px;
  border-radius: 12px;
  color: white;
  position: relative;
  overflow: hidden;
}

.adv-history-side::after {
  content: "";
  position: absolute;
  width: 180px;
  height: 180px;
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 50%;
  right: -70px;
  bottom: -70px;
}

.adv-history-symbol {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,.1);
  color: #f08c94;
  border-radius: 12px;
  font-size: 29px;
  margin-bottom: 27px;
}

.adv-history-caption {
  color: #ed8d95;
  font-size: 10px;
  letter-spacing: 2px;
  font-weight: 700;
}

.adv-history-side h3 {
  color: white;
  font-size: 23px;
  font-weight: 700;
  line-height: 1.5;
  margin: 13px 0;
}

.adv-history-line {
  width: 45px;
  height: 3px;
  background: var(--adv-red);
  margin: 20px 0;
}

.adv-history-side p {
  color: #c6cfdb;
  font-size: 13px;
  line-height: 1.8;
  margin: 0;
}

.adv-history-content {
  padding: 5px 0;
}

.adv-history-content p {
  font-size: 14px;
  color: var(--adv-text);
  line-height: 2;
  text-align: justify;
  margin-bottom: 16px;
}

/* VISION */

.adv-vision-card {
  display: flex;
  position: relative;
  gap: 22px;
  align-items: flex-start;
  padding: 30px;
  background: #f7f8fa;
  border-radius: 12px;
  border-left: 4px solid var(--adv-red);
  overflow: hidden;
}

.adv-vision-icon {
  min-width: 55px;
  height: 55px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fce8ea;
  color: var(--adv-red);
  border-radius: 10px;
  font-size: 25px;
}

.adv-card-label {
  color: var(--adv-red);
  font-size: 9px;
  letter-spacing: 2px;
  font-weight: 800;
}

.adv-vision-card h3 {
  color: var(--adv-navy);
  font-size: 20px;
  font-weight: 800;
  margin: 5px 0 10px;
}

.adv-vision-card p {
  max-width: 750px;
  color: var(--adv-text);
  font-size: 14px;
  line-height: 1.9;
  margin: 0;
}

.adv-quote {
  position: absolute;
  right: 25px;
  bottom: -20px;
  font-size: 100px;
  color: rgba(180,35,50,.07);
}

.adv-mission-heading {
  margin: 35px 0 18px;
}

.adv-mission-heading h3 {
  color: var(--adv-navy);
  font-size: 20px;
  font-weight: 800;
  margin: 0;
}

.adv-mission-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.adv-mission-card {
  position: relative;
  padding: 23px;
  background: white;
  border: 1px solid var(--adv-border);
  border-radius: 10px;
  transition: .3s;
  overflow: hidden;
}

.adv-mission-card:hover {
  border-color: #e5a1a6;
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(36,52,77,.07);
}

.adv-mission-number {
  position: absolute;
  top: 15px;
  right: 17px;
  color: #e9edf2;
  font-size: 27px;
  font-weight: 800;
}

.adv-mission-icon {
  width: 43px;
  height: 43px;
  background: #fff0f1;
  color: var(--adv-red);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  font-size: 20px;
  margin-bottom: 17px;
}

.adv-mission-card h4 {
  color: var(--adv-navy);
  font-size: 15px;
  font-weight: 800;
  margin-bottom: 9px;
}

.adv-mission-card p {
  color: var(--adv-muted);
  font-size: 12px;
  line-height: 1.8;
  margin: 0;
}

/* ORGANIZATION */

.adv-org-intro {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 17px 20px;
  background: #f7f8fa;
  border-radius: 9px;
  margin-bottom: 20px;
}

.adv-org-intro i {
  color: var(--adv-red);
  font-size: 25px;
}

.adv-org-intro p {
  color: var(--adv-text);
  font-size: 13px;
  line-height: 1.8;
  margin: 0;
}

.adv-org-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.adv-org-card {
  position: relative;
  padding: 23px;
  border: 1px solid var(--adv-border);
  border-radius: 10px;
  background: white;
  transition: .3s;
  overflow: hidden;
}

.adv-org-card:hover {
  border-color: #dca0a5;
  transform: translateY(-4px);
  box-shadow: 0 10px 25px rgba(36,52,77,.06);
}

.adv-org-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 19px;
}

.adv-org-icon {
  width: 48px;
  height: 48px;
  background: #fff0f1;
  color: var(--adv-red);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.adv-org-tag {
  font-size: 9px;
  color: var(--adv-red);
  letter-spacing: 1px;
  font-weight: 800;
  background: #fff3f4;
  padding: 6px 9px;
  border-radius: 4px;
}

.adv-org-card h3 {
  color: var(--adv-navy);
  font-size: 16px;
  font-weight: 800;
  max-width: 85%;
  margin-bottom: 9px;
}

.adv-org-card p {
  color: var(--adv-muted);
  font-size: 13px;
  line-height: 1.8;
  max-width: 90%;
  margin: 0;
}

.adv-org-index {
  position: absolute;
  right: 17px;
  bottom: 8px;
  color: #f1f2f5;
  font-size: 43px;
  font-weight: 900;
}

.adv-note {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: #fff9ed;
  border: 1px solid #f4e7c9;
  border-radius: 8px;
  padding: 13px 16px;
  margin-top: 20px;
}

.adv-note i {
  color: #bd8b2c;
  font-size: 17px;
}

.adv-note p {
  font-size: 12px;
  color: #826a3e;
  line-height: 1.7;
  margin: 0;
}

/* SERVICES */

.adv-service-intro {
  max-width: 850px;
  margin-bottom: 25px;
}

.adv-service-intro p {
  color: var(--adv-text);
  font-size: 14px;
  line-height: 1.9;
  text-align: justify;
}

.adv-service-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 17px;
}

.adv-service-card {
  position: relative;
  background: white;
  border: 1px solid var(--adv-border);
  border-radius: 11px;
  padding: 25px;
  min-height: 220px;
  transition: .3s;
}

.adv-service-card:hover {
  transform: translateY(-5px);
  border-color: #e0a0a5;
  box-shadow: 0 12px 30px rgba(36,52,77,.08);
}

.adv-service-icon {
  width: 50px;
  height: 50px;
  background: #fff0f1;
  color: var(--adv-red);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 23px;
  margin-bottom: 18px;
  transition: .3s;
}

.adv-service-card:hover .adv-service-icon {
  background: var(--adv-red);
  color: white;
}

.adv-service-number {
  color: #a1aabb;
  font-size: 9px;
  letter-spacing: 1.5px;
  font-weight: 800;
}

.adv-service-card h3 {
  color: var(--adv-navy);
  font-size: 17px;
  font-weight: 800;
  margin: 9px 0;
}

.adv-service-card p {
  color: var(--adv-muted);
  font-size: 13px;
  line-height: 1.8;
  max-width: 90%;
  margin: 0;
}

.adv-service-arrow {
  position: absolute;
  bottom: 20px;
  right: 22px;
  font-size: 19px;
  color: #d1d6df;
  transition: .3s;
}

.adv-service-card:hover .adv-service-arrow {
  color: var(--adv-red);
  transform: translate(3px,-3px);
}

.adv-service-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  padding: 18px 20px;
  background: var(--adv-navy);
  border-radius: 9px;
  margin-top: 22px;
}

.adv-service-bottom > div {
  display: flex;
  align-items: center;
  gap: 10px;
  color: white;
  font-size: 12px;
  font-weight: 600;
}

.adv-service-bottom > div i {
  color: #f08d96;
  font-size: 20px;
}

.adv-service-bottom a {
  color: white;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.adv-service-bottom a:hover {
  color: #f08d96;
}

/* CONTACT */

.adv-contact-layout {
  display: grid;
  grid-template-columns: 1.4fr .8fr;
  gap: 20px;
}

.adv-contact-info {
  padding: 30px;
  background: #f7f8fa;
  border-radius: 12px;
}

.adv-contact-info h3 {
  font-size: 23px;
  color: var(--adv-navy);
  font-weight: 800;
  line-height: 1.4;
  max-width: 450px;
  margin-bottom: 12px;
}

.adv-contact-info > p {
  color: var(--adv-muted);
  font-size: 13px;
  line-height: 1.8;
}

.adv-contact-items {
  margin-top: 25px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.adv-contact-item {
  display: flex;
  align-items: flex-start;
  gap: 13px;
}

.adv-contact-icon {
  min-width: 42px;
  height: 42px;
  background: white;
  color: var(--adv-red);
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 19px;
  box-shadow: 0 3px 10px rgba(36,52,77,.04);
}

.adv-contact-item small {
  color: #9aa4b3;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1px;
}

.adv-contact-item p {
  color: var(--adv-navy);
  font-size: 13px;
  font-weight: 600;
  line-height: 1.7;
  margin: 4px 0 0;
}

.adv-contact-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 28px;
}

.adv-btn-light {
  color: var(--adv-navy) !important;
  background: white;
  border: 1px solid var(--adv-border);
}

.adv-btn-light:hover {
  border-color: var(--adv-red);
  color: var(--adv-red) !important;
}

.adv-map-card {
  position: relative;
  padding: 30px 25px;
  border-radius: 12px;
  background: var(--adv-navy);
  color: white;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.adv-map-decoration {
  position: absolute;
  right: -20px;
  top: -20px;
  font-size: 160px;
  color: rgba(255,255,255,.04);
}

.adv-map-card .adv-eyebrow {
  color: #f08d96;
}

.adv-map-card h3 {
  color: white;
  font-size: 21px;
  font-weight: 800;
  margin-bottom: 12px;
}

.adv-map-card > p {
  color: #c3ccda;
  font-size: 13px;
  line-height: 1.8;
}

.adv-map-placeholder {
  min-height: 120px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px dashed rgba(255,255,255,.25);
  border-radius: 9px;
  margin: 15px 0;
  background: rgba(255,255,255,.04);
  color: #e2a0a5;
}

.adv-map-placeholder i {
  font-size: 35px;
}

.adv-map-placeholder span {
  color: #d5dce7;
  font-size: 11px;
}

.adv-map-button {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  background: var(--adv-red);
  color: white !important;
  padding: 13px;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 700;
  transition: .25s;
}

.adv-map-button:hover {
  background: #d13c49;
}

/* FOOTER */

.adv-footer {
  text-align: center;
  padding: 45px 25px 20px;
  background: #f7f8fa;
  border-radius: 13px;
  margin: 30px 0 10px;
}

.adv-footer-icon {
  width: 58px;
  height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 18px;
  border-radius: 14px;
  background: #fff0f1;
  color: var(--adv-red);
  font-size: 28px;
}

.adv-footer .adv-eyebrow {
  margin-bottom: 8px;
}

.adv-footer h2 {
  color: var(--adv-navy);
  font-size: 32px;
  font-weight: 900;
  letter-spacing: 3px;
  margin-bottom: 10px;
}

.adv-footer > p {
  max-width: 500px;
  margin: 0 auto 18px;
  color: var(--adv-muted);
  font-size: 13px;
  line-height: 1.8;
}

.adv-footer > a {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--adv-red);
  font-size: 12px;
  font-weight: 800;
}

.adv-footer-bottom {
  border-top: 1px solid var(--adv-border);
  margin-top: 30px;
  padding: 17px 0 0;
  display: flex;
  justify-content: space-between;
  gap: 10px;
  color: #a0a9b6;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 1px;
}

/* BACK TO TOP */

.adv-back-top {
  position: fixed;
  right: 25px;
  bottom: 25px;
  z-index: 999;
  width: 43px;
  height: 43px;
  border: none;
  border-radius: 9px;
  background: var(--adv-red);
  color: white;
  font-size: 19px;
  box-shadow: 0 5px 18px rgba(180,35,50,.25);
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  transition: .3s;
}

.adv-back-top.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.adv-back-top:hover {
  background: var(--adv-red-dark);
  transform: translateY(-4px);
}

/* SCROLL ANIMATION */

.reveal {
  opacity: 1;
  transform: none;
  transition: opacity .45s ease, transform .45s ease;
}

.reveal.visible {
  opacity: 1;
  transform: none;
}

/* RESPONSIVE */

@media (max-width: 900px) {
  .adv-nav-links {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .adv-hero {
    padding: 45px 35px;
  }

  .adv-hero-decoration {
    right: -70px;
    opacity: .55;
  }

  .adv-history-layout {
    grid-template-columns: 1fr;
  }

  .adv-history-side {
    min-height: 230px;
  }

  .adv-mission-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .adv-contact-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 576px) {
  .adv-hero {
    min-height: 380px;
    padding: 35px 23px;
    border-radius: 11px;
  }

  .adv-hero h1 {
    font-size: 34px;
  }

  .adv-hero-content > p {
    font-size: 13px;
  }

  .adv-hero-decoration {
    right: -150px;
    opacity: .23;
  }

  .adv-hero-actions {
    gap: 8px;
  }

  .adv-btn-primary,
  .adv-btn-outline {
    padding: 11px 13px;
    font-size: 11px;
  }

  .adv-navigation {
    top: 5px;
    padding: 12px 10px;
  }

  .adv-nav-links {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .adv-nav-link {
    padding: 10px 12px;
    font-size: 11px;
  }

  .adv-section {
    padding: 25px 3px;
  }

  .adv-section-heading h2 {
    font-size: 23px;
  }

  .adv-section-number {
    font-size: 32px;
  }

  .adv-history-side {
    padding: 24px;
  }

  .adv-history-side h3 {
    font-size: 21px;
  }

  .adv-history-content p {
    font-size: 13px;
  }

  .adv-vision-card {
    padding: 22px 18px;
    gap: 13px;
  }

  .adv-vision-icon {
    min-width: 42px;
    height: 42px;
    font-size: 20px;
  }

  .adv-vision-card h3 {
    font-size: 17px;
  }

  .adv-vision-card p {
    font-size: 12px;
  }

  .adv-mission-grid {
    grid-template-columns: 1fr;
  }

  .adv-org-grid {
    grid-template-columns: 1fr;
  }

  .adv-service-grid {
    grid-template-columns: 1fr;
  }

  .adv-service-card {
    min-height: 195px;
  }

  .adv-service-bottom {
    flex-direction: column;
    align-items: flex-start;
  }

  .adv-contact-info {
    padding: 23px 18px;
  }

  .adv-contact-info h3 {
    font-size: 20px;
  }

  .adv-map-card {
    padding: 25px 20px;
  }

  .adv-footer-bottom {
    flex-direction: column;
    align-items: center;
  }

  .adv-back-top {
    right: 15px;
    bottom: 15px;
  }
}

/* ACCESSIBILITY */

@media (prefers-reduced-motion: reduce) {
  .advokita-page *,
  .advokita-page *::before,
  .advokita-page *::after {
    scroll-behavior: auto !important;
    animation-duration: .01ms !important;
    transition-duration: .01ms !important;
  }

  .reveal {
    opacity: 1;
    transform: none;
  }
}
</style>

