{{-- Founder spotlight. $bio — the paragraph for this page. --}}
<div class="founder rv">
    <a href="https://www.instagram.com/annantdagur?igsh=OTMyZnIzaGR2aDVs" target="_blank" rel="noopener noreferrer" class="founder-photo">
        <img src="{{ asset('website-image/annant.jpg') }}" alt="Annant Dagur"
            onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
        <span class="fb"><x-site.icon name="user" /></span>
        <span class="ig"><x-site.icon name="instagram" /> View Profile</span>
    </a>
    <div>
        <span class="tag">Founder &amp; CEO</span>
        <h3 class="founder-name">Annant Dagur</h3>
        <p class="founder-bio">{{ $bio }}</p>
        <div class="founder-quote"><x-site.icon name="quote-fill" />“Great technology should empower every teacher and reach every child —
            not just the privileged few.”</div>
        <a href="https://www.instagram.com/annantdagur?igsh=OTMyZnIzaGR2aDVs" target="_blank" rel="noopener noreferrer" class="link-arrow">
            <x-site.icon name="instagram" /> View Profile <x-site.icon name="chevron-right" />
        </a>
    </div>
</div>
