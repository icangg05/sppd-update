---
target: halaman dashboard
total_score: 30
p0_count: 0
p1_count: 0
timestamp: 2026-07-09T07-12-39Z
slug: resources-views-dashboard-blade-php
---
## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 3 | Good Livewire loading; charts still lack loading/empty skeleton |
| 2 | Match System / Real World | 4 | Strong Indonesian domain language |
| 3 | User Control and Freedom | 3 | Modal Esc/close/backdrop; no dashboard customization |
| 4 | Consistency and Standards | 4 | Cards/tokens/buttons now unified to DESIGN.md; residual hand-rolled Detail button |
| 5 | Error Prevention | 3 | Read-only surface, low error risk |
| 6 | Recognition Rather Than Recall | 3 | budget-bar now has aria status word; visible threshold still color+% only |
| 7 | Flexibility and Efficiency | 2 | No keyboard shortcuts/bulk; DPA capped at 10 |
| 8 | Aesthetic and Minimalist Design | 3 | Hero de-cluttered, KPI numbers bigger; still 8 stacked equal sections |
| 9 | Error Recovery | 3 | Readable empty states; no chart-failure state |
| 10 | Help and Documentation | 2 | No contextual help/tooltips for domain terms |
| **Total** | | **30/40** | **Good (up from 27)** |

## Anti-Patterns Verdict

**LLM assessment**: The SaaS tell is gone. The hero no longer uses a gradient or the banned uppercase eyebrow — it's a quiet work-card with a heading, subtitle, and two `x-ui.button` actions. The page now reads as a government work tool, not a marketing surface. No AI-slop verdict.

**Deterministic scan**: detect.mjs → 0 findings across all 8 dashboard files (was 1 warning last run — a false positive that no longer even triggers).

**Visual overlay**: not run (Blade via Docker; no browser server spun up). Source-based review, plus in-container Blade compile + component render verification from the prior pass.

## What's Working
- **Consistency reclaimed** (heuristic 4: 2 → 4). Every card is `rounded-xl border-slate-200 shadow-sm`; every accent is `primary-*`; hero and modal buttons route through `x-ui.button` with the standard `focus-visible:ring-2`. The "tidak konsisten" anti-reference no longer appears on the flagship page.
- **Task-first ordering**. For approver/requester the action queues (approval, TTE, SPPD saya) now render above the budget widget — on mobile the pejabat sees the TTE queue right after the hero, not after a long scroll.
- **Contrast + a11y**: micro-text lifted off `slate-400`; tinted-box labels darkened to `-700`; budget-bar carries `role="progressbar"` + an aria-label with a status word (Aman/Waspada/Tinggi/Kritis), so meaning is no longer color-only.

## Priority Issues

- **[P2] Flat hierarchy across 8 stacked sections**: bigger KPI numbers and the reorder help, but the page is still a vertical stack of equal-weight white cards. Nothing beyond order signals "this matters most". For the approver, the approval/TTE queues could carry slightly more visual primacy (size, heading weight) than passive cards like "Keputusan Terakhir". Fix: introduce one deliberate tier of emphasis for the primary-task card per archetype. → `/impeccable layout`

- **[P2] Charts: external CDN + no loading/empty state**: Chart.js still loads from `cdn.jsdelivr.net` (render-blocking, fragile on slow govt networks); the `<canvas>` is blank if JS fails or is slow — no skeleton, no "grafik tidak tersedia" fallback; still no screen-reader table alternative for the trend/donut data. Font + reduced-motion are now fixed. Fix: bundle Chart.js via Vite, add a skeleton + failure fallback, expose the series as a visually-hidden table. → `/impeccable optimize` + `/impeccable harden`

- **[P2] No power-user affordances**: operators live here daily but there are no keyboard shortcuts to "Pengajuan Baru" or the approval queue, no bulk actions, and the DPA list hard-caps at 10. Fix: add a shortcut for the primary action and a keyboard path to the queues. → `/impeccable layout` (accelerators)

- **[P3] Domain terms unexplained**: Pagu, Realisasi, DPA, TTE appear without inline definition for a first-timer. Fix: a small tooltip/hint on first occurrence. → `/impeccable clarify`

## Persona Red Flags

**Alex (Power User / operator)**: still no keyboard shortcuts or bulk actions; DPA capped at 10. Scanning is faster now that KPI numbers are `text-2xl`.

**Sam (Accessibility)**: contrast failures resolved; budget-bar now announces status. Remaining: `<canvas>` charts have no table/aria fallback, so trend/donut data is invisible to screen readers.

**Casey (Pejabat penandatangan on HP)**: TTE queue now surfaces early (reorder) — the main red flag from last run is resolved. Remaining nit: primary actions still live in the hero at the top rather than a thumb-reachable zone, but the critical queue is now high in the scroll.

## Minor Observations
- Hand-rolled "Detail" button and "Lihat lainnya" link in dpa-panel are styled consistently now but still bypass `x-ui.button`; fine, but a future extract could fold them in.
- Item-count chip in dpa-panel uses `rounded` rather than the pill/tag scale — cosmetic only.
- Charts still animate by default except under reduced-motion (correct), but there's no "as of" timestamp on the trend.

## Questions to Consider
- Which single card per archetype deserves to be visually loudest, and does the current layout make it so?
- If Chart.js failed to load right now, what would the user see — and is that acceptable on a Kendari government network?
- What's the one keyboard shortcut an operator would use fifty times a day?
