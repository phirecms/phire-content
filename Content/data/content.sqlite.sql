--
-- Content Module SQLite Database
--

-- --------------------------------------------------------

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
  "uri" integer NOT NULL,
  "order" integer NOT NULL,
  UNIQUE ("id")
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]content_types', 5000);
CREATE INDEX "content_type_name" ON "[{prefix}]content_types" ("name");

--
-- Dumping data for table "content_types"
--

INSERT INTO "[{prefix}]content_types" ("id", "name", "uri", "order") VALUES (5001, 'Page', 1, 1);
INSERT INTO "[{prefix}]content_types" ("id", "name", "uri", "order") VALUES (5002, 'Media', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table "content"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]content" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "site_id" integer,
  "type_id" integer,
  "parent_id" integer,
  "template" varchar,
  "title" varchar NOT NULL,
  "uri" varchar NOT NULL,
  "slug" varchar NOT NULL,
  "feed" integer,
  "force_ssl" integer,
  "status" integer,
  "roles" text,
  "created" datetime,
  "updated" datetime,
  "publish" datetime,
  "expire" datetime,
  "created_by" integer,
  "updated_by" integer,
  UNIQUE ("id"),
  CONSTRAINT "fk_content_parent_id" FOREIGN KEY ("parent_id") REFERENCES "[{prefix}]content" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "fk_content_type" FOREIGN KEY ("type_id") REFERENCES "[{prefix}]content_types" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "fk_created_by" FOREIGN KEY ("created_by") REFERENCES "[{prefix}]users" ("id") ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT "fk_updated_by" FOREIGN KEY ("updated_by") REFERENCES "[{prefix}]users" ("id") ON DELETE SET NULL ON UPDATE CASCADE
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]content', 6000);
CREATE INDEX "content_site_id" ON "[{prefix}]content" ("site_id");
CREATE INDEX "content_type_id" ON "[{prefix}]content" ("type_id");
CREATE INDEX "content_parent_id" ON "[{prefix}]content" ("parent_id");
CREATE INDEX "content_template" ON "[{prefix}]content" ("template");
CREATE INDEX "content_title" ON "[{prefix}]content" ("title");
CREATE INDEX "content_uri" ON "[{prefix}]content" ("uri");
CREATE INDEX "content_slug" ON "[{prefix}]content" ("slug");
CREATE INDEX "content_force_ssl" ON "[{prefix}]content" ("force_ssl");
CREATE INDEX "content_status" ON "[{prefix}]content" ("status");
CREATE INDEX "content_publish" ON "[{prefix}]content" ("publish");
CREATE INDEX "content_expire" ON "[{prefix}]content" ("expire");

--
-- Dumping data for table "content"
--

INSERT INTO "[{prefix}]content" ("id", "site_id", "type_id", "parent_id", "template", "title", "uri", "slug", "feed", "force_ssl", "status") VALUES (6001, 0, 5001, NULL, 'index.phtml', 'Home', '/', '', 1, 0, 2);
INSERT INTO "[{prefix}]content" ("id", "site_id", "type_id", "parent_id", "template", "title", "uri", "slug", "feed", "force_ssl", "status") VALUES (6002, 0, 5001, NULL, 'sub.phtml', 'About', '/about', 'about', 1, 0, 2);
INSERT INTO "[{prefix}]content" ("id", "site_id", "type_id", "parent_id", "template", "title", "uri", "slug", "feed", "force_ssl", "status") VALUES (6003, 0, 5001, 6002, 'sub.phtml', 'Sample Page', '/about/sample-page', 'sample-page', 1, 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table "navigation"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]navigation" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "navigation" varchar NOT NULL,
  "spaces" integer,
  "top_node" varchar,
  "top_id" varchar,
  "top_class" varchar,
  "top_attributes" varchar,
  "parent_node" varchar,
  "parent_id" varchar,
  "parent_class" varchar,
  "parent_attributes" varchar,
  "child_node" varchar,
  "child_id" varchar,
  "child_class" varchar,
  "child_attributes" varchar,
  "on_class" varchar,
  "off_class" varchar,
  UNIQUE ("id")
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]navigation', 7000);
CREATE INDEX "nav_navigation" ON "[{prefix}]navigation" ("navigation");

--
-- Dumping data for table "navigation"
--

INSERT INTO "[{prefix}]navigation" ("id", "navigation", "spaces", "top_node", "top_id") VALUES (7001, 'Main Nav', 4, 'ul', 'main-nav');

-- --------------------------------------------------------

--
-- Table structure for table "categories"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]categories" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "parent_id" integer,
  "title" varchar NOT NULL,
  "uri" varchar NOT NULL,
  "slug" varchar NOT NULL,
  "order" integer NOT NULL,
  "total" integer NOT NULL,
  UNIQUE ("id"),
  CONSTRAINT "fk_category_parent_id" FOREIGN KEY ("parent_id") REFERENCES "[{prefix}]categories" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]categories', 8000);
CREATE INDEX "category_parent_id" ON "[{prefix}]categories" ("parent_id");
CREATE INDEX "category_title" ON "[{prefix}]categories" ("title");
CREATE INDEX "category_uri" ON "[{prefix}]categories" ("uri");
CREATE INDEX "category_slug" ON "[{prefix}]categories" ("slug");
CREATE INDEX "category_order" ON "[{prefix}]categories" ("order");

--
-- Dumping data for table "categories"
--

INSERT INTO "[{prefix}]categories" ("id", "parent_id", "title", "uri", "slug", "order", "total") VALUES (8001, NULL, 'My Favorites', '/my-favorites', 'my-favorites', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table "content_to_categories"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]content_to_categories" (
  "content_id" integer NOT NULL,
  "category_id" integer NOT NULL,
  "order" integer NOT NULL,
  UNIQUE ("content_id", "category_id"),
  CONSTRAINT "fk_category_content_id" FOREIGN KEY ("content_id") REFERENCES "[{prefix}]content" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "fk_content_category_id" FOREIGN KEY ("category_id") REFERENCES "[{prefix}]categories" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE INDEX "category_content_id" ON "[{prefix}]content_to_categories" ("content_id");
CREATE INDEX "content_category_id" ON "[{prefix}]content_to_categories" ("category_id");

--
-- Dumping data for table "content_to_categories"
--

INSERT INTO "[{prefix}]content_to_categories" ("content_id", "category_id", "order") VALUES (6002, 8001, 1);
INSERT INTO "[{prefix}]content_to_categories" ("content_id", "category_id", "order") VALUES (6003, 8001, 2);

-- --------------------------------------------------------

--
-- Table structure for table "content_to_navigation"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]content_to_navigation" (
  "navigation_id" integer NOT NULL,
  "content_id" integer,
  "category_id" integer,
  "order" integer NOT NULL,
  UNIQUE ("navigation_id", "content_id", "category_id"),
  CONSTRAINT "fk_navigation_id" FOREIGN KEY ("navigation_id") REFERENCES "[{prefix}]navigation" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "fk_navigation_content_id" FOREIGN KEY ("content_id") REFERENCES "[{prefix}]content" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE INDEX "nav_navigation_id" ON "[{prefix}]content_to_navigation" ("navigation_id");
CREATE INDEX "nav_content_id" ON "[{prefix}]content_to_navigation" ("content_id");
CREATE INDEX "nav_category_id" ON "[{prefix}]content_to_navigation" ("category_id");

--
-- Dumping data for table "content_to_navigation"
--

INSERT INTO "[{prefix}]content_to_navigation" ("navigation_id", "content_id", "category_id", "order") VALUES (7001, 6001, NULL, 1);
INSERT INTO "[{prefix}]content_to_navigation" ("navigation_id", "content_id", "category_id", "order") VALUES (7001, 6002, NULL, 2);
INSERT INTO "[{prefix}]content_to_navigation" ("navigation_id", "content_id", "category_id", "order") VALUES (7001, 6003, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table "templates"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]templates" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "parent_id" integer,
  "name" varchar NOT NULL,
  "content_type" varchar NOT NULL,
  "device" varchar NOT NULL,
  "template" text NOT NULL,
  UNIQUE ("id"),
  CONSTRAINT "fk_template_parent_id" FOREIGN KEY ("parent_id") REFERENCES "[{prefix}]templates" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]templates', 9000);
CREATE INDEX "template_parent_id" ON "[{prefix}]templates" ("parent_id");
CREATE INDEX "template_name" ON "[{prefix}]templates" ("name");

-- --------------------------------------------------------

--
-- Dumping data for table `config`
--

INSERT INTO "[{prefix}]config" ("setting", "value") VALUES ('feed_type', '9');
INSERT INTO "[{prefix}]config" ("setting", "value") VALUES ('feed_limit', '20');
INSERT INTO "[{prefix}]config" ("setting", "value") VALUES ('open_authoring', '1');
INSERT INTO "[{prefix}]config" ("setting", "value") VALUES ('incontent_editing', '0');

--
-- Dumping data for table "fields"
--

INSERT INTO "[{prefix}]fields" ("group_id", "type", "name", "label", "values", "default_values", "attributes", "validators", "encryption", "order", "required", "editor", "models") VALUES (NULL, 'text', 'description', 'Description', '', '', 'size="80" style="display: block; width: 100%;"', NULL, 0, 1, 0, 'source', 'a:1:{i:0;a:2:{s:5:"model";s:21:"Content\Model\Content";s:7:"type_id";i:5001;}}');
INSERT INTO "[{prefix}]fields" ("group_id", "type", "name", "label", "values", "default_values", "attributes", "validators", "encryption", "order", "required", "editor", "models") VALUES (NULL, 'text', 'keywords', 'Keywords', '', '', 'size="80" style="display: block; width: 100%;"', NULL, 0, 2, 0, 'source', 'a:1:{i:0;a:2:{s:5:"model";s:21:"Content\Model\Content";s:7:"type_id";i:5001;}}');
INSERT INTO "[{prefix}]fields" ("group_id", "type", "name", "label", "values", "default_values", "attributes", "validators", "encryption", "order", "required", "editor", "models") VALUES (NULL, 'textarea-history', 'content', 'Content', '', '', 'rows="20" cols="110" style="display: block; width: 100%;"', NULL, 0, 3, 0, 'source', 'a:1:{i:0;a:2:{s:5:"model";s:21:"Content\Model\Content";s:7:"type_id";i:5001;}}');
