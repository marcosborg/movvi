-- SQLite schema and synthetic data for the isolated maintenance API sandbox.
CREATE TABLE companies (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    deleted_at TEXT
);

CREATE TABLE vehicle_items (
    id INTEGER PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id),
    driver_id INTEGER,
    license_plate TEXT NOT NULL,
    created_at TEXT,
    updated_at TEXT,
    deleted_at TEXT
);

CREATE TABLE vehicle_usages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_item_id INTEGER NOT NULL REFERENCES vehicle_items(id),
    driver_id INTEGER,
    usage_exceptions TEXT NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT,
    created_at TEXT,
    updated_at TEXT,
    deleted_at TEXT
);

CREATE TABLE vehicle_expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_item_id INTEGER NOT NULL REFERENCES vehicle_items(id),
    expense_type TEXT NOT NULL,
    supplier TEXT,
    date TEXT NOT NULL,
    description TEXT NOT NULL,
    value DECIMAL(12, 2) NOT NULL,
    vat DECIMAL(5, 2) NOT NULL,
    created_at TEXT,
    updated_at TEXT,
    deleted_at TEXT
);

INSERT INTO companies (id, name) VALUES (1, 'Frota de teste'), (2, 'Empresa de controlo');
INSERT INTO vehicle_items (id, company_id, driver_id, license_plate, updated_at) VALUES
    (10, 1, NULL, 'AA-01-AA', CURRENT_TIMESTAMP),
    (11, 1, 5, 'BB-02-BB', CURRENT_TIMESTAMP),
    (20, 2, NULL, 'CC-03-CC', CURRENT_TIMESTAMP);
INSERT INTO vehicle_usages (vehicle_item_id, driver_id, usage_exceptions, start_date, updated_at)
    VALUES (11, 5, 'usage', '2026-11-01 10:00:00', CURRENT_TIMESTAMP);
