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

# CHECK (0 < age AND age < 120)
# CHECK (100 < number AND number < 1000000)


CREATE TABLE account
(
    id SERIAL PRIMARY KEY NOT NULL,
    number INT NOT NULL,
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

# CHECK (country_code = 4)
# CHECK (0 < region_code AND region_code < 100)
# CHECK (0 <= division_code AND division_code <= 99)
# CHECK (50 <= credit_institution_number AND credit_institution_number <= 999)
# CHECK ( house > 0 )
# CHECK (building > 0)

CREATE TABLE card
(
    id SERIAL PRIMARY KEY NOT NULL,
    number BIGINT NOT NULL,
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
    number BIGINT NOT NULL,
    applicant_id INT NOT NULL REFERENCES client (id),
    date_of_submission TIME NOT NULL,
    path_to_scan CHAR(100) NOT NULL,
    status ENUM('accepted', 'approved', 'rejected') NOT NULL
);

CREATE TABLE contract
(
    id SERIAL PRIMARY KEY NOT NULL,
    number BIGINT NOT NULL,
    client_id INT NOT NULL REFERENCES client (id),
    account_id INT REFERENCES account (id),
    card_id INT REFERENCES card (id),
    employee_id INT REFERENCES employee (id),
    date_of_submission TIME,
    path_to_scan CHAR(100) NOT NULL,
    status ENUM ('ready', 'accepted', 'rejected') NOT NULL
);