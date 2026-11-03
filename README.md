# QTPass — Mobile Door & Bar Payment for an EDM Club (Concept 2008)

> **Direct Carrier Billing (DCB) · iPhone OS 2.x · SMS · Stored-Value Wallet**
> A pioneering concept from **QT Records** (Switzerland) to let clubbers pay
> entry and drinks with their phone — years before TWINT, Apple Pay or NFC
> wallets existed. Designed ~2006–2008, **never built, discontinued**.

---

## Provenance: read this first

| Item | Status |
|---|---|
| Core idea (phone as door pass + wallet) | Conceived by the QT Records team in the early smartphone era (c. 2006–2008) |
| Implementation in 2008 | **Not developed.** The project was discontinued at QT Records. |
| This repository | A **2026 retrospective reconstruction**. It documents the concept and drafts the system **as it could have been built with 2008 technology only**. |
| Commit dates | Real (2026). Nothing in this repository is backdated or presented as a historical original. |
| Code | Draft / reference quality. Written to 2008 constraints (Objective-C 2.0 with manual retain/release, iPhone OS 2.x SDK, PHP 5.2, MySQL 5.0). Not production code. |

The purpose is to show the **intent and the system thinking**: an automated,
cash-light door and bar process for a high-volume EDM club, using the phone as
identity, ticket and wallet. A rudimentary forerunner of what Switzerland later
got with TWINT (launched 2014/2015).

---

## The idea in one paragraph

A guest registers once with their mobile number. They top up a club wallet
(credit card via a Swiss PSP, or carrier billing via premium SMS). At the door
the iPhone app shows a **rotating 6-digit one-time code** (HOTP, RFC 4226) on a
full-screen, animated "pass" in the colour of the night. Door staff type or
scan the code into the door terminal; entry fee is debited, wristband handed
out, queue moves on. Guests without an iPhone send an SMS (`IN 4711`) and get
their code by reply. Drinks are paid the same way at the bar. No cash handling
at the door, no change, no till counting at 05:00, real-time attendance data
for the promoter.

---

## Repository map

| Path | Content |
|---|---|
| [`docs/01-vision-and-context.md`](docs/01-vision-and-context.md) | Problem, vision, why 2008, target club operations |
| [`docs/02-technology-landscape-2008.md`](docs/02-technology-landscape-2008.md) | What was technically available in 2008 — and what was not |
| [`docs/03-process-construct.md`](docs/03-process-construct.md) | Logical process construct: actors, end-to-end flows, state machines, exception handling |
| [`docs/04-system-architecture.md`](docs/04-system-architecture.md) | Components, interfaces, data model, deployment on 2008 infrastructure |
| [`docs/05-security-and-risk.md`](docs/05-security-and-risk.md) | Threat model, one-time codes, fraud, operational risk |
| [`docs/06-legal-regulatory-ch-2008.md`](docs/06-legal-regulatory-ch-2008.md) | Swiss legal frame as of 2008 (telecom, price disclosure, AML, data protection, youth protection) |
| [`docs/07-business-model.md`](docs/07-business-model.md) | Revenue logic, fee comparison (premium SMS vs. card), unit economics |
| [`docs/08-roadmap-and-discontinuation.md`](docs/08-roadmap-and-discontinuation.md) | Planned phases, why it stopped, lessons, how the market later solved it |
| [`docs/09-glossary.md`](docs/09-glossary.md) | Terms and abbreviations |
| [`src/iphone/`](src/iphone/) | iPhone OS 2.x native app draft (Objective-C) |
| [`src/webapp/`](src/webapp/) | iPhone Safari web app (the 2007/2008 "web clip" approach) |
| [`src/server/`](src/server/) | LAMP backend draft: wallet ledger, HOTP verification, SMS gateway callbacks |
| [`src/door-terminal/`](src/door-terminal/) | Door/bar terminal UI for staff (runs on a MacBook / Mac mini at the door) |
| [`src/automation/`](src/automation/) | Cron jobs: night settlement, SMS reconciliation, offline cache, backups |
| [`tests/`](tests/) | HOTP check against the RFC 4226 test vectors |
| [`website/pws_import_qtpass.sql`](website/pws_import_qtpass.sql) | SQL import of this project for giannialagna.me (`ga_project`, prefix `pws_`, EN/DE/IT/FR/ES/LA) |

---

## Quick look at the core flow

```
 GUEST (iPhone)            DOOR TERMINAL              QTPASS SERVER            PSP / CARRIER
      |                          |                          |                        |
      |-- open app, PIN -------->|                          |                        |
      |   shows code 482 913     |                          |                        |
      |   (HOTP, counter n)      |                          |                        |
      |                          |                          |                        |
      |== staff types 482913 ===>|-- POST /checkin -------->|                        |
      |   + member no. 4711      |   (member, code, event)  |-- verify HOTP window   |
      |                          |                          |-- debit wallet CHF 25  |
      |                          |<-- OK, name, photo, age -|                        |
      |<== wristband, entry =====|                          |                        |
      |                          |                          |                        |
      |          ... later: top-up ...                      |                        |
      |-- SMS "TOP 20" to short number ---------------------------------------------->|
      |                          |                          |<-- MO/DLR callback ----|
      |<-- SMS "CHF 20 credited, balance CHF 37" -----------|                        |
```

---

## Running the reference test

The only part that is executable as-is is the HOTP library (pure PHP, 5.2-compatible
syntax, also runs on current PHP):

```bash
php tests/hotp_rfc4226_test.php
```

---

## Kurzfassung (Deutsch)

QTPass war ein Konzept von QT Records (ca. 2006–2008): Eintritt und Getränke in
einem EDM-Club per Handy bezahlen — über ein Guthabenkonto, aufgeladen per
Kreditkarte oder Premium-SMS (Carrier Billing), Identifikation an der Türe per
iPhone-App mit Einmalcode oder per SMS. Das System wurde damals **nicht
umgesetzt** und bei QT Records eingestellt. Dieses Repository ist eine
**Rekonstruktion aus dem Jahr 2026**, strikt mit der Technik von 2008 gedacht,
und dokumentiert Idee, Prozesse, Architektur, Recht (Stand 2008) und einen
ersten Code-Entwurf.

---

## License & disclaimer

Documentation and draft code © QT Records / Gianni Alagna. Published for
documentation and portfolio purposes. Legal references describe the Swiss
framework **as of 2008** for historical context; they are not legal advice and
several of the cited acts have since been revised (e.g. FMG 2021, revDSG 2023).
"iPhone" is a trademark of Apple Inc.; "TWINT" is a trademark of TWINT AG — both
are mentioned only for historical comparison; no affiliation.
