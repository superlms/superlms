<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="brand" href="{{ url('/') }}">
                    <img src="{{ $logo }}" alt="Logo">
                    <span class="brand-word">SUPER<span>LMS</span></span>
                </a>
                <p class="footer-desc">SUPERLMS is India's leading affordable Learning Management System for
                    schools and educational institutions. Trusted by many schools across India.</p>
                <div class="socials">
                    <a class="social s-ig" href="https://www.instagram.com/superlms.in?igsh=b2Y4dHhnbWFycWs4" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram">
                        <x-site.icon name="instagram" />
                    </a>
                    <a class="social s-wa" href="https://whatsapp.com/channel/0029Vb8GOxcK5cD7a9WYCM0N" target="_blank" rel="noopener noreferrer" title="WhatsApp" aria-label="WhatsApp">
                        <x-site.icon name="whatsapp" />
                    </a>
                    <a class="social s-yt" href="https://youtube.com/@superlms.education?si=8-lsDNs_TXAyC9qD" target="_blank" rel="noopener noreferrer" title="YouTube" aria-label="YouTube">
                        <x-site.icon name="youtube" />
                    </a>
                    <a class="social s-mail" href="mailto:support@superlms.in" title="Email" aria-label="Email">
                        <x-site.icon name="mail" />
                    </a>
                </div>
            </div>

            <div>
                <div class="fcol-t">Quick Links</div>
                <ul class="flinks">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li><a href="{{ url('web/about') }}">About Us</a></li>
                    <li><a href="{{ url('web/features') }}">Features</a></li>
                    <li><a href="{{ url('web/pricing') }}">Pricing</a></li>
                    <li><a href="{{ url('web/contact') }}">Contact Us</a></li>
                    <li><a href="{{ url('web/demo') }}">Request Demo</a></li>
                </ul>
            </div>

            <div>
                <div class="fcol-t">Company</div>
                <ul class="flinks">
                    <li><a href="{{ route('website.why-us') }}">Why Us</a></li>
                    <li><a href="{{ route('website.services') }}">Services</a></li>
                    <li><a href="{{ route('website.careers') }}">Careers</a></li>
                    <li><a href="{{ route('website.become-executive') }}">Become an Executive</a></li>
                    <li><a href="{{ route('website.blogs') }}">Blogs</a></li>
                    <li><a href="{{ route('website.faqs') }}">FAQs</a></li>
                </ul>
            </div>

            <div>
                <div class="fcol-t">Legal</div>
                <ul class="flinks">
                    <li><a href="{{ route('website.privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('website.terms-conditions') }}">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('website.terms-of-use') }}">Terms of Use</a></li>
                </ul>
            </div>

            <div>
                <div class="fcol-t">Contact</div>
                <div class="fcontact">
                    <a href="mailto:support@superlms.in"><x-site.icon name="mail" /><span>support@superlms.in</span></a>
                    <a href="tel:+919084748563"><x-site.icon name="phone" /><span>+91 9084748563</span></a>
                    <div><x-site.icon name="map-pin" /><span>Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137</span></div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>© 2026 SUPERLMS (Super Learnings Private Limited). All rights reserved. Made with ❤️ in India.</div>
            <nav>
                <a href="{{ route('website.privacy') }}">Privacy Policy</a>
                <a href="{{ route('website.terms-conditions') }}">Terms &amp; Conditions</a>
                <a href="{{ route('website.terms-of-use') }}">Terms of Use</a>
            </nav>
        </div>
    </div>
</footer>
