

CREATE DATABASE IF NOT EXISTS resource_allocation;
USE resource_allocation;




CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) NOT NULL,
    meeting_required TINYINT(1) NOT NULL DEFAULT 0,
    assigned_to INT,
    attachment VARCHAR(255) DEFAULT NULL,
    date_closed DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    
);

CREATE TABLE resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



CREATE TABLE signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    signature_data TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    details TEXT,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

CREATE TABLE student_health_insurance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    questions_answers TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

CREATE TABLE agent_compensation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE workers_invoice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE returned_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial_no VARCHAR(255),
    name VARCHAR(255),
    resource_type VARCHAR(255),
    quantity INT,
    returned_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE resources 
ADD COLUMN assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN expiry_date DATETIME;
ALTER TABLE budgets ADD COLUMN year INT NOT NULL DEFAULT YEAR(CURDATE());

ALTER TABLE workers_invoice
ADD COLUMN supervisor VARCHAR(100),
ADD COLUMN hours_approved FLOAT,
ADD COLUMN hourly_pay FLOAT;
ALTER TABLE refunds ADD COLUMN description TEXT AFTER amount;


CREATE TABLE agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) UNIQUE,
    student_name VARCHAR(100),
    term ENUM('Spring', 'Summer', 'Fall') NOT NULL,
    year YEAR NOT NULL,
    program ENUM('Graduate', 'Undergraduate', 'Exchange') NOT NULL,
    major VARCHAR(100),
    admitted BOOLEAN DEFAULT 0,
    attended BOOLEAN DEFAULT 0,
    agent_id INT NOT NULL,
    FOREIGN KEY (agent_id) REFERENCES agents(id)
);

CREATE TABLE agent_compensation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Paid', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
CREATE TABLE agent_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT,
    term VARCHAR(20),
    year INT,
    student_count INT,
    total_compensation DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE people (
    worker_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role ENUM('DSO', 'Admissions Advisor', 'Student Worker', 'Records Specialist', 'Recruitment Officer', 'Office Manager') NOT NULL,
    supervisor VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE resource_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type VARCHAR(100),
    serial_no VARCHAR(100) UNIQUE
);

ALTER TABLE resources ADD COLUMN unique_id VARCHAR(255) UNIQUE AFTER id;
    ALTER TABLE tickets DROP COLUMN assigned_to;

    ALTER TABLE tickets ADD COLUMN assigned_to INT;

    ALTER TABLE tickets
    ADD CONSTRAINT fk_assigned_to
    FOREIGN KEY (assigned_to) REFERENCES people(worker_id)
    ON DELETE SET NULL ON UPDATE CASCADE;
    ALTER TABLE people ADD COLUMN email VARCHAR(255);
    



