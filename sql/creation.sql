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
    password CHAR(100) NOT NULL
);

CREATE TABLE account
(
    id SERIAL PRIMARY KEY NOT NULL,
    number INT NULL,
    client_id INT NOT NULL REFERENCES client (id),
    open_date TIME NOT NULL,
    balance BIGINT NOT NULL,
    country_code INT NOT NULL,
    region_code INT NOT NULL,
    division_code INT NOT NULL,
    credit_institution_number INT NOT NULL,
    correspondent_account NUMERIC (22, 0) NOT NULL,
    city CHAR(30) NOT NULL,
    street CHAR(50) NOT NULL,
    house INT NOT NULL,
    building INT
);

CREATE TABLE card
(
    id SERIAL PRIMARY KEY NOT NULL,
    number BIGINT NULL,
    client_id INT NOT NULL REFERENCES client (id),
    pin NUMERIC (6, 0),
    cvv NUMERIC (5, 0) NOT NULL,
    service_end_date TIME NOT NULL,
    lost BOOLEAN,
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
    password CHAR(100) NOT NULL
);

# CHECK (0 < age AND age < 120)

CREATE TABLE application
(
    id SERIAL PRIMARY KEY NOT NULL,
    number BIGINT NULL,
    applicant_id INT NOT NULL REFERENCES client (id),
    inspector_id INT NULL REFERENCES employee (id),
    date_of_submission TIME NOT NULL,
    status ENUM('accepted', 'approved', 'rejected') NOT NULL,
    comment CHAR(255)
);

CREATE TABLE contract
(
    id SERIAL PRIMARY KEY NOT NULL,
    number BIGINT NULL,
    client_id INT NOT NULL REFERENCES client (id),
    application_id INT NOT NULL REFERENCES application (id),
    inspector_id INT NULL REFERENCES employee (id),
    account_id INT REFERENCES account (id),
    card_id INT REFERENCES card (id),
    date_of_submission TIME,
    status ENUM ('ready', 'accepted', 'rejected') NOT NULL,
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
        SET NEW.number = tmp;
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
        SET NEW.number = tmp;
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