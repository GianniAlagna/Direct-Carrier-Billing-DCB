# 06 — Legal and Regulatory Frame (Switzerland, as of 2008)

> **Historical reference, not legal advice.** This chapter reconstructs the
> legal questions the project had to answer in 2008. Every statutory reference
> below was checked against the consolidated version **in force in 2008** on
> fedlex.admin.ch (see the verification table in section 11). Points that rest
> on administrative practice rather than on a published act are marked
> **[practice]**. Several acts have since been revised (FMG 2021, FINMA
> practice, revDSG 1 Sep 2023, UWG 2012, MWSTG 2010).

## 1. Executive summary

| Area | Key question | 2008 assessment | Severity |
|---|---|---|---|
| Banking law | Is a prepaid wallet balance a "public deposit"? | Strong exception: funds that are **consideration for a service contract** are not deposits (BankV Art. 3a Abs. 3 lit. a); still mitigate by caps, no interest | Medium |
| Anti-money laundering | Is QT a financial intermediary? | Payment services are in scope (GwG Art. 2 Abs. 3 lit. b); with > 10 lasting customer relationships the activity is "berufsmässig" (VB-GwG Art. 5). Exemption for closed-loop credit rests on **[practice]** → legal opinion mandatory | **High** |
| Telecom / value-added services | Premium SMS top-up rules | FDV Art. 35–41: short numbers, caps, blocking rights, billing transparency | Medium |
| Price disclosure | How must SMS prices be shown? | PBV Art. 11a/11b; **fixed fee > CHF 10 requires explicit confirmation** → affects the CHF 20 top-up | **High** |
| Data protection | Photos, DOB, attendance data | DSG Art. 4, 6, 7, 8; **registration duty** if data is regularly disclosed to third parties (promoters) | Medium |
| Youth protection | Age-restricted entry and alcohol | AlkG Art. 41 Abs. 1 lit. i (spirits 18+), LGV Art. 11 (alcohol 16+) | **High** |
| Alcohol promotion | Is a top-up bonus a prohibited incentive? | AlkG Art. 41 Abs. 1 lit. h prohibits retail of spirits with "Zugaben und anderen Vergünstigungen" → bonus credit must not be usable for spirits | Medium |
| Marketing SMS | Consent for event SMS | UWG Art. 3 lit. o — **in force since 1 April 2007**: prior consent, correct sender, free opt-out | Medium |
| VAT | Rate and timing | MWSTG 1999 Art. 36 Abs. 3: 7.6 %; voucher timing **[practice]** | Low |
| Contract law / records | T&C, retention | OR Art. 1 ff., OR Art. 962 (10 years) | Low |

## 2. Banking law — public deposits

- **BankG (SR 952.0) Art. 1 Abs. 2** (Stand 27.12.2006): natural and legal
  persons not subject to the Banking Act may not accept public deposits on a
  professional basis ("gewerbsmässig").
- **BankV (SR 952.02) Art. 3a** (Stand 1.4.2008):
  - Abs. 2: "gewerbsmässig" = continuously accepting **more than 20** public deposits.
  - **Abs. 3 lit. a:** not deposits are "Gelder, die eine Gegenleistung aus einem
    Vertrag auf Übertragung des Eigentums oder aus einem Dienstleistungsvertrag
    darstellen".

**Assessment:** a QTPass top-up is an advance payment for club services
(entry, drinks) under the member contract → it falls under the exception of
Art. 3a Abs. 3 lit. a, **provided** the credit is tied to QT's own services.
The argument weakens if the balance can be paid out at will, earns interest,
is transferable, or is accepted by third parties.

**Design response:** balance usable only for QT services; payout only on
account closure; caps (CHF 300 balance, CHF 500 top-up/month); no interest;
no P2P; customer funds on a separate account.

## 3. Anti-money laundering

- **GwG (SR 955.0) Art. 2 Abs. 3 lit. b** (Stand 19.12.2006): financial
  intermediaries include persons who professionally provide "Dienstleistungen
  für den Zahlungsverkehr", in particular electronic transfers for third
  parties or issuing/administering means of payment such as credit cards and
  traveller's cheques.
- **VB-GwG (SR 955.20)**, ordinance of the Kontrollstelle of 20 Aug 2002
  (Stand 25.4.2006): activity is "berufsmässig" if any criterion is met
  (Art. 2): gross revenue > CHF 20,000/year (Art. 4), **lasting relationships
  with more than 10 contracting parties** (Art. 5), control over third-party
  assets > CHF 5 m (Art. 6), or transaction volume > CHF 2 m/year (Art. 7).

**Assessment:** if QTPass credit counted as a "means of payment", the
thresholds would be exceeded immediately (thousands of members). The ordinance
contains **no explicit exception** for closed-loop instruments; the view that a
credit usable only with the issuer itself is not a payment service rests on
the supervisory practice of the Kontrollstelle (from 1 Jan 2009 FINMA)
**[practice]**. This was the single biggest legal uncertainty of the project.

**Design response:** strictly closed loop; no P2P; refund only to an account
in the member's own name; caps; verified mobile number + physical ID check
(already required for age control). **Written legal opinion and, if needed, a
ruling request to the Kontrollstelle before launch.** Opening QTPass to other
merchants (clubs, festivals) would clearly trigger SRO membership or direct
supervision.

## 4. Telecommunications and value-added services

- **FMG (SR 784.10)**, Stand 1.8.2008. The revision of 24 March 2006 (in force
  **1 April 2007**) inserted:
  - **Art. 12b — Mehrwertdienste:** the Federal Council regulates value-added
    services to prevent abuse, in particular price caps, price disclosure and a
    seat or branch in Switzerland (Abs. 1); fees above a threshold may only be
    charged with the user's **express consent** (Abs. 2).
  - **Art. 12c — Schlichtung:** OFCOM sets up a conciliation body for disputes
    between customers and providers of telecom or value-added services
    (ombudscom).
- **FDV (SR 784.101.1)** of **9 March 2007**, in force 1 April 2007 (Art. 109),
  **5th chapter "Mehrwertdienste", Art. 35–41:**
  - **Art. 36 Abs. 3:** SMS/MMS value-added services only via short numbers
    allocated under the AEFV.
  - **Art. 37:** the holder of the short number is the value-added service
    provider; seat or branch in a Lugano Convention state (Abs. 2).
  - **Art. 38:** itemised billing on request; a disputed bill must not lead to
    blocking the line (Abs. 4).
  - **Art. 39:** caps — fixed fee max. **CHF 100** (Abs. 1); total per
    connection or subscription max. **CHF 400** (Abs. 4).
  - **Art. 40 Abs. 2:** customers can **block all paid short-number SMS
    services**, free of charge (Abs. 4) → some guests cannot top up by SMS at
    all; card/cash channels remain necessary.
  - **Art. 41:** carriers block erotic/pornographic value-added services for
    known customers under 16 (not relevant for QTPass content, but relevant
    for the carriers' age data).
- Carrier and aggregator **contracts** were the practical gatekeeper: many
  carrier terms restricted premium SMS to digital content; using it to fund
  **venue credit** had to be cleared with each carrier.

## 5. Price disclosure

- **PBV (SR 942.211)**, Stand 1.4.2007:
  - **Art. 10 Abs. 1 lit. q:** value-added telecom services are subject to price disclosure.
  - **Art. 11a Abs. 1:** if the fixed fee or the price per minute **exceeds
    CHF 2**, nothing may be charged unless the price was announced beforehand,
    free of charge and unambiguously, in the language of the offer.
  - **Art. 11a Abs. 4:** if **fixed fees exceed CHF 10**, the service may only
    be charged after the consumer has **expressly confirmed** acceptance.
  - **Art. 11b:** push services — base fee, price per message, deactivation
    procedure and max. messages per minute must be disclosed before
    activation; charging only after express confirmation.

**Consequence for QTPass:**

| Top-up | Rule | Implementation |
|---|---|---|
| `TOP` to 9391 (CHF 10) | > CHF 2 → price must be announced beforehand (flyer, poster, web, `HELP` reply) | Price on every medium; no confirmation step needed (does not exceed CHF 10) |
| `TOP` to 9392 (CHF 20) | > CHF 10 → **express confirmation required** | Two-step flow: `TOP` → free SMS "Reply YES to confirm CHF 20.-" → `YES` triggers the billed MT (implemented in `src/server/sms/inbound.php`) |

No subscription/push model is used, so Art. 11b does not apply; `STOP` and
`HELP` are always free.

## 6. Data protection

- **DSG (SR 235.1)**, version of 19 June 1992, Stand 1.1.2008:
  - **Art. 4** — lawfulness, good faith, proportionality, purpose limitation.
  - **Art. 6** — cross-border disclosure → hosting in Switzerland.
  - **Art. 7** — appropriate technical and organisational security, detailed
    in **VDSG (SR 235.11) Art. 8–12** (general and special measures, logging,
    processing regulation).
  - **Art. 8** — right of access; answer within **30 days** (VDSG Art. 1 Abs. 4).
  - **Art. 11a Abs. 3** — private persons must **register data files** with the
    EDÖB before opening them if they regularly process sensitive data or
    personality profiles (**lit. a**) **or regularly disclose personal data to
    third parties (lit. b)**.
- **Assessment:** sharing attendance lists with promoters/DJs would trigger
  registration under lit. b; a detailed attendance history per member may
  also be a personality profile (lit. a). **Decision: register the QTPass data
  file with the EDÖB** or share only aggregated statistics.
- Photos are personal data (not "besonders schützenswert" per se); purpose
  limited to identity checks.

## 7. Youth protection and alcohol

- **AlkG (SR 680)**, Stand 1.6.2008, **Art. 41 Abs. 1:** retail of spirits
  ("gebrannte Wasser") is prohibited
  - **lit. i:** to children and young people **under 18**;
  - **lit. h:** "unter Gewährung von Zugaben und anderen Vergünstigungen, die
    den Konsumenten anlocken sollen".
- **LGV (SR 817.02)** of 23 Nov 2005, Stand 1.10.2008, **Art. 11:**
  - Abs. 1: no alcoholic beverages to persons **under 16**;
  - Abs. 2: a clearly visible sign at the point of sale must state the minimum
    ages.
- Cantonal hospitality law and permit conditions (closing hours, minimum age
  for events, security staff).

**QTPass response:** activation only after **physical ID check**; bar screen
shows the age flag (16+/18+); event minimum age enforced in the door
decision table. **New from verification:** the +10 % card top-up bonus is
booked as a separate `BONUS` credit that is **not usable for spirits**
(AlkG Art. 41 Abs. 1 lit. h); signage per LGV Art. 11 Abs. 2 at every bar.

## 8. Contract law, consumer protection, marketing

- **OR (SR 220) Art. 1 ff.:** T&C become part of the contract only if
  incorporated (accepted at registration, version stored).
- **UWG (SR 241)**, Stand 1.4.2007:
  - **Art. 3 lit. b:** no incorrect or misleading statements about prices.
  - **Art. 3 lit. o** (inserted by the act of 24 March 2006, **in force since
    1 April 2007**): mass advertising by telecom means requires **prior
    consent**, the correct sender and a free, simple opt-out. Exception: own
    similar offers to customers whose contact data was obtained at a sale
    and who were told about the opt-out.
  - **Art. 8** (2007 version): only T&C that are **misleading** and deviate
    considerably from the statutory order are unfair — weaker than after
    2012; fair terms nevertheless chosen.
- **QTPass:** marketing SMS only with separate opt-in at registration;
  sender = QT short number; every SMS states "STOP = free unsubscribe".

## 9. VAT and accounting

- **MWSTG 1999 (SR 641.20)**, Stand 1.1.2008, **Art. 36 Abs. 3:** standard
  rate **7.6 %**.
- The act contains no voucher rule. Treating multi-purpose credit like a
  voucher (VAT due at redemption, not at top-up) follows ESTV practice
  **[practice — confirm with the ESTV]**.
- **OR Art. 962 Abs. 1:** business books, vouchers and correspondence must be
  kept for **ten years**.

## 10. Compliance checklist before launch (2008)

| # | Item | Responsible | Status (2008) |
|---|---|---|---|
| 1 | Legal opinion banking (BankV 3a Abs. 3 lit. a) and AML (GwG 2 Abs. 3 lit. b / VB-GwG); ruling request to the Kontrollstelle if needed | Lawyer | Not started (project discontinued) |
| 2 | Carrier approval for premium SMS use case (Swisscom, Sunrise, Orange); short numbers per FDV Art. 36 Abs. 3 | Management + aggregator | Initial talks |
| 3 | T&C + privacy notice | Lawyer | Draft |
| 4 | Price disclosure review of all media (PBV Art. 11a); two-step confirmation for CHF 20 | Marketing + CTO | Implemented in draft code |
| 5 | Register data file with the EDÖB (DSG Art. 11a Abs. 3) | CTO | — |
| 6 | Bonus credit excluded from spirits (AlkG Art. 41 Abs. 1 lit. h); LGV signage | Club manager | Designed |
| 7 | Cantonal permit conditions for age control | Club manager | Existing |
| 8 | VAT treatment confirmation (ESTV) | Accountant | — |
| 9 | Separate customer-funds bank account | CFO | — |

## 11. Verification against fedlex

Checked on fedlex.admin.ch (consolidated versions and the official
compilation AS), November 2026.

| Act (SR) | Version used | Articles verified | Result |
|---|---|---|---|
| BankG (952.0) | Stand 27.12.2006 | Art. 1 Abs. 2 | ✅ confirmed |
| BankV (952.02) | Stand 1.4.2008 | Art. 3a Abs. 2, 3 lit. a | ✅ confirmed; lit. a added as key argument |
| GwG (955.0) | Stand 19.12.2006 | Art. 2 Abs. 3 lit. b | ✅ confirmed |
| VB-GwG (955.20) | Stand 25.4.2006 | Art. 2, 4–7 | ✅ added; no closed-loop exception in the ordinance → **[practice]** |
| FMG (784.10) | Stand 1.8.2008 | Art. 12b, 12c | ⚠️ **corrected:** Art. 12b concerns regulation, price caps and express consent — not blocking; 12c confirmed |
| FDV (784.101.1) | AS 2007 (original text of 9.3.2007) | Art. 35–41, 109 | ✅ confirmed; blocking right is **FDV Art. 40** |
| PBV (942.211) | Stand 1.4.2007 | Art. 10 Abs. 1 lit. q, 11a, 11b | ✅ confirmed; **new finding:** Art. 11a Abs. 4 (> CHF 10 needs express confirmation) |
| DSG (235.1) | Stand 1.1.2008 | Art. 4, 6, 7, 8, 11a | ✅ confirmed; Art. 11a Abs. 3 lit. b added |
| VDSG (235.11) | Stand 1.1.2008 | Art. 1 Abs. 4, 8–12 | ✅ confirmed |
| AlkG (680) | Stand 1.6.2008 | Art. 41 Abs. 1 lit. h, i | ✅ confirmed; **new finding:** lit. h (incentives) |
| LGV (817.02) | Stand 1.10.2008 | Art. 11 Abs. 1, 2 | ✅ confirmed |
| UWG (241) | Stand 1.4.2007 | Art. 3 lit. b, 3 lit. o, 8 | ⚠️ **corrected:** Art. 3 lit. o was in force since 1.4.2007, not only from 2012 |
| OR (220) | Stand 1.8.2008 | Art. 962 | ✅ confirmed |
| MWSTG (641.20) | Stand 1.1.2008 | Art. 36 Abs. 3 | ✅ confirmed; voucher timing = ESTV practice |

Note: fedlex's PDF filed under the FDV consolidation of 1.1.2008 actually shows the
text as of 1.3.2012; the FDV articles were therefore verified against the
original publication in the official compilation (AS 2007).
