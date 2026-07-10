---
target: halaman dashboard
total_score: 27
p0_count: 0
p1_count: 3
timestamp: 2026-07-09T06-48-55Z
slug: resources-views-dashboard-blade-php
---
## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 3 | Good Livewire loading states; charts have no loading/empty skeleton; "real-time" claim has no timestamp |
| 2 | Match System / Real World | 4 | Strong Indonesian domain language (Pagu, Realisasi, DPA, SPPD) |
| 3 | User Control and Freedom | 3 | Modal Esc/close/backdrop good; no dashboard customization |
| 4 | Consistency and Standards | 2 | Three card radii (rounded / rounded-xl / rounded-2xl), two shadows, literal cyan-* vs primary-* mixed, hand-rolled buttons vs x-ui.button — violates own DESIGN.md across flagship page |
| 5 | Error Prevention | 3 | Mostly read-only surface, low error risk |
| 6 | Recognition Rather Than Recall | 3 | Labels + icons present; budget thresholds by color only |
| 7 | Flexibility and Efficiency | 2 | No keyboard shortcuts, no bulk, DPA capped at 10 |
| 8 | Aesthetic and Minimalist Design | 2 | Hero eyebrow+gradient decoration; flat hierarchy; 8 equal-weight cards |
| 9 | Error Recovery | 3 | Friendly empty states; no chart-failure state |
| 10 | Help and Documentation | 2 | No contextual help/tooltips for domain terms |
| **Total** | | **27/40** | **Acceptable (top of band)** |

## Anti-Patterns Verdict

**LLM assessment**: Not obviously AI-generated, thanks to genuine domain language and thoughtful empty states — but the **Welcome Hero Banner** is a textbook SaaS cliché: gradient `from-cyan-50 to-white`, a tiny uppercase tracked eyebrow ("SELAMAT DATANG KEMBALI"), and a hand-rolled gradient-adjacent CTA. That eyebrow is a cross-register absolute ban, and the banner is exactly the "SaaS startup berlebihan" anti-reference from PRODUCT.md.

**Deterministic scan**: detect.mjs → 1 warning (`gray-on-color`, dpa-panel.blade.php:51). False positive: `text-slate-600 bg-white` base; the cyan bg+text swap together on hover.

## What's Working
- **Domain fluency** (heuristic 4 = 4/4). Pagu/Realisasi/DPA/SPPD in natural Indonesian; requester sees softened "Perlu Perbaikan" where admin sees "Ditolak".
- **Empty states teach** rather than dead-end: "Semua laporan sudah lengkap", "Tidak ada pengajuan menunggu persetujuan" with warm icons.
- **DPA budget widget** with lazy-loaded detail modal is a genuinely useful, thoughtful signature component — summary → per-item bars → drill-in without leaving the page.

## Priority Issues

- **[P1] Consistency breakdown across the flagship page**: admin KPI cards use `rounded-xl + shadow-sm` (correct per DESIGN.md), but leadership/requester KPI cards use `rounded-2xl + shadow-md`, and the hero/DPA summary/DPA panel use bare `rounded` (0.25rem) + `shadow-md`. Icon tiles mix `rounded-lg` and `rounded`. Accent tokens mix literal `cyan-*` (hero, dpa-panel, dpa-summary) with correct `primary-*` (leadership, requester). Buttons are hand-rolled `<a>`/`<button>` with `focus:ring-1` instead of `x-ui.button`'s `focus-visible:ring-2`. This is the "tidak konsisten" anti-reference manifesting on the most-seen page, and it violates the DESIGN.md just written. Fix: normalize all cards to `rounded-xl border-slate-200 shadow-sm`, replace every literal `cyan-*` with `primary-*`, route buttons through `x-ui.button`.

- **[P1] Contrast failures on micro-text**: pervasive `text-slate-400` (#94a3b8 ≈ 2.8:1 on white) at 10–11px for sublabels, hints, "Penggunaan", "Rp …/Rp …", empty-state copy. Fails WCAG AA (4.5:1) and the "Ink Floor Rule" in the DESIGN.md ("teks tubuh tidak pernah lebih terang dari #64748b"). Fix: bump body/sublabel text to `slate-500`/`slate-600`; reserve `slate-400` for truly decorative glyphs (bullets).

- **[P1] Welcome hero banner is a SaaS cliché**: gradient bg + banned uppercase eyebrow + decorative shadow-md. Contradicts PRODUCT.md anti-reference. Fix: drop the gradient and eyebrow; a plain heading + subtitle on the app canvas, actions via `x-ui.button`. Keep it quiet — this is a govt work tool, not a marketing page.

- **[P2] Flat visual hierarchy**: 8 stacked white cards of identical weight; nothing signals priority. KPI numbers are only `text-lg` (18px) — timid for the one thing a KPI exists to show. High-stakes queues ("Menunggu Persetujuan Anda", "Menunggu Tanda Tangan") look like every other card. Fix: enlarge KPI values; give the approval/TTE queues visual primacy; vary spacing/weight so the eye lands on what matters first.

- **[P2] Charts: perf, font, motion, color-only meaning**: Chart.js loaded from external CDN (`cdn.jsdelivr.net`) — render-blocking and fragile on the slow govt networks PRODUCT.md calls normal; sets font to **Inter**, not the app's **Poppins**; no loading/empty skeleton (blank canvas if JS fails); no `prefers-reduced-motion` for chart animation or budget-bar's `duration-500`; budget-bar thresholds (green/yellow/orange/red) convey meaning by color alone. Fix: bundle Chart.js via Vite, set Poppins, add skeleton + reduced-motion, add a non-color threshold cue.

## Persona Red Flags

**Alex (Power User / operator)**: No keyboard shortcuts to jump to pending approvals or new SPPD. DPA list hard-capped at 10 with "Lihat lainnya". Flat hierarchy means scanning KPIs takes longer than it should. No bulk/quick actions.

**Sam (Accessibility)**: `text-slate-400` micro-text fails 4.5:1 in many places. Budget-bar communicates status by color only. `<canvas>` charts are opaque to screen readers with no table/aria fallback. Hero buttons weaken focus to `ring-1` and use `focus:` not `focus-visible:`. Reduced-motion not honored (chart animation, budget-bar 500ms).

**Casey (Pejabat penandatangan on HP — project persona)**: The "Menunggu Tanda Tangan" queue — the signer's entire job — sits in the right column below approvals on desktop, and on mobile stacks far down after hero + DPA summary + DPA list + KPIs + approval queue. The primary action is nowhere near the thumb zone on the small screens PRODUCT.md says signers actually use.

## Minor Observations
- Same field `$stats['rejected']` shown as "Ditolak" (X icon) for admin/leadership but "Perlu Perbaikan" (pen icon) for requester — likely intentional softening, but worth a conscious decision.
- "pantau … secara real-time" claim with no last-updated timestamp.
- DPA per-item list caps visible height (~5 rows) then scrolls inside a card — nested scroll areas can be easy to miss.

## Questions to Consider
- What if the signer's TTE queue were the first thing a pejabat sees, not the last?
- Does the dashboard need a decorative hero at all, or should the work start immediately?
- What would a confident KPI row look like if the numbers, not the cards, carried the weight?
