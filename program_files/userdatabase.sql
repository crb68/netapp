CREATE DATABASE Netapp;

USE Netapp;

-- user table

CREATE TABLE IF NOT EXISTS `user` (
  `username` varchar(32) NOT NULL,
  `email` varchar(35) NOT NULL,
  `fname` varchar(32),
  `lname` varchar(32),
  `password` varchar(32) NOT NULL,
  `user_level` int NOT NULL,
  PRIMARY KEY (`username`),
  UNIQUE KEY (`email`)
);

INSERT INTO `user` VALUES('admin','DEFAULT',null,null,'password',2);
commit;