--
-- Table structure for table `playsms_tblAuth_acl`
--

DROP TABLE IF EXISTS `playsms_tblAuth_acl`;
CREATE TABLE `playsms_tblAuth_acl` (
  `c_timestamp` bigint(20) NOT NULL DEFAULT '0',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `plugin` text NOT NULL,
  `url` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `playsms_tblAuth_acl`
--

INSERT INTO `playsms_tblAuth_acl` (`c_timestamp`, `id`, `name`, `plugin`, `url`) VALUES
(0, 1, 'Broadcast', '', 'inc=core_sendsms,\r\ninc=core_user,\r\ninc=feature_report,\r\ninc=feature_msgtemplate,\r\ninc=feature_queuelog,\r\ninc=feature_credit,\r\ninc=feature_report&route=user\r\n');
