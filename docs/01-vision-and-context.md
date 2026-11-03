# 01 — Vision and Context

## 1. Executive summary

QTPass turns the guest's mobile phone into **ticket, identity and wallet** for
an EDM club. Goal: remove cash from the door and bar, shorten queues, stop
cash leakage, and give the promoter real-time data — using only what existed
in 2008: SMS, carrier billing, the brand-new iPhone and a LAMP server.

## 2. Situation in 2008

### 2.1 The club problem

A Swiss EDM club night (800–1,500 guests, 22:00–05:00) in 2008 ran almost
entirely on cash:

| Pain point | Effect |
|---|---|
| Door queue peaks 23:30–01:00 | 20–40 min waiting time; guests leave for the competitor |
| Cash at the door (CHF 20–35 entry) | Change management, counterfeit risk, robbery risk at closing |
| Cash at the bar | Slow service, staff leakage ("Schwund") typically 2–5 % of bar revenue |
| Guest lists / VIP lists on paper | Abuse, no audit trail |
| No guest data | Promoter cannot measure which DJ / which flyer campaign brings whom |
| Card terminals (Maestro/EC, credit card) | Slow (PIN + dial-up/GPRS authorisation, 15–40 s), fees, not accepted for small amounts |
| Age control (16+/18+) | Manual ID check every night, every guest |

### 2.2 The technology moment

- **9 January 2007**: iPhone announced. **29 June 2007**: US launch.
- **6 March 2008**: iPhone SDK announced.
- **10/11 July 2008**: iPhone 3G and the **App Store** launch; iPhone 3G
  available in Switzerland via Swisscom and Orange.
- SMS was universal: every guest, every phone, every carrier.
- Swiss carriers billed **premium SMS / value-added services** (Mehrwertdienste)
  directly on the phone bill or prepaid balance — Direct Carrier Billing before
  the term was common.

The club audience (18–35, early adopters, music/tech culture) was exactly the
crowd that bought the first iPhones. An EDM label and club brand had the
credibility to introduce a new behaviour.

## 3. Vision

> *"Your phone is your ticket, your ID and your money at QT. Walk in. Dance.
> Pay with your phone."*

### 3.1 Objectives (measurable)

| # | Objective | Target |
|---|---|---|
| O1 | Door throughput | ≥ 8 guests/min per door lane (vs. ~3–4 cash) |
| O2 | Cash share at door after 6 months | ≤ 40 % |
| O3 | Bar leakage | −50 % on QTPass transactions |
| O4 | Registered members after season 1 | 5,000 |
| O5 | Data | Per-event attendance, arrival curve, repeat rate, top-up behaviour |

### 3.2 Design principles

1. **Works on every phone.** iPhone gets the premium experience; every other
   phone works via SMS. No guest is excluded.
2. **Works without network in the basement.** Door verification must be
   possible with an offline cache — clubs are concrete bunkers with no signal.
3. **Prepaid, not credit.** The guest spends only what was topped up. No
   debt collection, no credit risk for the club.
4. **Door staff stays in control.** The system supports the bouncer, it does
   not replace face control or age checks.
5. **Cash remains possible.** QTPass is a fast lane, not a gate.

## 4. Scope

| In scope | Out of scope (2008) |
|---|---|
| Member registration via web, iPhone, SMS | NFC / contactless (no consumer handsets in CH) |
| Wallet top-up via credit card (PSP) and premium SMS | Peer-to-peer payments between guests |
| Door check-in + entry fee debit | Use outside QT venues |
| Bar payment (same mechanism) | Interest, credit lines, overdraft |
| Guest list / VIP / pre-sale tickets as wallet items | Biometric identification |
| Promoter reporting | Integration in third-party ticketing |

## 5. Stakeholders

| Stakeholder | Interest |
|---|---|
| Guest | Faster entry, no cash, guest-list perks, bonus credit |
| Club operator (QT) | Throughput, cash security, data, lower leakage |
| Promoters / DJs | Attendance data, pre-sales |
| Door & bar staff | Simple tool, no change counting |
| Carriers (Swisscom, Sunrise, Orange) | Value-added service revenue share |
| SMS aggregator | Transaction volume |
| PSP (e.g. Datatrans, Saferpay) | Card acquiring volume |
| Authorities (BAKOM, police, canton) | Compliance with price disclosure, youth protection, AML |

## 6. Why it mattered

In 2008 there was **no** consumer mobile payment in Switzerland that worked at
a door: paybox (Austria/Germany) had retreated, PayPal Mobile was US-centric,
NFC trials were pilots only. QTPass would have been a closed-loop, single-venue
wallet — technically modest, but behaviourally pioneering: teaching a whole
club audience to pay with the phone six years before TWINT.
