--
-- Content Module MySQL Database
--

-- --------------------------------------------------------

--
-- Table structure for table `content_types`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]content_types` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `uri` int(1) NOT NULL,
  `order` int(16) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `content_type_name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5003 ;

--
-- Dumping data for table `content_types`
--

INSERT INTO `[{prefix}]content_types` (`id`, `name`, `uri`, `order`) VALUES
(5001, 'Page', 1, 1),
(5002, 'Media', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]content` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `site_id` int(16),
  `type_id` int(16),
  `parent_id` int(16),
  `template` varchar(255),
  `title` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `feed` int(1),
  `force_ssl` int(1),
  `status` int(1),
  `roles` text,
  `created` datetime,
  `updated` datetime,
  `publish` datetime,
  `expire` datetime,
  `created_by` int(16),
  `updated_by` int(16),
  PRIMARY KEY (`id`),
  INDEX `content_site_id` (`site_id`),
  INDEX `content_type_id` (`type_id`),
  INDEX `content_parent_id` (`parent_id`),
  INDEX `content_template` (`template`),
  INDEX `content_title` (`title`),
  INDEX `content_uri` (`uri`),
  INDEX `content_slug` (`slug`),
  INDEX `content_force_ssl` (`force_ssl`),
  INDEX `content_status` (`status`),
  INDEX `content_publish` (`publish`),
  INDEX `content_expire` (`expire`),
  CONSTRAINT `fk_content_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `[{prefix}]content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_content_type` FOREIGN KEY (`type_id`) REFERENCES `[{prefix}]content_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `[{prefix}]users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `[{prefix}]users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6004 ;

--
-- Dumping data for table `content`
--

INSERT INTO `[{prefix}]content` (`id`, `site_id`, `type_id`, `parent_id`, `template`, `title`, `uri`, `slug`, `feed`, `force_ssl`, `status`) VALUES
(6001, 0, 5001, NULL, 'index.phtml', 'Home', '/', '', 1, 0, 2),
(6002, 0, 5001, NULL, 'sub.phtml', 'About', '/about', 'about', 1, 0, 2),
(6003, 0, 5001, 6002, 'sub.phtml', 'Sample Page', '/about/sample-page', 'sample-page', 1, 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `navigation`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]navigation` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `navigation` varchar(255) NOT NULL,
  `spaces` int(16),
  `top_node` varchar(255),
  `top_id` varchar(255),
  `top_class` varchar(255),
  `top_attributes` varchar(255),
  `parent_node` varchar(255),
  `parent_id` varchar(255),
  `parent_class` varchar(255),
  `parent_attributes` varchar(255),
  `child_node` varchar(255),
  `child_id` varchar(255),
  `child_class` varchar(255),
  `child_attributes` varchar(255),
  `on_class` varchar(255),
  `off_class` varchar(255),
  PRIMARY KEY (`id`),
  INDEX `nav_navigation` (`navigation`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7002 ;

--
-- Dumping data for table `navigation`
--

INSERT INTO `[{prefix}]navigation` (`id`, `navigation`, `spaces`, `top_node`, `top_id`) VALUES
(7001, 'Main Nav', 4, 'ul', 'main-nav');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]categories` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `parent_id` int(16),
  `title` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `order` int(16) NOT NULL,
  `total` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `category_parent_id` (`parent_id`),
  INDEX `category_title` (`title`),
  INDEX `category_uri` (`uri`),
  INDEX `category_slug` (`slug`),
  INDEX `category_order` (`order`),
  CONSTRAINT `fk_category_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `[{prefix}]categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8002 ;

--
-- Dumping data for table `categories`
--

INSERT INTO `[{prefix}]categories` (`id`, `parent_id`, `title`, `uri`, `slug`, `order`, `total`) VALUES
(8001, NULL, 'My Favorites', '/my-favorites', 'my-favorites', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `content_to_categories`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]content_to_categories` (
  `content_id` int(16) NOT NULL,
  `category_id` int(16) NOT NULL,
  `order` int(16) NOT NULL,
  INDEX `category_content_id` (`content_id`),
  INDEX `content_category_id` (`category_id`),
  UNIQUE (`content_id`, `category_id`),
  CONSTRAINT `fk_category_content_id` FOREIGN KEY (`content_id`) REFERENCES `[{prefix}]content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_content_category_id` FOREIGN KEY (`category_id`) REFERENCES `[{prefix}]categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `content_to_categories`
--

INSERT INTO `[{prefix}]content_to_categories` (`content_id`, `category_id`, `order`) VALUES
(6002, 8001, 1),
(6003, 8001, 2);

-- --------------------------------------------------------

--
-- Table structure for table `content_to_navigation`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]content_to_navigation` (
  `navigation_id` int(16) NOT NULL,
  `content_id` int(16),
  `category_id` int(16),
  `order` int(16) NOT NULL,
  INDEX `nav_navigation_id` (`navigation_id`),
  INDEX `nav_content_id` (`content_id`),
  INDEX `nav_category_id` (`category_id`),
  UNIQUE (`navigation_id`, `content_id`, `category_id`),
  CONSTRAINT `fk_navigation_id` FOREIGN KEY (`navigation_id`) REFERENCES `[{prefix}]navigation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_navigation_content_id` FOREIGN KEY (`content_id`) REFERENCES `[{prefix}]content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `content_to_navigation`
--

INSERT INTO `[{prefix}]content_to_navigation` (`navigation_id`, `content_id`, `category_id`, `order`) VALUES
(7001, 6001, NULL, 1),
(7001, 6002, NULL, 2),
(7001, 6003, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]templates` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `parent_id` int(16),
  `name` varchar(255) NOT NULL,
  `content_type` varchar(255) NOT NULL,
  `device` varchar(255) NOT NULL,
  `template` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `template_parent_id` (`parent_id`),
  INDEX `template_name` (`name`),
  CONSTRAINT `fk_template_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `[{prefix}]templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9001 ;

-- --------------------------------------------------------

--
-- Dumping data for table `config`
--

INSERT INTO `[{prefix}]config` (`setting`, `value`) VALUES
('feed_type', '9'),
('feed_limit', '20'),
('open_authoring', '1'),
('incontent_editing', '0');

--
-- Dumping data for table `fields`
--

INSERT INTO `[{prefix}]fields` (`group_id`, `type`, `name`, `label`, `values`, `default_values`, `attributes`, `validators`, `encryption`, `order`, `required`, `editor`, `models`) VALUES
(NULL, 'text', 'description', 'Description', '', '', 'size="80" style="display: block; width: 100%;"', NULL, 0, 1, 0, 'source', 'a:1:{i:0;a:2:{s:5:"model";s:21:"Content\\Model\\Content";s:7:"type_id";i:5001;}}'),
(NULL, 'text', 'keywords', 'Keywords', '', '', 'size="80" style="display: block; width: 100%;"', NULL, 0, 2, 0, 'source', 'a:1:{i:0;a:2:{s:5:"model";s:21:"Content\\Model\\Content";s:7:"type_id";i:5001;}}'),
(NULL, 'textarea-history', 'content', 'Content', '', '', 'rows="20" cols="110" style="display: block; width: 100%;"', NULL, 0, 3, 0, 'source', 'a:1:{i:0;a:2:{s:5:"model";s:21:"Content\\Model\\Content";s:7:"type_id";i:5001;}}');
