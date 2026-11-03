# 07 — Business Model and Unit Economics

> Figures are **planning assumptions** for a 2008 Swiss club, not historical
> results (the system was never launched).

## 1. Value proposition

| For | Value |
|---|---|
| Guest | Skip-the-queue lane, no cash, +10 % bonus on card top-ups, guest-list perks, balance in the app |
| Club | Throughput, less cash risk, lower bar leakage, float on prepaid balances, guest data |
| Promoters / DJs | Attendance and arrival curves per act, targeted SMS (opt-in) |

## 2. Revenue and cost levers

| Lever | Effect |
|---|---|
| Faster door | More guests admitted in the peak hour instead of lost to the queue |
| Lower leakage | 2–5 % of bar revenue on cash; target ≤ 1 % on QTPass |
| Breakage | Small residual balances never refunded (must be handled per T&C, not relied upon) |
| Float | Prepaid balances held before consumption (interest income minor in 2008) |
| Sponsoring | Branded "night colour" pass screens, drink promotions |

## 3. Top-up channel economics (per CHF 100 credit)

| Channel | Gross paid by guest | Typical cost 2008 (assumption) | Net to QT |
|---|---|---|---|
| Premium SMS (10 × CHF 10) | CHF 100 | Carrier + aggregator share **~35–50 %** | CHF 50–65 |
| Credit card via PSP | CHF 100 | ~2.5–3.5 % + CHF 0.30 per transaction | ~CHF 96–97 |
| PostFinance via PSP | CHF 100 | ~1.5–2.5 % | ~CHF 97–98 |
| Cash at venue counter | CHF 100 | Handling, counting, transport | ~CHF 98–99 |

**Conclusion:** premium SMS is an **acquisition and convenience channel**,
not a profit channel. Policy options:

| Variant | Rule | Trade-off |
|---|---|---|
| A — "Risk-minimising" | SMS top-up credited at **80 %** (CHF 10 → CHF 8), disclosed | Honest cost pass-through; lower adoption |
| B — "Growth" | SMS credited at 100 %, capped at CHF 20/night | Club absorbs cost as marketing; encourages first use |
| C — "Hybrid" (recommended) | First SMS top-up 100 %, later ones 80 %; card top-ups +10 % bonus | Steers guests to cheap channel after onboarding |

## 4. Cost model (season 1, one club)

| Item | One-off (CHF) | Recurring / year (CHF) |
|---|---|---|
| Development (backend, terminals, iPhone app, web app) | 60,000–90,000 | — |
| Apple Developer Program | — | ~110 |
| Servers (2 × 1U) + venue node + 4 MacBooks + keypads + UPS | 18,000 | — |
| Hosting/colocation Swiss DC | — | 4,800 |
| SMS aggregator setup + short numbers | 3,000–8,000 | 6,000 + per-SMS fees |
| PSP setup | 1,000 | 600 + transaction fees |
| Legal opinion + T&C | 8,000–15,000 | 2,000 |
| SSL certificate | — | 500 |
| Staff training, marketing launch | 10,000 | 5,000 |
| **Total** | **~100,000–140,000** | **~20,000 + variable** |

## 5. Break-even sketch

Assumptions: 50 nights/year, 1,000 guests/night, QTPass share 30 % in year 1.

| Effect | Year 1 estimate |
|---|---|
| Additional entries from faster door (+40 guests/night × CHF 25 × 50) | CHF 50,000 |
| Reduced bar leakage (bar revenue CHF 30,000/night × 30 % share × 2.5 % saved × 50) | CHF 11,250 |
| Reduced cash handling/security | CHF 5,000 |
| Less: channel costs (SMS share) | −CHF 8,000 |
| **Net benefit year 1** | **~CHF 58,000** |

→ Payback **~2 seasons** for one club; the case improves strongly with a
second venue or festival use (fixed costs shared). This sensitivity — the
dependence on scale — was a key factor in the discontinuation (chapter 08).
