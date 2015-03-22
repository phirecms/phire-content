--
-- Content Module SQLite Database for Phire CMS 2.0
--

--  --------------------------------------------------------

--
-- Set database encoding
--

PRAGMA encoding = "UTF-8";
PRAGMA foreign_keys = ON;

-- --------------------------------------------------------
--
-- Table structure for table "content_types"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]content_types" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar NOT NULL,
  "content_type" varchar NOT NULL,
  "open_authoring" integer NOT NULL,
  "order" integer,
  PRIMARY KEY ("id")
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]content_types', 50000);

-- --------------------------------------------------------

--
-- Table structure for table "content"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]content" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "type_id" integer NOT NULL,
  "parent_id" integer,
  "title" varchar NOT NULL,
  "uri" varchar NOT NULL,
  "slug" varchar,
  "status" integer NOT NULL,
  "publish" datetime,
  "expire" datetime,
  "created" datetime,
  "updated" datetime,
  "created_by" integer,
  "updated_by" integer,
  PRIMARY KEY ("id"),
  CONSTRAINT "fk_content_type" FOREIGN KEY ("type_id") REFERENCES "[{prefix}]content_types" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "fk_content_parent_id" FOREIGN KEY ("parent_id") REFERENCES "[{prefix}]content" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT "fk_created_by" FOREIGN KEY ("created_by") REFERENCES "[{prefix}]users" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT "fk_updated_by" FOREIGN KEY ("updated_by") REFERENCES "[{prefix}]users" ("id") ON DELETE SET NULL ON UPDATE CASCADE
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]content', 51000);
CREATE INDEX "content_type_id" ON "[{prefix}]content" ("type_id");
CREATE INDEX "content_title" ON "[{prefix}]content" ("title");
CREATE INDEX "content_uri" ON "[{prefix}]content" ("uri");
CREATE INDEX "content_slug" ON "[{prefix}]content" ("slug");
CREATE INDEX "content_publish" ON "[{prefix}]content" ("publish");
CREATE INDEX "content_expire" ON "[{prefix}]content" ("expire");
CREATE INDEX "content_status" ON "[{prefix}]content" ("status");

-- --------------------------------------------------------