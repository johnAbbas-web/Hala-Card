-- Sierah Expo 2026 reservation tables.
-- Created automatically on first API call, or run manually in phpMyAdmin.

CREATE TABLE IF NOT EXISTS expo_registrations (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    first_name           VARCHAR(60)  NOT NULL,
    email                VARCHAR(254) NOT NULL,
    email_key            VARCHAR(254) NOT NULL UNIQUE,
    country              VARCHAR(80)  NULL,
    status               VARCHAR(16)  NOT NULL DEFAULT 'pending',
    slot_number          INT          NULL UNIQUE,
    reservation_code     VARCHAR(20)  NULL UNIQUE,
    share_id             VARCHAR(24)  NOT NULL UNIQUE,
    link_token_hash      CHAR(64)     NULL UNIQUE,
    link_expires_at      DATETIME     NULL,
    link_sent_at         DATETIME     NULL,
    verified_at          DATETIME     NULL,
    terms_version        VARCHAR(20)  NOT NULL,
    terms_accepted_at    DATETIME     NOT NULL,
    marketing_consent    TINYINT      NOT NULL DEFAULT 0,
    marketing_consent_at DATETIME     NULL,
    invited_at           DATETIME     NULL,
    redeemed_at          DATETIME     NULL,
    redeemed_ref         VARCHAR(100) NULL,
    created_at           DATETIME     NOT NULL,
    updated_at           DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expo_devices (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT      NOT NULL,
    token_hash      CHAR(64) NOT NULL UNIQUE,
    created_at      DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expo_counter (
    id    INT PRIMARY KEY,
    taken INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO expo_counter (id, taken) VALUES (1, 0);

CREATE TABLE IF NOT EXISTS expo_rate (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    bucket     VARCHAR(40) NOT NULL,
    created_at DATETIME    NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_expo_rate ON expo_rate (bucket, created_at);
CREATE INDEX idx_expo_dev_reg ON expo_devices (registration_id);
CREATE INDEX idx_expo_status ON expo_registrations (status)
