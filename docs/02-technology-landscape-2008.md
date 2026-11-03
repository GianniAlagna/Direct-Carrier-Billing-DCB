# 02 — Technology Landscape 2008

This chapter fixes the **technical boundary** of the design. Anything not
available (or not realistic for a club in Switzerland) in 2008 is excluded.

## 1. Handsets

| Device / platform | Year | Relevant capabilities | Constraints |
|---|---|---|---|
| iPhone (1st gen, "2G") | 2007 (US), not officially sold in CH | Safari, multi-touch, EDGE, Wi-Fi, 2 MP camera | No SDK until iPhone OS 2.0; no 3G; no GPS |
| **iPhone 3G** | July 2008 (CH: Swisscom, Orange) | **iPhone OS 2.0 SDK**, App Store, 3G/UMTS, A-GPS, Wi-Fi, Keychain, CommonCrypto | 2 MP fixed-focus camera (no reliable barcode scanning), **no push notifications** (APNs only with iPhone OS 3.0, June 2009), no background apps, no copy & paste (3.0), 128 MB RAM |
| iPod touch | 2007 | Same as iPhone via Wi-Fi only | No SMS, no mobile data |
| Nokia S60 (N95, E-series) | 2007–2008 | Symbian, J2ME, good camera, some QR readers | Fragmented UI, app installation unusual for mainstream users |
| Feature phones (Sony Ericsson, Samsung, Motorola) | — | SMS, WAP 2.0, J2ME MIDP 2.0 | Tiny screens, inconsistent J2ME |
| BlackBerry | 2008 | Push e-mail, J2ME | Business audience, not club crowd |
| Android (HTC Dream / T-Mobile G1) | Oct 2008 (US) | — | Not available in CH in 2008 → ignored |

**Consequence:** two channels — native iPhone app (premium) and **SMS** (universal).
A Safari web app covers iPhone users before the App Store approval and iPod touch
users on club Wi-Fi.

## 2. Connectivity

| Technology | Status 2008 | Use in QTPass |
|---|---|---|
| GSM / SMS | Universal | Registration, top-up, door code fallback, receipts |
| EDGE / GPRS | Universal, slow (~100–200 kbit/s) | App sync |
| UMTS / HSDPA (3G) | Major Swiss cities | App sync |
| Wi-Fi 802.11b/g | Venue-controlled | Door terminal LAN, optional guest Wi-Fi for web app |
| Indoor coverage in a basement club | **Poor / none** | → app must generate codes **offline**; door terminal has wired uplink |
| Bluetooth 2.0 | On phones | Not used (pairing UX unusable at a door; no BLE until 2010+) |
| NFC | Pilot handsets only (Nokia 6131 NFC) | **Excluded** |

## 3. Payment rails available in Switzerland 2008

| Rail | Operator examples | Fit | Notes |
|---|---|---|---|
| Premium SMS / Mehrwertdienst (MT-billed) | Swisscom, Sunrise, Orange via aggregators | Top-up CHF 5–20 | Universal, instant, but carrier + aggregator keep a large share (see chapter 07); amount caps per message |
| Carrier billing for WAP/content | Carrier portals | Limited | Content-only rules, not for physical goods/services in all carrier contracts → must be validated with each carrier |
| Credit card online (e-commerce) | Swiss PSPs (e.g. Datatrans, Saferpay/Telekurs) | Top-up CHF 20–200 | 3-D Secure rolling out; fees ~2–3.5 % + fixed |
| PostFinance Card / e-finance | PostFinance | Top-up | Popular in CH; via PSP |
| Maestro / EC at POS | Terminal | Existing | Slow at a door, the problem we solve |
| Bank transfer (ESR/BVR slip) | Banks | Corporate/VIP pre-pay | Slow, manual reconciliation |
| Cash at "top-up counter" in venue | Club | Always | Converts cash once, then fast lanes |

## 4. Identification and code technology

| Option | 2008 reality | Decision |
|---|---|---|
| QR code on phone screen, read by scanner | 2D imagers existed (Symbol/Motorola, Honeywell) but reading **from LCD screens** was unreliable and expensive | Phase 2 option |
| QR scanned **by** the phone camera | iPhone 3G fixed-focus → poor | Excluded |
| Numeric one-time code typed by door staff | Works everywhere, 2–3 s typing | **Primary** |
| HOTP (RFC 4226, Dec 2005) | Standard, implementable with HMAC-SHA1 | **Chosen algorithm** |
| TOTP (time-based) | Only an IETF draft in 2008 (RFC 6238 came 2011); phone clocks drift | Rejected; HOTP with look-ahead window used |
| Magnetic/RFID member cards | Mature | Optional backup token (Mifare Classic, later known as broken) |
| Photo check | Door terminal shows member photo from server/cache | **Yes** — anti-sharing |

## 5. iPhone OS 2.x development stack (2008)

- Mac OS X 10.5 Leopard, **Xcode 3.1**, iPhone SDK 2.0/2.1/2.2
- **Objective-C 2.0**, Cocoa Touch / UIKit, Foundation
- **Manual memory management** (retain / release / autorelease). No ARC
  (2011), no blocks on iOS (iOS 4, 2010), no GCD (iOS 4), no storyboards (iOS 5)
- Networking: `NSURLConnection` (async delegate)
- Persistence: `NSUserDefaults`, plist files, SQLite 3 (C API); Core Data only
  from iPhone OS 3.0
- Security: Keychain Services (`SecItemAdd`, `SecItemCopyMatching`),
  CommonCrypto (`CCHmac`)
- JSON: no system parser → bundled third-party parser (e.g. json-framework /
  SBJSON) or **plist over HTTP** (chosen: plist is native)
- Distribution: Apple Developer Program (USD 99/year), App Store review, ad-hoc
  for staff devices (100 device limit)

## 6. Web app on iPhone (2007/2008)

- Mobile Safari, `<meta name="viewport">`, `apple-touch-icon`
- `apple-mobile-web-app-capable` for full-screen home-screen web apps
  (iPhone OS 2.1+)
- No offline storage before HTML5 client-side DB / application cache in later
  2.x releases → web app needs connectivity; therefore only a secondary channel
- JavaScript libraries of the time: Prototype, jQuery 1.2, iUI (iPhone-style UI)

## 7. Server side

| Layer | 2008 choice |
|---|---|
| OS | Debian 4.0 "Etch" / Ubuntu 8.04 LTS |
| Web | Apache 2.2 + mod_ssl |
| Language | **PHP 5.2** (no namespaces, no closures — those came with 5.3 in June 2009) |
| DB | **MySQL 5.0**, InnoDB for transactions |
| Data format | XML, plist, JSON (`json_encode` since PHP 5.2) |
| SMS | HTTP API of a Swiss SMS aggregator (MO callback, MT send, DLR) |
| Hosting | Swiss data centre (colocation or managed server), daily backup to tape/remote |
| SSL | Commercial certificate (Verisign/Thawte/SwissSign) |
| Versioning | Subversion (Git was 3 years old; GitHub launched April 2008) |

## 8. What we deliberately did not have

- Push notifications → receipts go via SMS
- Background processing on the phone → code generation is on-demand
- Location services at the door → not needed
- Social login → own account with mobile number as primary key
- Cloud (AWS EC2 existed since 2006, but no Swiss region; data residency) → Swiss hosting
