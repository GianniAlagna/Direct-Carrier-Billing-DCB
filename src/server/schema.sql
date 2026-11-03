-- QTPass - database schema (MySQL 5.0, InnoDB, utf8)
-- All amounts in Rappen (integer). Ledger is append-only.

CREATE TABLE member (
  member_no      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  msisdn         VARCHAR(16)  NOT NULL,              -- E.164, +41...
  first_name     VARCHAR(64)  NOT NULL,
  last_name      VARCHAR(64)  NOT NULL,
  birth_date     DATE         NOT NULL,
  email          VARCHAR(128) NULL,
  status         ENUM('NEW','PENDING_ID','ACTIVE','BLOCKED','EXPIRED','CLOSED') NOT NULL DEFAULT 'NEW',
  photo_path     VARCHAR(255) NULL,
  id_checked_at  DATETIME     NULL,
  id_checked_by  INT UNSIGNED NULL,
  tc_version     VARCHAR(16)  NULL,
  tc_accepted_at DATETIME     NULL,
  marketing_optin TINYINT(1)  NOT NULL DEFAULT 0,
  verify_pin     CHAR(5)      NULL,
  verify_pin_expires DATETIME NULL,
  verify_tries   TINYINT      NOT NULL DEFAULT 0,
  created_at     DATETIME     NOT NULL,
  PRIMARY KEY (member_no),
  UNIQUE KEY uq_msisdn (msisdn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=4700;

CREATE TABLE device (
  device_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_no      INT UNSIGNED NOT NULL,
  kind           ENUM('IPHONE','SMS') NOT NULL,
  device_name    VARCHAR(64)  NULL,
  secret_enc     VARCHAR(128) NOT NULL,              -- AES, see lib/db.php
  counter        BIGINT UNSIGNED NOT NULL DEFAULT 0, -- last accepted counter
  failed_tries   TINYINT      NOT NULL DEFAULT 0,
  locked_until   DATETIME     NULL,
  activation_code CHAR(6)     NULL,
  activated_at   DATETIME     NULL,
  revoked_at     DATETIME     NULL,
  PRIMARY KEY (device_id),
  KEY ix_member (member_no),
  CONSTRAINT fk_device_member FOREIGN KEY (member_no) REFERENCES member (member_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE venue (
  venue_id   SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(64) NOT NULL,
  cache_salt CHAR(40)    NOT NULL,
  PRIMARY KEY (venue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE event (
  event_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_id        SMALLINT UNSIGNED NOT NULL,
  name            VARCHAR(128) NOT NULL,
  event_date      DATE         NOT NULL,
  door_fee_rappen INT          NOT NULL,
  min_age         TINYINT      NOT NULL DEFAULT 18,
  night_colour    CHAR(6)      NOT NULL DEFAULT 'FF0066', -- pass screen colour
  state           ENUM('PLANNED','OPEN','CLOSED','SETTLED') NOT NULL DEFAULT 'PLANNED',
  PRIMARY KEY (event_id),
  CONSTRAINT fk_event_venue FOREIGN KEY (venue_id) REFERENCES venue (venue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE staff (
  staff_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(64) NOT NULL,
  role        ENUM('DOOR','BAR','SECURITY','MANAGER','BACKOFFICE') NOT NULL,
  pin_hash    CHAR(64)    NOT NULL,   -- sha256(salt . pin)
  pin_salt    CHAR(16)    NOT NULL,
  active      TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ledger_entry (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_no     INT UNSIGNED NOT NULL,
  type          ENUM('TOPUP_CARD','TOPUP_SMS','TOPUP_CASH','BONUS',
                     'ENTRY','BAR','ENTRY_REVERSAL','BAR_REVERSAL',
                     'REFUND','CHARGEBACK','ADMIN_FEE') NOT NULL,
  amount_rappen INT          NOT NULL,          -- + credit, - debit
  event_id      INT UNSIGNED NULL,
  venue_id      SMALLINT UNSIGNED NULL,
  terminal_id   VARCHAR(16)  NULL,
  staff_id      INT UNSIGNED NULL,
  ref           VARCHAR(64)  NULL,              -- PSP refno, SMS msgid, venue seq
  reverses_id   BIGINT UNSIGNED NULL,
  created_at    DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ref (type, ref),                -- idempotency for callbacks/sync
  KEY ix_member (member_no, created_at),
  KEY ix_event (event_id, type),
  CONSTRAINT fk_ledger_member FOREIGN KEY (member_no) REFERENCES member (member_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Cache of SUM(ledger_entry.amount_rappen); updated in the same transaction,
-- verified nightly by automation/night_settlement.php
CREATE TABLE member_balance (
  member_no      INT UNSIGNED NOT NULL,
  balance_rappen INT          NOT NULL DEFAULT 0,
  updated_at     DATETIME     NOT NULL,
  PRIMARY KEY (member_no),
  CONSTRAINT fk_balance_member FOREIGN KEY (member_no) REFERENCES member (member_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE entitlement (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_no   INT UNSIGNED NOT NULL,
  event_id    INT UNSIGNED NOT NULL,
  kind        ENUM('TICKET','GUESTLIST','VIP') NOT NULL,
  issued_by   VARCHAR(64)  NULL,
  consumed_at DATETIME     NULL,
  PRIMARY KEY (id),
  KEY ix_member_event (member_no, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE attendance (
  member_no   INT UNSIGNED NOT NULL,
  event_id    INT UNSIGNED NOT NULL,
  checked_in_at DATETIME   NOT NULL,
  terminal_id VARCHAR(16)  NOT NULL,
  PRIMARY KEY (member_no, event_id)             -- one check-in per night
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE sms_message (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  msgid         VARCHAR(64)  NOT NULL,
  direction     ENUM('MO','MT') NOT NULL,
  msisdn        VARCHAR(16)  NOT NULL,
  short_no      VARCHAR(8)   NOT NULL,
  carrier       VARCHAR(16)  NULL,
  text          VARCHAR(160) NOT NULL,
  tariff_rappen INT          NOT NULL DEFAULT 0,
  dlr_status    ENUM('NONE','PENDING','DELIVERED','BILLED','FAILED') NOT NULL DEFAULT 'NONE',
  member_no     INT UNSIGNED NULL,
  created_at    DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_msgid (direction, msgid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE journal_sync (
  venue_id    SMALLINT UNSIGNED NOT NULL,
  venue_seq   INT UNSIGNED NOT NULL,
  received_at DATETIME     NOT NULL,
  PRIMARY KEY (venue_id, venue_seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
