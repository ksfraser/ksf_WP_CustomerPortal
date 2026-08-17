# Sun Life ETF+ Portfolios — WP Customer Portal Integration (Planned)

The Sun Life ETF+ Portfolios fund family data now lives in:
- the Knowledge Base (`fa_kb_articles`, article *Sun Life ETF+ Portfolios - Fund Family Reference*),
- the stockmarket app seg-fund section (`seg_funds`, seeded via `sql/seed_sun_life_etf_plus_seg_funds.sql`),
- the canonical dataset `Ksfraser\KnowledgeBase\Data\SunLifeEtfPlusPortfolios` and
  `~/.hermes/skills/ksf_stockmarket/references/sun_life_etf_plus.json`.

## Current state
The WP customer portal (`ksf_WP_CustomerPortal`) has **no investment / seg-fund display yet**
(current services: Woo sync, salesman approval). So the data is not yet surfaced to clients on the portal.

## Proposed integration
1. Add a read-only "Investment Products" / "Segregated Funds" page template that pulls from the KB article
   (via the FA_KnowledgeBase / KB API) or directly from the stockmarket `seg_funds` data.
2. Show, per portfolio: name, risk profile, management fee (Series A/F), asset mix, and a link to the full
   fund facts. Do **not** show the predecessor Tactical ETF returns as ETF+ performance.
3. Reuse the canonical dataset / KB article as the content source to avoid drift.

## Open items
- Obtain seg-fund contract codes from Sun Life (mutual-fund codes are prefix SUN; seg codes differ).
- Decide whether the portal reads live NAVs from the stockmarket app or a static snapshot.
- Confirm client-facing disclaimer wording (ADVISOR USE ONLY material must not be shown to clients verbatim).
