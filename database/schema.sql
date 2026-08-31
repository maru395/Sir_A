-- Run database/createdb.php to create and select inventorydb.
-- Keep existing rows when installing again; do not drop these tables.

CREATE TABLE IF NOT EXISTS `user`
(
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username             VARCHAR(40) NOT NULL UNIQUE,
    password_hash        VARCHAR(255) NOT NULL,
    role                 ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER',
    first_name           VARCHAR(80) NOT NULL,
    last_name            VARCHAR(80) NOT NULL,
    email                VARCHAR(190) NOT NULL,
    mobile               VARCHAR(20) NOT NULL DEFAULT '',
    is_active            TINYINT(1) NOT NULL DEFAULT 1,
    session_version      INT UNSIGNED NOT NULL DEFAULT 1,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_users_active CHECK (is_active IN (0, 1))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS equipment
(
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code                 VARCHAR(30) NOT NULL UNIQUE,
    name                 VARCHAR(120) NOT NULL,
    category             VARCHAR(80) NOT NULL,
    location             VARCHAR(120) NOT NULL DEFAULT '',
    description          VARCHAR(1000) NOT NULL DEFAULT '',
    total_quantity       INT NOT NULL,
    available_quantity   INT NOT NULL,
    history              LONGTEXT NULL,
    version              INT UNSIGNED NOT NULL DEFAULT 1,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_stock CHECK (total_quantity BETWEEN 0 AND 1000000 AND available_quantity BETWEEN 0 AND total_quantity),
    INDEX idx_equipment_name(name)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS records
(
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id              INT UNSIGNED NOT NULL,
    equipment_id         INT UNSIGNED NOT NULL,
    quantity             INT NOT NULL,
    request_token        CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    equipment_name       VARCHAR(120) NOT NULL,
    equipment_code       VARCHAR(30) NOT NULL,
    note                 VARCHAR(500) NOT NULL DEFAULT '',
    history              LONGTEXT NULL,
    status               ENUM('PENDING', 'BORROWED', 'RETURN_PENDING', 'RETURNED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    requested_at         DATETIME NOT NULL,
    borrowed_at          DATETIME NULL,
    return_requested_at  DATETIME NULL,
    returned_at          DATETIME NULL,
    released_by          INT UNSIGNED NULL,
    received_by          INT UNSIGNED NULL,
    -- Keep borrowing records linked to real users and equipment.
    CONSTRAINT fk_records_user FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE RESTRICT,
    CONSTRAINT fk_records_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE RESTRICT,
    CONSTRAINT fk_records_releaser FOREIGN KEY (released_by) REFERENCES `user`(id) ON DELETE RESTRICT,
    CONSTRAINT fk_records_receiver FOREIGN KEY (received_by) REFERENCES `user`(id) ON DELETE RESTRICT,
    CONSTRAINT ck_record_quantity CHECK (quantity BETWEEN 1 AND 1000000),
    CONSTRAINT ck_record_borrow_time CHECK (borrowed_at IS NULL OR borrowed_at >= requested_at),
    CONSTRAINT ck_record_return_time CHECK (returned_at IS NULL OR (borrowed_at IS NOT NULL AND returned_at >= borrowed_at)),
    CONSTRAINT ck_record_state CHECK (
        (status IN ('PENDING', 'REJECTED', 'CANCELLED') AND borrowed_at IS NULL AND returned_at IS NULL)
        OR (status IN ('BORROWED', 'RETURN_PENDING') AND borrowed_at IS NOT NULL AND returned_at IS NULL)
        OR (status = 'RETURNED' AND borrowed_at IS NOT NULL AND returned_at IS NOT NULL)
    ),
    -- A retry with the same token must not create another borrowing record.
    UNIQUE KEY uq_request(user_id, request_token),
    INDEX idx_records_owner_status(user_id, status, id),
    INDEX idx_records_equipment_status(equipment_id, status),
    INDEX idx_records_status(status, id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Remove unused procedures left by older drafts.
DROP PROCEDURE IF EXISTS sp_prepare_history;
DROP PROCEDURE IF EXISTS sp_create_admin;
DROP PROCEDURE IF EXISTS sp_rate_limit;
DROP PROCEDURE IF EXISTS sp_equipment_archive;
DROP PROCEDURE IF EXISTS sp_setup_populate;

-- History helpers
DROP PROCEDURE IF EXISTS sp_record_event;
DELIMITER $$
CREATE PROCEDURE sp_record_event
(
    IN p_record INT,
    IN p_actor INT,
    IN p_type VARCHAR(32),
    IN p_note VARCHAR(500)
)
BEGIN
    -- Encoding the note keeps its tabs and newlines from breaking the history format.
    UPDATE records
    SET history = CONCAT(
        COALESCE(history, ''),
        DATE_FORMAT(UTC_TIMESTAMP(), '%Y-%m-%d %H:%i:%s'), CHAR(9),
        p_type, CHAR(9),
        p_actor, CHAR(9),
        HEX((SELECT username FROM `user` WHERE id = p_actor)), CHAR(9),
        HEX(COALESCE(p_note, '')), CHAR(10)
    )
    WHERE id = p_record;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_equipment_event;
DELIMITER $$
CREATE PROCEDURE sp_equipment_event
(
    IN p_equipment INT,
    IN p_actor INT,
    IN p_type VARCHAR(20),
    IN p_details TEXT
)
BEGIN
    UPDATE equipment
    SET history = CONCAT(
        COALESCE(history, ''),
        DATE_FORMAT(UTC_TIMESTAMP(), '%Y-%m-%d %H:%i:%s'), CHAR(9),
        p_type, CHAR(9),
        p_actor, CHAR(9),
        HEX((SELECT username FROM `user` WHERE id = p_actor)), CHAR(9),
        HEX(COALESCE(p_details, '')), CHAR(10)
    )
    WHERE id = p_equipment;
END$$
DELIMITER ;

-- Account checks and login
DROP PROCEDURE IF EXISTS sp_assert_role;
DELIMITER $$
CREATE PROCEDURE sp_assert_role
(
    IN p_actor INT UNSIGNED,
    IN p_role VARCHAR(10)
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM `user` WHERE id = p_actor AND is_active = 1 AND (p_role = 'ANY' OR role = p_role)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'forbidden|You do not have permission for this action.';
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_user_by_id;
DELIMITER $$
CREATE PROCEDURE sp_user_by_id
(
    IN p_id INT UNSIGNED
)
BEGIN
    SELECT
        id, username, role, first_name, last_name, email, mobile, is_active, session_version
    FROM `user`
    WHERE id = p_id AND is_active = 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_auth_find;
DELIMITER $$
CREATE PROCEDURE sp_auth_find
(
    IN p_username VARCHAR(40)
)
BEGIN
    SELECT
        id, username, password_hash, role, is_active, session_version
    FROM `user`
    WHERE username = p_username
    LIMIT 1;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_username_exists;
DELIMITER $$
CREATE PROCEDURE sp_username_exists
(
    IN p_username VARCHAR(40)
)
BEGIN
    SELECT
        EXISTS (SELECT 1 FROM `user` WHERE username = p_username) AS taken;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_register;
DELIMITER $$
CREATE PROCEDURE sp_register
(
    IN p_username VARCHAR(40),
    IN p_hash VARCHAR(255),
    IN p_first VARCHAR(80),
    IN p_last VARCHAR(80),
    IN p_email VARCHAR(190),
    IN p_mobile VARCHAR(20)
)
BEGIN
    IF CHAR_LENGTH(TRIM(p_username)) < 3
        OR CHAR_LENGTH(TRIM(p_first)) = 0
        OR CHAR_LENGTH(TRIM(p_last)) = 0
        OR CHAR_LENGTH(p_hash) < 50 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'form|Invalid account details.';
    END IF;
    INSERT INTO `user`
    (
        username, password_hash, role, first_name, last_name, email, mobile, created_at
    )
    VALUES
    (
        p_username, p_hash, 'USER', p_first, p_last, p_email, p_mobile, UTC_TIMESTAMP()
    );
    SELECT
        LAST_INSERT_ID() AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_change_password;
DELIMITER $$
CREATE PROCEDURE sp_change_password
(
    IN p_actor INT UNSIGNED,
    IN p_old_hash VARCHAR(255),
    IN p_new_hash VARCHAR(255)
)
BEGIN
    CALL sp_assert_role(p_actor, 'ANY');
    UPDATE `user`
    SET password_hash = p_new_hash,
        session_version = session_version + 1
    WHERE id = p_actor AND BINARY password_hash = BINARY p_old_hash;
    IF ROW_COUNT() <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'current_password|Account changed. Sign in again.';
    END IF;
END$$
DELIMITER ;

-- Equipment
DROP PROCEDURE IF EXISTS sp_equipment_list;
DELIMITER $$
CREATE PROCEDURE sp_equipment_list
(
    IN p_actor INT UNSIGNED,
    IN p_search VARCHAR(120),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    CALL sp_assert_role(p_actor, 'ANY');
    SELECT
        e.id, e.code, e.name, e.category, e.location, e.description, e.total_quantity, e.available_quantity,
        e.version, e.created_at, e.updated_at, COUNT(*) OVER () AS total_rows
    FROM equipment e
    WHERE p_search = ''
        OR LOCATE(p_search, e.name) > 0
        OR LOCATE(p_search, e.code) > 0
        OR LOCATE(p_search, e.category) > 0
    ORDER BY e.name, e.id
    LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_equipment_get;
DELIMITER $$
CREATE PROCEDURE sp_equipment_get
(
    IN p_actor INT UNSIGNED,
    IN p_id INT UNSIGNED
)
BEGIN
    CALL sp_assert_role(p_actor, 'ANY');
    SELECT
        e.id, e.code, e.name, e.category, e.location, e.description, e.total_quantity, e.available_quantity,
        e.version, e.created_at, e.updated_at,
        EXISTS (SELECT 1 FROM records r WHERE r.equipment_id = e.id) AS has_records
    FROM equipment e
    WHERE e.id = p_id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_equipment_code_exists;
DELIMITER $$
CREATE PROCEDURE sp_equipment_code_exists
(
    IN p_actor INT UNSIGNED,
    IN p_code VARCHAR(30),
    IN p_id INT UNSIGNED
)
BEGIN
    CALL sp_assert_role(p_actor, 'ADMIN');
    SELECT
        EXISTS (SELECT 1 FROM equipment WHERE code = p_code AND id <> p_id) AS taken;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_equipment_save;
DELIMITER $$
CREATE PROCEDURE sp_equipment_save
(
    IN p_actor INT UNSIGNED,
    IN p_id INT UNSIGNED,
    IN p_version INT UNSIGNED,
    IN p_code VARCHAR(30),
    IN p_name VARCHAR(120),
    IN p_category VARCHAR(80),
    IN p_location VARCHAR(120),
    IN p_description VARCHAR(1000),
    IN p_total INT
)
BEGIN
    DECLARE v_id INT UNSIGNED DEFAULT NULL;
    DECLARE v_total INT;
    DECLARE v_available INT;
    DECLARE v_version INT;
    DECLARE v_before TEXT DEFAULT NULL;
    -- Undo the whole change if any step fails, including the history entry.
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    CALL sp_assert_role(p_actor, 'ADMIN');
    IF p_total IS NULL
        OR p_total < 0
        OR p_total > 1000000
        OR CHAR_LENGTH(TRIM(p_code)) = 0
        OR CHAR_LENGTH(TRIM(p_name)) = 0
        OR CHAR_LENGTH(TRIM(p_category)) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'form|Check equipment details and quantity.';
    END IF;
    START TRANSACTION;
    IF p_id = 0 THEN
        INSERT INTO equipment
        (
            code, name, category, location, description, total_quantity, available_quantity, created_at,
            updated_at
        )
        VALUES
        (
            p_code, p_name, p_category, p_location, p_description, p_total, p_total, UTC_TIMESTAMP(),
            UTC_TIMESTAMP()
        );
        SET v_id = LAST_INSERT_ID();
    ELSE
        SELECT
            id, total_quantity, available_quantity, version,
            CONCAT(
            'Code: ', code, CHAR(10),
            'Name: ', name, CHAR(10),
            'Category: ', category, CHAR(10),
            'Location: ', location, CHAR(10),
            'Description: ', description, CHAR(10),
            'Total: ', total_quantity
        )
        INTO v_id, v_total, v_available, v_version, v_before
        FROM equipment
        WHERE id = p_id
        FOR UPDATE;
        IF v_id IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'equipment_id|Equipment not found.';
        END IF;
        IF v_version <> p_version THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'conflict|This equipment changed. Reload it before saving.';
        END IF;
        IF p_total < v_total - v_available THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'total_quantity|Total cannot be below the quantity currently on loan.';
        END IF;
        UPDATE equipment
        SET code = p_code,
            name = p_name,
            category = p_category,
            location = p_location,
            description = p_description,
            available_quantity = v_available +(p_total - v_total),
            total_quantity = p_total,
            version = version + 1,
            updated_at = UTC_TIMESTAMP()
        WHERE id = p_id;
    END IF;
    CALL sp_equipment_event(
        v_id, p_actor, IF(p_id = 0, 'CREATED', 'UPDATED'),
        CONCAT(
            'Before:', CHAR(10), COALESCE(v_before, 'New equipment'), CHAR(10), CHAR(10),
            'After:', CHAR(10),
            'Code: ', p_code, CHAR(10),
            'Name: ', p_name, CHAR(10),
            'Category: ', p_category, CHAR(10),
            'Location: ', p_location, CHAR(10),
            'Description: ', p_description, CHAR(10),
            'Total: ', p_total
        )
    );
    COMMIT;
    SELECT
        v_id AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_equipment_delete;
DELIMITER $$
CREATE PROCEDURE sp_equipment_delete
(
    IN p_actor INT UNSIGNED,
    IN p_id INT UNSIGNED,
    IN p_version INT UNSIGNED
)
BEGIN
    DECLARE v_id INT DEFAULT NULL;
    DECLARE v_version INT;
    DECLARE v_total INT;
    DECLARE v_available INT;
    -- Undo the whole change if any step fails, including the history entry.
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    CALL sp_assert_role(p_actor, 'ADMIN');
    IF p_id IS NULL OR p_id < 1 OR p_version IS NULL OR p_version < 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'form|Invalid equipment deletion request.';
    END IF;
    START TRANSACTION;
    -- Lock this item so it cannot be borrowed or edited during deletion.
    SELECT
        id, version, total_quantity, available_quantity
    INTO v_id, v_version, v_total, v_available
    FROM equipment
    WHERE id = p_id
    FOR UPDATE;
    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'equipment_id|Equipment no longer exists. Refresh the catalog.';
    END IF;
    IF v_version <> p_version THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'conflict|Equipment changed. Refresh before deleting.';
    END IF;
    IF EXISTS (SELECT 1 FROM records WHERE equipment_id = p_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'form|Cannot delete equipment with borrowing records. Transaction history must be preserved.';
    END IF;
    IF v_available <> v_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'form|Cannot delete equipment while units are on loan.';
    END IF;
    -- Foreign keys also prevent deletion if any transaction references the item.
    DELETE FROM equipment WHERE id = p_id;
    COMMIT;
END$$
DELIMITER ;

-- Borrowing and returning
DROP PROCEDURE IF EXISTS sp_request_existing;
DELIMITER $$
CREATE PROCEDURE sp_request_existing
(
    IN p_actor INT UNSIGNED,
    IN p_token CHAR(32)
)
BEGIN
    CALL sp_assert_role(p_actor, 'USER');
    SELECT
        id, equipment_id, quantity, note
    FROM records
    WHERE user_id = p_actor AND request_token = p_token;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_borrow_request;
DELIMITER $$
CREATE PROCEDURE sp_borrow_request
(
    IN p_actor INT UNSIGNED,
    IN p_equipment INT UNSIGNED,
    IN p_quantity INT,
    IN p_token CHAR(32),
    IN p_note VARCHAR(500)
)
BEGIN
    DECLARE v_id INT DEFAULT NULL;
    DECLARE v_stock INT;
    DECLARE v_name VARCHAR(120);
    DECLARE v_code VARCHAR(30);
    DECLARE v_existing INT DEFAULT NULL;
    DECLARE v_equipment INT;
    DECLARE v_quantity INT;
    DECLARE v_note VARCHAR(500);
    -- Undo the whole change if any step fails, including the history entry.
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    CALL sp_assert_role(p_actor, 'USER');
    IF p_quantity IS NULL OR p_quantity < 1 OR p_quantity > 1000000 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quantity|Enter a positive whole-number quantity.';
    END IF;
    IF p_token IS NULL OR p_token NOT REGEXP '^[a-f0-9]{32}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'form|Invalid request token. Reload the page.';
    END IF;
    START TRANSACTION;
    -- Lock the user first so two retries cannot create the same request twice.
    SELECT
        id
    INTO v_id
    FROM `user`
    WHERE id = p_actor
    FOR UPDATE;
    SELECT
        id, equipment_id, quantity, note
    INTO v_existing, v_equipment, v_quantity, v_note
    FROM records
    WHERE user_id = p_actor AND request_token = p_token;
    IF v_existing IS NOT NULL THEN
        IF v_equipment <> p_equipment OR v_quantity <> p_quantity OR BINARY v_note <> BINARY p_note THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'conflict|Request token was already used for different details.';
        END IF;
        SET v_id = v_existing;
    ELSE
        SET v_id = NULL;
        SELECT
            id, available_quantity, name, code
        INTO v_id, v_stock, v_name, v_code
        FROM equipment
        WHERE id = p_equipment
        FOR UPDATE;
        IF v_id IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'equipment_id|This equipment is not available for borrowing.';
        END IF;
        IF p_quantity > v_stock THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quantity|Requested quantity exceeds currently available stock.';
        END IF;
        INSERT INTO records
        (
            user_id, equipment_id, quantity, request_token, equipment_name, equipment_code, note, requested_at
        )
        VALUES
        (
            p_actor, p_equipment, p_quantity, p_token, v_name, v_code, p_note, UTC_TIMESTAMP()
        );
        SET v_id = LAST_INSERT_ID();
        CALL sp_record_event(v_id, p_actor, 'REQUESTED', p_note);
    END IF;
    COMMIT;
    SELECT
        v_id AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_record_transition;
DELIMITER $$
CREATE PROCEDURE sp_record_transition
(
    IN p_actor INT UNSIGNED,
    IN p_record INT UNSIGNED,
    IN p_action VARCHAR(24),
    IN p_note VARCHAR(500)
)
BEGIN
    DECLARE v_id INT DEFAULT NULL;
    DECLARE v_owner INT;
    DECLARE v_equipment INT;
    DECLARE v_quantity INT;
    DECLARE v_status VARCHAR(20);
    DECLARE v_stock INT;
    DECLARE v_event VARCHAR(32);
    -- Undo the whole change if any step fails, including the history entry.
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    IF p_action IN ('approve_borrow', 'reject_borrow', 'confirm_return', 'reject_return') THEN
        CALL sp_assert_role(p_actor, 'ADMIN');
    ELSEIF p_action IN ('cancel_request', 'request_return') THEN
        CALL sp_assert_role(p_actor, 'USER');
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'form|Unknown record action.';
    END IF;
    IF p_action IN ('reject_borrow', 'reject_return') AND CHAR_LENGTH(TRIM(p_note)) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'note|Give a reason for rejecting this request.';
    END IF;
    START TRANSACTION;
    SELECT
        id, user_id, equipment_id, quantity, status
    INTO v_id, v_owner, v_equipment, v_quantity, v_status
    FROM records
    WHERE id = p_record
    FOR UPDATE;
    IF v_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'record_id|Record not found.';
    END IF;
    IF p_action IN ('cancel_request', 'request_return') AND v_owner <> p_actor THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'forbidden|This record does not belong to you.';
    END IF;
    IF p_action IN ('approve_borrow', 'reject_borrow', 'cancel_request') AND v_status <> 'PENDING' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'conflict|This request has already been processed. Refresh the records.';
    END IF;
    IF p_action = 'request_return' AND v_status <> 'BORROWED' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'conflict|Only an active loan can be submitted for return.';
    END IF;
    IF p_action IN ('confirm_return', 'reject_return') AND v_status <> 'RETURN_PENDING' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'conflict|There is no pending return to confirm.';
    END IF;
    -- A pending request does not remove stock. Confirm the handover first.
    IF p_action = 'approve_borrow' THEN
        SELECT
            available_quantity
        INTO v_stock
        FROM equipment
        WHERE id = v_equipment
        FOR UPDATE;
        IF v_stock < v_quantity THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quantity|Not enough stock remains. Reject this request or wait for a return.';
        END IF;
        UPDATE equipment
        SET available_quantity = available_quantity - v_quantity,
            version = version + 1,
            updated_at = UTC_TIMESTAMP()
        WHERE id = v_equipment;
        UPDATE records
        SET status = 'BORROWED',
            borrowed_at = UTC_TIMESTAMP(),
            released_by = p_actor
        WHERE id = p_record;
        SET v_event = 'RELEASE_CONFIRMED';
    -- Add stock back only after staff have received all the borrowed units.
    ELSEIF p_action = 'confirm_return' THEN
        UPDATE equipment
        SET available_quantity = available_quantity + v_quantity,
            version = version + 1,
            updated_at = UTC_TIMESTAMP()
        WHERE id = v_equipment;
        UPDATE records
        SET status = 'RETURNED',
            returned_at = UTC_TIMESTAMP(),
            received_by = p_actor
        WHERE id = p_record;
        SET v_event = 'RETURN_CONFIRMED';
    -- A user's return request alone is not proof the equipment was received.
    ELSEIF p_action = 'request_return' THEN
        UPDATE records
        SET status = 'RETURN_PENDING',
            return_requested_at = UTC_TIMESTAMP()
        WHERE id = p_record;
        SET v_event = 'RETURN_REQUESTED';
    ELSEIF p_action = 'reject_return' THEN
        UPDATE records
        SET status = 'BORROWED',
            return_requested_at = NULL
        WHERE id = p_record;
        SET v_event = 'RETURN_REJECTED';
    ELSEIF p_action = 'reject_borrow' THEN
        UPDATE records
        SET status = 'REJECTED'
        WHERE id = p_record;
        SET v_event = 'REQUEST_REJECTED';
    ELSE
        UPDATE records
        SET status = 'CANCELLED'
        WHERE id = p_record;
        SET v_event = 'REQUEST_CANCELLED';
    END IF;
    CALL sp_record_event(p_record, p_actor, v_event, p_note);
    COMMIT;
    SELECT
        p_record AS id;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_record_list;
DELIMITER $$
CREATE PROCEDURE sp_record_list
(
    IN p_actor INT UNSIGNED,
    IN p_search VARCHAR(120),
    IN p_status VARCHAR(20),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    DECLARE v_role VARCHAR(10);
    CALL sp_assert_role(p_actor, 'ANY');
    SELECT
        role
    INTO v_role
    FROM `user`
    WHERE id = p_actor;
    -- INNER JOIN: only real `user`/equipment can appear; FKs enforce the relationship.
    SELECT
        r.*, CONCAT(u.first_name, ' ', u.last_name) AS borrower_name, u.username,
        e.name AS current_equipment_name, release_user.username AS released_by_name,
        receive_user.username AS received_by_name, COUNT(*) OVER () AS total_rows
    FROM records r
    INNER JOIN `user` u ON u.id = r.user_id
    INNER JOIN equipment e ON e.id = r.equipment_id
    LEFT JOIN `user` release_user ON release_user.id = r.released_by
    LEFT JOIN `user` receive_user ON receive_user.id = r.received_by
    WHERE(v_role = 'ADMIN' OR r.user_id = p_actor)
        AND (p_status = 'ALL' OR r.status = p_status)
        AND (p_search = '' OR LOCATE(p_search, r.equipment_name) > 0 OR LOCATE(p_search, r.equipment_code) > 0 OR LOCATE(p_search, u.username) > 0 OR LOCATE(p_search, CONCAT(u.first_name, ' ', u.last_name)) > 0)
    ORDER BY r.id DESC
    LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_record_get;
DELIMITER $$
CREATE PROCEDURE sp_record_get
(
    IN p_actor INT UNSIGNED,
    IN p_record INT UNSIGNED
)
BEGIN
    CALL sp_assert_role(p_actor, 'ANY');
    SELECT
        r.*
    FROM records r
    JOIN `user` a ON a.id = p_actor
    WHERE r.id = p_record AND (a.role = 'ADMIN' OR r.user_id = p_actor);
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_record_history;
DELIMITER $$
CREATE PROCEDURE sp_record_history
(
    IN p_actor INT UNSIGNED,
    IN p_record INT UNSIGNED
)
BEGIN
    CALL sp_assert_role(p_actor, 'ANY');
    IF NOT EXISTS (SELECT 1 FROM records r JOIN `user` a ON a.id = p_actor WHERE r.id = p_record AND (a.role = 'ADMIN' OR r.user_id = p_actor)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'forbidden|Record not accessible.';
    END IF;
    SELECT
        COALESCE(history, '') AS events
    FROM records
    WHERE id = p_record;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_equipment_history;
DELIMITER $$
CREATE PROCEDURE sp_equipment_history
(
    IN p_actor INT UNSIGNED,
    IN p_equipment INT UNSIGNED
)
BEGIN
    CALL sp_assert_role(p_actor, 'ADMIN');
    SELECT
        COALESCE(history, '') AS events
    FROM equipment
    WHERE id = p_equipment;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_summary;
DELIMITER $$
CREATE PROCEDURE sp_summary
(
    IN p_actor INT UNSIGNED
)
BEGIN
    DECLARE v_role VARCHAR(10);
    CALL sp_assert_role(p_actor, 'ANY');
    SELECT
        role
    INTO v_role
    FROM `user`
    WHERE id = p_actor;
    SELECT
        (SELECT COALESCE(SUM(total_quantity), 0) FROM equipment) AS total_quantity,
        (SELECT COALESCE(SUM(available_quantity), 0) FROM equipment) AS available_quantity,
        COALESCE(SUM(CASE WHEN status IN ('BORROWED', 'RETURN_PENDING') THEN quantity ELSE 0 END), 0) AS on_loan,
        COALESCE(SUM(status = 'PENDING'), 0) AS pending_requests,
        COALESCE(SUM(status = 'RETURN_PENDING'), 0) AS pending_returns,
        COALESCE(SUM(CASE WHEN status = 'RETURNED' THEN quantity ELSE 0 END), 0) AS returned_quantity
    FROM records
    WHERE v_role = 'ADMIN' OR user_id = p_actor;
END$$
DELIMITER ;

-- Reports
DROP PROCEDURE IF EXISTS sp_report_equipment;
DELIMITER $$
CREATE PROCEDURE sp_report_equipment
(
    IN p_actor INT UNSIGNED,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    CALL sp_assert_role(p_actor, 'ADMIN');
    -- LEFT JOIN includes equipment with zero requests/loans.
    SELECT
        e.code, e.name, e.total_quantity, e.available_quantity, COUNT(r.id) AS requests,
        COALESCE(SUM(CASE WHEN r.borrowed_at IS NOT NULL THEN r.quantity ELSE 0 END), 0) AS released_units,
        COALESCE(SUM(CASE WHEN r.status IN ('BORROWED', 'RETURN_PENDING') THEN r.quantity ELSE 0 END), 0) AS on_loan,
        COUNT(*) OVER () AS total_rows
    FROM equipment e
    LEFT JOIN records r ON r.equipment_id = e.id
    GROUP BY e.id
    ORDER BY e.name
    LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_users;
DELIMITER $$
CREATE PROCEDURE sp_report_users
(
    IN p_actor INT UNSIGNED,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    CALL sp_assert_role(p_actor, 'ADMIN');
    -- RIGHT JOIN retains `user` who have never requested equipment.
    SELECT
        u.username, CONCAT(u.first_name, ' ', u.last_name) AS name, u.role,
        COUNT(r.id) AS requests,
        COALESCE(SUM(CASE WHEN r.status IN ('BORROWED', 'RETURN_PENDING') THEN r.quantity ELSE 0 END), 0) AS on_loan,
        MAX(r.borrowed_at) AS last_borrowed_at, COUNT(*) OVER () AS total_rows
    FROM records r
    RIGHT JOIN `user` u ON u.id = r.user_id
    GROUP BY u.id
    ORDER BY u.username
    LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_report_full;
DELIMITER $$
CREATE PROCEDURE sp_report_full
(
    IN p_actor INT UNSIGNED,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    CALL sp_assert_role(p_actor, 'ADMIN');
    -- MariaDB has no FULL OUTER JOIN, so combine both sides without repeating matches.
    -- Foreign keys normally prevent activity with no matching equipment.
    WITH activity AS (
    SELECT
        equipment_id, SUM(quantity) AS released_units
    FROM records
    WHERE borrowed_at IS NOT NULL
    GROUP BY equipment_id), combined AS (SELECT e.id AS equipment_id, e.code, e.name, COALESCE(a.released_units, 0) AS released_units, CASE WHEN a.equipment_id IS NULL THEN 'CATALOG_ONLY' ELSE 'MATCHED' END AS match_type
    FROM equipment e
    LEFT JOIN activity a ON a.equipment_id = e.id UNION ALL SELECT a.equipment_id, NULL, NULL, a.released_units, 'ACTIVITY_ONLY'
    FROM equipment e
    RIGHT JOIN activity a ON a.equipment_id = e.id
    WHERE e.id IS NULL) SELECT combined.*, COUNT(*) OVER () AS total_rows FROM combined ORDER BY equipment_id LIMIT p_limit OFFSET p_offset;
END$$
DELIMITER ;
