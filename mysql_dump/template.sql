SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;

-- start

START TRANSACTION;
SET time_zone = "+00:00";

DROP database IF EXISTS getrss;
CREATE database getrss;


DROP user IF EXISTS 'getrss_user'@'localhost';
CREATE user 'getrss_user'@'localhost' IDENTIFIED BY 'MnR8#?f9';
GRANT SELECT, INSERT, UPDATE, DELETE on getrss.* to 'getrss_user'@'localhost';

use getrss;

CREATE TABLE urls(
    id_url int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    rss_url varchar(3000) NOT NULL,
    alias varchar(100) NOT NULL,
    PRIMARY KEY (id_url)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE requests(
    id_request int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    req_date datetime NOT NULL,
    url int(10) UNSIGNED NOT NULL,
    http_code varchar(3) NOT NULL,
    PRIMARY KEY (id_request),
    KEY req_urls (url),
    CONSTRAINT req_urls_control FOREIGN KEY (url) REFERENCES urls (id_url)
        ON DELETE CASCADE ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


CREATE TABLE news(
    id_new int(10) UNSIGNED NOT NULL,
    title varchar(2000) NOT NULL,
    new_link varchar(2000) NOT NULL,
    new_date datetime  NOT NULL,
    guid varchar(1000) UNIQUE NOT NULL,
    description varchar(5000) DEFAULT '',
    author varchar(2000) DEFAULT '',
    image varchar(2000) DEFAULT '',
    url int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (id_new),
    KEY news_urls (url),
    CONSTRAINT news_urls_control FOREIGN KEY (url) REFERENCES urls (id_url)
        ON DELETE CASCADE ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- end transaction --
COMMIT;
