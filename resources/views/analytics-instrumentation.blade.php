<script
    src="{{ mix('js/analytics-instrumentation.js') }}"
    data-api-key="{{ config('services.amplitude.api_key') }}"
></script>
<script>
    document.addEventListener('copy', function() {
        if (window.getSelection().toString().includes('info@eideasy.com')) {
            document.tracker.track('signing_demo_email_address_copied')
        }
    });
</script>
