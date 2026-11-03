# 04 — System Architecture (2008 technology)

## 1. Overview

```
                          INTERNET                                    CLUB (basement)
 ┌──────────────┐                                            ┌─────────────────────────────────┐
 │ iPhone app   │──HTTPS (3G/EDGE, outside the club)──┐      │  Office                         │
 │ (S1)         │                                     │      │  ┌───────────────────────────┐  │
 └──────────────┘                                     v      │  │ Venue node S4 (Mac mini)  │  │
 ┌──────────────┐  SMS   ┌───────────────┐  HTTP  ┌──────────┴┐ │ Apache+PHP+MySQL (local)  │  │
 │ Any phone    │<──────>│ SMS aggregator│<──────>│ Central   │ │ cache + offline journal   │  │
 │ (S2)         │        │ + carriers S7 │        │ server S3 │<┤ sync every 60 s (DSL+VPN) │  │
 └──────────────┘        └───────────────┘        │ Swiss DC  │ └─────────────┬─────────────┘  │
 ┌──────────────┐                                 │ LAMP      │               │ wired LAN       │
 │ Web browser  │──HTTPS──────────────────────────>│ MySQL 5.0 │    ┌──────────┼──────────┐      │
 └──────────────┘                                 └─────┬─────┘    v          v          v      │
                         ┌──────────────┐               │      Door 1      Door 2      Bar 1-4   │
                         │ PSP (cards)  │<──HTTPS───────┘      MacBook     MacBook     Thin      │
                         │ S6           │                      + webcam    + keypad    clients   │
                         └──────────────┘                      (S5)                              │
                                                             └─────────────────────────────────┘
```

## 2. Components

| Component | Hardware / software 2008 | Responsibility |
|---|---|---|
| **S1 iPhone app** | iPhone 3G, iPhone OS 2.1+, Objective-C | Activation, HOTP code display, balance view, top-up launcher |
| **S1b Web app** | Mobile Safari, PHP-rendered pages | Registration, balance, top-up, history (online only) |
| **S2 SMS** | Any GSM phone | JOIN, PIN, IN, TOP, BAL, STOP, REFUND |
| **S3 Central server** | 1U server (e.g. Dell PowerEdge / HP ProLiant), Debian 4.0, Apache 2.2, PHP 5.2, MySQL 5.0 InnoDB; second server as MySQL replication slave | Master ledger, member DB, APIs, SMS logic, PSP callbacks, reporting |
| **S4 Venue node** | Mac mini (Intel Core 2 Duo), Mac OS X 10.5 Server or MAMP, UPS | Local cache + journal, serves terminal UI, offline verification |
| **S5 Door terminals** | MacBook with built-in iSight (registration photos), USB numeric keypad, Safari full-screen | Staff UI |
| **S5 Bar terminals** | Touchscreen thin clients or old MacBooks with Safari | Staff UI, product buttons |
| **Network** | Business DSL + backup UMTS USB modem, IPsec VPN (router) | S4 ↔ S3 |
| **S6 PSP** | Swiss PSP hosted payment page | Card top-ups (no card data at QT → reduced PCI DSS scope) |
| **S7 SMS aggregator** | Swiss aggregator with connections to Swisscom/Sunrise/Orange | MO/MT, premium billing, DLR |

## 3. Interfaces

### 3.1 iPhone app ↔ central server (HTTPS, plist)

| Endpoint | Method | Request | Response (plist dict) |
|---|---|---|---|
| `/api/activate.php` | POST | `msisdn` | `status` |
| `/api/activate_confirm.php` | POST | `msisdn`, `code`, `device` | `member_no`, `secret` (Base32), `counter` |
| `/api/balance.php` | POST | `member_no`, `otp` | `balance`, `entitlements`, `last_tx` |
| `/api/topup_start.php` | POST | `member_no`, `amount` | `psp_url` (opened in Safari) |

Plist was chosen over JSON because iPhone OS 2.x parses it natively
(`NSPropertyListSerialization`) — no third-party parser.

### 3.2 Terminals ↔ venue node (HTTP on isolated LAN)

| Endpoint | Purpose |
|---|---|
| `/door/checkin.php` | Member no. + code → decision (GREEN/ORANGE/RED) |
| `/bar/charge.php` | Member no. + code + items → decision |
| `/door/register_photo.php` | Upload photo from iSight (first visit) |
| `/manager/reverse.php` | Reversal with manager PIN |

### 3.3 Venue node ↔ central server (HTTPS over VPN)

| Endpoint | Direction | Content |
|---|---|---|
| `/sync/pull.php?since=<seq>` | S3 → S4 | Changed members, balances, status, code hashes, photos |
| `/sync/push.php` | S4 → S3 | Journal rows with venue sequence numbers (idempotent) |

### 3.4 SMS aggregator ↔ central server

| Endpoint | Direction | Content |
|---|---|---|
| `/sms/inbound.php` | Aggregator → S3 | MO: `from`, `to` (short no.), `text`, `msgid`, `carrier` |
| `/sms/dlr.php` | Aggregator → S3 | DLR: `msgid`, `status`, `billed` |
| Aggregator HTTP send API | S3 → Aggregator | MT: `to`, `text`, `tariff` |

### 3.5 PSP ↔ central server

| Endpoint | Content |
|---|---|
| Hosted page redirect | `amount`, `currency=CHF`, `refno`, `sign` |
| `/psp/notify.php` | Server-to-server confirmation, signature check, idempotent on `refno` |

## 4. Data model (core)

```
 member 1───* device            (one active device)
 member 1───* ledger_entry      (append-only)
 member 1───* entitlement       (tickets, guest list)
 event  1───* entitlement
 event  1───* ledger_entry
 member 1───* sms_message
 venue  1───* terminal
 staff  1───* ledger_entry      (who booked it)
```

| Table | Key fields |
|---|---|
| `member` | `member_no`, `msisdn`, `first_name`, `last_name`, `birth_date`, `status`, `photo_path`, `id_checked_at`, `id_checked_by` |
| `device` | `member_no`, `secret_enc`, `counter`, `activated_at`, `revoked_at` |
| `ledger_entry` | `id`, `member_no`, `type`, `amount_rappen` (integer, never float), `event_id`, `venue_id`, `terminal_id`, `staff_id`, `ref`, `created_at`, `reverses_id` |
| `entitlement` | `member_no`, `event_id`, `kind`, `consumed_at` |
| `event` | `event_id`, `name`, `date`, `door_fee_rappen`, `min_age`, `state` |
| `sms_message` | `msgid`, `direction`, `msisdn`, `text`, `tariff_rappen`, `dlr_status` |
| `journal_sync` | `venue_id`, `venue_seq`, `received_at` (idempotency) |

Full DDL: [`src/server/schema.sql`](../src/server/schema.sql).

**Balance** = `SUM(amount_rappen)` over a member's ledger entries. A
`member_balance` table is maintained as a cache inside the same InnoDB
transaction and is checked nightly against the sum.

## 5. HOTP code generation

```
 K       = 20-byte secret per device
 c       = 8-byte big-endian counter
 HS      = HMAC-SHA1(K, c)
 offset  = HS[19] & 0x0F
 P       = (HS[offset..offset+3]) & 0x7FFFFFFF
 code    = P mod 10^6              -> 6 digits
```

Server accepts `c_last+1 … c_last+10` (look-ahead window); on success
`c_last := c_used`. A code is therefore usable exactly once.

## 6. Deployment and operations

| Topic | Approach |
|---|---|
| Environments | `dev` (developer Mac + MAMP), `staging` (VM), `prod` |
| Release | Subversion tag → `rsync` to servers; DB migrations as numbered SQL files |
| Backups | MySQL dump nightly (05:45, after settlement) + binary logs; encrypted copy offsite; 30-day retention; monthly restore test |
| Replication | MySQL master → slave in second rack; manual failover runbook |
| Monitoring | Nagios checks: HTTP, MySQL, SMS aggregator API reachability, VPN tunnel, disk |
| Time | NTP on all servers and venue node (journal ordering) |
| Logs | syslog, logrotate; transaction log retention per Swiss accounting rules (10 years for ledger records) |

## 7. Capacity estimate (one club, peak night)

| Metric | Value |
|---|---|
| Guests | 1,500 |
| Check-ins in peak hour | ~700 → ~0.2 req/s |
| Bar transactions | ~4,000/night, peak ~30/min |
| SMS (receipts + codes) | ~2,000/night |

A single 2008 LAMP server handles this with large headroom; the bottleneck
is human (typing, face check), not IT.
