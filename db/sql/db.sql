-- MySQL Script generated by MySQL Workbench
-- 09/26/16 10:36:56
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema login
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `login` DEFAULT CHARACTER SET utf8 ;
USE `login` ;

-- -----------------------------------------------------
-- Table `User`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `User` (
  `Username` VARCHAR(255) NOT NULL,
  `Email` VARCHAR(64) NULL,
  `LastLogin` DATETIME NULL,
  `Active` TINYINT(1) NULL,
  `ActivationHash` VARCHAR(40) NULL,
  `RememberMeToken` VARCHAR(64) NULL,
  `PwdHash` VARCHAR(255) NULL,
  `PwdResetHash` VARCHAR(40) NULL,
  `PwdResetTime` DATETIME NULL,
  `FailedLogins` TINYINT(1) NULL,
  `LastFailedLogin` DATETIME NULL,
  `CreationTime` DATETIME NULL,
  `CreationIp` VARCHAR(39) NULL,
  `SessionId` VARCHAR(48) NULL,
  `Deleted` TINYINT(1) NULL,
  `AccountType` TINYINT(1) NULL,
  `HasAvatar` TINYINT(1) NULL,
  `ProviderType` VARCHAR(10) NULL,
  `SuspensionType` DATETIME NULL,
  `ApiKey` VARCHAR(25) NULL,
  `SuspensionTime` DATETIME NULL,
  PRIMARY KEY (`Username`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `UserExternal`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `UserExternal` (
  `Id` VARCHAR(255) NOT NULL,
  `Display` VARCHAR(255) NULL,
  `FirstName` VARCHAR(64) NULL,
  `MiddleName` VARCHAR(64) NULL,
  `Email` VARCHAR(64) NULL,
  `LastName` VARCHAR(64) NULL,
  `PictureUrl` VARCHAR(255) NULL,
  `CreationTime` DATETIME NULL,
  `CreationIp` VARCHAR(39) NULL,
  `AccessToken` VARCHAR(255) NULL,
  `AccessTokenScope` VARCHAR(255) NULL,
  `AccessTokenExpireAt` DATETIME NULL,
  `ProviderType` VARCHAR(10) NULL,
  PRIMARY KEY (`Id`))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
