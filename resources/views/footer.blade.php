@if(request()->is('login-widget'))
<footer class="footer">
    <div class="footer__row">
        <div>
            Built with ❤️ by <a href="https://eideasy.com/" target="_blank" data-event-name="signing_demo_eideasy_clicked">eID Easy</a>
        </div>
        <div class="footer__col_spacer">-</div>
        <div>
            info@eideasy.com
        </div>
        <div class="footer__col_spacer">-</div>
        <div>
            <a target="_blank" href="https://eideasy.com/privacy-policy/" data-event-name="signing_demo_privacy_policy_clicked">Privacy Policy</a>
        </div>
        <div class="footer__col_spacer">-</div>
        <div>
            <a target="_blank" href="https://eideasy.com/terms-of-service/" data-event-name="signing_demo_terms_and_conditions_clicked">Terms &amp; Conditions</a>
        </div>
    </div>

    <div class="footer__note">
        Disclaimer! This is a free service to test out eID Easy API-s. Your information might be available for the service provider after signing or user identification.
    </div>
</footer>

@else
<footer class="footer">
    <div class="footer__row"><span>Built by <a href="https://eideasy.com/" target="_blank" rel="noopener" data-event-name="signing_demo_eideasy_clicked">eID Easy</a></span><span>info@eideasy.com</span><a href="https://eideasy.com/privacy-policy/" target="_blank" rel="noopener" data-event-name="signing_demo_privacy_policy_clicked">Privacy Policy</a><a href="https://eideasy.com/terms-of-service/" target="_blank" rel="noopener" data-event-name="signing_demo_terms_and_conditions_clicked">Terms &amp; Conditions</a></div>
    <p class="footer__note">This free demo connects to eID Easy services. Information you submit may be available to the service provider after signing or identification.</p>
</footer>
@endif
