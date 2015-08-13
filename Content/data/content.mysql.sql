--
-- Content Module MySQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------

--
-- Table structure for table `content_types`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]content_types` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content_type` varchar(255) NOT NULL,
  `open_authoring` int(1) NOT NULL,
  `order` int(16),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5001 ;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]content` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `type_id` int(16) NOT NULL,
  `parent_id` int(16),
  `title` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `slug` varchar(255),
  `status` int(1) NOT NULL,
  `template` varchar(255),
  `roles` text,
  `order` int(16),
  `hierarchy` varchar(255),
  `publish` datetime,
  `expire` datetime,
  `created` datetime,
  `updated` datetime,
  `created_by` int(16),
  `updated_by` int(16),
  PRIMARY KEY (`id`),
  INDEX `content_type_id` (`type_id`),
  INDEX `content_parent_id` (`parent_id`),
  INDEX `content_title` (`title`),
  UNIQUE `content_uri` (`uri`),
  INDEX `content_slug` (`slug`),
  INDEX `content_status` (`status`),
  INDEX `content_publish` (`publish`),
  INDEX `content_expire` (`expire`),
  CONSTRAINT `fk_content_type` FOREIGN KEY (`type_id`) REFERENCES `[{prefix}]content_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_content_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `[{prefix}]content` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `[{prefix}]users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `[{prefix}]users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6001;

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
