# Design Upgrade Verification Quickstart

For every completed vertical slice:

1. Run focused PHPUnit tests for changed Livewire/components.
2. Run the full PHPUnit suite before phase exit.
3. Run Pint in test mode.
4. Build production assets with Vite.
5. Start the app against an isolated seeded SQLite QA database.
6. Capture 390×844 and 1440×900 together; add 360px, 768px, and the user's reported viewport where relevant.
7. Verify Arabic RTL, English LTR, light, dark, keyboard, reduced motion, empty/loading/error states, and no console errors.
8. Run the Impeccable detector once on changed UI targets.
9. Send valid captures and the design contract to the finish reviewer; act on `recapture`, `rebuild`, `fix`, or `ship` exactly.
10. Update `tasks.md` and the roadmap progress log before advancing.

Never use production data for UI screenshots or destructive flow verification.

