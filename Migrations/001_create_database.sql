-- MySQL dump 10.13  Distrib 5.7.21, for Linux (x86_64)
--
-- Host: localhost    Database: forum
-- ------------------------------------------------------
-- Server version	5.7.21-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `local_branch_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ext_branch_id` int(11) unsigned DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`local_branch_id`),
  UNIQUE KEY `branch_id_ext` (`ext_branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `local_company_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ext_company_id` int(11) unsigned DEFAULT NULL,
  `local_branch_id` int(11) unsigned NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`local_company_id`),
  UNIQUE KEY `company_id_ext` (`ext_company_id`),
  UNIQUE KEY `company_name_branch_id` (`company_name`, `local_branch_id`),
  CONSTRAINT fk_companies_branch_id
    FOREIGN KEY (local_branch_id) REFERENCES branches(local_branch_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `offices`
--


DROP TABLE IF EXISTS `offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offices` (
  `local_office_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ext_office_id` int(11) unsigned DEFAULT NULL,
  `local_company_id` int(11) unsigned NOT NULL,
  `local_branch_id` int(11) unsigned NOT NULL,
  `office_name` int(11) unsigned NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`local_office_id`),
  UNIQUE KEY(`ext_office_id`),
  UNIQUE KEY `office_unique_name_local_branch_id` (`office_name`, `local_company_id`),
  CONSTRAINT fk_offices_branch_id
    FOREIGN KEY (local_branch_id) REFERENCES branches(local_branch_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_offices_company_id
  FOREIGN KEY (local_company_id) REFERENCES companies(local_company_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `userTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userTokens` (
  `token_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `forum_type` ENUM('FORUM', 'CHAT', 'SYSTEM') DEFAULT NULL,
  `local_branch_id` int(11) unsigned NOT NULL,
  `local_company_id` int(11) unsigned NOT NULL,
  `local_office_id` int(11) unsigned NOT NULL,
  `ext_user_id` int(11) unsigned NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `avatar_url` VARCHAR(255)  NULL  DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `token_ttl` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_id`),
  UNIQUE `usertokens__unique_index`(`forum_type`, `ext_user_id`),
  CONSTRAINT fk_usertokens__local_branch_id
    FOREIGN KEY (local_branch_id) REFERENCES branches(local_branch_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_usertokens__local_company_id
    FOREIGN KEY (local_company_id) REFERENCES companies(local_company_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_userTokens_office_id
    FOREIGN KEY (local_office_id) REFERENCES offices(local_branch_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token_id` int(11) unsigned NOT NULL ,
  `local_branch_id` int(11) unsigned DEFAULT NULL,
  `parent_id` int(11) unsigned DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  CONSTRAINT fk_categories__local_branch_id
    FOREIGN KEY (local_branch_id) REFERENCES branches(local_branch_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_categories__token_id
    FOREIGN KEY (token_id) REFERENCES userTokens(token_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `topics`
--

DROP TABLE IF EXISTS `topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topics` (
  `topic_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned NOT NULL,
  `token_id` int(11) unsigned NOT NULL,
  `local_branch_id` int(11) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`topic_id`),
  CONSTRAINT fk_topics__local_branch_id
  FOREIGN KEY (local_branch_id) REFERENCES branches(local_branch_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_topics__category_id
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_topics__token_id
    FOREIGN KEY (token_id) REFERENCES userTokens(token_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `comment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) unsigned DEFAULT NULL,
  `token_id` int(11) unsigned DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  CONSTRAINT fk_comments__topic_id
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_comments__token_id
    FOREIGN KEY (token_id) REFERENCES userTokens(token_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `userTokens`
--




DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations`(
  `migration_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `index` int(11) unsigned DEFAULT NULL,
  `fileName` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`migration_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-02-13 17:00:28
