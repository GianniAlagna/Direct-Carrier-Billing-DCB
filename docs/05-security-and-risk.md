# 05 — Security and Risk Analysis

## 1. Executive summary

The design is a **closed-loop, prepaid** system: the worst case per member is
the loss of their own balance, not unlimited credit. The main risks in 2008
were not cryptographic but **operational**: code sharing, staff fraud, SMS
billing disputes and the regulatory classification of the wallet.

## 2. Assets

| Asset | Protection need |
|---|---|
| Wallet balances / ledger | Integrity, auditability |
| HOTP secrets | Confidentiality |
| Personal data (name, DOB, photo, phone no.) | Confidentiality (DSG) |
| Door decision | Availability (peak hour), integrity |
| Cash at counter | Physical security |

## 3. Threat model

| ID | Threat | Likelihood | Impact | Control |
|---|---|---|---|---|
| T1 | Guest shares screenshot of code | High | Low | One-time codes; **photo shown at door**; member flagged on mismatch |
| T2 | Code guessing at door (6 digits) | Low | Low | 1:100,000 per try with window 10; 3 tries then 10 min lock per member no. |
| T3 | Stolen phone | Medium | Medium | App PIN; Keychain; SMS `STOP`; photo check |
| T4 | Secret extraction from phone (jailbreak) | Low (2008) | Medium | Only own wallet affected; revoke on re-activation |
| T5 | Theft of venue node / door MacBook | Low | High | FileVault on Mac, no HOTP secrets on S4 (only hashes of next 20 codes), VPN keys revocable |
| T6 | Staff creates fake reversals / top-ups | Medium | Medium | Manager PIN, four-eyes > CHF 50, daily exception report, staff ID on every row |
| T7 | Forged PSP callback | Medium | High | Signature verification, amount + refno match, idempotency, IP allow-list |
| T8 | Forged SMS MO / spoofed sender | Medium | Medium | MO accepted only from aggregator IPs over HTTPS with shared secret; credit only on billed DLR |
| T9 | SQL injection / XSS | Medium | High | PDO prepared statements, output escaping, no dynamic SQL |
| T10 | Replay of a check-in request on the LAN | Low | Low | Counter advance makes replays fail; LAN isolated (separate VLAN, no guest Wi-Fi) |
| T11 | DoS on central server | Low | Medium | Venue works offline |
| T12 | Chargeback fraud (top-up with stolen card, spend at bar) | Medium | Medium | 3-D Secure where available; new members: card top-up limit CHF 100/week for 30 days |
| T13 | Underage guest uses adult's account | Medium | High (legal) | Photo + bouncer; age flag on bar screen |

### Brute-force arithmetic (T2)

With a look-ahead window of 10, a random guess succeeds with probability
10 / 1,000,000 = 1/100,000. With 3 attempts per 10 minutes, an attacker at the
door gets ~18 attempts per hour → expected success after ~5,500 hours.

## 4. Cryptography (2008 state of the art)

| Purpose | Mechanism |
|---|---|
| One-time code | HOTP / HMAC-SHA1 (RFC 4226). SHA-1 collision weakness does not affect HMAC use. |
| Transport | TLS 1.0 (SSLv2 disabled), RC4/AES suites per 2008 defaults |
| Secrets at rest (server) | AES-128-CBC via `mcrypt` with key held outside the DB (config file, root-only) |
| Passwords (back office) | Salted SHA-256 (bcrypt via `crypt()` became reliable in PHP 5.3) |
| Venue code cache | `SHA1(member_no ‖ code ‖ venue_salt)` for next 20 counters |

Known limitation: the venue cache hashes a 6-digit space; an attacker with the
disk could brute-force them. Impact is limited to the next 20 codes of each
member, which are invalidated by resync after an incident.

## 5. Data protection by design

- Collect only: mobile no., name, DOB, photo; e-mail optional.
- No storage of card data (PSP hosted page).
- Photo used only for door/bar identity check; not for marketing.
- Marketing SMS only with separate opt-in; `STOP` always free of charge.
- Retention: member data deleted 12 months after `CLOSED`, except ledger rows
  (accounting retention, pseudonymised).

## 6. Operational risk register

| Risk | Probability | Impact | Mitigation | Owner |
|---|---|---|---|---|
| Door staff not trained → slower than cash | High | High | Training night, laminated cheat sheet, dedicated "QTPass lane" | Night manager |
| Low adoption (< 10 % of guests) | Medium | High | Bonus credit (+10 % on top-up), skip-the-queue lane, DJ promotion | Marketing |
| Carrier refuses premium SMS for club services | Medium | High | Early contract review with each carrier; fallback card + cash top-up | Management |
| Regulatory reclassification of wallet (AML/banking) | Medium | High | Closed loop, caps, legal opinion before launch (chapter 06) | Management |
| Single developer dependency | High | Medium | Documentation, SVN, second developer briefed | CTO |
| Power/DSL failure | Medium | Medium | UPS, UMTS backup, offline mode | Tech |
| Reputation: data leak | Low | High | Minimisation, encryption, access logs | CTO |
