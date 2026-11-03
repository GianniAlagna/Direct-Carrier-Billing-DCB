# 08 — Roadmap, Discontinuation and Legacy

## 1. Planned roadmap (as conceived for 2008/2009)

| Phase | Period (planned) | Deliverables | Exit criterion |
|---|---|---|---|
| 0 — Concept | 2006–2008 | Idea, process construct, carrier/PSP talks | Go/no-go by QT management |
| 1 — Pilot "Door only" | Q4 2008 | Registration (web + SMS), card + cash top-up, door check-in via SMS code, door terminals, venue node | 1,000 members, door ≤ 10 s/guest |
| 2 — iPhone app | Q1 2009 | Native app (offline HOTP), App Store release | 30 % of QTPass check-ins via app |
| 3 — Bar | Q2 2009 | Bar terminals, night tab, age flag | Leakage reduction measurable |
| 4 — Premium SMS top-up | Q2 2009 | Carrier approvals, short numbers, DLR booking | Approval by all 3 carriers |
| 5 — Multi-venue / festivals | 2010 | Second venue, festival mode (mobile node) | Legal clearance for wider use |
| 6 — 2D codes | 2010+ | QR on screen + 2D imager at door (when reliable) | Scan rate ≥ 98 % |

## 2. Status

**The project did not go beyond phase 0.** No production system was built,
no guest ever paid with QTPass. The concept was discontinued at QT Records.

## 3. Why it stopped (reconstructed assessment)

The following factors are a structured reconstruction of the obstacles a
project of this kind faced in 2008. They are the analytical view from today
and should be read as such.

| Factor | Effect |
|---|---|
| **Scale** | Fixed costs (development, legal, carriers) were high for a single venue; payback ~2 seasons |
| **Carrier gatekeeping** | Premium SMS terms were built for ringtones and content; use for venue credit was uncertain and slow to approve |
| **SMS economics** | 35–50 % channel cost made carrier billing unattractive beyond onboarding |
| **Smartphone penetration** | iPhone 3G had just launched; most guests still had feature phones → the SMS path (slower than the app) would have dominated |
| **Regulatory uncertainty** | Wallet classification (deposits/AML) required a legal opinion and possibly supervisory contact; FINMA was only being formed (1 Jan 2009) |
| **Door culture** | Door and bar staff had to change routines at peak hours; risk of slowing down the most critical moment of the night |
| **Focus** | A record label's core business is music; payment infrastructure is a different company |

## 4. What the market did later (for context)

| Year | Development |
|---|---|
| 2009 | iPhone OS 3.0: push notifications, in-app purchase |
| 2010 | Festival cashless wristbands (RFID) spread in Europe |
| 2011 | Google Wallet (NFC) in the US; RFC 6238 (TOTP) published |
| 2012 | Passbook (iOS 6) — boarding passes and tickets on the phone, QR on screen as standard |
| 2014 | Apple Pay (US); **TWINT** launched by PostFinance in Switzerland |
| 2015 | Paymit by Swiss banks/SIX |
| 2016 | TWINT and Paymit merge — QR-based mobile payment becomes Swiss standard |
| 2020+ | Contactless and mobile payment dominant at Swiss venues |

QTPass anticipated several of these elements — phone as wallet, one-time codes
on screen, prepaid closed loop for venues, SMS fallback — in a **2008-sized**
form: no NFC, no QR scanning, no push, only SMS, a fixed-focus camera and HMAC.

## 5. Lessons learned

1. **Payment is a network business.** One venue cannot carry the fixed costs;
   the model needs many acceptance points (what TWINT solved via banks).
2. **The channel owner sets the economics.** Carrier billing gave reach but
   took the margin.
3. **Identity is the hard part, not the code.** The physical ID check and the
   photo at the door were the real security anchor — still true for age
   verification today.
4. **Offline-first was right.** Basement clubs still have poor coverage;
   modern systems still cache and sync.
5. **Start with the door, not the bar.** The door is one controlled point
   with one decision; the bar has many points and much higher frequency.
