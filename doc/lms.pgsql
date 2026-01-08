CREATE SEQUENCE IF NOT EXISTS metroport_settings_id_seq;

CREATE TABLE IF NOT EXISTS metroport_settings (
    id integer DEFAULT nextval('metroport_settings_id_seq'::regclass) NOT NULL,
    name character varying(60) NOT NULL,
    value text NOT NULL,
    updated timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (name)
);

CREATE SEQUENCE IF NOT EXISTS metroport_customers_id_seq;

CREATE TABLE IF NOT EXISTS metroport_customers (
    id integer DEFAULT nextval('metroport_customers_id_seq'::regclass) NOT NULL,
    metroport_user_id integer NOT NULL,
    lms_user_id integer NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (metroport_user_id, lms_user_id)
);

-- uiconfig
INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'serwer_url', 'api.mmsc.metroport.pl ', 'Adres url serwera API Metroport', 0, 7, NULL, NULL, NULL);
INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'api_login', 'loginki ', 'Login do serwera API', 0, 7, NULL, NULL, NULL);
INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'api_pass', 'pass', 'Hasło do serwera API', 0, 7, NULL, NULL, NULL);
INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'automatically_update_customer_data', 'true', 'true - przy edycji klienta automatycznie aktualizuje dane w MMS, false wymaga ręcznej aktualizacji', 0, 7, NULL, NULL, NULL);
INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'api_token_expiration_time', '1200', 'Timeout dla tokena w sekundach. Domyslnie 1200s', 0, 7, NULL, NULL, NULL);

-- dbinfo
INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSMetroportPlugin', '2025090303');