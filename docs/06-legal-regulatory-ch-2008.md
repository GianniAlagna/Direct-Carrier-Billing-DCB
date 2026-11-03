# 06 — Legal and Regulatory Frame (Switzerland, as of 2008)

> **Historical reference, not legal advice.** This chapter reconstructs the
> legal questions the project had to answer in 2008. Article references are
> given to the versions in force at the time to the best of current knowledge
> and are marked **[verify]** where the exact article numbering should be
> checked against the historical consolidated text (fedlex.admin.ch, "Fassung"
> per date). Several acts have since been revised (FMG 2021, GwG/FINMA
> practice, revDSG 1 Sep 2023, UWG 2012).

## 1. Executive summary

| Area | Key question | 2008 assessment | Severity |
|---|---|---|---|
| Banking law | Is a prepaid wallet balance a "public deposit"? | Risk if open loop / unlimited; mitigate by closed loop, caps, no interest, legal opinion | **High** |
| Anti-money laundering | Is QT a financial intermediary? | Closed-loop instruments usable only at the issuer's own venues generally outside scope; open loop = in scope | **High** |
| Telecom / value-added services | Premium SMS top-up rules | Carrier contracts + price disclosure; stop always free | Medium |
| Price disclosure | How must SMS prices be shown? | PBV rules for value-added services | Medium |
| Data protection | Photos, DOB, attendance data | DSG 1992 principles, security, access right | Medium |
| Youth protection | Age-restricted entry and alcohol | Physical ID check at registration; age flag at bar | **High** |
| VAT | When is VAT due on top-ups? | At redemption (consumption), not at top-up | Low |
| Contract law | T&C, refunds, dormancy | Clear T&C incorporated at registration | Medium |

## 2. Banking law — public deposits

- **BankG (SR 952.0) Art. 1 Abs. 2:** persons and companies that are not banks
  may not accept deposits from the public on a professional basis.
- **BankV (SR 952.02) Art. 3a** defines public deposits and their exceptions
  **[verify the exact exceptions in the 2008 version]**.

**Risk:** a wallet that holds customer money for later use can be read as a
deposit. **Design response:**

1. Balance usable **only for QT services** (entry, bar, merchandise) — no
   cash-out except refund on account closure.
2. **Cap** per member (e.g. CHF 300 balance, CHF 500 top-up/month).
3. **No interest**, no transfers between members.
4. Customer funds on a separate bank account; balance monitoring.
5. **Written legal opinion** before launch; contact with the EBK
   (Eidgenössische Bankenkommission, replaced by FINMA on 1 Jan 2009) if in doubt.

## 3. Anti-money laundering

- **GwG (SR 955.0) Art. 2 Abs. 3:** financial intermediaries include persons
  who professionally accept, hold or transfer third-party assets, in
  particular providers of payment services (lit. b).
- 2008 supervisory practice (Kontrollstelle für die Bekämpfung der
  Geldwäscherei, from 2009 FINMA) treated **instruments that can only be used
  with the issuer itself** (closed loop, e.g. customer cards, venue credit) as
  generally outside financial intermediation **[verify the precise 2008
  ordinance / practice note]**.

**Design response:** strictly closed loop; no P2P; refund only to an account
in the member's own name; caps as above; registration with verified mobile
number + physical ID check (already part of P1 for age reasons).
**If QTPass were opened to third-party merchants** (other clubs, festivals)
→ SRO membership or direct FINMA supervision would become necessary.

## 4. Telecommunications and value-added services

- **FMG (SR 784.10)**, revised with effect from 1 April 2007, contains rules on
  value-added services, including the customer's right to have them blocked
  (**Art. 12b FMG [verify]**) and the conciliation body (**Art. 12c FMG**,
  ombudscom, operational 2008).
- **FDV (SR 784.101.1)** of 9 March 2007, section on value-added services
  **(Art. 35 ff. [verify])**: obligations of providers of value-added
  services, short-number allocation via carriers/BAKOM rules.
- Carrier and aggregator **contracts** were the practical gatekeeper: many
  carrier terms restricted premium SMS to **digital content and services**;
  billing for physical goods (drinks) was often excluded → **top-up of a
  service credit for club entry/events** must be explicitly cleared with each
  carrier.

## 5. Price disclosure

- **PBV (SR 942.211)** obliges disclosure of prices for value-added services,
  with specific rules for SMS-based services (**Art. 11a/11b PBV [verify]**):
  price per message shown clearly in all advertising, information on
  subscription/push services before contract conclusion, free deactivation.
- **QTPass rules:** every flyer, poster, web page and SMS response states the
  price in CHF ("CHF 10.– per SMS, plus standard SMS fee"). No subscription
  model. `STOP` and `HELP` always free.

## 6. Data protection

- **DSG (SR 235.1, version 1992)**:
  - **Art. 4** — lawfulness, good faith, proportionality, purpose limitation,
    recognisability of collection.
  - **Art. 7** — appropriate technical and organisational security measures
    (with VDSG Art. 8 ff.).
  - **Art. 8** — right of access (free of charge, within 30 days per VDSG).
  - **Art. 11a** — register of data files with the EDÖB if sensitive data or
    personality profiles are processed regularly **[assess: attendance
    history may constitute a personality profile → consider registration]**.
  - **Art. 6** — cross-border disclosure → hosting in Switzerland.
- Photos are personal data (not "besonders schützenswert" per se); purpose
  limited to identity checks.
- Marketing SMS: separate opt-in; UWG Art. 3 lit. o (mass advertising without
  consent) came only with the 2012 revision — in 2008 an opt-in was still
  best practice and carrier-required.

## 7. Youth protection

- **AlkG (SR 680) Art. 41 Abs. 1 lit. i:** no sale of spirits to persons under 18.
- **LGV (SR 817.02) Art. 11 [verify 2008 numbering]:** no alcoholic beverages
  to persons under 16.
- **Cantonal** Gastgewerbe/hospitality law and permit conditions (closing
  hours, minimum age for events, security staff).
- **QTPass response:** account activation only after **physical ID check**;
  date of birth stored; bar screen displays age flag (16+ / 18+); event-level
  minimum age enforced in the door decision table.

## 8. Contract law and consumer aspects

- **OR (SR 220) Art. 1 ff.:** T&C become part of the contract only if
  incorporated (accepted at registration, version stored).
- Clear rules on: refund of balance, fees (disclosed in advance), dormancy,
  blocking, liability for lost phones (member liable until `STOP`), dispute
  procedure.
- **UWG (SR 241)** 2008 version: no misleading price statements (Art. 3 lit. b);
  Art. 8 on T&C was weak in 2008 (strengthened 2012) — fair terms
  nevertheless chosen.

## 9. VAT and accounting

- **MWSTG 1999 (SR 641.20)**, standard rate **7.6 %** in 2008.
- Top-ups of a credit usable for different services (entry, food, drinks) are
  treated like multi-purpose vouchers: **VAT due at redemption**, not at top-up
  **[confirm with ESTV practice]**.
- Ledger retention **10 years** (OR Art. 962, 2008 version).

## 10. Compliance checklist before launch (2008)

| # | Item | Responsible | Status (2008) |
|---|---|---|---|
| 1 | Legal opinion banking/AML classification | Lawyer | Not started (project discontinued) |
| 2 | Carrier approval for premium SMS use case (Swisscom, Sunrise, Orange) | Management + aggregator | Initial talks |
| 3 | T&C + privacy notice | Lawyer | Draft |
| 4 | Price disclosure review of all media | Marketing | — |
| 5 | DSG: data file register assessment | CTO | — |
| 6 | Cantonal permit conditions for age control | Club manager | Existing |
| 7 | VAT treatment confirmation (ESTV) | Accountant | — |
| 8 | Separate customer-funds bank account | CFO | — |
