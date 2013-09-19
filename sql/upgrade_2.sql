

create table if not exists mensajes
(  `id` INT NOT NULL AUTO_INCREMENT ,
  `time` DATETIME NOT NULL ,
  `user` INT NULL ,
  `msg` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `index2` (`time` ASC) );
