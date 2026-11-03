# 03 — Logical Process Construct

## 1. Actors and systems

| ID | Actor / system | Role |
|---|---|---|
| A1 | Guest (Member) | Registers, tops up, pays at door and bar |
| A2 | Door staff (Kasse) | Enters code, checks photo and age, hands out wristband |
| A3 | Bouncer (Security) | Face control, ID check; can override/deny |
| A4 | Bar staff | Charges drinks |
| A5 | Night manager | Opens/closes event, approves overrides, runs settlement |
| A6 | QT back office | Refunds, blocking, reporting, accounting |
| S1 | QTPass iPhone app | Generates one-time codes offline, shows balance |
| S2 | SMS channel | Commands for non-iPhone guests, receipts |
| S3 | QTPass central server | Ledger, members, verification, reporting |
| S4 | Venue node | Mac mini in the club office; local cache, offline operation |
| S5 | Door / bar terminals | Browser front end on the LAN to S4 |
| S6 | PSP | Card top-ups |
| S7 | SMS aggregator + carriers | MO/MT SMS, premium billing, DLR |

## 2. Process landscape

```
 CORE PROCESSES (guest-facing)
 ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐
 │ P1 Register│─>│ P2 Activate│─>│ P3 Top-up  │─>│ P5 Door    │─>│ P6 Bar     │
 │            │  │    device  │  │            │  │   check-in │  │   payment  │
 └────────────┘  └────────────┘  └────────────┘  └────────────┘  └────────────┘
                                       ^               ^
                                 ┌────────────┐        │
                                 │ P4 Presale/│────────┘
                                 │ guest list │
                                 └────────────┘
 SUPPORT PROCESSES
 ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐
 │ P7 Refund /│  │ P8 Block / │  │ P9 Night   │  │ P10 Offline│
 │    payout  │  │  lost phone│  │ settlement │  │  operation │
 └────────────┘  └────────────┘  └────────────┘  └────────────┘
```

---

## 3. P1 — Registration

**Trigger:** guest wants to join (flyer, website, SMS keyword, in-club promo stand).
**Channels:** web form, iPhone app, SMS `JOIN`.

| Step | Actor | Action | System rule |
|---|---|---|---|
| 1.1 | Guest | Enters mobile no., first/last name, date of birth, e-mail (optional) | Mobile no. is unique key (E.164, `+41…`) |
| 1.2 | S3 | Sends SMS with 5-digit verification PIN | PIN valid 15 min, max 3 attempts |
| 1.3 | Guest | Enters PIN | Mobile number verified = account `PENDING_ID` |
| 1.4 | Guest | Accepts T&C and privacy notice | Stored with timestamp and version |
| 1.5 | Door staff (first visit) | Checks ID card / passport, takes photo with door webcam | Date of birth confirmed, photo stored → `ACTIVE` |
| 1.6 | S3 | Assigns member no. (4–6 digits, e.g. `4711`) | Member no. printed on welcome SMS |

**Rule:** a member is only `ACTIVE` after the **physical ID check** (age, photo).
This is the anti-fraud anchor of the whole system and doubles as youth protection.

SMS variant:
```
Guest  -> 939 (short no.): JOIN Max Muster 14.02.1986
Server -> Guest: Welcome to QT! PIN 52817. Reply PIN 52817 to confirm. Std. SMS fee.
Guest  -> 939: PIN 52817
Server -> Guest: Confirmed. Member no. 4711. Show ID at your first visit.
```

---

## 4. P2 — Device activation (iPhone app)

Provisions the HOTP secret onto the phone.

```
iPhone app                 Server                          Guest's phone (SMS)
    |-- POST /activate ------->|                                 |
    |   msisdn=+4179...        |-- generate activation code ---->|
    |                          |   SMS "QT activation: 381-204"  |
    |<- (user types code) -----------------------------------------|
    |-- POST /activate/confirm>|                                 |
    |   msisdn, code, devname  |-- create secret K (20 bytes)    |
    |<-- plist {member, K, c=0}|   store K encrypted, counter=0  |
    |-- store K in Keychain    |                                 |
    |-- ask user for app PIN   |                                 |
```

| Rule | Value |
|---|---|
| Secret | 160-bit random (`/dev/urandom`) |
| Transport | HTTPS only; secret shown exactly once |
| Storage on phone | Keychain (`kSecClassGenericPassword`) |
| App PIN | 4 digits, local only, 5 wrong attempts → wipe secret, re-activate |
| One device per member | New activation revokes the old secret |

---

## 5. P3 — Top-up

### 5.1 Card (PSP, web or app)

| Step | Action |
|---|---|
| 3.1 | Guest chooses amount CHF 20 / 50 / 100 / 200 |
| 3.2 | Redirect to PSP hosted payment page (card data never touches QT servers) |
| 3.3 | PSP returns to `success_url`, plus server-to-server notification |
| 3.4 | S3 verifies notification signature + amount, books `TOPUP_CARD` |
| 3.5 | SMS receipt: `QT: CHF 50.00 credited. Balance CHF 62.00. Ref T0815` |

### 5.2 Premium SMS (Direct Carrier Billing)

| Step | Action |
|---|---|
| 3.6 | Guest sends `TOP` to the premium short number of the chosen price point (9391 = CHF 10, 9392 = CHF 20; example numbers set up with the aggregator) |
| 3.6a | **CHF 20 only:** S3 replies free of charge "Reply YES to confirm CHF 20.-"; guest answers `YES` within 15 min. Required because fixed fees above CHF 10 need express confirmation (PBV Art. 11a Abs. 4) |
| 3.7 | Aggregator forwards MO to `sms/inbound.php` |
| 3.8 | S3 sends billed MT ("CHF 10 credit") through aggregator; guests who blocked paid short numbers (FDV Art. 40 Abs. 2) receive no billing and are pointed to card/cash |
| 3.9 | Only after **successful delivery report (DLR)** = carrier billed → book `TOPUP_SMS` with net credit |
| 3.10 | Failed DLR → no credit, info SMS |

**Credit policy for SMS top-up:** guest pays CHF 10, receives **CHF 10 credit**
(club absorbs carrier/aggregator share as acquisition cost) — or CHF 8 credit
(cost passed on). Decision: see chapter 07.

### 5.3 Cash top-up at the venue counter

Night manager's counter converts cash into credit once (one queue, then fast
lanes). Booked as `TOPUP_CASH` with staff ID.

---

## 6. P4 — Pre-sale and guest list

| Type | Wallet effect |
|---|---|
| Pre-sale ticket (paid online) | Entitlement `TICKET(event_id)`, no debit at door |
| Guest list (DJ/promoter) | Entitlement `GUESTLIST(event_id, issuer)`, fee 0 or reduced |
| VIP | Entitlement + optional welcome credit |

Entitlements are consumed at check-in **before** the wallet balance.

---

## 7. P5 — Door check-in (core process)

### 7.1 Happy path (iPhone)

| # | Actor | Action | Time budget |
|---|---|---|---|
| 5.1 | Guest (in queue) | Opens app, enters app PIN, taps "Show pass" → app increments counter, shows **6-digit code**, member no., animated background in the colour of the night | before reaching door |
| 5.2 | Door staff | Types member no. + code on numeric keypad | 3 s |
| 5.3 | S4 | Verifies HOTP within look-ahead window, checks member status, entitlement/balance, already-inside flag | < 1 s |
| 5.4 | Terminal | Shows **green** screen: photo, first name, age, "CHF 25 debited / Presale / Guest list" | — |
| 5.5 | Door staff | Compares face to photo, hands out wristband | 3 s |
| 5.6 | S4 | Books `ENTRY` transaction, sets `inside=1`, SMS receipt queued (sent outside peak, batched) | — |

Target: **≤ 8 seconds per guest**.

### 7.2 SMS path (any phone)

```
Guest -> 939: IN 4711
Server -> Guest: QT door code 482913 (valid 15 min). Balance CHF 37.
Door staff types 4711 + 482913 -> same verification
```
SMS codes are server-generated HOTP values from the same counter space and
are invalidated after 15 min or first use.

### 7.3 Decision table at the door

| Member status | Code valid | Already inside | Entitlement | Balance ≥ fee | Result |
|---|---|---|---|---|---|
| ACTIVE | yes | no | ticket/guest list | n/a | GREEN — no debit |
| ACTIVE | yes | no | none | yes | GREEN — debit fee |
| ACTIVE | yes | no | none | no | ORANGE — "top up at counter or pay cash" |
| ACTIVE | yes | yes | any | any | RED — "already checked in" → bouncer |
| ACTIVE | no | — | — | — | RED — "invalid code" (max 3 tries, then lock 10 min) |
| PENDING_ID | yes | no | — | — | ORANGE — perform P1 steps 1.5/1.6, then retry |
| BLOCKED | — | — | — | — | RED — refer to night manager |
| Under age for event (16+/18+) | — | — | — | — | RED — no entry, regardless of balance |

**Bouncer override:** bouncer can always deny entry; a denial after a debit
triggers an automatic reversal (`ENTRY_REVERSAL`).

### 7.4 Re-entry

Wristband + `inside` flag. Exit is not scanned in phase 1 (re-entry by wristband).

---

## 8. P6 — Bar payment

| # | Action |
|---|---|
| 6.1 | Bar staff types order on terminal (pre-configured buttons: Beer, Longdrink, Water, Shot) |
| 6.2 | Guest shows new code (app) or says member no. + receives SMS code (slow; discouraged at the bar) |
| 6.3 | S4 verifies, shows photo + **age flag** (spirits only 18+, AlkG Art. 41 Abs. 1 lit. i; other alcohol 16+, LGV Art. 11) |
| 6.3a | Spirits are debited from paid credit only, never from `BONUS` credit (no incentives for spirits, AlkG Art. 41 Abs. 1 lit. h) |
| 6.4 | Debit, green screen, drink served |

**Bar speed option ("tab"):** at check-in the guest may enable a
*night tab* — at the bar, member no. + photo check is enough, up to CHF 50
per night without code. Reduces friction; risk limited by cap and photo.

---

## 9. P7 — Refund / payout

| Case | Process |
|---|---|
| Guest leaves the system | Request via web/SMS `REFUND` → back office pays remaining balance to bank/postal account (IBAN/PC account) minus admin fee disclosed in T&C, within 30 days |
| Card top-up disputed (chargeback) | Ledger reversal, member `BLOCKED` until settled |
| Door/bar error | Night manager reversal with reason code, four-eyes rule above CHF 50 |
| Event cancelled | Automatic re-credit of all `TICKET` entitlements |

Unused balance does not expire silently; the T&C define a dormancy rule (for
example notice after 24 months of inactivity).

---

## 10. P8 — Blocking / lost phone

```
Guest -> 939: STOP 4711          (from a different phone: hotline/web with PIN)
Server: member status BLOCKED, secret revoked, balance frozen
Guest re-activates on new phone (P2), ID check at next visit if phone number changed
```

---

## 11. P9 — Night settlement (automated, 05:30)

| # | Step | Automation |
|---|---|---|
| 9.1 | Night manager closes event on terminal | Manual |
| 9.2 | Venue node uploads all offline transactions | `cron: sync_venue.php` |
| 9.3 | Server reconciles venue journal vs. central ledger (count + sum per type) | `cron: night_settlement.php` |
| 9.4 | Report: entries by channel (QTPass / cash / guest list), bar revenue, top-ups, reversals, arrival curve per 15 min | e-mail PDF/CSV to management |
| 9.5 | Cash count (remaining cash only) entered and compared | Manual |
| 9.6 | Weekly: SMS aggregator CSV vs. `TOPUP_SMS` bookings | `cron: reconcile_sms.php` |
| 9.7 | Monthly: PSP settlement vs. `TOPUP_CARD` | `cron` + manual review |

---

## 12. P10 — Offline operation

The basement has no mobile signal; the DSL line of the club can fail.

| Component | Offline behaviour |
|---|---|
| iPhone app | Fully offline: code = HOTP(K, c) on device |
| Venue node S4 | Holds cache: members, photos, balances, status, and for each member the **hashes of the next 20 expected codes** (not the secret) |
| Door/bar terminals | Talk only to S4 over wired LAN; never directly to the internet |
| SMS path | Requires network; offline → "use app or cash" |
| Sync | Every 60 s when online; journal uploaded with sequence numbers; server is master for balances |

**Offline credit limit:** while offline, S4 accepts debits up to the cached
balance; conflicts (same member debited at two venues) are prevented because
a member is bound to one venue per night at check-in.

---

## 13. State machines

### 13.1 Member

```
          JOIN/verify             ID check
 [NEW] ───────────────> [PENDING_ID] ─────────> [ACTIVE] <──────────┐
                              │                    │   │            │ unblock
                              │ 30 days no visit   │   │ STOP/fraud │ (back office)
                              v                    │   v            │
                          [EXPIRED]                │ [BLOCKED] ─────┘
                                                   │
                                          REFUND   v
                                              [CLOSED]
```

### 13.2 Transaction

```
 [CREATED] ──verify ok──> [AUTHORISED] ──booked──> [BOOKED] ──settled──> [SETTLED]
     │                         │                       │
     └──verify fail──> [DECLINED]   └──timeout──> [VOID]   └──reversal──> [REVERSED]
```

Every state change is an append-only journal row; balances are derived,
never edited.

### 13.3 Event night

```
 [PLANNED] -> [OPEN] (doors) -> [CLOSED] (last entry) -> [SETTLED] (P9 done)
```

---

## 14. Exception catalogue

| Code | Situation | Handling |
|---|---|---|
| E01 | Phone battery empty | SMS path from friend's phone (`IN 4711` + door asks DOB) or cash |
| E02 | Code shown via screenshot by another person | Photo check fails → RED, member flagged |
| E03 | Counter desync (guest pressed "show" 30×) | Look-ahead window 10 online; > 10 → resync by entering two consecutive codes |
| E04 | Server/DSL down | P10 offline mode |
| E05 | Venue node down | Paper fallback: cash only, QTPass paused; announce |
| E06 | SMS DLR delayed (carrier) | Top-up stays `PENDING`; guest sees "pending" in app |
| E07 | Chargeback on card top-up | Reverse, block, collection letter |
| E08 | Staff fraud (fake reversal) | Reversal needs night manager PIN; daily reversal report |
| E09 | Minor with fake ID at registration | Bouncer re-check; flag; permanent block |
| E10 | Guest disputes debit | Journal + terminal ID + timestamp + staff ID; refund decision by back office |
