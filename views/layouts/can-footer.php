<?php
/** @var yii\web\View $this */

$this->registerCss(<<<CSS
footer.footer {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
    color: #475569 !important;
    padding: 60px 0 40px !important;
    font-size: 14px !important;
    border-top: 1px solid #e2e8f0 !important;
    text-align: center !important;
    height: auto !important;
    min-height: unset !important;
    margin-top: 0 !important;
    display: block !important;
}
footer.footer a {
    color: #1e293b !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
}
footer.footer a:hover {
    color: #0b5ed7 !important;
    text-decoration: none !important;
}
.footer-slogan {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.footer-slogan .plane {
    display: inline-block;
    animation: av-plane-hover 3s ease-in-out infinite alternate;
}
@keyframes av-plane-hover {
    0% { transform: translateY(0) rotate(0); }
    100% { transform: translateY(-4px) rotate(5deg); }
}
.social-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 24px;
}
.social-links a {
    color: #64748b;
    font-size: 20px;
    transition: all 0.2s ease;
    text-decoration: none;
}
.social-links a:hover {
    color: #0b5ed7;
    transform: scale(1.1);
}
.contact-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
}
.contact-info p {
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    color: #1e293b;
    font-weight: 500;
}
.contact-info a {
    color: #1e293b;
    font-weight: 500;
}
.contact-info a:hover {
    color: #0b5ed7;
}
.company-info {
    max-width: 600px;
    margin: 0 auto 24px;
    font-style: normal;
    color: #94a3b8;
    font-size: 11px;
    line-height: 1.6;
    font-weight: 400;
    text-align: center !important;
}
.legal-links {
    list-style: none !important;
    padding: 0 !important;
    margin: 0 auto 20px !important;
    display: flex !important;
    justify-content: center !important;
    flex-wrap: wrap !important;
    gap: 16px !important;
    max-width: 800px !important;
}
.legal-links li {
    list-style: none !important;
    display: inline-block !important;
}
.legal-links a {
    color: #94a3b8 !important;
    font-size: 11px !important;
    font-weight: 500;
    transition: color 0.2s ease;
}
.legal-links a:hover {
    color: #475569 !important;
}
.footer .small {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 10px;
    margin-bottom: 0;
}
.footer .small strong {
    color: #64748b;
}

/* ================== SCROLL TO TOP BUTTON ================== */
.scroll-top-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 40px;
    height: 40px;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    opacity: 0;
    visibility: hidden;
    transform: translateY(15px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 9998;
}
.scroll-top-btn:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    color: #1e293b;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}
.scroll-top-btn.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
CSS
);

$jsFooter = <<<JS
(function () {
  var btn = document.getElementById('scrollTopBtn');
  if (!btn) return;

  window.addEventListener('scroll', function () {
    if (window.scrollY > 300) {
      btn.classList.add('visible');
    } else {
      btn.classList.remove('visible');
    }
  });

  btn.addEventListener('click', function () {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
})();
JS;
$this->registerJs($jsFooter);
?>
<footer class="footer visible">
  <div class="container">
    <p class="footer-slogan">
      <span class="plane" aria-hidden="true">✈️</span> Aircraft Maintenance Anytime, Anywhere
    </p>

    <div class="social-links">
      <a aria-label="Facebook" href="#"><i class="ri-facebook-circle-line"></i></a>
      <a aria-label="X" href="#"><i class="ri-twitter-x-line"></i></a>
      <a aria-label="LinkedIn" href="#"><i class="ri-linkedin-box-line"></i></a>
      <a aria-label="Instagram" href="#"><i class="ri-instagram-line"></i></a>
    </div>

    <div class=" text-center">
      <p>📧 <a href="mailto:support@coreaviationnetwork.com">support@coreaviationnetwork.com</a></p>
      <p>☎️ <a href="tel:+441234567890">+44 1234 567 890</a></p>
    </div>

    <address class=" text-center">
      5 Stavedown Road, South Wanston, Winchester, SO21 3HA – United Kingdom<br>
      Registered in England and Wales · Company No. 15789027
    </address>

    <ul class="legal-links">
      <li><a href="<?= \yii\helpers\Url::to(['site/terms']) ?>">Terms (MRO)</a></li>
      <li><a href="<?= \yii\helpers\Url::to(['site/terms']) ?>">Terms (Aircraft Operators)</a></li>
      <li><a href="<?= \yii\helpers\Url::to(['site/privacy-policy']) ?>">Privacy Policy</a></li>
      <li><a href="<?= \yii\helpers\Url::to(['site/cookie-policy']) ?>">Cookie Policy</a></li>
    </ul>

    <p class="small">
      © <?= date('Y') ?> <strong>Core Aviation Network</strong>. All Rights Reserved.
    </p>
  </div>
</footer>

<!-- ================== BOUTON RETOUR EN HAUT ================== -->
<button id="scrollTopBtn" class="scroll-top-btn" aria-label="Scroll to top" type="button">
  <i class="ri-arrow-up-line" aria-hidden="true"></i>
</button>
