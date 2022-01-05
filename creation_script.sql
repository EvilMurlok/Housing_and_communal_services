USE housing_communal_services;

CREATE TABLE IF NOT EXISTS News(
                                   News_id INT NOT NULL AUTO_INCREMENT,
                                   Title VARCHAR(255) NOT NULL,
                                   Content TEXT NOT NULL,
                                   Is_published BOOL DEFAULT TRUE NOT NULL,
                                   Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                                   PRIMARY KEY (News_id)

);

CREATE TABLE IF NOT EXISTS ManagementCompany(
                                                Management_company_id INT NOT NULL AUTO_INCREMENT,
                                                Company_name VARCHAR(40) NOT NULL,
                                                Company_password VARCHAR(255) NOT NULL,
                                                Full_name_boss VARCHAR(45) NOT NULL,
                                                Company_email VARCHAR(25) NOT NULL,
                                                Company_link VARCHAR(30),
                                                Telephone_number CHAR(11) NOT NULL,
                                                Address VARCHAR(50) NOT NULL,
                                                Is_staff BOOLEAN DEFAULT TRUE NOT NULL,
                                                PRIMARY KEY (Management_company_id),
                                                UNIQUE (Company_name),
                                                UNIQUE (Company_email),
                                                UNIQUE (Telephone_number)
);

CREATE TABLE IF NOT EXISTS Address(
                                      Address_id INT NOT NULL AUTO_INCREMENT,
                                      City_name VARCHAR(25) NOT NULL,
                                      Street VARCHAR(50) NOT NULL,
                                      House INT UNSIGNED  NOT NULL,
                                      Housing INT UNSIGNED,
                                      Management_company_id INT,
                                      PRIMARY KEY (Address_id),
                                      CHECK (House > 0),
                                      CHECK (Housing > 0),
                                      FOREIGN KEY (Management_company_id) REFERENCES ManagementCompany(Management_company_id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS  Consumer(
                                        Consumer_id INT NOT NULL AUTO_INCREMENT,
                                        First_name VARCHAR(20) NOT NULL,
                                        Last_name VARCHAR(20) NOT NULL,
                                        Patronymic VARCHAR(20),
                                        Consumer_email VARCHAR(30) NOT NULL,
                                        Consumer_password VARCHAR(255) NOT NULL,
                                        Birthday DATE NOT NULL,
                                        Telephone_number CHAR(11),
                                        Passport_series INT UNSIGNED NOT NULL,
                                        Passport_number INT UNSIGNED NOT NULL,
                                        Living_space DECIMAL(6,2) UNSIGNED NOT NULL,
                                        Personal_acc_hcs DECIMAL(8, 2) DEFAULT 0.0 NOT NULL,
                                        Personal_acc_landline_ph DECIMAL(8, 2) DEFAULT 0.0 NOT NULL,
                                        Personal_acc_long_dist_ph DECIMAL(8, 2) DEFAULT 0.0 NOT NULL,
                                        Address_id INT,
                                        Flat INT UNSIGNED NOT NULL,
                                        Is_staff BOOLEAN DEFAULT FALSE NOT NULL,
                                        Password_cookie_token VARCHAR(255) NULL,
                                        PRIMARY KEY (Consumer_id),
                                        CHECK (Passport_series > 999 AND Passport_series < 10000),
                                        CHECK (Passport_number > 99999 AND Passport_number < 1000000),
                                        CHECK (Living_space > 0),
                                        CHECK (Personal_acc_hcs >= 0),
                                        CHECK (Personal_acc_landline_ph >= 0),
                                        CHECK (Personal_acc_long_dist_ph >= 0),
                                        CHECK (Flat > 0),
                                        FOREIGN KEY (Address_id) REFERENCES Address(Address_id) ON DELETE SET NULL ON UPDATE CASCADE,
                                        UNIQUE (Consumer_email),
                                        UNIQUE (Passport_series, Passport_number),
                                        UNIQUE (Telephone_number)
);

CREATE TABLE  IF NOT EXISTS ResourceOrganization (
                                                     Resource_organization_id INT AUTO_INCREMENT,
                                                     Organization_name VARCHAR(255) NOT NULL,
                                                     Telephone_number CHAR(11) NOT NULL,
                                                     Organization_email VARCHAR(255),
                                                     Organization_link VARCHAR(355),
                                                     Bank_details VARCHAR(255) NOT NULL,
                                                     Address VARCHAR(50) NOT NULL,

                                                     PRIMARY KEY (Resource_organization_id)
);

CREATE TABLE IF NOT EXISTS Rate(
                                   Rate_id INT AUTO_INCREMENT,
                                   Service_name VARCHAR(255) NOT NULL,
                                   Unit VARCHAR(10) NOT NULL,
                                   Unit_cost DECIMAL(6, 2) DEFAULT 0.0 NOT NULL,
                                   Resource_organization_id INT NOT NULL,
                                   PRIMARY KEY (Rate_id),
                                   CHECK (Unit_cost >= 0),
                                   FOREIGN KEY (Resource_organization_id) REFERENCES ResourceOrganization(Resource_organization_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                   UNIQUE (Service_name, Resource_organization_id)
);

CREATE TABLE IF NOT EXISTS ElectricityСharge(
                                                Electricity_charge_id INT AUTO_INCREMENT,
                                                Amount_of_unit DECIMAL(6, 2) NOT NULL, -- показания потребителя, либо же самой УК
                                                Charge_period DATE NOT NULL, -- период начисления, будет указываться ПОСЛЕДНЕЕ число месяца (30.09.2021)
                                                Information_entering_date DATE NOT NULL,  -- дата внесения информации
                                                Is_consumer BOOLEAN DEFAULT FALSE NOT NULL, -- внесены ли данные потребителем
                                                Consumer_id INT NOT NULL,
                                                Management_company_id INT, -- управляющая компания
                                                Rate_id INT NOT NULL, -- тариф
                                                Tariff_amount DECIMAL(8,2), -- сумма по тарифу

                                                PRIMARY KEY (Electricity_charge_id),
                                                FOREIGN KEY (Consumer_id) REFERENCES Consumer(Consumer_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                                FOREIGN KEY (Management_company_id) REFERENCES ManagementCompany(Management_company_id) ON DELETE SET NULL ON UPDATE CASCADE,
                                                FOREIGN KEY (Rate_id) REFERENCES Rate(Rate_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                                CHECK (Amount_of_unit >= 0),
                                                CHECK (Charge_period <= Information_entering_date), -- очевидно,
    -- что потребитель может внести данные за месяц только по прошествии этого месяца
                                                UNIQUE (Charge_period, Consumer_id) -- не больше одного начисления в месяц по конкретной услуге
);

CREATE TABLE IF NOT EXISTS HotWaterСharge(
                                             Hot_water_charge_id INT AUTO_INCREMENT,
                                             Amount_of_unit DECIMAL(6, 2) NOT NULL, -- показания потребителя, либо же самой УК
                                             Charge_period DATE NOT NULL, -- период начисления, будет указываться ПОСЛЕДНЕЕ число месяца (30.09.2021)
                                             Information_entering_date DATE NOT NULL, -- дата внесения информации
                                             Is_consumer BOOLEAN DEFAULT FALSE NOT NULL, -- внесены ли данные потребителем
                                             Consumer_id INT NOT NULL,
                                             Management_company_id INT, -- управляющая компания
                                             Rate_id INT NOT NULL, -- тариф
                                             Tariff_amount DECIMAL(8,2), -- сумма по тарифу

                                             PRIMARY KEY (Hot_water_charge_id),
                                             FOREIGN KEY (Consumer_id) REFERENCES Consumer(Consumer_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                             FOREIGN KEY (Management_company_id) REFERENCES ManagementCompany(Management_company_id) ON DELETE SET NULL ON UPDATE CASCADE,
                                             FOREIGN KEY (Rate_id) REFERENCES Rate(Rate_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                             CHECK (Amount_of_unit >= 0),
                                             CHECK (Charge_period <= Information_entering_date), -- очевидно,
    -- что потребитель может внести данные за месяц только по прошествии этого месяца
                                             UNIQUE (Charge_period, Consumer_id) -- не больше одного начисления в месяц по конкретной услуге

);

CREATE TABLE IF NOT EXISTS ColdWaterСharge(
                                              Cold_water_charge_id INT AUTO_INCREMENT,
                                              Amount_of_unit DECIMAL(6, 2) NOT NULL, -- показания потребителя, либо же самой УК
                                              Charge_period DATE NOT NULL, -- период начисления, будет указываться ПОСЛЕДНЕЕ число месяца (30.09.2021)
                                              Information_entering_date DATE NOT NULL, -- дата внесения информации
                                              Is_consumer BOOLEAN DEFAULT FALSE NOT NULL, -- внесены ли данные потребителем
                                              Consumer_id INT NOT NULL,
                                              Management_company_id INT, -- управляющая компания
                                              Rate_id INT NOT NULL, -- тариф
                                              Tariff_amount DECIMAL(8,2), -- сумма по тарифу

                                              PRIMARY KEY (Cold_water_charge_id),
                                              FOREIGN KEY (Consumer_id) REFERENCES Consumer(Consumer_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                              FOREIGN KEY (Management_company_id) REFERENCES ManagementCompany(Management_company_id) ON DELETE SET NULL ON UPDATE CASCADE,
                                              FOREIGN KEY (Rate_id) REFERENCES Rate(Rate_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                              CHECK (Amount_of_unit >= 0),
                                              CHECK (Charge_period <= Information_entering_date), -- очевидно,
    -- что потребитель может внести данные за месяц только по прошествии этого месяца
                                              UNIQUE (Charge_period, Consumer_id) -- не больше одного начисления в месяц по конкретной услуге

);

CREATE TABLE IF NOT EXISTS GasСharge(
                                        Gas_charge_id INT AUTO_INCREMENT,
                                        Amount_of_unit DECIMAL(6, 2) NOT NULL, -- показания потребителя, либо же самой УК
                                        Charge_period DATE NOT NULL, -- период начисления, будет указываться ПОСЛЕДНЕЕ число месяца (30.09.2021)
                                        Information_entering_date DATE NOT NULL, -- дата внесения информации
                                        Is_consumer BOOLEAN DEFAULT FALSE NOT NULL, -- внесены ли данные потребителем
                                        Consumer_id INT NOT NULL,
                                        Management_company_id INT, -- управляющая компания
                                        Rate_id INT NOT NULL, -- тариф
                                        Tariff_amount DECIMAL(8,2), -- сумма по тарифу

                                        PRIMARY KEY (Gas_charge_id),
                                        FOREIGN KEY (Consumer_id) REFERENCES Consumer(Consumer_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                        FOREIGN KEY (Management_company_id) REFERENCES ManagementCompany(Management_company_id) ON DELETE SET NULL ON UPDATE CASCADE,
                                        FOREIGN KEY (Rate_id) REFERENCES Rate(Rate_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                        CHECK (Amount_of_unit >= 0),
                                        CHECK (Charge_period <= Information_entering_date), -- очевидно,
    -- что потребитель может внести данные за месяц только по прошествии этого месяца
                                        UNIQUE (Charge_period, Consumer_id) -- не больше одного начисления в месяц по конкретной услуге

);

CREATE TABLE IF NOT EXISTS HeatingСharge(
                                            Heating_charge_id INT AUTO_INCREMENT,
                                            Amount_of_unit DECIMAL(6, 2) NOT NULL, -- показания потребителя, либо же самой УК
                                            Charge_period DATE NOT NULL, -- период начисления, будет указываться ПОСЛЕДНЕЕ число месяца (30.09.2021)
                                            Information_entering_date DATE NOT NULL, -- дата внесения информации
                                            Is_consumer BOOLEAN DEFAULT FALSE NOT NULL, -- внесены ли данные потребителем
                                            Consumer_id INT NOT NULL,
                                            Management_company_id INT, -- управляющая компания
                                            Rate_id INT NOT NULL, -- тариф
                                            Tariff_amount DECIMAL(8,2), -- сумма по тарифу

                                            PRIMARY KEY (Heating_charge_id),
                                            FOREIGN KEY (Consumer_id) REFERENCES Consumer(Consumer_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                            FOREIGN KEY (Management_company_id) REFERENCES ManagementCompany(Management_company_id) ON DELETE SET NULL ON UPDATE CASCADE,
                                            FOREIGN KEY (Rate_id) REFERENCES Rate(Rate_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                            CHECK (Amount_of_unit >= 0),
                                            CHECK (Charge_period <= Information_entering_date), -- очевидно,
    -- что потребитель может внести данные за месяц только по прошествии этого месяца
                                            UNIQUE (Charge_period, Consumer_id) -- не больше одного начисления в месяц по конкретной услуге

);

CREATE TABLE IF NOT EXISTS ReceiptCityPhone(
                                               Receipt_id INT AUTO_INCREMENT,
                                               Amount_of_minutes INT NOT NULL, -- показания потребителя, либо же самой УК
                                               Receipt_period DATE NOT NULL, -- период начисления, будет указываться ПОСЛЕДНЕЕ число месяца (30.09.2021)
                                               Information_entering_date DATE NOT NULL, -- дата внесения информации
                                               Consumer_id INT NOT NULL,
                                               Rate_id INT NOT NULL, -- тариф
                                               Tariff_amount DECIMAL(8,2), -- сумма по тарифу
                                               Deadline_date DATE NOT NULL, -- дата срока оплаты
                                               Payment_date DATE, -- по этим двум датам будет вычисляться кол-во дней просрочки и непосредственно надбавка
                                               Overdue_days INT DEFAULT 0 NOT NULL,
                                               Total_summ DECIMAL(8,2), -- общая сумма с учетом всевозможных штрафов
                                               Is_paid BOOLEAN DEFAULT False,

                                               PRIMARY KEY (Receipt_id),
                                               FOREIGN KEY (Consumer_id) REFERENCES Consumer(Consumer_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                               FOREIGN KEY (Rate_id) REFERENCES Rate(Rate_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                               CHECK (Amount_of_minutes >= 0),
                                               CHECK (Receipt_period <= Information_entering_date), -- очевидно,
    -- что потребитель может внести данные за месяц только по прошествии этого месяца
                                               CHECK (Total_summ >= 0),
                                               CHECK (Tariff_amount >= 0)

);

CREATE TABLE IF NOT EXISTS ReceiptDistancePhone(
                                                   Receipt_id INT AUTO_INCREMENT,
                                                   Amount_of_minutes INT NOT NULL, -- показания потребителя, либо же самой УК
                                                   Receipt_period DATE NOT NULL, -- период начисления, будет указываться ПОСЛЕДНЕЕ число месяца (30.09.2021)
                                                   Information_entering_date DATE NOT NULL, -- дата внесения информации
                                                   Consumer_id INT NOT NULL,
                                                   Rate_id INT NOT NULL, -- тариф
                                                   Tariff_amount DECIMAL(8,2), -- сумма по тарифу
                                                   Deadline_date DATE NOT NULL, -- дата срока оплаты
                                                   Payment_date DATE, -- по этим двум датам будет вычисляться кол-во дней просрочки и непосредственно надбавка
                                                   Overdue_days INT DEFAULT 0 NOT NULL,
                                                   Total_summ DECIMAL(8,2), -- общая сумма с учетом всевозможных штрафов
                                                   Is_paid BOOLEAN DEFAULT False,

                                                   PRIMARY KEY (Receipt_id),
                                                   FOREIGN KEY (Consumer_id) REFERENCES Consumer(Consumer_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                                   FOREIGN KEY (Rate_id) REFERENCES Rate(Rate_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                                   CHECK (Amount_of_minutes >= 0),
                                                   CHECK (Receipt_period <= Information_entering_date), -- очевидно,
    -- что потребитель может внести данные за месяц только по прошествии этого месяца
                                                   CHECK (Total_summ >= 0),
                                                   CHECK (Tariff_amount >= 0)
);

CREATE TABLE IF NOT EXISTS ReceiptHCS(
                                         Receipt_id INT AUTO_INCREMENT,
                                         Receipt_period DATE NOT NULL, -- период составления квитанции, будет указываться ПОСЛЕДНЕЕ число месяца (30.09.2021)
                                         Consumer_id INT NOT NULL,
                                         Electricity_charge_id INT NOT NULL,
                                         Hot_water_charge_id INT  NOT NULL,
                                         Cold_water_charge_id INT  NOT NULL,
                                         Gas_charge_id INT  NOT NULL,
                                         Heating_charge_id INT  NOT NULL,

                                         Amount_water_disposal DECIMAL(6, 2), -- ((ГВС + ХВС) х (тариф за куб. м.))
                                         Amount_housing_maintenance DECIMAL(6, 2), -- ((жил. пл.) х (тариф за кв. м.))
                                         Amount_overhaul DECIMAL(6, 2), -- ((жил. пл.) х (тариф за кв. м.))
                                         Amount_intercom DECIMAL(6, 2), -- ((жил. пл.) х (тариф за кв. м.))

                                         Deadline_date DATE NOT NULL, -- дата срока оплаты
                                         Payment_date DATE, -- по этим двум датам будет вычисляться кол-во дней просрочки и непосредственно надбавка
                                         Overdue_days INT DEFAULT 0 NOT NULL,
                                         Tariff_amount DECIMAL(8,2), -- сумма по тарифу
                                         Total_summ DECIMAL(8,2), -- общая сумма с учетом всевозможных штрафов
                                         Is_paid BOOLEAN DEFAULT False,

                                         PRIMARY KEY (Receipt_id),
                                         FOREIGN KEY (Consumer_id) REFERENCES Consumer(Consumer_id),
                                         FOREIGN KEY (Electricity_charge_id) REFERENCES ElectricityСharge(Electricity_charge_id) ON CASCADE ON UPDATE CASCADE,
                                         FOREIGN KEY (Hot_water_charge_id) REFERENCES HotWaterСharge(Hot_water_charge_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                         FOREIGN KEY (Cold_water_charge_id) REFERENCES ColdWaterСharge(Cold_water_charge_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                         FOREIGN KEY (Gas_charge_id) REFERENCES GasСharge(Gas_charge_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                         FOREIGN KEY (Heating_charge_id) REFERENCES HeatingСharge(Heating_charge_id) ON DELETE CASCADE ON UPDATE CASCADE,
                                         CHECK (Amount_water_disposal >= 0),
                                         CHECK (Amount_housing_maintenance >= 0),
                                         CHECK (Amount_overhaul >= 0),
                                         CHECK (Amount_intercom >= 0),
                                         CHECK (Total_summ >= 0)
);
