--
-- Content Module PostgreSQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------
--
-- Table structure for table "content_types"
--

CREATE SEQUENCE type_id_seq START 5001;

CREATE TABLE IF NOT EXISTS "[{prefix}]content_types" (
  "id" integer NOT NULL DEFAULT nextval('type_id_seq'),
  "name" varchar(255) NOT NULL,
  "content_type" varchar(255) NOT NULL,
  "open_authoring" integer NOT NULL,
  "order" integer,
  PRIMARY KEY ("id")
) ;

ALTER SEQUENCE type_id_seq OWNED BY "[{prefix}]content_types"."id";

-- --------------------------------------------------------


--
-- Table structure for table "content"
--

CREATE SEQUENCE content_id_seq START 601;

CREATE TABLE IF NOT EXISTS "[{prefix}]content" (
  "id" integer NOT NULL DEFAULT nextval('content_id_seq'),
  "type_id" integer NOT NULL,
  "parent_id" integer,
  "title" varchar(255) NOT NULL,
  "uri" varchar(255) NOT NULL,
  "slug" varchar(255),
  "status" integer NOT NULL,
  "publish" timestamp,
  "expire" timestamp,
  "created" timestamp,
  "updated" timestamp,
  "created_by" integer,
  "updated_by" integer,
  PRIMARY KEY ("id"),
  CONSTRAINT "fk_content_type" FOREIGN KEY ("type_id") REFERENCES "[{prefix}]content_types" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "fk_content_parent_id" FOREIGN KEY ("parent_id") REFERENCES "[{prefix}]content" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT "fk_created_by" FOREIGN KEY ("created_by") REFERENCES "[{prefix}]users" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT "fk_updated_by" FOREIGN KEY ("updated_by") REFERENCES "[{prefix}]users" ("id") ON DELETE SET NULL ON UPDATE CASCADE
) ;

ALTER SEQUENCE content_id_seq OWNED BY "[{prefix}]content"."id";
CREATE INDEX "content_type_id" ON "[{prefix}]content" ("type_id");
CREATE INDEX "content_title" ON "[{prefix}]content" ("title");
CREATE INDEX "content_uri" ON "[{prefix}]content" ("uri");
CREATE INDEX "content_slug" ON "[{prefix}]content" ("slug");
CREATE INDEX "content_publish" ON "[{prefix}]content" ("publish");
CREATE INDEX "content_expire" ON "[{prefix}]content" ("expire");
CREATE INDEX "content_status" ON "[{prefix}]content" ("status");
-- --------------------------------------------------------