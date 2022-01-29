CREATE DATABASE card_dealer;

CREATE TABLE client
(
    id SERIAL PRIMARY KEY NOT NULL,
    surname CHAR(30) NOT NULL,
    given_name CHAR(20) NOT NULL,
    patronymic CHAR(40),
    age INT NOT NULL,
    series INT NOT NULL,
    number INT NOT NULL,
    phone CHAR(20) UNIQUE NOT NULL,
    password CHAR(100) NOT NULL,
    password_cookie_token VARCHAR(255) NULL
);

CREATE TABLE account
(
    id SERIAL PRIMARY KEY NOT NULL,
    number NUMERIC (20, 0) NULL, # 408 17 810 5 6200 ХХХХХХХ
    correspondent_account NUMERIC (20, 0) NOT NULL DEFAULT 30101810400000000225,
    bic NUMERIC (9, 0) NOT NULL DEFAULT 044525225,
    inn NUMERIC (12, 0) NULL, # 7707 XXXXXX 00
    kpp NUMERIC (9, 0) NOT NULL DEFAULT 770743001,
    open_date DATE NOT NULL,
    balance BIGINT NOT NULL,
    client_id INT NOT NULL REFERENCES client (id),
    status ENUM ('frozen', 'working') NOT NULL
);

CREATE TABLE card
(
    id SERIAL PRIMARY KEY NOT NULL,
    number NUMERIC (16, 0) NULL, # 5469 38XX XXXX XXX7
    pin NUMERIC (4, 0) NULL,
    cvv NUMERIC (3, 0) NULL,
    service_end_date DATE NOT NULL,
    status ENUM ('preparing', 'ready', 'working', 'lost') NOT NULL,
    client_id INT NOT NULL REFERENCES client (id),
    account_id INT NOT NULL REFERENCES account (id)
);

CREATE TABLE employee
(
    id SERIAL PRIMARY KEY NOT NULL,
    surname CHAR(30) NOT NULL,
    given_name CHAR(20) NOT NULL,
    patronymic CHAR(40),
    age INT NOT NULL,
    phone CHAR(20) UNIQUE NOT NULL,
    password CHAR(100) NOT NULL,
    password_cookie_token VARCHAR(255) NULL
);

CREATE TABLE application
(
    id SERIAL PRIMARY KEY NOT NULL,
    number BIGINT UNSIGNED NULL,
    applicant_id INT NOT NULL REFERENCES client (id),
    inspector_id INT NULL REFERENCES employee (id),
    date_of_submission DATETIME NOT NULL,
    status ENUM('accepted', 'approved', 'rejected') NOT NULL,
    comment CHAR(255)
);

CREATE TABLE contract
(
    id SERIAL PRIMARY KEY NOT NULL,
    number BIGINT UNSIGNED NULL,
    client_id INT NOT NULL REFERENCES client (id),
    application_id INT NOT NULL REFERENCES application (id),
    inspector_id INT NULL REFERENCES employee (id),
    account_id INT REFERENCES account (id),
    card_id INT REFERENCES card (id),
    date_of_submission DATETIME,
    status ENUM ('preparing', 'ready', 'accepted', 'rejected') NOT NULL,
    comment CHAR(255)
);

DELIMITER //

CREATE TRIGGER account_num_trigger
    BEFORE INSERT ON account
    FOR EACH ROW
BEGIN
    DECLARE tmp INT;
    IF (NEW.number is null) THEN
        -- determine next auto_increment value
        SELECT AUTO_INCREMENT INTO tmp FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = 'account';
        -- and set the sort value to the same as the PK
        SET NEW.number = 40817810562000000000 + tmp;
        SET NEW.inn = 770700000000 + tmp * 100;
    END IF;
END

//

DELIMITER //

CREATE TRIGGER card_num_trigger
    BEFORE INSERT ON card
    FOR EACH ROW
BEGIN
    DECLARE tmp INT;
    IF (NEW.number is null) THEN
        -- determine next auto_increment value
        SELECT AUTO_INCREMENT INTO tmp FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = 'card';
        -- and set the sort value to the same as the PK
        SET NEW.number = 5469380000000007 + tmp * 10;
        SET NEW.cvv = 1 + (RAND() * 998);
    END IF;
END

//

DELIMITER //

CREATE TRIGGER application_num_trigger
    BEFORE INSERT ON application
    FOR EACH ROW
BEGIN
    DECLARE tmp INT;
    IF (NEW.number is null) THEN
        -- determine next auto_increment value
        SELECT AUTO_INCREMENT INTO tmp FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = 'application';
        -- and set the sort value to the same as the PK
        SET NEW.number = tmp;
    END IF;
END

//

DELIMITER //

CREATE TRIGGER contract_num_trigger
    BEFORE INSERT ON contract
    FOR EACH ROW
BEGIN
    DECLARE tmp INT;
    IF (NEW.number is null) THEN
        -- determine next auto_increment value
        SELECT AUTO_INCREMENT INTO tmp FROM information_schema.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = 'contract';
        -- and set the sort value to the same as the PK
        SET NEW.number = tmp;
    END IF;
END

//