-- 
-- SQL for Drafts Extension
-- 
-- Table for storing working changes to pages that
-- users have yet to commit.
-- Postgres version
-- See Drafts.sql for notes on each column
BEGIN;

CREATE SEQUENCE drafts_approve_id_seq;

CREATE TABLE drafts_approve (
  draft_id INTEGER NOT NULL DEFAULT nextval('drafts_approve_id_seq'),
  draft_token TEXT,
  draft_user INTEGER NOT NULL DEFAULT 0,
  draft_page INTEGER NOT NULL default 0,
  draft_namespace INTEGER NOT NULL,
  draft_title TEXT NOT NULL DEFAULT '',
  draft_section SMALLINT,
  draft_starttime TIMESTAMPTZ,
  draft_edittime  TIMESTAMPTZ,
  draft_savetime  TIMESTAMPTZ,
  draft_scrolltop INTEGER,
  draft_text TEXT NOT NULL,
  draft_summary TEXT,
  draft_minoredit SMALLINT,
  draft_status TEXT NOT NULL DEFAULT 'editing',
  draft_refuse_reason TEXT NULL,
  draft_refuse_user INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (draft_id)
);

-- Todo: determine which of these are really needed
CREATE INDEX draft_approve_user_savetime ON drafts_approve(draft_user, draft_savetime);
CREATE INDEX draft_approve_user_page_savetime ON drafts_approve(draft_user, draft_page, draft_namespace, draft_title, draft_savetime);
CREATE INDEX draft_approve_savetime ON drafts_approve(draft_savetime);
CREATE INDEX draft_approve_page ON drafts_approve(draft_page);

COMMIT;
