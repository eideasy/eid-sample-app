<header class="topbar">
    <div class="topbar_unit">
        <a
            href="/"
            class="topbar__brand_link"
        >
            <img
                class="topbar__brand_image"
                src="{{ asset('img/eid-easy-icon.png') }}"
                alt="eID Easy Demo"
            >
            <span class="topbar__brand_text">eID Easy Services Demo</span>
        </a>
        @if(!request()->is('login-widget'))
        <nav class="demo-nav" aria-label="Main navigation">
            <a href="{{ url('/') }}" @class(['active' => request()->is('/')]) @if(request()->is('/')) aria-current="page" @endif>All demos</a>
            <a href="https://docs.eideasy.com/" target="_blank" rel="noopener">Documentation <i data-lucide="external-link" aria-hidden="true"></i></a>
            <a href="https://github.com/eideasy/eid-sample-app" target="_blank" rel="noopener">Source code <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
        </nav>
        @endif
    </div>
</header>
