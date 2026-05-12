-- Archive support migration
-- Run each ALTER TABLE once to add soft-delete columns.
-- If columns already exist, remove the relevant ALTER statement before rerunning.

ALTER TABLE baptismal_records
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER entry_no,
    ADD COLUMN archived_at DATETIME NULL AFTER is_archived;

ALTER TABLE liberty_records
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER year_of_our_lord,
    ADD COLUMN archived_at DATETIME NULL AFTER is_archived;

ALTER TABLE confirmation_records
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER entry_no,
    ADD COLUMN archived_at DATETIME NULL AFTER is_archived;

ALTER TABLE marriage_permits
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER year_of_lord,
    ADD COLUMN archived_at DATETIME NULL AFTER is_archived;

ALTER TABLE death_certificates
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER entry_no,
    ADD COLUMN archived_at DATETIME NULL AFTER is_archived;

ALTER TABLE events
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER updated_at,
    ADD COLUMN archived_at DATETIME NULL AFTER is_archived;
