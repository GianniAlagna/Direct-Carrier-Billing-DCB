-- QTPass venue node (Mac mini in the club office) - local MySQL 5.0
-- Holds a cache of the central data and an offline journal.
-- No HOTP secrets are stored here: only hashes of the next 20 codes.

CREATE TABLE member_cache (
  member_no      INT UNSIGNED NOT NULL,
  first_name     VARCHAR(64)  NOT NULL,
  birth_date     DATE         NOT NULL,
  status         VARCHAR(12)  NOT NULL,
  balance_rappen INT          NOT NULL,
  photo_file     VARCHAR(64)  NULL,        -- JPEG synced to /photos
  inside         TINYINT(1)   NOT NULL DEFAULT 0,
  failed_tries   TINYINT      NOT NULL DEFAULT 0,
  locked_until   DATETIME     NULL,
  synced_at      DATETIME     NOT NULL,
  PRIMARY KEY (member_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE code_cache (
  member_no  INT UNSIGNED    NOT NULL,
  counter    BIGINT UNSIGNED NOT NULL,
  device_id  INT UNSIGNED    NOT NULL,
  code_hash  CHAR(40)        NOT NULL,     -- sha1(member_no|code|venue_salt)
  used_at    DATETIME        NULL,
  PRIMARY KEY (member_no, device_id, counter),
  KEY ix_hash (code_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE entitlement_cache (
  member_no INT UNSIGNED NOT NULL,
  event_id  INT UNSIGNED NOT NULL,
  kind      VARCHAR(12)  NOT NULL,
  consumed  TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (member_no, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE current_event (
  event_id        INT UNSIGNED NOT NULL,
  name            VARCHAR(128) NOT NULL,
  event_date      DATE         NOT NULL,
  door_fee_rappen INT          NOT NULL,
  min_age         TINYINT      NOT NULL,
  night_colour    CHAR(6)      NOT NULL,
  PRIMARY KEY (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Offline journal; uploaded by automation/sync_venue.php, idempotent on seq
CREATE TABLE journal (
  seq           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_no     INT UNSIGNED NOT NULL,
  type          VARCHAR(16)  NOT NULL,     -- ENTRY, BAR, ENTRY_REVERSAL ...
  amount_rappen INT          NOT NULL,
  event_id      INT UNSIGNED NOT NULL,
  terminal_id   VARCHAR(16)  NOT NULL,
  staff_id      INT UNSIGNED NOT NULL,
  device_id     INT UNSIGNED NULL,
  counter_used  BIGINT UNSIGNED NULL,      -- lets the server advance its counter
  decision      VARCHAR(8)   NOT NULL,
  created_at    DATETIME     NOT NULL,
  uploaded_at   DATETIME     NULL,
  PRIMARY KEY (seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE settings (
  name  VARCHAR(32)  NOT NULL,
  value VARCHAR(255) NOT NULL,
  PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- settings: venue_id, venue_salt, last_pull_seq
