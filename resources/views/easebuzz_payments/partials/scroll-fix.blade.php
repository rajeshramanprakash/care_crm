<style>
    /*
     * Admin layout: body { overflow: hidden } + .wrapper .content-wrapper { min-height: ~100vh }
     * Outer box gets a fixed viewport height; inner div scrolls.
     */
    .wrapper .content-wrapper.ebp-scroll-outer {
        min-height: 0 !important;
        /* navbar + footer (navbar is outside .wrapper in this layout) */
        height: calc(100vh - 120px) !important;
        max-height: calc(100vh - 120px) !important;
        overflow: hidden !important;
        padding: 0 !important;
        display: block !important;
    }
    @media (max-width: 991.98px) {
        .wrapper .content-wrapper.ebp-scroll-outer {
            height: calc(100vh - 88px) !important;
            max-height: calc(100vh - 88px) !important;
        }
    }
    .ebp-scroll-inner {
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        padding: 0.75rem 0.5rem 2rem;
    }
</style>
